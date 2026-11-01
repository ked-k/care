<?php

namespace App\Livewire\Shop\Admin;

use App\Models\Shop\ShopStore;
use App\Services\Shop\Payments\PaymentManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;

/** Store details, branding, payments (gateway credentials) and policy pages. */
class ShopSettingsComponent extends ShopAdminComponent
{
    use WithFileUploads;

    #[Url(except: 'store')]
    public string $tab = 'store';

    public array $form = [];
    public array $payments = [];
    public $logo = null;

    /** Secret fields the user hasn't retyped stay as they are. */
    private const MASK = '••••••••';

    public function mount(): void
    {
        $store = $this->store();
        $this->form = collect($store->only([
            'name', 'slug', 'domain', 'tagline', 'brand_color', 'accent_color', 'contact_email', 'orders_email', 'phone', 'address',
            'company_number', 'vat_number', 'prices_include_vat', 'default_vat_rate', 'order_prefix',
            'about', 'delivery_info', 'returns_policy', 'terms', 'privacy', 'is_live', 'domain',
        ]))->map(fn ($v) => is_bool($v) ? $v : (string) ($v ?? ''))->all();
        $this->form['default_vat_rate'] = (string) (float) $store->default_vat_rate;
        $this->form['prices_include_vat'] = $store->prices_include_vat ? '1' : '0';

        foreach (PaymentManager::all() as $code => $gateway) {
            $saved = $store->paymentSetting($code);
            $this->payments[$code] = ['enabled' => (bool) ($saved['enabled'] ?? false)];
            foreach ($gateway->settingsFields() as $field) {
                $value = $saved[$field['key']] ?? ($field['type'] === 'select' ? array_key_first($field['options']) : '');
                $this->payments[$code][$field['key']] = $field['type'] === 'password' && filled($value) ? self::MASK : (string) $value;
            }
        }
    }

    public function saveStore(): void
    {
        $store = $this->store();
        $this->form['slug'] = Str::slug($this->form['slug'] ?: $this->form['name']);
        $this->form['domain'] = (string) \App\Support\Shop\ShopDomains::normalise($this->form['domain'] ?? '');
        $this->validate([
            'form.name' => 'required|string|max:120',
            'form.slug' => ['required', 'string', 'max:80', Rule::unique('shop_stores', 'slug')->ignore($store->id)],
            'form.domain' => ['nullable', 'string', 'max:190', 'regex:/^(?=.{1,190}$)([a-z0-9](-?[a-z0-9])*\.)+[a-z]{2,}$/',
                Rule::unique('shop_stores', 'domain')->ignore($store->id),
                function ($attr, $value, $fail) {
                    if ($value && strtolower($value) === strtolower(request()->getHost())) {
                        $fail(__('That’s the address of the care app itself — use a separate one such as shop.yourdomain.co.uk.'));
                    }
                }],
            'form.tagline' => 'nullable|string|max:160',
            'form.brand_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'form.accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'form.contact_email' => 'nullable|email',
            'form.orders_email' => 'nullable|email',
            'form.phone' => 'nullable|string|max:40',
            'form.address' => 'nullable|string|max:500',
            'form.company_number' => 'nullable|string|max:20',
            'form.vat_number' => 'nullable|string|max:20',
            'form.default_vat_rate' => 'required|numeric|in:0,5,20',
            'form.order_prefix' => 'required|alpha_dash|max:10',
            'logo' => 'nullable|image|max:2048',
        ]);

        $data = collect($this->form)->only([
            'name', 'slug', 'tagline', 'brand_color', 'accent_color', 'contact_email', 'orders_email', 'phone', 'address',
            'company_number', 'vat_number', 'prices_include_vat', 'default_vat_rate', 'order_prefix',
        ])->map(fn ($v) => $v === '' ? null : $v)->all();
        $data['prices_include_vat'] = (bool) $this->form['prices_include_vat'];
        $data['order_prefix'] = strtoupper($data['order_prefix']);
        if ($this->logo) {
            $data['logo_path'] = $this->logo->store('shop/logos', 'public');
            $this->logo = null;
        }
        $store->update($data + ['updated_by' => Auth::id()]);
        $this->toast($store->wasChanged('domain') && $store->domain
            ? __('Saved. The shop now answers at :d once that address points at this server.', ['d' => $store->domain])
            : __('Shop details saved.'));
    }

