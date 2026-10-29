<?php

namespace App\Livewire\Shop\Storefront;

use App\Models\Shop\ShopCustomer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RegisterComponent extends StorefrontComponent
{
    public string $firstName = '';
    public string $lastName = '';
    public string $email = '';
    public string $phone = '';
    public string $company = '';
    public string $password = '';
    public bool $marketingOptIn = false;

    public function mount(string $storeSlug): void
    {
        $this->resolveStore($storeSlug);
        if ($this->customer()) {
            $this->redirectRoute('shop.account', $storeSlug);
        }
    }

    public function register()
    {
        $this->email = Str::lower(trim($this->email));
        $existing = ShopCustomer::where('store_id', $this->storeId)->where('email', $this->email)->first();

        $this->validate([
            'firstName' => 'required|string|max:80',
            'lastName' => 'required|string|max:80',
            'email' => ['required', 'email:rfc', 'max:190', Rule::when($existing?->hasAccount(), ['prohibited'])],
            'phone' => 'nullable|string|max:30',
            'company' => 'nullable|string|max:120',
            'password' => 'required|string|min:8',
        ], [
            'email.prohibited' => __('There’s already an account with this email. Sign in instead, or reset your password.'),
            'password.min' => __('Use at least 8 characters for your password.'),
        ]);

        // A guest who has ordered before already has a customer record — give it a password.
        $customer = $existing ?? new ShopCustomer(['store_id' => $this->storeId, 'email' => $this->email]);
        $customer->fill([
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'phone' => $this->phone ?: $customer->phone,
            'company' => $this->company ?: $customer->company,
            'password' => $this->password,
            'marketing_opt_in' => $this->marketingOptIn,
            'last_login_at' => now(),
        ])->save();

        Auth::guard('shop')->login($customer, true);
        session()->regenerate();
        $this->cart()->mergeGuestCart($customer);

        session()->flash('success', __('Welcome, :name — your account is ready.', ['name' => $customer->first_name]));

        return redirect(session()->pull('shop.intended') ?: route('shop.account', $this->store()->slug));
    }

    public function render()
    {
        return $this->page('livewire.shop.storefront.register', [], __('Create an account'));
    }
}
