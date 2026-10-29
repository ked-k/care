<?php

namespace App\Models\Shop;

use App\Mail\Shop\CustomerPasswordResetMail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Batch 40: a member of the public with an account at one agency's shop.
 * Authenticates through the separate 'shop' guard (config/auth.php) — never
 * the staff 'web' guard — and has no roles or permissions of any kind.
 */
class ShopCustomer extends Authenticatable
{
    use HasUuids;

    protected $fillable = [
        'store_id', 'first_name', 'last_name', 'email', 'phone', 'company',
        'password', 'marketing_opt_in', 'is_blocked', 'notes', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'password' => 'hashed',
        'marketing_opt_in' => 'boolean',
        'is_blocked' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(ShopStore::class, 'store_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(ShopCustomerAddress::class, 'customer_id')->orderByDesc('is_default');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ShopOrder::class, 'customer_id')->latest();
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function hasAccount(): bool
    {
        return ! empty($this->password);
    }

    /**
     * Laravel's password broker calls this. The link has to point at this
     * customer's own store, so the default staff notification can't be used.
     */
    public function sendPasswordResetNotification($token): void
    {
        $url = route('shop.password.reset', [$this->store->slug, $token]).'?email='.urlencode($this->email);

        try {
            Mail::to($this->email)->send(new CustomerPasswordResetMail($this, $url));
        } catch (\Throwable $e) {
            Log::warning('Shop password reset email failed: '.$e->getMessage());
        }
    }
}
