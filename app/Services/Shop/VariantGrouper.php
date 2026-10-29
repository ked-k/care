<?php

namespace App\Services\Shop;

use App\Models\Shop\ShopCartItem;
use App\Models\Shop\ShopOrderItem;
use App\Models\Shop\ShopPriceTier;
use App\Models\Shop\ShopProduct;
use App\Models\Shop\ShopProductImage;
use App\Models\Shop\ShopProductVariant;
use App\Models\Shop\ShopStockMovement;
use App\Models\Shop\ShopStore;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Batch 41: turns separate products that are really sizes of one thing —
 *   "Economy Blue Powder Free Nitrile Gloves - Small - 100 gloves"
 *   "Economy Blue Powder Free Nitrile Gloves - Large - 10 x 100 gloves" …
 * — into one product with variants (Size × Pack).
 *
 * How a group is found: same store, and the same name before the first
 * " - ". How the options are named: the imported descriptions carry the
 * attributes ("Colour: Blue. Size: Small. Pack quantity: 100 gloves." /
 * "Size: S; Chest Size: 36-38"; Material: Polyester"), so an attribute whose
 * value changes across the group becomes an option (Size, Pack…). Name parts
 * that differ but aren't in the description ("Fire Marshal") become an extra
 * option. Two attributes that always move together (Size A3 ↔ 297 x 420mm)
 * are folded into one ("A3 (297 x 420mm)").
 *
 * What happens to the rows: the first product of the group becomes the
 * parent (renamed to the shared name, new web address); the others are
 * soft-deleted and become its variants, each keeping its code, barcode,
 * price, bulk prices, stock and supplier link. Basket lines, order lines and
 * stock history are re-pointed at the parent + variant, so nothing is lost.
 */
class VariantGrouper
{
    private const SIZE_ORDER = [
        'xxs', 'xs', 'extra small', 's', 'small', 'm', 'medium', 'l', 'large', 'xl', 'x-large', 'extra large',
        'xxl', '2xl', 'xx-large', 'xxxl', '3xl', 'xxx-large', '4xl', '5xl',
    ];

    /** @var array<int, array<string, mixed>> what plan() decided, for reporting */
    public array $report = [];

    public function __construct(private ShopStore $store)
    {
    }

    /**
     * Work out the groups without changing anything.
     *
     * @return Collection<int, array{base: string, products: Collection, options: array, rows: array, reason: ?string}>
     */
    public function plan(?string $source = null): Collection
    {
        $products = ShopProduct::where('store_id', $this->store->id)
            ->where('has_variants', false)
            ->where('name', 'like', '% - %')
            ->when($source, fn ($q) => $q->where('source', $source))
            ->with('tiers')
            ->orderBy('created_at')->orderBy('id')
            ->get();

        return $products
            // The supplier's product page is the surest sign two items are sizes
            // of one thing (one page per item, one product per size); the name
            // before the first " - " is the fallback.
            ->groupBy(fn (ShopProduct $p) => $p->supplier_url ? 'url:'.$p->supplier_url : 'name:'.mb_strtolower(trim(Str::before($p->name, ' - '))))
            ->filter(fn ($group) => $group->count() > 1)
            ->map(fn ($group) => $this->planGroup($group->values()))
            ->values();
    }

    /** @return array{base: string, products: Collection, options: array, rows: array, reason: ?string} */
    private function planGroup(Collection $group): array
    {
        // The shared name: the " - " parts every item starts with
        // ("Bristol Maid - Alcohol Gel Holders - 77 x 300mm" → "Bristol Maid - Alcohol Gel Holders").
        $split = $group->map(fn (ShopProduct $p) => array_map('trim', explode(' - ', $p->name)));
        $common = 0;
        $shortest = $split->map(fn ($s) => count($s))->min();
        while ($common < $shortest - 1 && $split->every(fn ($s) => mb_strtolower($s[$common]) === mb_strtolower($split->first()[$common]))) {
            $common++;
        }
        $common = max(1, $common);
        $base = implode(' - ', array_slice($split->first(), 0, $common));

        // Each product's attributes ("Size" => "Small") and its name parts after the base.
        $attrs = $group->map(fn (ShopProduct $p) => self::attributes((string) $p->description));
        $parts = $split->map(fn ($s) => array_values(array_filter(array_slice($s, $common), 'strlen')));

        // 1. Attributes whose value changes across the group.
        $labels = $attrs->flatMap(fn ($a) => array_keys($a))->unique()->values();
        $columns = [];
        foreach ($labels as $label) {
            $values = $attrs->map(fn ($a) => $a[$label] ?? null);
            if ($values->contains(null) || $values->unique()->count() < 2) {
                continue;
            }
            $columns[] = ['name' => $label, 'values' => $values->all()];
        }

        // 2. Name parts that differ but no attribute explains ("Fire Marshal" vs "Security").
        $leftovers = $group->keys()->map(function ($i) use ($attrs, $parts) {
            $known = array_map('mb_strtolower', array_values($attrs[$i]));

            return array_values(array_filter($parts[$i], fn ($part) => ! in_array(mb_strtolower($part), $known, true)));
        });
        if ($leftovers->map(fn ($l) => count($l))->unique()->count() === 1) {
            $width = count($leftovers->first());
            for ($k = 0; $k < $width; $k++) {
                $values = $leftovers->map(fn ($l) => $l[$k]);
                if ($values->unique()->count() > 1) {
                    $columns[] = ['name' => $this->guessName($values->all(), count($columns)), 'values' => $values->all()];
                }
            }
        } else {
            // Different shapes: the differing tails, as one option, if they tell the rows apart.
            $tails = $leftovers->map(fn ($l) => implode(' - ', $l));
            if ($tails->unique()->count() > 1) {
                $columns[] = ['name' => __('Type'), 'values' => $tails->map(fn ($t) => $t !== '' ? $t : __('Standard'))->all()];
            }
        }

        // 3. Fold columns that always move together (Size A3 ↔ Dimensions 297 x 420mm).
        $columns = $this->foldTwins($columns);

        // 4. At most three options: the rest join the third.
        if (count($columns) > 3) {
            $extra = array_slice($columns, 2);
            $columns = array_slice($columns, 0, 2);
            $columns[] = [
                'name' => implode(' / ', array_column($extra, 'name')),
                'values' => array_map(fn ($i) => implode(' · ', array_map(fn ($c) => $c['values'][$i], $extra)), array_keys($extra[0]['values'])),
            ];
        }

        // Option names must differ ("Type", "Option 2"…).
        $seen = [];
        foreach ($columns as $k => $column) {
            $name = $column['name'];
            for ($n = 2; isset($seen[mb_strtolower($name)]); $n++) {
                $name = $column['name'].' '.$n;
            }
            $seen[mb_strtolower($name)] = true;
            $columns[$k]['name'] = $name;
        }

        // Nothing told them apart, or two rows would be identical: leave the group alone.
        $rows = [];
        foreach ($group->keys() as $i) {
            $rows[$i] = array_map(fn ($c) => Str::limit(Str::squish((string) $c['values'][$i]), 150, ''), $columns);
        }
        $reason = null;
        if (! $columns) {
            $reason = 'no differing size/option found';
        } elseif (collect($rows)->map(fn ($r) => mb_strtolower(implode('|', $r)))->unique()->count() < count($rows)) {
            $reason = 'two items would be identical';
        }

        // Option values in a sensible order (sizes small → large, numbers ascending).
        $options = array_map(fn ($c, $k) => [
            'name' => Str::limit($c['name'], 60, ''),
            'values' => $this->sortValues(array_values(array_unique(array_column($rows, $k)))),
        ], $columns, array_keys($columns));

        return ['base' => $base, 'products' => $group, 'options' => $options, 'rows' => $rows, 'reason' => $reason];
    }

    /** Merge the planned groups. Returns how many products became variants. */
    public function apply(Collection $plan): array
    {
        $stats = ['groups' => 0, 'variants' => 0, 'skipped' => 0];
        foreach ($plan as $group) {
            if ($group['reason']) {
                $stats['skipped']++;

                continue;
            }
            DB::transaction(fn () => $this->merge($group));
            $stats['groups']++;
            $stats['variants'] += $group['products']->count();
        }

        return $stats;
    }

    private function merge(array $group): void
    {
        /** @var Collection<int, ShopProduct> $members */
        $members = $group['products'];
        $options = $group['options'];

        // Shared pricing: the most common price + bulk-price set sells as the default.
        $signature = fn (ShopProduct $p) => number_format((float) $p->price, 2).'|'.$p->tiers->map(fn ($t) => $t->min_qty.':'.$t->unit_price)->implode(',');
        $common = $members->countBy($signature)->sortDesc()->keys()->first();
        $template = $members->first(fn ($p) => $signature($p) === $common);

        $parent = $members->first();
        $order = $this->rowOrder($group['rows'], $options);

        $variantIds = [];
        foreach ($members as $i => $member) {
            $values = array_pad($group['rows'][$i], 3, null);
            $shared = $signature($member) === $common;
            $variant = ShopProductVariant::create([
                'product_id' => $parent->id,
                'option1' => $values[0], 'option2' => $values[1], 'option3' => $values[2],
                'sku' => $member->sku,
                'barcode' => $member->barcode,
                'price' => $shared ? null : $member->price,
                'compare_at_price' => $member->compare_at_price,
                'cost_price' => $member->cost_price,
                'stock_qty' => (int) $member->stock_qty,
                'weight_grams' => $member->weight_grams,
                'supplier_sku' => $member->supplier_sku,
                'supplier_url' => $member->supplier_url,
                'is_active' => $member->status !== 'archived',
                'sort_order' => $order[$i],
                'source_ref' => $member->source_ref,
            ]);
            $variantIds[$member->id] = $variant->id;

            if (! $shared) {
                foreach ($member->tiers as $tier) {
                    ShopPriceTier::create(['product_id' => $parent->id, 'variant_id' => $variant->id, 'min_qty' => $tier->min_qty, 'unit_price' => $tier->unit_price]);
                }
            }

            // Photos move to the parent; the first one becomes this option's photo.
            $images = ShopProductImage::where('product_id', $member->id)->orderBy('sort_order')->get();
            foreach ($images as $image) {
                $image->update(['product_id' => $parent->id]);
            }
            if ($images->isNotEmpty()) {
                $variant->update(['image_id' => $images->first()->id]);
            }

            // Re-point history and baskets at parent + variant.
            ShopStockMovement::where('product_id', $member->id)->update(['product_id' => $parent->id, 'variant_id' => $variant->id]);
            ShopOrderItem::where('product_id', $member->id)->update(['product_id' => $parent->id, 'variant_id' => $variant->id]);
            foreach (ShopCartItem::where('product_id', $member->id)->get() as $item) {
                $existing = ShopCartItem::where('cart_id', $item->cart_id)->where('product_id', $parent->id)->where('variant_id', $variant->id)->first();
                if ($existing) {
                    $existing->increment('qty', $item->qty);
                    $item->delete();
                } else {
                    $item->update(['product_id' => $parent->id, 'variant_id' => $variant->id]);
                }
            }
        }

        // The parent: shared name, defaults from the common pricing, product-level bulk prices.
        ShopPriceTier::where('product_id', $parent->id)->whereNull('variant_id')->delete();
        foreach ($template->tiers as $tier) {
            ShopPriceTier::create(['product_id' => $parent->id, 'variant_id' => null, 'min_qty' => $tier->min_qty, 'unit_price' => $tier->unit_price]);
        }

        $packOption = collect($options)->search(fn ($o) => preg_match('/^pack/i', $o['name']));
        $parent->fill([
            'name' => $group['base'],
            'slug' => ShopProduct::uniqueSlug($parent->store_id, $group['base'], $parent->id),
            'sku' => null,
            'barcode' => null,
            'supplier_sku' => null,
            'price' => $template->price,
            'compare_at_price' => null,
            'pack_size' => $packOption === false ? $parent->pack_size : null,
            'description' => $this->sharedDescription($members),
            'summary' => $this->sharedSummary((string) $parent->summary),
            'status' => $members->contains('status', 'active') ? 'active' : $parent->status,
            'is_featured' => $members->contains('is_featured', true),
            'views' => (int) $members->sum('views'),
            'has_variants' => true,
            'options' => $options,
            'stock_qty' => (int) $members->sum('stock_qty'),
        ]);
        $parent->save();

        foreach ($members as $member) {
            if ($member->id !== $parent->id) {
                ShopPriceTier::where('product_id', $member->id)->delete();
                $member->update(['status' => 'archived']);
                $member->delete();
            }
        }
    }

    /**
     * "Colour: Blue. Size: Small. Pack quantity: 100 gloves." →
     * ['Colour' => 'Blue', 'Size' => 'Small', 'Pack' => '100 gloves']
     */
    public static function attributes(string $html): array
    {
        $text = html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '<br/>', '<br />', '</li>'], "\n", $html)), ENT_QUOTES | ENT_HTML5);
        $out = [];
        // Label: value — value runs to ";" / newline, or to ". " before the next "Label:".
        preg_match_all('/(?:^|[;\n.]\s*)([A-Z][A-Za-z ]{1,24}?):\s*([^;\n]+?)(?=\s*(?:;|\n|$|\.\s+[A-Z][A-Za-z ]{1,24}?:|\.\s*$))/u', $text, $m, PREG_SET_ORDER);
        foreach ($m as $match) {
            $label = trim($match[1]);
            $value = trim(rtrim($match[2], '. '));
            if ($value === '' || mb_strlen($value) > 80 || preg_match('~https?://~', $value)) {
                continue;
            }
            if (preg_match('/^pack(\s+(quantity|qty|size))?$/i', $label)) {
                $label = 'Pack';
            }
            $out[Str::ucfirst($label)] ??= $value;
        }

        return $out;
    }

    /** Fold a column into another when each value of one always means the same value of the other. */
    private function foldTwins(array $columns): array
    {
        $result = [];
        $used = [];
        foreach ($columns as $a => $colA) {
            if (isset($used[$a])) {
                continue;
            }
            foreach ($columns as $b => $colB) {
                if ($b <= $a || isset($used[$b])) {
                    continue;
                }
                if ($this->oneToOne($colA['values'], $colB['values'])) {
                    $colA['values'] = array_map(fn ($x, $y) => mb_strtolower($x) === mb_strtolower($y) ? $x : "$x ($y)", $colA['values'], $colB['values']);
                    $used[$b] = true;
                }
            }
            $result[] = $colA;
        }

        return $result;
    }

    private function oneToOne(array $a, array $b): bool
    {
        $ab = [];
        $ba = [];
        foreach ($a as $i => $x) {
            $y = $b[$i];
            if ((isset($ab[$x]) && $ab[$x] !== $y) || (isset($ba[$y]) && $ba[$y] !== $x)) {
                return false;
            }
            $ab[$x] = $y;
            $ba[$y] = $x;
        }

        return true;
    }

    private function guessName(array $values, int $position): string
    {
        $all = fn (string $pattern) => collect($values)->every(fn ($v) => preg_match($pattern, $v));
        $sizes = implode('|', array_map(fn ($s) => preg_quote($s, '/'), self::SIZE_ORDER));

        return match (true) {
            $all('/^('.$sizes.')$/i') => 'Size',
            $all('/^\d+(\s*x\s*\d+)?\s+\D/i') => 'Pack',
            $all('/^(left|right)\b/i') => 'Side',
            $all('/\d+\s*(mm|cm|ml|l|g|kg)\b/i') => 'Size',
            default => 'Type',
        };
    }

    /** Sizes in size order, then anything numeric ascending, else as first seen. */
    private function sortValues(array $values): array
    {
        $rank = function (string $v) {
            $key = mb_strtolower(trim(Str::before($v, ' (')));
            $pos = array_search($key, self::SIZE_ORDER, true);
            if ($pos !== false) {
                return [0, $pos, 0];
            }
            if (preg_match('/^(\d+(?:\.\d+)?)(?:\s*x\s*(\d+))?/', $v, $m)) {
                return [1, (float) $m[1] * (float) ($m[2] ?? 1), (float) $m[1]];
            }

            return [2, 0, 0];
        };
        $indexed = array_map(fn ($v, $i) => [$v, $rank($v), $i], $values, array_keys($values));
        usort($indexed, fn ($x, $y) => [$x[1][0], $x[1][1], $x[1][2], $x[2]] <=> [$y[1][0], $y[1][1], $y[1][2], $y[2]]);

        return array_column($indexed, 0);
    }

    /** Sort position of each row: by option 1's value order, then option 2's, then 3's. */
    private function rowOrder(array $rows, array $options): array
    {
        $keys = [];
        foreach ($rows as $i => $values) {
            $keys[$i] = array_map(fn ($v, $k) => array_search($v, $options[$k]['values'], true), $values, array_keys($values));
        }
        uasort($keys, fn ($a, $b) => $a <=> $b);

        return array_flip(array_keys($keys));
    }

    /** The one-line summary without the size-specific "Size: Small. Pack quantity: 100 gloves." */
    private function sharedSummary(string $summary): ?string
    {
        $clean = preg_replace('~\b(?:Size|Pack(?:\s+(?:quantity|qty|size))?|Chest Size|Dimensions|Capacity|Length|Weight)\s*:\s*[^.;]+[.;]?\s*~i', '', $summary);
        $clean = trim((string) preg_replace('/\s{2,}/', ' ', (string) $clean));

        return $clean !== '' ? $clean : null;
    }

    /**
     * The description shown on the merged page: the first member's, with the
     * per-size spec line ("Colour: Blue. Size: Small. Pack quantity: 100")
     * replaced by the attributes that are the same for every size.
     */
    private function sharedDescription(Collection $members): ?string
    {
        $html = (string) $members->first()->description;
        $attrs = $members->map(fn ($m) => self::attributes((string) $m->description));
        $constant = collect($attrs->first())->filter(fn ($value, $label) => $attrs->every(fn ($a) => ($a[$label] ?? null) === $value));

        // A paragraph made up only of "Label: value" pairs.
        $cleaned = trim((string) preg_replace('~<p>\s*(?:[A-Z][A-Za-z ]{1,24}:\s*[^<;]+?[.;]?\s*)+</p>~u', '', $html));
        if ($constant->isNotEmpty()) {
            $cleaned .= '<p>'.e($constant->map(fn ($v, $k) => $k.': '.$v)->implode('. ')).'.</p>';
        }

        return $cleaned !== '' ? $cleaned : null;
    }
}
