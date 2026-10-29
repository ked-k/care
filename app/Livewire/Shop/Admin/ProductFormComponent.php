<?php

namespace App\Livewire\Shop\Admin;

use App\Models\Shop\ShopCategory;
use App\Models\Shop\ShopPriceTier;
use App\Models\Shop\ShopProduct;
use App\Models\Shop\ShopProductImage;
use App\Models\Shop\ShopProductVariant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\WithFileUploads;

/**
 * Batch 41: the full-page product editor (replaces Batch 40's drawer, which
 * had no room for a variants grid). Laid out like a stock "New item" form:
 * details, price & VAT, sizes & options, stock on the left; visibility,
 * photos and supplier on the right.
 *
 * Variants: up to three options (e.g. Size, Pack), each with a list of
 * values. Every combination is a row in the grid with its own code, barcode,
 * price (blank = the product's price), stock and bulk prices. Adding or
 * removing a value rebuilds the grid and keeps what was typed for rows that
 * still exist.
 */
class ProductFormComponent extends ShopAdminComponent
{
    use WithFileUploads;

    public const MAX_OPTIONS = 3;
    public const MAX_VARIANTS = 200;

    public const PRESETS = [
        'letters' => ['label' => 'XS – XXL', 'values' => ['XS', 'S', 'M', 'L', 'XL', 'XXL']],
        'words' => ['label' => 'Small – X-Large', 'values' => ['Small', 'Medium', 'Large', 'X-Large']],
        'gloves' => ['label' => 'S / M / L', 'values' => ['Small', 'Medium', 'Large']],
        'colours' => ['label' => 'Common colours', 'values' => ['Blue', 'Black', 'White', 'Purple']],
        'packs' => ['label' => 'Box / Case', 'values' => ['Box', 'Case']],
        'paper' => ['label' => 'A4 / A3 / A2', 'values' => ['A4', 'A3', 'A2']],
    ];

    #[Locked]
    public ?string $productId = null;

    public array $form = [];
    public array $tiers = [];
    public int $openingStock = 0;
    public array $uploads = [];

    public bool $hasVariants = false;
    /** @var array<int, array{name: string, values: array<int, string>}> */
    public array $options = [];
    /** Text typed into each option's "add a value" box. */
    public array $newValues = [];
    /** @var array<int, array<string, mixed>> one row per combination */
    public array $variants = [];
    public string $bulkPrice = '';

    public function mount(?string $productId = null): void
    {
        $store = $this->store();
        $this->form = [
            'name' => '', 'sku' => '', 'barcode' => '', 'brand' => '', 'pack_size' => '', 'unit_name' => '', 'category_id' => '',
            'summary' => '', 'description' => '', 'price' => '', 'compare_at_price' => '', 'cost_price' => '',
            'vat_rate' => (string) (float) $store->default_vat_rate, 'track_stock' => true, 'low_stock_threshold' => 5,
            'allow_backorder' => false, 'track_expiry' => false, 'stock_rotation' => 'fefo', 'min_order_qty' => 1, 'max_order_qty' => '', 'status' => 'draft', 'is_featured' => false,
            'supplier_name' => '', 'supplier_sku' => '', 'supplier_url' => '',
        ];

        if (! $productId) {
            return;
        }

        $product = $store->products()->with(['tiers', 'variants.tiers'])->findOrFail($productId);
        $this->productId = $product->id;
        foreach (array_keys($this->form) as $key) {
            $value = $product->{$key};
            $this->form[$key] = is_bool($value) ? $value : (string) ($value ?? '');
        }
        $this->form['vat_rate'] = (string) (float) $product->vat_rate;
        $this->tiers = $product->tiers->map(fn ($t) => ['min_qty' => (string) $t->min_qty, 'unit_price' => (string) $t->unit_price])->all();

        $this->hasVariants = $product->has_variants;
        $this->options = collect($product->options ?? [])->map(fn ($o) => [
            'name' => (string) ($o['name'] ?? ''),
            'values' => array_values(array_map('strval', $o['values'] ?? [])),
        ])->all();
        $this->newValues = array_fill(0, count($this->options), '');
        $this->variants = $product->variants->map(fn (ShopProductVariant $v) => $this->rowFromVariant($v))->all();
    }

    /* ---------- Variant rows ---------- */

