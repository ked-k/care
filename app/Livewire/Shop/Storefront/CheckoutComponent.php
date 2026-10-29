<?php

namespace App\Livewire\Shop\Storefront;

use App\Services\Shop\CheckoutService;
use App\Services\Shop\Payments\PaymentException;
use App\Services\Shop\Payments\PaymentManager;
use App\Services\Shop\ShopMailer;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * One-page checkout in three sections that really are a sequence: your
 * details → delivery → payment. Guests can check out without an account and
 * optionally create one in the same step.
 */
class CheckoutComponent extends StorefrontComponent
{
    public string $email = '';
    public string $phone = '';

    public array $billing = [
        'first_name' => '', 'last_name' => '', 'company' => '',
        'line1' => '', 'line2' => '', 'city' => '', 'county' => '', 'postcode' => '', 'country' => 'GB',
    ];

    public bool $shipToDifferent = false;

    public array $shipping = [
        'name' => '', 'company' => '', 'line1' => '', 'line2' => '', 'city' => '', 'county' => '', 'postcode' => '', 'phone' => '',
    ];

    public string $savedAddressId = '';
    public string $deliveryMethodId = '';
    public string $paymentMethod = '';
    public string $notes = '';
    public bool $createAccount = false;
    public string $password = '';
    public bool $marketingOptIn = false;
    public bool $acceptTerms = false;
    public string $discountCode = '';

    public function mount(string $storeSlug): void
    {
        $store = $this->resolveStore($storeSlug);

        if ($this->cart()->count() === 0) {
            $this->redirectRoute('shop.cart', $storeSlug, navigate: true);

            return;
        }

        if ($customer = $this->customer()) {
            $this->email = $customer->email;
            $this->phone = (string) $customer->phone;
            $this->billing['first_name'] = $customer->first_name;
            $this->billing['last_name'] = (string) $customer->last_name;
            $this->billing['company'] = (string) $customer->company;
            if ($address = $customer->addresses()->first()) {
                $this->useSavedAddress($address->id);
            }
        }

        $totals = $this->cart()->totals();
        $this->deliveryMethodId = (string) ($totals['delivery_method']?->id ?? '');
        $this->paymentMethod = (string) array_key_first(PaymentManager::available($store));
    }

    public function useSavedAddress(string $id): void
    {
        $address = $this->customer()?->addresses()->find($id);
        if (! $address) {
            return;
        }
        $this->savedAddressId = $address->id;
        [$first, $last] = array_pad(explode(' ', $address->name, 2), 2, '');
        $this->billing = array_merge($this->billing, [
            'first_name' => $this->billing['first_name'] ?: $first,
            'last_name' => $this->billing['last_name'] ?: $last,
            'company' => (string) $address->company,
            'line1' => $address->line1,
            'line2' => (string) $address->line2,
            'city' => $address->city,
            'county' => (string) $address->county,
            'postcode' => $address->postcode,
        ]);
    }

    /**
     * After a failed "Place order", re-check each field as soon as the
     * customer fixes it, so the red message disappears straight away rather
     * than lingering until the next submit.
     */
    public function updated($property): void
    {
        if ($this->getErrorBag()->has($property)) {
            $this->validateOnly($property);
        }
    }

    public function updatedDeliveryMethodId($value): void
    {
        $this->cart()->setDeliveryMethod($value ?: null);
    }

    public function applyDiscount(): void
    {
        $error = $this->cart()->applyDiscount($this->discountCode, $this->email ?: null);
        if ($error) {
            $this->addError('discountCode', $error);

            return;
        }
        $this->resetErrorBag('discountCode');
        $this->discountCode = '';
        $this->notify(__('Discount applied.'));
    }

    public function removeDiscount(): void
    {
        $this->cart()->removeDiscount();
    }

