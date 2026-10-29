<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Batch 42: who deliveries (GRNs) come from. */
class ShopSupplier extends Model
{
    use HasUuids;

    protected $fillable = ['store_id', 'name', 'contact_name', 'email', 'phone', 'address', 'account_number', 'website', 'notes', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function grns(): HasMany
    {
        return $this->hasMany(ShopGrn::class, 'supplier_id');
    }
}
