<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Batch 42: one batch of stock on the shelf — "Lot 23A118, expires 03/2027,
 * 40 of 100 left". Lots are only ever changed through StockLedger.
 */
class ShopStockLot extends Model
{
    use HasUuids;

    protected $fillable = [
        'store_id', 'product_id', 'variant_id', 'lot_number', 'expiry_date', 'received_on',
        'qty_received', 'qty_remaining', 'unit_cost', 'source', 'grn_id', 'grn_line_id',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'received_on' => 'date',
        'unit_cost' => 'decimal:4',
    ];

    public const SOURCE_LABELS = [
        'grn' => 'Delivery',
        'opening' => 'Opening balance',
        'adjustment' => 'Adjustment',
        'return' => 'Customer return',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'product_id')->withTrashed();
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ShopProductVariant::class, 'variant_id');
    }

    public function grn(): BelongsTo
    {
        return $this->belongsTo(ShopGrn::class, 'grn_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ShopLotAllocation::class, 'lot_id');
    }

    public function itemName(): string
    {
        $name = $this->product?->name ?? '—';

        return $this->variant ? $name.' — '.$this->variant->title() : $name;
    }

    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->lt(today());
    }

    public function daysToExpiry(): ?int
    {
        return $this->expiry_date ? (int) today()->diffInDays($this->expiry_date, false) : null;
    }

    /** expired | soon | ok | none — for badges. */
    public function expiryState(int $warnDays = 60): string
    {
        $days = $this->daysToExpiry();

        return match (true) {
            $days === null => 'none',
            $days < 0 => 'expired',
            $days <= $warnDays => 'soon',
            default => 'ok',
        };
    }

    public function label(): string
    {
        $parts = [$this->lot_number ? __('Lot :n', ['n' => $this->lot_number]) : __('No lot number')];
        if ($this->expiry_date) {
            $parts[] = __('exp. :d', ['d' => $this->expiry_date->format('d/m/Y')]);
        }

        return implode(', ', $parts);
    }

    public function sourceLabel(): string
    {
        return __(self::SOURCE_LABELS[$this->source] ?? ucfirst($this->source));
    }
}