    private function rowFromVariant(ShopProductVariant $v): array
    {
        $values = array_slice([(string) $v->option1, (string) $v->option2, (string) $v->option3], 0, max(1, count($this->options)));

        return [
            'id' => $v->id,
            'values' => $values,
            'key' => $this->keyFor($values),
            'sku' => (string) $v->sku,
            'barcode' => (string) $v->barcode,
            'price' => $v->price !== null ? (string) $v->price : '',
            'compare_at_price' => $v->compare_at_price !== null ? (string) $v->compare_at_price : '',
            'cost_price' => $v->cost_price !== null ? (string) $v->cost_price : '',
            'stock' => (int) $v->stock_qty,
            'opening' => '',
            'low_stock_threshold' => $v->low_stock_threshold !== null ? (string) $v->low_stock_threshold : '',
            'image_id' => (string) $v->image_id,
            'supplier_url' => (string) $v->supplier_url,
            'is_active' => (bool) $v->is_active,
            'tiers' => $v->tiers->map(fn ($t) => ['min_qty' => (string) $t->min_qty, 'unit_price' => (string) $t->unit_price])->all(),
        ];
    }

    private function blankRow(array $values): array
    {
        return [
            'id' => null, 'values' => $values, 'key' => $this->keyFor($values),
            'sku' => '', 'barcode' => '', 'price' => '', 'compare_at_price' => '', 'cost_price' => '',
            'stock' => 0, 'opening' => '', 'low_stock_threshold' => '', 'image_id' => '', 'supplier_url' => '',
            'is_active' => true, 'tiers' => [],
        ];
    }

    private function keyFor(array $values): string
    {
        return mb_strtolower(implode("\u{1F}", array_map('trim', $values)));
    }

    /**
     * Rebuild the grid after the options change. Existing rows keep their
     * codes, prices and stock. New rows are added only for values that are
     * new — so a product that was deliberately sparse (no "Small" in a case)
     * doesn't suddenly grow every missing combination.
     */
    private function rebuild(): void
    {
        $lists = collect($this->options)->map(fn ($o) => array_values(array_filter($o['values'], fn ($v) => trim($v) !== '')))->all();
        $width = count($lists);
        if (! $width || collect($lists)->every(fn ($l) => ! $l)) {
            $this->variants = [];

            return;
        }

        // Existing rows: line their values up with the options. A position with
        // no value yet (an option just added) takes that option's first value.
        $rows = [];
        foreach ($this->variants as $row) {
            $values = array_slice(array_pad($row['values'], $width, ''), 0, $width);
            foreach ($values as $i => $value) {
                if ($value === '' && $lists[$i]) {
                    $values[$i] = $lists[$i][0];
                } elseif ($value !== '' && ! in_array($value, $lists[$i], true)) {
                    continue 2; // its value was removed — the row goes
                }
            }
            $row['values'] = $values;
            $row['key'] = $this->keyFor($values);
            $rows[$row['key']] ??= $row;
        }

        $known = [];
        foreach ($rows as $row) {
            foreach ($row['values'] as $i => $value) {
                $known[$i][$value] = true;
            }
        }

        $combos = [[]];
        foreach ($lists as $values) {
            $values = $values ?: [''];
            $next = [];
            foreach ($combos as $combo) {
                foreach ($values as $value) {
                    $next[] = array_merge($combo, [$value]);
                }
            }
            $combos = $next;
            if (count($combos) > self::MAX_VARIANTS) {
                $this->addError('options', __('That would make more than :n options — split it into separate products.', ['n' => self::MAX_VARIANTS]));

                return;
            }
        }

        $result = [];
        foreach ($combos as $values) {
            $key = $this->keyFor($values);
            if (isset($rows[$key])) {
                $result[] = $rows[$key];
            } elseif (! $rows || collect($values)->contains(fn ($v, $i) => ! isset($known[$i][$v]))) {
                $result[] = $this->blankRow($values);
            }
        }
        $this->variants = $result;
        $this->resetErrorBag('options');
    }

    public function updatedHasVariants(bool $on): void
    {
        if ($on && ! $this->options) {
            $this->options = [['name' => 'Size', 'values' => []]];
            $this->newValues = [''];
        }
    }

    public function addOption(): void
    {
        if (count($this->options) >= self::MAX_OPTIONS) {
            return;
        }
        $used = array_map('mb_strtolower', array_column($this->options, 'name'));
        $name = collect(['Size', 'Colour', 'Pack', 'Material', 'Style'])->first(fn ($n) => ! in_array(mb_strtolower($n), $used, true)) ?? '';
        $this->options[] = ['name' => $name, 'values' => []];
        $this->newValues[] = '';
        // Existing rows get an empty value for the new option until values are added.
        $this->rebuild();
    }

