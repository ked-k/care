<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopGrnLine extends Model
{
    use HasUuids;

    protected $fillable = [
        'grn_id', 'product_id', 'variant_id', 'name', 'sku', 'qty', 'unit_cost', 'line_total',
        'lot_number', 'expiry_date', 'lot_id', 'sort_order',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:4',
        'line_total' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    public function grn(): BelongsTo
    {
        return $this->belongsTo(ShopGrn::class, 'grn_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'product_id')->withTrashed();
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ShopProductVariant::class, 'variant_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(ShopStockLot::class, 'lot_id');
    }
}
