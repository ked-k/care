<?php

namespace App\Livewire\Shop\Storefront;

use App\Models\Shop\ShopCustomer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;

class ResetPasswordComponent extends StorefrontComponent
{
    #[Locked]
    public string $token = '';

    public string $email = '';
    public string $password = '';

    public function mount(string $storeSlug, string $token): void
    {
        $this->resolveStore($storeSlug);
        $this->token = $token;
        $this->email = (string) request()->query('email', '');
    }

    public function resetPassword()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ], ['password.min' => __('Use at least 8 characters for your password.')]);

        $customer = ShopCustomer::where('store_id', $this->storeId)->where('email', Str::lower(trim($this->email)))->first();
        $broker = Password::broker('shop_customers');

        if (! $customer || ! $broker->tokenExists($customer, $this->token)) {
            $this->addError('email', __('This reset link has expired or was already used. Request a new one.'));

            return null;
        }

        $customer->forceFill(['password' => $this->password, 'remember_token' => Str::random(60)])->save();
        $broker->deleteToken($customer);

        Auth::guard('shop')->login($customer);
        session()->regenerate();
        session()->flash('success', __('Password updated — you’re signed in.'));

        return redirect()->route('shop.account', $this->store()->slug);
    }

    public function render()
    {
        return $this->page('livewire.shop.storefront.reset-password', [], __('Choose a new password'));
    }
}
