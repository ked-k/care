<?php

namespace App\Models\Accounting;

use App\Models\ServiceUser;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Batch 43: a customer, a supplier, or both. */
class Contact extends Model
{
    use HasUuids;

    protected $table = 'acc_contacts';

    protected $fillable = [
        'agency_id', 'is_customer', 'is_supplier', 'name', 'contact_name', 'email', 'phone', 'address',
        'tax_number', 'payment_terms_days', 'service_user_id', 'payer_key', 'shop_supplier_id',
        'external_ref', 'notes', 'is_active',
    ];

    protected $casts = ['is_customer' => 'boolean', 'is_supplier' => 'boolean', 'is_active' => 'boolean'];

    public function serviceUser(): BelongsTo
    {
        return $this->belongsTo(ServiceUser::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function roleLabel(): string
    {
        return match (true) {
            $this->is_customer && $this->is_supplier => __('Customer & supplier'),
            $this->is_supplier => __('Supplier'),
            default => __('Customer'),
        };
    }
}
