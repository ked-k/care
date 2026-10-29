<?php

namespace App\Livewire\Shop\Admin;

use App\Models\Shop\ShopSupplier;

/** Batch 42: the people deliveries (GRNs) come from. */
class SupplierIndexComponent extends ShopAdminComponent
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
        $this->form = ['name' => '', 'contact_name' => '', 'email' => '', 'phone' => '', 'address' => '', 'account_number' => '', 'website' => '', 'notes' => '', 'is_active' => true];
        $this->resetErrorBag();
    }

    public function create(): void
    {
        $this->blank();
        $this->dispatch('open-drawer', 'supplier-form');
    }

    public function edit(string $id): void
    {
        $this->blank();
        $s = ShopSupplier::where('store_id', $this->store()->id)->findOrFail($id);
        $this->editingId = $s->id;
        foreach (array_keys($this->form) as $k) {
            $this->form[$k] = is_bool($s->{$k}) ? $s->{$k} : (string) $s->{$k};
        }
        $this->dispatch('open-drawer', 'supplier-form');
    }

    public function save(): void
    {
        $this->validate([
            'form.name' => 'required|string|max:255',
            'form.contact_name' => 'nullable|string|max:255',
            'form.email' => 'nullable|email|max:255',
            'form.phone' => 'nullable|string|max:40',
            'form.address' => 'nullable|string|max:1000',
            'form.account_number' => 'nullable|string|max:60',
            'form.website' => 'nullable|url|max:255',
            'form.notes' => 'nullable|string|max:2000',
        ]);
        $data = array_map(fn ($v) => is_string($v) && trim($v) === '' ? null : $v, $this->form);
        $data['is_active'] = (bool) $this->form['is_active'];

        $this->editingId
            ? ShopSupplier::where('store_id', $this->store()->id)->findOrFail($this->editingId)->update($data)
            : ShopSupplier::create($data + ['store_id' => $this->store()->id]);

        $this->dispatch('close-drawer', 'supplier-form');
        $this->toast(__('Supplier saved.'));
    }

    public function delete(string $id): void
    {
        $s = ShopSupplier::where('store_id', $this->store()->id)->withCount('grns')->findOrFail($id);
        if ($s->grns_count) {
            $s->update(['is_active' => false]);
            $this->toast(__('This supplier has deliveries on record, so it has been hidden instead of deleted.'), 'warning');
        } else {
            $s->delete();
            $this->toast(__('Supplier deleted.'));
        }
        $this->dispatch('close-drawer', 'supplier-form');
    }

    public function render()
    {
        return $this->adminPage('livewire.shop.admin.suppliers', [
            'suppliers' => ShopSupplier::where('store_id', $this->store()->id)
                ->withCount(['grns' => fn ($q) => $q->where('status', 'received')])
                ->withMax(['grns' => fn ($q) => $q->where('status', 'received')], 'received_on')
                ->orderByDesc('is_active')->orderBy('name')->get(),
        ], __('Suppliers'));
    }
}
