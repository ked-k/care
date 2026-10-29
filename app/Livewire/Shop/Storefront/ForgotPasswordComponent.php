<?php

namespace App\Livewire\Shop\Storefront;

use App\Models\Shop\ShopCustomer;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class ForgotPasswordComponent extends StorefrontComponent
{
    public string $email = '';
    public bool $sent = false;

    public function mount(string $storeSlug): void
    {
        $this->resolveStore($storeSlug);
    }

    public function send(): void
    {
        $this->validate(['email' => 'required|email']);
        $key = 'shop-reset:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('email', __('Too many requests. Try again in a few minutes.'));

            return;
        }
        RateLimiter::hit($key, 300);

        $email = Str::lower(trim($this->email));
        // Customer emails are only unique per store, so look the customer up
        // ourselves and hand the broker that exact record.
        $customer = ShopCustomer::where('store_id', $this->storeId)->where('email', $email)->first();
        if ($customer?->hasAccount()) {
            $broker = Password::broker('shop_customers');
            $customer->sendPasswordResetNotification($broker->createToken($customer));
        }

        // Same message either way, so this form can't be used to find out who has an account.
        $this->sent = true;
    }

    public function render()
    {
        return $this->page('livewire.shop.storefront.forgot-password', [], __('Reset your password'));
    }
}
