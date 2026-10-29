<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopDeliveryMethod extends Model
{
    use HasUuids;

    protected $fillable = [
        'store_id', 'name', 'description', 'price', 'free_over',
        'min_days', 'max_days', 'is_collection', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'free_over' => 'decimal:2',
        'is_collection' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(ShopStore::class, 'store_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('price');
    }

    public function priceFor(float $goodsSubtotal): float
    {
        if ($this->free_over !== null && $goodsSubtotal >= (float) $this->free_over) {
            return 0.0;
        }

        return (float) $this->price;
    }

    /** "2–3 working days", "Next working day", or null. */
    public function eta(): ?string
    {
        if (! $this->min_days && ! $this->max_days) {
            return null;
        }
        $min = $this->min_days ?: $this->max_days;
        $max = $this->max_days ?: $this->min_days;
        if ($max == 1) {
            return __('Next working day');
        }

        return $min == $max
            ? __(':n working days', ['n' => $min])
            : __(':min–:max working days', ['min' => $min, 'max' => $max]);
    }
}
