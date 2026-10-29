<?php

namespace App\Models\Shop;

use App\Models\Agency;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Batch 40: an agency's online shop. One per agency (see Agency::shopStore()).
 * Staff manage it from the "Shop" workspace; the public storefront lives at
 * /store/{slug}.
 */
class ShopStore extends Model
{
    use HasUuids;

    protected $fillable = [
        'agency_id', 'name', 'slug', 'tagline', 'logo_path', 'brand_color', 'accent_color',
        'contact_email', 'orders_email', 'phone', 'address', 'company_number', 'vat_number',
        'currency', 'prices_include_vat', 'default_vat_rate', 'order_prefix', 'next_order_number',
        'about', 'delivery_info', 'returns_policy', 'terms', 'privacy',
        'payment_settings', 'is_live', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'prices_include_vat' => 'boolean',
        'default_vat_rate' => 'decimal:2',
        'is_live' => 'boolean',
        'payment_settings' => 'encrypted:array',
    ];

    protected $hidden = ['payment_settings'];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(ShopCategory::class, 'store_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(ShopProduct::class, 'store_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(ShopCustomer::class, 'store_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ShopOrder::class, 'store_id');
    }

    public function deliveryMethods(): HasMany
    {
        return $this->hasMany(ShopDeliveryMethod::class, 'store_id');
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(ShopDiscount::class, 'store_id');
    }

    /** Create the agency's store the first time anyone opens the Shop workspace. */
    public static function forAgency(Agency $agency): self
    {
        return static::firstOrCreate(
            ['agency_id' => $agency->id],
            [
                'name' => $agency->name,
                'slug' => static::uniqueSlug($agency->slug ?: $agency->name),
                'contact_email' => $agency->contact_email,
                'orders_email' => $agency->contact_email,
                'phone' => $agency->phone,
                'address' => $agency->address,
            ]
        );
    }

    public static function uniqueSlug(string $base, ?string $ignoreId = null): string
    {
        $slug = Str::slug($base) ?: 'shop';
        $candidate = $slug;
        $i = 1;
        while (static::where('slug', $candidate)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $candidate = $slug.'-'.(++$i);
        }

        return $candidate;
    }

    /** Reserve the next order number atomically, e.g. "AHS-1001". */
    public function reserveOrderNumber(): string
    {
        return DB::transaction(function () {
            $store = static::whereKey($this->id)->lockForUpdate()->first();
            $number = $store->next_order_number;
            $store->increment('next_order_number');

            return ($store->order_prefix ?: 'ORD').'-'.$number;
        });
    }

    /** Gateway settings for one payment method, e.g. setting('stripe') → ['enabled' => true, ...]. */
    public function paymentSetting(string $gateway, ?string $key = null, mixed $default = null): mixed
    {
        $settings = $this->payment_settings[$gateway] ?? [];

        return $key === null ? $settings : ($settings[$key] ?? $default);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function currencySymbol(): string
    {
        return match ($this->currency) {
            'GBP' => '£',
            'EUR' => '€',
            'USD' => '$',
            'UGX' => 'USh ',
            default => $this->currency.' ',
        };
    }

    public function money(float|string|null $amount): string
    {
        return $this->currencySymbol().number_format((float) $amount, 2);
    }

    public function storefrontUrl(): string
    {
        return route('shop.home', $this->slug);
    }
}
