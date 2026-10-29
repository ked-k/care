<?php

namespace App\Livewire\Shop\Storefront;

use App\Models\Shop\ShopCustomerAddress;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/** The signed-in customer's own area: orders, saved addresses, details. */
class AccountComponent extends StorefrontComponent
{
    use WithPagination;

    #[Url(except: 'orders')]
    public string $tab = 'orders';

    public array $profile = ['first_name' => '', 'last_name' => '', 'phone' => '', 'company' => '', 'marketing_opt_in' => false];
    public string $currentPassword = '';
    public string $newPassword = '';

    public ?string $editingAddressId = null;
    public array $address = ['label' => '', 'name' => '', 'company' => '', 'line1' => '', 'line2' => '', 'city' => '', 'county' => '', 'postcode' => '', 'phone' => '', 'is_default' => false];

    public function mount(string $storeSlug)
    {
        $this->resolveStore($storeSlug);
        $customer = $this->customer();
        if (! $customer) {
            session(['shop.intended' => route('shop.account', $storeSlug)]);

            return $this->redirectRoute('shop.login', $storeSlug);
        }

        $this->profile = [
            'first_name' => $customer->first_name,
            'last_name' => (string) $customer->last_name,
            'phone' => (string) $customer->phone,
            'company' => (string) $customer->company,
            'marketing_opt_in' => $customer->marketing_opt_in,
        ];
    }

    private function signedIn()
    {
        $customer = $this->customer();
        abort_unless($customer, 403);

        return $customer;
    }

    public function saveProfile(): void
    {
        $this->validate([
            'profile.first_name' => 'required|string|max:80',
            'profile.last_name' => 'required|string|max:80',
            'profile.phone' => 'nullable|string|max:30',
            'profile.company' => 'nullable|string|max:120',
        ]);
        $this->signedIn()->update($this->profile);
        $this->notify(__('Details saved.'));
    }

    public function changePassword(): void
    {
        $customer = $this->signedIn();
        $this->validate(['currentPassword' => 'required', 'newPassword' => 'required|string|min:8']);
        if (! Hash::check($this->currentPassword, $customer->password)) {
            $this->addError('currentPassword', __('That isn’t your current password.'));

            return;
        }
        $customer->update(['password' => $this->newPassword]);
        $this->reset('currentPassword', 'newPassword');
        $this->notify(__('Password changed.'));
    }

    public function editAddress(?string $id = null): void
    {
        $this->resetErrorBag();
        $this->editingAddressId = $id ?? 'new';
        $existing = $id ? $this->signedIn()->addresses()->find($id) : null;
        $this->address = $existing
            ? $existing->only(array_keys($this->address))
            : ['label' => '', 'name' => $this->signedIn()->full_name, 'company' => '', 'line1' => '', 'line2' => '', 'city' => '', 'county' => '', 'postcode' => '', 'phone' => '', 'is_default' => false];
        $this->address = array_map(fn ($v) => $v ?? '', $this->address);
    }

    public function saveAddress(): void
    {
        $customer = $this->signedIn();
        $this->validate([
            'address.name' => 'required|string|max:120',
            'address.line1' => 'required|string|max:150',
            'address.city' => 'required|string|max:80',
            'address.postcode' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z]{1,2}[0-9][A-Za-z0-9]?\s*[0-9][A-Za-z]{2}$/'],
        ], ['address.postcode.regex' => __('Enter a UK postcode, like CR0 1AB.')]);

        $data = $this->address;
        $data['postcode'] = strtoupper(trim($data['postcode']));
        $data['is_default'] = (bool) $data['is_default'] || ! $customer->addresses()->exists();

        if ($data['is_default']) {
            $customer->addresses()->update(['is_default' => false]);
        }

        $this->editingAddressId && $this->editingAddressId !== 'new'
            ? $customer->addresses()->whereKey($this->editingAddressId)->first()?->update($data)
            : $customer->addresses()->create($data);

        $this->editingAddressId = null;
        $this->notify(__('Address saved.'));
    }

    public function deleteAddress(string $id): void
    {
        $this->signedIn()->addresses()->whereKey($id)->delete();
        $this->notify(__('Address removed.'));
    }

    public function cancelAddress(): void
    {
        $this->editingAddressId = null;
    }

    public function render()
    {
        $customer = $this->customer();
        if (! $customer) {
            return $this->page('livewire.shop.storefront.login', [], __('Sign in'));
        }

        return $this->page('livewire.shop.storefront.account', [
            'customer' => $customer,
            'orders' => $customer->orders()->with('items')->paginate(10),
            'addresses' => $customer->addresses()->get(),
        ], __('Your account'));
    }
}
