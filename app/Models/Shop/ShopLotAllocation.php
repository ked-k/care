<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Batch 42: how much of a lot a stock movement took (+) or put back (−). */
class ShopLotAllocation extends Model
{
    use HasUuids;

    protected $fillable = ['lot_id', 'movement_id', 'order_id', 'order_item_id', 'qty'];

    public function lot(): BelongsTo
    {
        return $this->belongsTo(ShopStockLot::class, 'lot_id');
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(ShopStockMovement::class, 'movement_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ShopOrder::class, 'order_id');
    }
}
