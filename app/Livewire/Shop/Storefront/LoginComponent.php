<?php

namespace App\Livewire\Shop\Storefront;

use App\Models\Shop\ShopCustomer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginComponent extends StorefrontComponent
{
    public string $email = '';
    public string $password = '';
    public bool $remember = true;

    public function mount(string $storeSlug): void
    {
        $this->resolveStore($storeSlug);
        if ($this->customer()) {
            $this->redirectRoute('shop.account', $storeSlug);
        }
    }

    public function login()
    {
        $this->validate(['email' => 'required|email', 'password' => 'required']);

        $key = 'shop-login:'.$this->storeId.':'.Str::lower($this->email).'|'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 6)) {
            $this->addError('email', __('Too many attempts. Try again in :s seconds.', ['s' => RateLimiter::availableIn($key)]));

            return null;
        }

        $ok = Auth::guard('shop')->attempt([
            'store_id' => $this->storeId,
            'email' => Str::lower(trim($this->email)),
            'password' => $this->password,
            'is_blocked' => false,
        ], $this->remember);

        if (! $ok) {
            RateLimiter::hit($key, 60);
            $this->addError('email', __('That email and password don’t match an account here.'));

            return null;
        }

        RateLimiter::clear($key);
        session()->regenerate();

        /** @var ShopCustomer $customer */
        $customer = Auth::guard('shop')->user();
        $customer->forceFill(['last_login_at' => now()])->save();
        $this->cart()->mergeGuestCart($customer);

        $intended = session()->pull('shop.intended');

        return redirect($intended ?: route('shop.account', $this->store()->slug));
    }

    public function render()
    {
        return $this->page('livewire.shop.storefront.login', [], __('Sign in'));
    }
}
