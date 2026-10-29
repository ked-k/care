<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Batch 40: a product in an agency's shop.
 *
 * Price is the single-unit price as displayed (VAT-inclusive when the store
 * says so). Quantity breaks live in shop_price_tiers — unitPriceFor() picks
 * the right one for a basket quantity.
 *
 * Batch 41: a product can have variants (sizes, colours, pack sizes) — see
 * ShopProductVariant. When has_variants is true the product is the parent:
 * its price/bulk prices are the defaults the variants inherit, stock lives on
 * the variants (stock_qty here is their total), and the storefront shows an
 * order grid with one row per variant.
 */
class ShopProduct extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'store_id', 'category_id', 'name', 'slug', 'sku', 'barcode', 'brand', 'pack_size', 'unit_name',
        'summary', 'description', 'price', 'compare_at_price', 'cost_price', 'vat_rate',
        'track_stock', 'stock_qty', 'low_stock_threshold', 'allow_backorder', 'track_expiry', 'stock_rotation',
        'min_order_qty', 'max_order_qty', 'weight_grams',
        'supplier_name', 'supplier_sku', 'supplier_url',
        'status', 'has_variants', 'options', 'is_featured', 'views', 'source', 'source_ref', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'track_stock' => 'boolean',
        'allow_backorder' => 'boolean',
        'track_expiry' => 'boolean',
        'is_featured' => 'boolean',
        'has_variants' => 'boolean',
        'options' => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(ShopStore::class, 'store_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ShopCategory::class, 'category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ShopProductImage::class, 'product_id')->orderBy('sort_order');
    }

    /** Product-level bulk prices (variants with their own prices have their own). */
    public function tiers(): HasMany
    {
        return $this->hasMany(ShopPriceTier::class, 'product_id')->whereNull('variant_id')->orderBy('min_qty');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ShopProductVariant::class, 'product_id')->orderBy('sort_order');
    }

    /**
     * The loaded variants with their parent set, so variant price/stock
     * methods don't query the product again for every row.
     *
     * @return \Illuminate\Support\Collection<int, ShopProductVariant>
     */
    public function variantList(bool $activeOnly = false)
    {
        $variants = $this->variants;
        foreach ($variants as $variant) {
            if (! $variant->relationLoaded('product')) {
                $variant->setRelation('product', $this);
            }
        }

        return $activeOnly ? $variants->where('is_active', true)->values() : $variants;
    }

    /** Eager loads for product cards and grids. */
    public function scopeWithCardData($query)
    {
        return $query->with(['tiers', 'images', 'category', 'variants.tiers']);
    }

    /** @return array<int, string> option names, e.g. ['Size', 'Pack'] */
    public function optionNames(): array
    {
        return collect($this->options ?? [])->pluck('name')->map(fn ($n) => (string) $n)->all();
    }

    /** "4 sizes", "3 colours", "12 options" — the card's variant hint. */
    public function variantSummary(): ?string
    {
        if (! $this->has_variants) {
            return null;
        }
        $count = $this->variantList(true)->count();
        $options = $this->options ?? [];
        if (count($options) === 1) {
            $name = strtolower((string) ($options[0]['name'] ?? ''));
            $plural = match (true) {
                $name === 'size' => __('sizes'),
                in_array($name, ['colour', 'color'], true) => __('colours'),
                $name === 'pack' || $name === 'pack size' => __('pack sizes'),
                default => null,
            };
            if ($plural) {
                return $count.' '.$plural;
            }
        }

        return trans_choice(':count option|:count options', $count, ['count' => $count]);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(ShopStockMovement::class, 'product_id')->latest();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch($query, ?string $term)
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        // Every word must appear somewhere (name, SKU, brand or summary), so
        // "nitrile medium" finds "Blue Nitrile Gloves - Medium - 100".
        foreach (preg_split('/\s+/', $term) as $word) {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $word).'%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhere('brand', 'like', $like)
                    ->orWhere('summary', 'like', $like)
                    // "gloves large" still finds gloves sold in sizes
                    ->orWhereHas('variants', fn ($v) => $v->where('option1', 'like', $like)->orWhere('option2', 'like', $like)
                        ->orWhere('option3', 'like', $like)->orWhere('sku', 'like', $like)->orWhere('barcode', 'like', $like));
            });
        }

        return $query;
    }

    /** The per-unit price for a given quantity, after quantity breaks. */
    public function unitPriceFor(int $qty): float
    {
        $price = (float) $this->price;
        foreach ($this->tiers as $tier) {
            if ($qty >= $tier->min_qty) {
                $price = (float) $tier->unit_price;
            }
        }

        return $price;
    }

    /** Lowest price any quantity can reach — used for "from £x" on cards. */
    public function lowestPrice(): float
    {
        if ($this->has_variants && ($variants = $this->variantList(true))->isNotEmpty()) {
            return (float) $variants->map(fn ($v) => $v->lowestPrice())->min();
        }
        $prices = $this->tiers->pluck('unit_price')->map(fn ($p) => (float) $p)->push((float) $this->price);

        return (float) $prices->min();
    }

    public function hasQuantityBreaks(): bool
    {
        if ($this->has_variants) {
            return $this->variantList(true)->contains(fn ($v) => $v->hasQuantityBreaks());
        }

        return $this->tiers->contains(fn ($t) => $t->min_qty > 1 && (float) $t->unit_price < (float) $this->price);
    }

    /**
     * Price ladder rows for the storefront, e.g.
     * [['from' => 1, 'to' => 19, 'price' => 3.90, 'saving' => 0], ...]
     */
    public function priceLadder(): array
    {
        $steps = collect([['min' => 1, 'price' => (float) $this->price]])
            ->concat($this->tiers->where('min_qty', '>', 1)->map(fn ($t) => ['min' => (int) $t->min_qty, 'price' => (float) $t->unit_price]))
            ->sortBy('min')->values();

        $base = (float) $this->price;

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

    /** The single-unit price a card shows: the cheapest variant's when they differ. */
    public function displayPrice(): float
    {
        if ($this->has_variants && ($variants = $this->variantList(true))->isNotEmpty()) {
            return (float) $variants->map(fn ($v) => $v->basePrice())->min();
        }

        return (float) $this->price;
    }

    /** True when variants sell at different single-unit prices ("From £x"). */
    public function priceVaries(): bool
    {
        return $this->has_variants && $this->variantList(true)->map(fn ($v) => $v->basePrice())->unique()->count() > 1;
    }

    public function isPurchasable(): bool
    {
        if ($this->has_variants) {
            return $this->status === 'active' && $this->variantList(true)->contains(fn ($v) => $v->isPurchasable());
        }

        return $this->status === 'active' && $this->price > 0 && $this->inStock();
    }

    public function inStock(): bool
    {
        if ($this->has_variants) {
            return $this->variantList(true)->contains(fn ($v) => $v->inStock());
        }

        return ! $this->track_stock || $this->stock_qty > 0 || $this->allow_backorder;
    }

    /** in_stock | low_stock | backorder | out_of_stock — for badges. */
    public function stockState(): string
    {
        if ($this->has_variants) {
            $states = $this->variantList(true)->map(fn ($v) => $v->stockState());
            if ($states->isEmpty() || $states->every(fn ($s) => $s === 'out_of_stock')) {
                return 'out_of_stock';
            }
            if ($states->contains('in_stock')) {
                return 'in_stock';
            }

            return $states->contains('low_stock') ? 'low_stock' : 'backorder';
        }
        if (! $this->track_stock) {
            return 'in_stock';
        }
        if ($this->stock_qty <= 0) {
            return $this->allow_backorder ? 'backorder' : 'out_of_stock';
        }

        return $this->stock_qty <= $this->low_stock_threshold ? 'low_stock' : 'in_stock';
    }

    /** Largest quantity a customer may put in a basket right now. */
    public function maxPurchasableQty(): int
    {
        $max = $this->max_order_qty ?: 9999;
        if ($this->track_stock && ! $this->allow_backorder) {
            $max = min($max, max(0, $this->stock_qty));
        }

        return $max;
    }

    public function primaryImageUrl(): ?string
    {
        $image = $this->relationLoaded('images') ? $this->images->first() : $this->images()->first();

        return $image?->url();
    }

    /**
     * A short "carton label" title for the placeholder tile when there's no
     * photo: "Economy Blue Powder Free Nitrile Gloves - Small - 100 gloves"
     * → "Economy Blue Powder Free Nitrile Gloves".
     */
    public function labelTitle(): string
    {
        return trim(Str::before($this->name, ' - ')) ?: $this->name;
    }

    /**
     * Record a stock change through the ledger and keep stock_qty (and the
     * batches) in step. Always go through this — never write stock_qty directly.
     * $extra: lot details, order line, GRN — see StockLedger::record().
     */
    public function adjustStock(int $qty, string $type, ?string $reference = null, ?string $note = null, ?string $orderId = null, ?int $userId = null, array $extra = []): ShopStockMovement
    {
        return \App\Services\Shop\StockLedger::record($this, null, $qty, $type, [
            'reference' => $reference, 'note' => $note, 'order_id' => $orderId, 'user_id' => $userId,
        ] + $extra);
    }

    public function lots(): HasMany
    {
        return $this->hasMany(ShopStockLot::class, 'product_id');
    }

    /** Keep stock_qty as the total of the variants' stock (after edits outside the ledger). */
    public function syncVariantStock(): void
    {
        if ($this->has_variants) {
            $this->stock_qty = (int) $this->variants()->sum('stock_qty');
            $this->saveQuietly();
        }
    }

    public static function uniqueSlug(string $storeId, string $name, ?string $ignoreId = null): string
    {
        $slug = Str::slug(Str::limit($name, 80, '')) ?: 'product';
        $candidate = $slug;
        $i = 1;
        while (static::withTrashed()->where('store_id', $storeId)->where('slug', $candidate)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $candidate = $slug.'-'.(++$i);
        }

        return $candidate;
    }
}
