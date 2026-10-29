<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopCartItem extends Model
{
    use HasUuids;

    protected $fillable = ['cart_id', 'product_id', 'variant_id', 'qty'];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(ShopCart::class, 'cart_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ShopProductVariant::class, 'variant_id');
    }
}
