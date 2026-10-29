<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopCustomerAddress extends Model
{
    use HasUuids;

    protected $fillable = [
        'customer_id', 'label', 'name', 'company', 'line1', 'line2',
        'city', 'county', 'postcode', 'country', 'phone', 'is_default',
    ];

    protected $casts = ['is_default' => 'boolean'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(ShopCustomer::class, 'customer_id');
    }

    /** The array shape snapshotted onto orders. */
    public function toOrderArray(): array
    {
        return $this->only(['name', 'company', 'line1', 'line2', 'city', 'county', 'postcode', 'country', 'phone']);
    }
}