    protected function rules(): array
    {
        $store = $this->store();
        $methods = $store->deliveryMethods()->active()->get();
        $isCollection = (bool) $methods->firstWhere('id', $this->deliveryMethodId)?->is_collection;
        $postcode = ['required', 'string', 'max:10', 'regex:/^[A-Za-z]{1,2}[0-9][A-Za-z0-9]?\s*[0-9][A-Za-z]{2}$/'];
        $needsShipping = $this->shipToDifferent && ! $isCollection;

        return [
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['required', 'string', 'max:30'],
            'billing.first_name' => ['required', 'string', 'max:80'],
            'billing.last_name' => ['required', 'string', 'max:80'],
            'billing.company' => ['nullable', 'string', 'max:120'],
            'billing.line1' => ['required', 'string', 'max:150'],
            'billing.line2' => ['nullable', 'string', 'max:150'],
            'billing.city' => ['required', 'string', 'max:80'],
            'billing.county' => ['nullable', 'string', 'max:80'],
            'billing.postcode' => $postcode,
            'shipping.name' => $needsShipping ? ['required', 'string', 'max:120'] : ['nullable'],
            'shipping.line1' => $needsShipping ? ['required', 'string', 'max:150'] : ['nullable'],
            'shipping.city' => $needsShipping ? ['required', 'string', 'max:80'] : ['nullable'],
            'shipping.postcode' => $needsShipping ? $postcode : ['nullable'],
            'deliveryMethodId' => $methods->isEmpty() ? ['nullable'] : ['required', Rule::in($methods->pluck('id')->all())],
            'paymentMethod' => ['required', Rule::in(array_keys(PaymentManager::available($store)))],
            'notes' => ['nullable', 'string', 'max:1000'],
            'password' => $this->createAccount && ! $this->customer() ? ['required', 'string', 'min:8'] : ['nullable'],
            'acceptTerms' => ['accepted'],
        ];
    }

    protected function messages(): array
    {
        return [
            'billing.postcode.regex' => __('Enter a UK postcode, like CR0 1AB.'),
            'shipping.postcode.regex' => __('Enter a UK postcode, like CR0 1AB.'),
            'paymentMethod.required' => __('Choose how you’d like to pay.'),
            'paymentMethod.in' => __('Choose how you’d like to pay.'),
            'deliveryMethodId.required' => __('Choose a delivery option.'),
            'acceptTerms.accepted' => __('Please agree to the terms to place your order.'),
            'password.min' => __('Use at least 8 characters for your password.'),
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'billing.first_name' => 'first name', 'billing.last_name' => 'last name',
            'billing.line1' => 'address', 'billing.city' => 'town or city', 'billing.postcode' => 'postcode',
            'shipping.name' => 'recipient name', 'shipping.line1' => 'delivery address',
            'shipping.city' => 'town or city', 'shipping.postcode' => 'postcode',
        ];
    }

    public function placeOrder(CheckoutService $checkout)
    {
        $this->validate();
        $store = $this->store();

        $billing = $this->billing;
        $billing['postcode'] = strtoupper(trim($billing['postcode']));
        $billing['name'] = trim($billing['first_name'].' '.$billing['last_name']);
        $billing['phone'] = $this->phone;

        $shipping = null;
        if ($this->shipToDifferent) {
            $shipping = $this->shipping;
            $shipping['postcode'] = strtoupper(trim((string) $shipping['postcode']));
            $shipping['country'] = 'GB';
        } else {
            $shipping = array_intersect_key($billing, array_flip(['name', 'company', 'line1', 'line2', 'city', 'county', 'postcode', 'country', 'phone']));
        }

        try {
            $order = $checkout->place($store, $this->cart(), [
                'email' => strtolower(trim($this->email)),
                'phone' => $this->phone,
                'billing' => $billing,
                'shipping' => $shipping,
                'delivery_method_id' => $this->deliveryMethodId ?: null,
                'payment_method' => $this->paymentMethod,
                'notes' => $this->notes ?: null,
                'create_account' => $this->createAccount,
                'password' => $this->password ?: null,
                'marketing_opt_in' => $this->marketingOptIn,
            ], request()->ip());
        } catch (RuntimeException $e) {
            $this->addError('checkout', $e->getMessage());

            return null;
        }

        $this->dispatch('cart-updated', count: 0);
        $orderUrl = route('shop.order', [$store->slug, $order->number]).'?t='.$order->access_token;
        $gateway = PaymentManager::get($order->payment_method);

        if ($gateway->isOnline()) {
            try {
                return redirect()->away($gateway->start($order));
            } catch (PaymentException $e) {
                session()->flash('warning', $e->getMessage());

                return redirect($orderUrl);
            }
        }

        ShopMailer::orderPlaced($order);

        return redirect($orderUrl);
    }

    public function render()
    {
        $store = $this->store();
        $totals = $this->cart()->totals($this->deliveryMethodId ?: null, $this->email ?: null);

        return $this->page('livewire.shop.storefront.checkout', [
            'totals' => $totals,
            'deliveryMethods' => $store->deliveryMethods()->active()->get(),
            'gateways' => PaymentManager::available($store),
            'customer' => $this->customer(),
            'addresses' => $this->customer()?->addresses ?? collect(),
        ], __('Checkout'));
    }
}
