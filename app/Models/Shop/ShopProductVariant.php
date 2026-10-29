<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Batch 41: one sellable version of a product — "Large", or "Large · Case of
 * 10 boxes". option1..option3 line up with the product's options.
 *
 * Price rules (see the migration for the why):
 *  - no own price  → the product's price and the product's bulk prices, and
 *                    the quantity counts together with the other variants
 *                    that do the same (usesProductLadder());
 *  - own price     → that price, plus this variant's own bulk prices if any.
 *
 * Product-level settings that stay on the product: VAT rate, stock tracking,
 * back orders, min/max order quantity, status.
 */
class ShopProductVariant extends Model
{
    use HasUuids;

    protected $fillable = [
        'product_id', 'option1', 'option2', 'option3', 'sku', 'barcode',
        'price', 'compare_at_price', 'cost_price', 'stock_qty', 'low_stock_threshold',
        'weight_grams', 'image_id', 'supplier_sku', 'supplier_url', 'is_active', 'sort_order', 'source_ref',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'product_id')->withTrashed();
    }

    /** This variant's own bulk prices (empty when it shares the product's). */
    public function tiers(): HasMany
    {
        return $this->hasMany(ShopPriceTier::class, 'variant_id')->orderBy('min_qty');
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(ShopProductImage::class, 'image_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(ShopStockMovement::class, 'variant_id')->latest();
    }

    /** @return array<int, string> the option values, in option order */
    public function values(): array
    {
        return array_values(array_filter([$this->option1, $this->option2, $this->option3], fn ($v) => $v !== null && $v !== ''));
    }

    /** "Large · Case of 10 boxes" */
    public function title(): string
    {
        return implode(' · ', $this->values());
    }

    /** "Size: Large, Pack: Case of 10 boxes" — for order lines and packing slips. */
    public function describe(): string
    {
        $names = collect($this->product->options ?? [])->pluck('name')->all();

        return collect($this->values())
            ->map(fn ($value, $i) => isset($names[$i]) && $names[$i] !== '' ? $names[$i].': '.$value : $value)
            ->implode(', ');
    }

    public function basePrice(): float
    {
        return $this->price !== null ? (float) $this->price : (float) $this->product->price;
    }

    public function compareAtPrice(): ?float
    {
        $was = $this->compare_at_price ?? ($this->price === null ? $this->product->compare_at_price : null);

        return $was !== null && (float) $was > $this->basePrice() ? (float) $was : null;
    }

    public function costPrice(): ?float
    {
        $cost = $this->cost_price ?? $this->product->cost_price;

        return $cost !== null ? (float) $cost : null;
    }

    /** True when this variant sells on the product's price and bulk prices. */
    public function usesProductLadder(): bool
    {
        return $this->price === null && $this->ownTiers()->isEmpty();
    }

    private function ownTiers()
    {
        return $this->relationLoaded('tiers') ? $this->tiers : $this->tiers()->get();
    }

    /** The bulk prices that apply to this variant (its own, or the product's). */
    public function ladderTiers()
    {
        $own = $this->ownTiers();
        if ($own->isNotEmpty()) {
            return $own;
        }

        return $this->price === null ? $this->product->tiers : collect();
    }

    public function unitPriceFor(int $qty): float
    {
        $price = $this->basePrice();
        foreach ($this->ladderTiers() as $tier) {
            if ($qty >= $tier->min_qty) {
                $price = (float) $tier->unit_price;
            }
        }

        return $price;
    }

    public function lowestPrice(): float
    {
        return (float) $this->ladderTiers()->pluck('unit_price')->map(fn ($p) => (float) $p)->push($this->basePrice())->min();
    }

    public function hasQuantityBreaks(): bool
    {
        $base = $this->basePrice();

        return $this->ladderTiers()->contains(fn ($t) => $t->min_qty > 1 && (float) $t->unit_price < $base);
    }

    /** Same shape as ShopProduct::priceLadder(). */
    public function priceLadder(): array
    {
        $base = $this->basePrice();
        $steps = collect([['min' => 1, 'price' => $base]])
            ->concat($this->ladderTiers()->where('min_qty', '>', 1)->map(fn ($t) => ['min' => (int) $t->min_qty, 'price' => (float) $t->unit_price]))
            ->sortBy('min')->values();

        return $steps->map(function ($step, $i) use ($steps, $base) {
            $next = $steps[$i + 1] ?? null;

            return [
                'from' => $step['min'],
                'to' => $next ? $next['min'] - 1 : null,
                'price' => $step['price'],
                'saving' => $base > 0 ? round((1 - $step['price'] / $base) * 100) : 0,
            ];
        })->all();
    }

    public function lowStockThreshold(): int
    {
        return (int) ($this->low_stock_threshold ?? $this->product->low_stock_threshold);
    }

    public function inStock(): bool
    {
        $p = $this->product;

        return ! $p->track_stock || $this->stock_qty > 0 || $p->allow_backorder;
    }

    public function isPurchasable(): bool
    {
        return $this->is_active && $this->product->status === 'active' && $this->basePrice() > 0 && $this->inStock();
    }

    /** in_stock | low_stock | backorder | out_of_stock */
    public function stockState(): string
    {
        $p = $this->product;
        if (! $p->track_stock) {
            return 'in_stock';
        }
        if ($this->stock_qty <= 0) {
            return $p->allow_backorder ? 'backorder' : 'out_of_stock';
        }

        return $this->stock_qty <= $this->lowStockThreshold() ? 'low_stock' : 'in_stock';
    }

    public function maxPurchasableQty(): int
    {
        $p = $this->product;
        $max = $p->max_order_qty ?: 9999;
        if ($p->track_stock && ! $p->allow_backorder) {
            $max = min($max, max(0, $this->stock_qty));
        }

        return $max;
    }

    /**
     * Record a stock change through the ledger (StockLedger). The movement is
     * filed under the product with this variant named, the product's
     * stock_qty stays the total of its variants, and the batches follow.
     */
    public function adjustStock(int $qty, string $type, ?string $reference = null, ?string $note = null, ?string $orderId = null, ?int $userId = null, array $extra = []): ShopStockMovement
    {
        return \App\Services\Shop\StockLedger::record($this->product, $this, $qty, $type, [
            'reference' => $reference, 'note' => $note, 'order_id' => $orderId, 'user_id' => $userId,
        ] + $extra);
    }

    public function lots(): HasMany
    {
        return $this->hasMany(ShopStockLot::class, 'variant_id');
    }
}
