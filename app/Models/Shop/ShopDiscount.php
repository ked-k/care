<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopDiscount extends Model
{
    use HasUuids;

    protected $fillable = [
        'store_id', 'code', 'description', 'type', 'value', 'min_subtotal',
        'starts_at', 'ends_at', 'usage_limit', 'used_count', 'once_per_customer', 'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_subtotal' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'once_per_customer' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(ShopStore::class, 'store_id');
    }

    public static function findByCode(string $storeId, string $code): ?self
    {
        return static::where('store_id', $storeId)->where('code', strtoupper(trim($code)))->first();
    }

    /**
     * Why this code can't be used right now, or null if it can. Messages are
     * shown to the customer as-is.
     */
    public function rejectionReason(float $goodsSubtotal, ?string $email = null): ?string
    {
        if (! $this->is_active) {
            return __('This code is no longer active.');
        }
        if ($this->starts_at && now()->lt($this->starts_at)) {
            return __('This code starts on :date.', ['date' => $this->starts_at->format('j M Y')]);
        }
        if ($this->ends_at && now()->gt($this->ends_at)) {
            return __('This code expired on :date.', ['date' => $this->ends_at->format('j M Y')]);
        }
        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return __('This code has been fully used.');
        }
        if ($this->min_subtotal !== null && $goodsSubtotal < (float) $this->min_subtotal) {
            return __('Spend :amount or more to use this code.', ['amount' => $this->store->money($this->min_subtotal)]);
        }
        if ($this->once_per_customer && $email && ShopOrder::where('store_id', $this->store_id)
            ->where('discount_code', $this->code)->where('email', $email)
            ->whereNotIn('status', ['cancelled'])->exists()) {
            return __('You have already used this code.');
        }

        return null;
    }

    /** Amount taken off the goods subtotal (free-delivery codes return 0 here). */
    public function amountOff(float $goodsSubtotal): float
    {
        return match ($this->type) {
            'percent' => round($goodsSubtotal * min(100, (float) $this->value) / 100, 2),
            'fixed' => min($goodsSubtotal, (float) $this->value),
            default => 0.0,
        };
    }

    public function summary(): string
    {
        return match ($this->type) {
            'percent' => rtrim(rtrim(number_format((float) $this->value, 2), '0'), '.').'% off',
            'fixed' => $this->store->money($this->value).' off',
            'free_delivery' => 'Free delivery',
        };
    }
}
