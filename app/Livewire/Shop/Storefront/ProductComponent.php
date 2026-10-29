<?php

namespace App\Livewire\Shop\Storefront;

use App\Livewire\Shop\Storefront\Concerns\AddsToCart;
use App\Models\Shop\ShopProduct;
use Livewire\Attributes\Locked;

/**
 * A product page. A plain product gets the quantity stepper and bulk-price
 * ladder; a product with variants (Batch 41) gets an order grid instead —
 * one row per size/option with its code, prices at each bulk step, stock and
 * a quantity box — and a single "Add to basket" for every row filled in.
 */
class ProductComponent extends StorefrontComponent
{
    use AddsToCart;

    #[Locked]
    public string $productId = '';

    public int $qty = 1;

    /** Variant grid: variant id => quantity typed in. */
    public array $quantities = [];

    public function mount(string $storeSlug, string $productSlug): void
    {
        $store = $this->resolveStore($storeSlug);
        $product = ShopProduct::where('store_id', $store->id)->where('slug', $productSlug)->first();

        // Drafts are viewable only in staff preview, so a product can be checked before it goes live.
        abort_unless($product && ($product->status === 'active' || \App\Http\Middleware\ResolveShopStore::isStaffPreview($store)), 404);

        $this->productId = $product->id;
        $this->qty = max(1, (int) $product->min_order_qty);
        ShopProduct::whereKey($product->id)->increment('views');
    }

    public function product(): ShopProduct
    {
        return ShopProduct::with(['tiers', 'images', 'category', 'variants.tiers', 'variants.image'])->findOrFail($this->productId);
    }

    public function setQty(int $qty): void
    {
        $product = $this->product();
        $this->qty = max(max(1, (int) $product->min_order_qty), min($qty, max(1, $product->maxPurchasableQty())));
    }

    public function increment(): void
    {
        $this->setQty($this->qty + 1);
    }

    public function decrement(): void
    {
        $this->setQty($this->qty - 1);
    }

    public function updatedQty($value): void
    {
        $this->setQty((int) $value);
    }

    public function add(): void
    {
        $this->addToCart($this->productId, $this->qty);
    }

    /* ---------- Variant grid ---------- */

    /** +1 / -1 buttons on a grid row. */
    public function bump(string $variantId, int $by): void
    {
        $this->quantities[$variantId] = max(0, (int) ($this->quantities[$variantId] ?? 0) + $by);
        $this->clampQuantities();
    }

    public function updatedQuantities(): void
    {
        $this->clampQuantities();
    }

    /** Whole numbers only, never more than can be sold. */
    private function clampQuantities(): void
    {
        $variants = $this->product()->variantList(true)->keyBy('id');
        foreach ($this->quantities as $id => $value) {
            $variant = $variants->get($id);
            $qty = max(0, (int) $value);
            if (! $variant) {
                unset($this->quantities[$id]);

                continue;
            }
            $this->quantities[$id] = $qty > 0 ? min($qty, max(0, $variant->maxPurchasableQty())) : 0;
        }
    }

    public function addSelected(): void
    {
        $this->clampQuantities();
        $chosen = array_filter($this->quantities, fn ($q) => $q > 0);
        if (! $chosen) {
            $this->notify(__('Enter a quantity next to the sizes you want.'), 'warning');

            return;
        }

        $product = $this->product();
        $variants = $product->variantList(true)->keyBy('id');
        $cart = $this->cart();
        $messages = [];
        $added = 0;
        foreach ($chosen as $id => $qty) {
            $variant = $variants->get($id);
            if (! $variant) {
                continue;
            }
            $message = $cart->add($product, $qty, $variant);
            $message ? $messages[] = $message : $added++;
        }

        $this->quantities = [];
        $this->dispatch('cart-updated', count: $cart->count(), added: $product->id);
        foreach (array_unique($messages) as $message) {
            $this->notify($message, 'warning');
        }
    }

    public function render()
    {
        $product = $this->product();
        $variants = $product->has_variants ? $product->variantList(true) : collect();

        $delivery = $this->store()->deliveryMethods()->active()->get();
        $data = [
            'product' => $product,
            'variants' => $variants,
            'related' => $this->related($product),
            'delivery' => $delivery,
            'freeOver' => $delivery->whereNotNull('free_over')->min('free_over'),
        ];

        if ($product->has_variants) {
            $data += $this->gridData($product, $variants);
        } else {
            $unit = $product->unitPriceFor($this->qty);
            $ladder = $product->priceLadder();
            $activeStep = collect($ladder)->filter(fn ($s) => $this->qty >= $s['from'])->keys()->last() ?? 0;
            $data += [
                'unit' => $unit,
                'lineTotal' => round($unit * $this->qty, 2),
                'ladder' => $ladder,
                'activeStep' => $activeStep,
                'nextStep' => $ladder[$activeStep + 1] ?? null,
            ];
        }

        return $this->page('livewire.shop.storefront.product', $data, $product->name);
    }