    public function removeOption(int $i): void
    {
        unset($this->options[$i], $this->newValues[$i]);
        $this->options = array_values($this->options);
        $this->newValues = array_values($this->newValues);
        // Drop that value from every row; rows that become identical keep the first.
        $this->variants = collect($this->variants)->map(function ($row) use ($i) {
            $values = $row['values'];
            unset($values[$i]);
            $values = array_values($values);

            return array_merge($row, ['values' => $values, 'key' => $this->keyFor($values)]);
        })->unique('key')->values()->all();
        $this->rebuild();
    }

    /** Adds what's typed in an option's box; commas add several at once ("S, M, L"). */
    public function addValues(int $i): void
    {
        $typed = (string) ($this->newValues[$i] ?? '');
        $this->pushValues($i, preg_split('/\s*[,;\n]\s*/', $typed));
        $this->newValues[$i] = '';
    }

    public function applyPreset(int $i, string $preset): void
    {
        if (isset(self::PRESETS[$preset])) {
            $this->pushValues($i, self::PRESETS[$preset]['values']);
        }
    }

    private function pushValues(int $i, array $values): void
    {
        if (! isset($this->options[$i])) {
            return;
        }
        $have = array_map('mb_strtolower', $this->options[$i]['values']);
        foreach ($values as $value) {
            $value = Str::limit(trim((string) $value), 150, '');
            if ($value !== '' && ! in_array(mb_strtolower($value), $have, true)) {
                $this->options[$i]['values'][] = $value;
                $have[] = mb_strtolower($value);
            }
        }
        $this->rebuild();
    }

    public function removeValue(int $i, int $j): void
    {
        unset($this->options[$i]['values'][$j]);
        $this->options[$i]['values'] = array_values($this->options[$i]['values']);
        $this->rebuild();
    }

    /** Rename a value without losing the rows (and their stock) that use it. */
    public function renameValue(int $i, int $j, string $name): void
    {
        $name = Str::limit(trim($name), 150, '');
        $old = $this->options[$i]['values'][$j] ?? null;
        if ($old === null || $name === '' || $name === $old) {
            return;
        }
        if (in_array(mb_strtolower($name), array_map('mb_strtolower', $this->options[$i]['values']), true) && mb_strtolower($name) !== mb_strtolower($old)) {
            $this->toast(__('“:v” is already in the list.', ['v' => $name]), 'warning');

            return;
        }
        $this->options[$i]['values'][$j] = $name;
        foreach ($this->variants as $r => $row) {
            if (($row['values'][$i] ?? null) === $old) {
                $this->variants[$r]['values'][$i] = $name;
                $this->variants[$r]['key'] = $this->keyFor($this->variants[$r]['values']);
            }
        }
    }

    public function moveValue(int $i, int $j, int $by): void
    {
        $values = $this->options[$i]['values'] ?? [];
        $k = $j + $by;
        if (! isset($values[$j], $values[$k])) {
            return;
        }
        [$values[$j], $values[$k]] = [$values[$k], $values[$j]];
        $this->options[$i]['values'] = $values;
        $this->rebuild();
    }

    /** Fill blank codes as PRODUCT-SKU-VALUE, e.g. GLV-NIT-L-BOX. */
    public function fillSkus(): void
    {
        $base = Str::upper(trim($this->form['sku'] ?? '')) ?: Str::upper(Str::limit(Str::slug($this->form['name'] ?: 'ITEM'), 12, ''));
        foreach ($this->variants as $r => $row) {
            if (trim($row['sku']) !== '') {
                continue;
            }
            $parts = array_map(function ($value) {
                $value = trim($value);
                // Short values as they are (S, XL, A3); longer ones shortened (Medium → MED).
                return mb_strlen($value) <= 4 ? Str::upper(Str::slug($value, '')) : Str::upper(Str::limit(Str::slug($value, ''), 4, ''));
            }, $row['values']);
            $this->variants[$r]['sku'] = Str::limit($base.'-'.implode('-', array_filter($parts)), 80, '');
        }
    }

