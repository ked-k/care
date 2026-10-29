<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopCart extends Model
{
    use HasUuids;

    protected $fillable = ['store_id', 'customer_id', 'token', 'discount_code', 'delivery_method_id'];

    public function store(): BelongsTo
    {
        return $this->belongsTo(ShopStore::class, 'store_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(ShopCustomer::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShopCartItem::class, 'cart_id')->orderBy('created_at');
    }
}
