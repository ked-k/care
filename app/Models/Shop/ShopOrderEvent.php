<?php

namespace App\Models\Shop;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One line on an order's timeline — staff see all, customers see visible_to_customer ones. */
class ShopOrderEvent extends Model
{
    use HasUuids;

    protected $fillable = ['order_id', 'type', 'message', 'visible_to_customer', 'user_id'];

    protected $casts = ['visible_to_customer' => 'boolean'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ShopOrder::class, 'order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