    public function applyBulkPrice(): void
    {
        $price = trim($this->bulkPrice);
        if ($price !== '' && ! is_numeric($price)) {
            $this->addError('bulkPrice', __('Enter a price, or leave it blank to use the product’s price.'));

            return;
        }
        foreach ($this->variants as $r => $row) {
            $this->variants[$r]['price'] = $price;
        }
        $this->bulkPrice = '';
        $this->resetErrorBag('bulkPrice');
    }

    public function addVariantTier(int $r): void
    {
        $last = end($this->variants[$r]['tiers']) ?: null;
        $this->variants[$r]['tiers'][] = ['min_qty' => (string) ((int) ($last['min_qty'] ?? 5) * 2), 'unit_price' => ''];
    }

    public function removeVariantTier(int $r, int $t): void
    {
        unset($this->variants[$r]['tiers'][$t]);
        $this->variants[$r]['tiers'] = array_values($this->variants[$r]['tiers']);
    }

    /* ---------- Product-level bulk prices ---------- */

    public function addTier(): void
    {
        $last = end($this->tiers);
        $this->tiers[] = ['min_qty' => (string) (($last['min_qty'] ?? 5) * 2), 'unit_price' => ''];
    }

    public function removeTier(int $i): void
    {
        unset($this->tiers[$i]);
        $this->tiers = array_values($this->tiers);
    }

    /* ---------- Photos ---------- */

    private function ownImage(string $imageId): ShopProductImage
    {
        return ShopProductImage::where('product_id', $this->productId)
            ->whereHas('product', fn ($q) => $q->where('store_id', $this->store()->id))->findOrFail($imageId);
    }

    public function deleteImage(string $imageId): void
    {
        $image = $this->ownImage($imageId);
        Storage::disk('public')->delete($image->path);
        ShopProductVariant::where('image_id', $image->id)->update(['image_id' => null]);
        $image->delete();
        foreach ($this->variants as $r => $row) {
            if ($row['image_id'] === $imageId) {
                $this->variants[$r]['image_id'] = '';
            }
        }
        $this->toast(__('Photo removed.'));
    }

    public function makePrimaryImage(string $imageId): void
    {
        $image = $this->ownImage($imageId);
        $image->product->images()->where('id', '!=', $image->id)->increment('sort_order');
        $image->update(['sort_order' => 0]);
    }

    /* ---------- Save ---------- */

