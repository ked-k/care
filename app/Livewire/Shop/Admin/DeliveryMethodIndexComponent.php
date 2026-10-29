<?php

namespace App\Livewire\Shop\Admin;

use App\Models\Shop\ShopDeliveryMethod;

class DeliveryMethodIndexComponent extends ShopAdminComponent
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
        $this->form = ['name' => '', 'description' => '', 'price' => '', 'free_over' => '', 'min_days' => '', 'max_days' => '', 'is_collection' => false, 'is_active' => true, 'sort_order' => 0];
        $this->resetErrorBag();
    }

    public function create(): void
    {
        $this->blank();
        $this->dispatch('open-drawer', 'delivery-form');
    }

    /** One click to add the usual UK options. */
    public function addDefaults(): void
    {
        $store = $this->store();
        $defaults = [
            ['name' => 'Standard delivery', 'description' => 'Tracked UK courier', 'price' => 4.95, 'free_over' => 50, 'min_days' => 2, 'max_days' => 3, 'sort_order' => 1],
            ['name' => 'Next working day', 'description' => 'Order before 2pm', 'price' => 8.95, 'free_over' => null, 'min_days' => 1, 'max_days' => 1, 'sort_order' => 2],
            ['name' => 'Click & collect', 'description' => 'Collect from our office', 'price' => 0, 'free_over' => null, 'min_days' => null, 'max_days' => null, 'is_collection' => true, 'sort_order' => 3],
        ];
        foreach ($defaults as $d) {
            $store->deliveryMethods()->firstOrCreate(['name' => $d['name']], $d);
        }
        $this->toast(__('Added standard, next-day and click & collect — adjust the prices to suit.'));
    }

    public function edit(string $id): void
    {
        $this->blank();
        $m = ShopDeliveryMethod::where('store_id', $this->store()->id)->findOrFail($id);
        $this->editingId = $m->id;
        $this->form = [
            'name' => $m->name, 'description' => (string) $m->description, 'price' => (string) $m->price, 'free_over' => (string) $m->free_over,
            'min_days' => (string) $m->min_days, 'max_days' => (string) $m->max_days, 'is_collection' => $m->is_collection,
            'is_active' => $m->is_active, 'sort_order' => $m->sort_order,
        ];
        $this->dispatch('open-drawer', 'delivery-form');
    }

    public function save(): void
    {
        $this->validate([
            'form.name' => 'required|string|max:80',
            'form.description' => 'nullable|string|max:255',
            'form.price' => 'required|numeric|min:0',
            'form.free_over' => 'nullable|numeric|min:0',
            'form.min_days' => 'nullable|integer|min:0|max:60',
            'form.max_days' => 'nullable|integer|min:0|max:60|gte:form.min_days',
            'form.sort_order' => 'integer|min:0',
        ]);
        $data = $this->form;
        foreach (['free_over', 'min_days', 'max_days', 'description'] as $k) {
            $data[$k] = $data[$k] === '' ? null : $data[$k];
        }

        $this->editingId
            ? ShopDeliveryMethod::where('store_id', $this->store()->id)->findOrFail($this->editingId)->update($data)
            : $this->store()->deliveryMethods()->create($data);

        $this->dispatch('close-drawer', 'delivery-form');
        $this->toast(__('Delivery option saved.'));
    }

    public function toggle(string $id): void
    {
        $m = ShopDeliveryMethod::where('store_id', $this->store()->id)->findOrFail($id);
        $m->update(['is_active' => ! $m->is_active]);
    }

    public function delete(string $id): void
    {
        ShopDeliveryMethod::where('store_id', $this->store()->id)->findOrFail($id)->delete();
        $this->editingId = null;
        $this->dispatch('close-drawer', 'delivery-form');
        $this->toast(__('Delivery option deleted.'));
    }

    public function render()
    {
        return $this->adminPage('livewire.shop.admin.delivery', [
            'methods' => ShopDeliveryMethod::where('store_id', $this->store()->id)->orderBy('sort_order')->orderBy('price')->get(),
        ], __('Delivery options'));
    }
}