    public function savePolicies(): void
    {
        $this->validate([
            'form.about' => 'nullable|string|max:20000',
            'form.delivery_info' => 'nullable|string|max:20000',
            'form.returns_policy' => 'nullable|string|max:20000',
            'form.terms' => 'nullable|string|max:40000',
            'form.privacy' => 'nullable|string|max:40000',
        ]);
        $this->store()->update(collect($this->form)->only(['about', 'delivery_info', 'returns_policy', 'terms', 'privacy'])
            ->map(fn ($v) => $v === '' ? null : $v)->all() + ['updated_by' => Auth::id()]);
        $this->toast(__('Policy pages saved.'));
    }

    public function savePayments(): void
    {
        $store = $this->store();
        $current = $store->payment_settings ?? [];
        $rules = [];

        foreach (PaymentManager::all() as $code => $gateway) {
            if (empty($this->payments[$code]['enabled'])) {
                continue;
            }
            foreach ($gateway->settingsFields() as $field) {
                $required = in_array($field['key'], ['secret_key', 'client_id', 'client_secret', 'account_number', 'sort_code', 'account_name'], true);
                if ($required) {
                    $rules["payments.$code.{$field['key']}"] = 'required';
                }
            }
        }
        // Livewire treats validate([]) as "use the component's $rules", which
        // this component doesn't have — so only validate when something
        // switched on actually needs a field (e.g. not for pay-on-delivery
        // alone, or when everything is switched off).
        if ($rules) {
            $this->validate($rules, ['required' => __('Needed to turn this payment method on.')]);
        }

        foreach (PaymentManager::all() as $code => $gateway) {
            $next = ['enabled' => (bool) ($this->payments[$code]['enabled'] ?? false)];
            foreach ($gateway->settingsFields() as $field) {
                $value = trim((string) ($this->payments[$code][$field['key']] ?? ''));
                $next[$field['key']] = $field['type'] === 'password' && $value === self::MASK
                    ? ($current[$code][$field['key']] ?? null)
                    : ($value === '' ? null : $value);
            }
            $current[$code] = $next;
        }

        if (($current['stripe']['enabled'] ?? false) && str_starts_with((string) ($current['stripe']['secret_key'] ?? ''), 'pk_')) {
            $this->addError('payments.stripe.secret_key', __('That’s the publishable key — the secret key starts sk_.'));

            return;
        }

        $store->update(['payment_settings' => $current, 'updated_by' => Auth::id()]);
        $this->toast(__('Payment settings saved.'));
    }

    public function setLive(bool $live): void
    {
        $store = $this->store();
        if ($live) {
            $missing = [];
            if (! $store->products()->active()->exists()) {
                $missing[] = __('at least one published product');
            }
            if (empty(PaymentManager::available($store))) {
                $missing[] = __('a way to pay');
            }
            if (! $store->deliveryMethods()->active()->exists()) {
                $missing[] = __('a delivery option');
            }
            if ($missing) {
                $this->toast(__('Before opening, add :list.', ['list' => implode(', ', $missing)]), 'warning');

                return;
            }
        }
        $store->update(['is_live' => $live, 'updated_by' => Auth::id()]);
        $this->form['is_live'] = $live;
        $this->toast($live ? __('Your shop is open to the public.') : __('Shop closed — only your staff can see it now.'), $live ? 'success' : 'warning');
    }

    public function render()
    {
        $store = $this->store();

        return $this->adminPage('livewire.shop.admin.settings', [
            'gateways' => PaymentManager::all(),
            'webhookUrl' => route('shop.webhooks.stripe', $store->id),
            'storefrontUrl' => route('shop.home', $store->slug),
            'mask' => self::MASK,
        ], __('Shop settings'));
    }
}