    public function save()
    {
        $store = $this->store();
        $variantRules = [];
        if ($this->hasVariants) {
            $variantRules = [
                'options' => 'array|min:1|max:'.self::MAX_OPTIONS,
                'options.*.name' => 'required|string|max:60|distinct:ignore_case',
                'options.*.values' => 'array|min:1',
                'variants' => 'array|min:1|max:'.self::MAX_VARIANTS,
                'variants.*.sku' => 'nullable|string|max:80|distinct:ignore_case',
                'variants.*.barcode' => 'nullable|string|max:80',
                'variants.*.price' => 'nullable|numeric|min:0|max:999999',
                'variants.*.compare_at_price' => 'nullable|numeric|min:0',
                'variants.*.cost_price' => 'nullable|numeric|min:0',
                'variants.*.opening' => 'nullable|integer|min:0',
                'variants.*.low_stock_threshold' => 'nullable|integer|min:0',
                'variants.*.supplier_url' => 'nullable|url|max:1000',
                'variants.*.tiers.*.min_qty' => 'required|integer|min:2',
                'variants.*.tiers.*.unit_price' => 'required|numeric|min:0',
            ];
        }

        $this->validate([
            'form.name' => 'required|string|max:255',
            'form.sku' => ['nullable', 'string', 'max:80', Rule::unique('shop_products', 'sku')->where('store_id', $store->id)->whereNull('deleted_at')->ignore($this->productId)],
            'form.category_id' => ['nullable', Rule::exists('shop_categories', 'id')->where('store_id', $store->id)],
            'form.price' => $this->hasVariants ? 'nullable|numeric|min:0|max:999999' : 'required|numeric|min:0|max:999999',
            'form.compare_at_price' => 'nullable|numeric|min:0',
            'form.cost_price' => 'nullable|numeric|min:0',
            'form.vat_rate' => 'required|numeric|in:0,5,20',
            'form.unit_name' => 'nullable|string|max:40',
            'form.low_stock_threshold' => 'required|integer|min:0',
            'form.stock_rotation' => 'required|in:fefo,fifo',
            'form.min_order_qty' => 'required|integer|min:1',
            'form.max_order_qty' => 'nullable|integer|min:1',
            'form.status' => 'required|in:draft,active,archived',
            'form.summary' => 'nullable|string|max:500',
            'form.supplier_url' => 'nullable|url|max:1000',
            'tiers.*.min_qty' => 'required|integer|min:2',
            'tiers.*.unit_price' => 'required|numeric|min:0',
            'openingStock' => 'integer|min:0',
            'uploads.*' => 'image|max:4096',
        ] + $variantRules, [
            'tiers.*.min_qty.min' => __('A quantity break starts at 2 or more.'),
            'variants.*.tiers.*.min_qty.min' => __('A quantity break starts at 2 or more.'),
            'form.vat_rate.in' => __('UK VAT is 20%, 5% or 0%.'),
            'options.*.values.min' => __('Add at least one value, e.g. Small.'),
            'options.*.name.distinct' => __('Each option needs a different name.'),
            'variants.*.sku.distinct' => __('Two options have the same code.'),
            'variants.min' => __('Add the sizes or options this product comes in.'),
        ], [
            'options.*.name' => __('option name'),
            'variants.*.price' => __('price'),
            'variants.*.sku' => __('code'),
        ]);

        if ($this->hasVariants) {
            $this->validateVariantsAgainstStore();
        }

        $data = $this->form;
        foreach (['compare_at_price', 'cost_price', 'max_order_qty', 'category_id', 'sku', 'barcode', 'brand', 'pack_size', 'unit_name', 'supplier_name', 'supplier_sku', 'supplier_url'] as $nullable) {
            $data[$nullable] = trim((string) $data[$nullable]) === '' ? null : $data[$nullable];
        }
        $data['track_stock'] = (bool) $data['track_stock'];
        $data['allow_backorder'] = (bool) $data['allow_backorder'];
        $data['track_expiry'] = (bool) $data['track_expiry'];
        $data['is_featured'] = (bool) $data['is_featured'];
        $data['has_variants'] = $this->hasVariants;
        $data['options'] = $this->hasVariants ? collect($this->options)->map(fn ($o) => ['name' => trim($o['name']), 'values' => array_values($o['values'])])->all() : null;

        if ($this->hasVariants && ($data['price'] === '' || $data['price'] === null)) {
            // No default price: fine as long as every option has its own.
            $missing = collect($this->variants)->filter(fn ($r) => trim((string) $r['price']) === '');
            if ($missing->isNotEmpty()) {
                throw ValidationException::withMessages(['form.price' => __('Give a default price, or a price for every option (:n have none).', ['n' => $missing->count()])]);
            }
            $data['price'] = collect($this->variants)->min(fn ($r) => (float) $r['price']);
        }

        $product = DB::transaction(function () use ($store, $data) {
            $wasVariants = false;
            if ($this->productId) {
                $product = $store->products()->findOrFail($this->productId);
                $wasVariants = $product->has_variants;
                if ($product->name !== $data['name']) {
                    $data['slug'] = ShopProduct::uniqueSlug($store->id, $data['name'], $product->id);
                }
                $oldStock = (int) $product->stock_qty;
                $product->update($data + ['updated_by' => Auth::id()]);

                // A single product becoming one with options: its stock now
                // lives on the options, so record the old total leaving.
                if ($this->hasVariants && ! $wasVariants && $oldStock !== 0 && $product->track_stock) {
                    $product->adjustStock(-$oldStock, 'adjustment', null, __('Stock moved to sizes/options (opening stock entered per option)'), null, Auth::id());
                }
            } else {
                $product = $store->products()->create($data + [
                    'slug' => ShopProduct::uniqueSlug($store->id, $data['name']),
                    'stock_qty' => 0,
                    'source' => 'manual',
                    'created_by' => Auth::id(),
                ]);
                if (! $this->hasVariants && $this->openingStock > 0) {
                    $product->adjustStock($this->openingStock, 'receipt', null, __('Opening stock'), null, Auth::id());
                }
            }

            // Product-level bulk prices: replace wholesale, de-duplicated by min_qty.
            $product->tiers()->delete();
            collect($this->tiers)->unique('min_qty')->sortBy('min_qty')->each(fn ($t) => $product->tiers()->create([
                'min_qty' => (int) $t['min_qty'],
                'unit_price' => (float) $t['unit_price'],
            ]));

            $next = (int) $product->images()->max('sort_order');
            foreach ($this->uploads as $file) {
                $product->images()->create([
                    'path' => $file->store('shop/products/'.$store->id, 'public'),
                    'alt' => $product->name,
                    'sort_order' => ++$next,
                ]);
            }

            if ($this->hasVariants) {
                $this->saveVariants($product);
            } elseif ($wasVariants) {
                // Options switched off: the product keeps the total as its own stock.
                $total = (int) $product->variants()->sum('stock_qty');
                $product->variants()->delete();
                $product->update(['stock_qty' => $total]);
            }

            return $product;
        });

        $this->uploads = [];
        session()->flash('success', __('Product saved.'));

        return $this->redirectRoute('shop.admin.products.edit', ['productId' => $product->id], navigate: true);
    }

