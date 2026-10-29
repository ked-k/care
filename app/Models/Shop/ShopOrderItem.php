<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopOrderItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'order_id', 'product_id', 'variant_id', 'sku', 'name', 'variant_title', 'pack_size', 'qty',
        'unit_price', 'vat_rate', 'vat_amount', 'line_total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ShopOrder::class, 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'product_id')->withTrashed();
    }

    public function lotAllocations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ShopLotAllocation::class, 'order_item_id');
    }

    /**
     * Which batches this line was picked from (net of anything put back):
     * [['lot' => ShopStockLot, 'qty' => 10], …]. Load lotAllocations.lot first.
     */
    public function lotsUsed()
    {
        return $this->lotAllocations->groupBy('lot_id')
            ->map(fn ($rows) => ['lot' => $rows->first()->lot, 'qty' => (int) $rows->sum('qty')])
            ->filter(fn ($r) => $r['lot'] && $r['qty'] > 0)->values();
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ShopProductVariant::class, 'variant_id');
    }
}
