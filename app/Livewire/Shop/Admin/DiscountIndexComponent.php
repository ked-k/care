<?php

namespace App\Livewire\Shop\Admin;

use App\Models\Shop\ShopDiscount;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DiscountIndexComponent extends ShopAdminComponent
{
    public ?string $editingId = null;
    public array $form = [];

    public function mount(): void
    {
        $this->blank();
    }

    private function blank(): void
    {
        $this->editingId = null;
        $this->form = ['code' => '', 'description' => '', 'type' => 'percent', 'value' => '', 'min_subtotal' => '', 'starts_at' => '', 'ends_at' => '', 'usage_limit' => '', 'once_per_customer' => false, 'is_active' => true];
        $this->resetErrorBag();
    }

    public function create(): void
    {
        $this->blank();
        $this->form['code'] = strtoupper(Str::random(8));
        $this->dispatch('open-drawer', 'discount-form');
    }

    public function edit(string $id): void
    {
        $this->blank();
        $d = ShopDiscount::where('store_id', $this->store()->id)->findOrFail($id);
        $this->editingId = $d->id;
        $this->form = [
            'code' => $d->code, 'description' => (string) $d->description, 'type' => $d->type, 'value' => (string) $d->value,
            'min_subtotal' => (string) $d->min_subtotal, 'starts_at' => $d->starts_at?->format('Y-m-d') ?? '', 'ends_at' => $d->ends_at?->format('Y-m-d') ?? '',
            'usage_limit' => (string) $d->usage_limit, 'once_per_customer' => $d->once_per_customer, 'is_active' => $d->is_active,
        ];
        $this->dispatch('open-drawer', 'discount-form');
    }

    public function save(): void
    {
        $store = $this->store();
        $this->form['code'] = strtoupper(preg_replace('/\s+/', '', $this->form['code']));
        $this->validate([
            'form.code' => ['required', 'string', 'max:40', 'alpha_dash', Rule::unique('shop_discounts', 'code')->where('store_id', $store->id)->ignore($this->editingId)],
            'form.type' => 'required|in:percent,fixed,free_delivery',
            'form.value' => $this->form['type'] === 'free_delivery' ? 'nullable' : ($this->form['type'] === 'percent' ? 'required|numeric|min:1|max:100' : 'required|numeric|min:0.01'),
            'form.min_subtotal' => 'nullable|numeric|min:0',
            'form.starts_at' => 'nullable|date',
            'form.ends_at' => 'nullable|date|after_or_equal:form.starts_at',
            'form.usage_limit' => 'nullable|integer|min:1',
        ]);

        $data = $this->form;
        foreach (['min_subtotal', 'starts_at', 'ends_at', 'usage_limit', 'description'] as $k) {
            $data[$k] = $data[$k] === '' ? null : $data[$k];
        }
        $data['value'] = $data['type'] === 'free_delivery' ? 0 : $data['value'];
        if ($data['ends_at']) {
            $data['ends_at'] = $data['ends_at'].' 23:59:59';
        }

        $this->editingId
            ? ShopDiscount::where('store_id', $store->id)->findOrFail($this->editingId)->update($data)
            : $store->discounts()->create($data);

        $this->dispatch('close-drawer', 'discount-form');
        $this->toast(__('Discount code saved.'));
    }

    public function toggle(string $id): void
    {
        $d = ShopDiscount::where('store_id', $this->store()->id)->findOrFail($id);
        $d->update(['is_active' => ! $d->is_active]);
    }

    public function delete(string $id): void
    {
        ShopDiscount::where('store_id', $this->store()->id)->findOrFail($id)->delete();
        $this->editingId = null;
        $this->dispatch('close-drawer', 'discount-form');
        $this->toast(__('Discount code deleted.'));
    }

    public function render()
    {
        return $this->adminPage('livewire.shop.admin.discounts', [
            'discounts' => ShopDiscount::where('store_id', $this->store()->id)->latest()->get(),
        ], __('Discount codes'));
    }
}
