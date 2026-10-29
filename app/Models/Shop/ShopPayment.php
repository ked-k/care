<?php

namespace App\Models\Shop;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopPayment extends Model
{
    use HasUuids;

    protected $fillable = ['order_id', 'gateway', 'provider_ref', 'amount', 'currency', 'status', 'payload', 'recorded_by'];

    protected $casts = [
        'amount' => 'decimal:2',
        'payload' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ShopOrder::class, 'order_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