    /**
     * Everything the order grid needs. Rows are split into one table per set
     * of price bands, as on supplier sites: single boxes priced 1–19 / 20–39
     * / 40+, cases priced 1 / 2–3 / 4+. Each table has a column per band, and
     * the band each chosen row falls into is highlighted. Shared bulk prices
     * count every size together; a live total sits under the last table.
     */
    private function gridData(ShopProduct $product, $variants): array
    {
        $sharedQty = 0;
        foreach ($variants as $v) {
            if ($v->usesProductLadder()) {
                $sharedQty += (int) ($this->quantities[$v->id] ?? 0);
            }
        }

        // Already in the basket — shown on each row, and counted for mixed bulk prices.
        $inBasket = [];
        $basketShared = 0;
        foreach ($this->cart()->existing()?->items()->where('product_id', $product->id)->get() ?? [] as $item) {
            $inBasket[$item->variant_id] = (int) $item->qty;
            $v = $variants->firstWhere('id', $item->variant_id);
            if ($v && $v->usesProductLadder()) {
                $basketShared += (int) $item->qty;
            }
        }

        $tables = [];
        $count = 0;
        $total = 0.0;
        foreach ($variants as $v) {
            $ladder = array_slice($v->priceLadder(), 0, 5);
            $key = implode(',', array_column($ladder, 'from'));
            $tables[$key] ??= ['bands' => $ladder, 'rows' => []];

            $qty = (int) ($this->quantities[$v->id] ?? 0);
            $tierQty = max(1, $v->usesProductLadder() ? $sharedQty + $basketShared : $qty + ($inBasket[$v->id] ?? 0));
            $unit = $v->unitPriceFor($tierQty);
            $hit = null;
            if ($qty > 0) {
                foreach ($tables[$key]['bands'] as $b => $band) {
                    if ($tierQty >= $band['from'] && (! $band['to'] || $tierQty <= $band['to'])) {
                        $hit = $b;
                    }
                }
            }

            $tables[$key]['rows'][] = [
                'variant' => $v,
                'values' => $v->values(),
                'prices' => array_map(fn ($band) => $v->unitPriceFor($band['from']), $tables[$key]['bands']),
                'hit' => $hit,
                'qty' => $qty,
                'in_basket' => $inBasket[$v->id] ?? 0,
                'state' => $v->stockState(),
                'purchasable' => $v->isPurchasable(),
                'max' => $v->maxPurchasableQty(),
                'was' => $v->compareAtPrice(),
            ];
            if ($qty > 0) {
                $count += $qty;
                $total += round($unit * $qty, 2);
            }
        }

        // The next shared bulk price, to nudge "add 4 more to pay £x each".
        $nextShared = null;
        $sharedLadder = $variants->contains(fn ($v) => $v->usesProductLadder()) ? $product->priceLadder() : [];
        $mixMatch = count($sharedLadder) > 1 && $variants->filter(fn ($v) => $v->usesProductLadder())->count() > 1;
        if ($mixMatch && $sharedQty > 0) {
            $counted = $sharedQty + $basketShared;
            foreach ($sharedLadder as $step) {
                if ($step['from'] > $counted) {
                    $nextShared = ['more' => $step['from'] - $counted, 'price' => $step['price']];
                    break;
                }
            }
        }

        // Option filters above the grid (only worth it when there are many rows).
        $filters = [];
        if ($variants->count() > 6) {
            foreach ($product->optionNames() as $i => $name) {
                $values = $variants->map(fn ($v) => $v->values()[$i] ?? null)->filter()->unique()->values();
                if ($values->count() > 1) {
                    $filters[] = ['index' => $i, 'name' => $name, 'values' => $values->all()];
                }
            }
        }

        $base = $variants->map(fn ($v) => $v->basePrice());

        return [
            'tables' => array_values($tables),
            'gridCount' => $count,
            'gridTotal' => round($total, 2),
            'mixMatch' => $mixMatch,
            'nextShared' => $nextShared,
            'filters' => $filters,
            'fromPrice' => (float) $base->min(),
            'lowest' => (float) $variants->map(fn ($v) => $v->lowestPrice())->min(),
            'priceVaries' => $base->unique()->count() > 1,
            'unitLabel' => $this->unitLabel($product),
        ];
    }

    /** "Price per box" — from the pack size, when it names a container. */
    private function unitLabel(ShopProduct $product): string
    {
        if ($product->unit_name) {
            return __('Price per :unit', ['unit' => \Illuminate\Support\Str::lower($product->unit_name)]);
        }
        foreach (['box', 'case', 'pack', 'roll', 'bottle', 'tub', 'bag', 'pair', 'carton'] as $unit) {
            if ($product->pack_size && preg_match('/\b'.$unit.'\b/i', $product->pack_size)) {
                return __('Price per :unit', ['unit' => $unit]);
            }
        }

        return __('Price each');
    }

    private function related(ShopProduct $product)
    {
        return ShopProduct::where('store_id', $product->store_id)->active()
            ->where('id', '!=', $product->id)
            ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
            ->withCardData()
            // Same first words of the name first — "Nitrile Gloves - Large" next to "- Medium".
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', [mb_substr($product->labelTitle(), 0, 18).'%'])
            ->orderByDesc('views')
            ->limit(4)->get();
    }
}