    /** Codes must be unique across the shop, not just within this product. */
    private function validateVariantsAgainstStore(): void
    {
        $store = $this->store();
        $ids = collect($this->variants)->pluck('id')->filter()->all();
        $errors = [];
        foreach ($this->variants as $r => $row) {
            $sku = trim((string) $row['sku']);
            if ($sku === '') {
                continue;
            }
            $clash = ShopProductVariant::where('sku', $sku)->whereNotIn('id', $ids)
                ->whereHas('product', fn ($q) => $q->where('store_id', $store->id))->exists()
                || ShopProduct::where('store_id', $store->id)->where('sku', $sku)->when($this->productId, fn ($q) => $q->where('id', '!=', $this->productId))->exists();
            if ($clash) {
                $errors["variants.$r.sku"] = __('Code :sku is already used by another product.', ['sku' => $sku]);
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function saveVariants(ShopProduct $product): void
    {
        $keep = [];
        $imageIds = $product->images()->pluck('id')->all();
        foreach (array_values($this->variants) as $i => $row) {
            $values = array_pad($row['values'], 3, null);
            $attrs = [
                'option1' => $values[0], 'option2' => $values[1], 'option3' => $values[2],
                'sku' => trim((string) $row['sku']) ?: null,
                'barcode' => trim((string) $row['barcode']) ?: null,
                'price' => trim((string) $row['price']) === '' ? null : (float) $row['price'],
                'compare_at_price' => trim((string) $row['compare_at_price']) === '' ? null : (float) $row['compare_at_price'],
                'cost_price' => trim((string) $row['cost_price']) === '' ? null : (float) $row['cost_price'],
                'low_stock_threshold' => trim((string) $row['low_stock_threshold']) === '' ? null : (int) $row['low_stock_threshold'],
                'image_id' => in_array($row['image_id'], $imageIds, true) ? $row['image_id'] : null,
                'supplier_url' => trim((string) $row['supplier_url']) ?: null,
                'is_active' => (bool) $row['is_active'],
                'sort_order' => $i,
            ];

            $variant = $row['id'] ? $product->variants()->find($row['id']) : null;
            if ($variant) {
                $variant->update($attrs);
            } else {
                $variant = $product->variants()->create($attrs + ['stock_qty' => 0]);
                $variant->setRelation('product', $product);
                if ((int) $row['opening'] > 0) {
                    $variant->adjustStock((int) $row['opening'], 'receipt', null, __('Opening stock'), null, Auth::id());
                }
            }
            $keep[] = $variant->id;

            $variant->tiers()->delete();
            collect($row['tiers'])->unique('min_qty')->sortBy('min_qty')->each(fn ($t) => ShopPriceTier::create([
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'min_qty' => (int) $t['min_qty'],
                'unit_price' => (float) $t['unit_price'],
            ]));
        }

        // Rows removed from the grid. Past orders keep their own copy of the name.
        $product->variants()->whereNotIn('id', $keep)->delete();
        $product->syncVariantStock();
    }

    public function delete()
    {
        $this->store()->products()->findOrFail($this->productId)->delete();
        session()->flash('success', __('Product deleted.'));

        return $this->redirectRoute('shop.admin.products', navigate: true);
    }

    public function render()
    {
        $store = $this->store();
        $product = $this->productId ? $store->products()->with('images')->find($this->productId) : null;

        return $this->adminPage('livewire.shop.admin.product-form', [
            'product' => $product,
            'categories' => ShopCategory::where('store_id', $store->id)->orderBy('name')->get(),
            'presets' => self::PRESETS,
        ], $product ? $product->name : __('New product'));
    }
}
