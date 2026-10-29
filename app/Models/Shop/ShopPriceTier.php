<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A quantity break: buying min_qty or more of a product (or of one variant, when variant_id is set) costs unit_price each. */
class ShopPriceTier extends Model
{
    use HasUuids;

    protected $fillable = ['product_id', 'variant_id', 'min_qty', 'unit_price'];

    protected $casts = ['unit_price' => 'decimal:2'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'product_id');
    }
}
