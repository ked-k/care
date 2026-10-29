<?php

namespace App\Livewire\Shop\Admin;

use App\Models\Shop\ShopCustomer;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

class CustomerIndexComponent extends ShopAdminComponent
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $type = '';

    public ?string $viewingId = null;
    public string $notes = '';

    public function updated($property): void
    {
        if (in_array($property, ['search', 'type'], true)) {
            $this->resetPage();
        }
    }

    public function view(string $id): void
    {
        $c = ShopCustomer::where('store_id', $this->store()->id)->findOrFail($id);
        $this->viewingId = $c->id;
        $this->notes = (string) $c->notes;
        $this->dispatch('open-drawer', 'customer-view');
    }

    public function saveNotes(): void
    {
        ShopCustomer::where('store_id', $this->store()->id)->findOrFail($this->viewingId)->update(['notes' => $this->notes ?: null]);
        $this->toast(__('Notes saved.'));
    }

    public function toggleBlocked(): void
    {
        $c = ShopCustomer::where('store_id', $this->store()->id)->findOrFail($this->viewingId);
        $c->update(['is_blocked' => ! $c->is_blocked]);
        $this->toast($c->is_blocked ? __('Account blocked — they can’t sign in.') : __('Account unblocked.'), $c->is_blocked ? 'warning' : 'success');
    }

    public function render()
    {
        $customers = ShopCustomer::where('store_id', $this->store()->id)
            ->withCount('orders')
            ->withSum(['orders as spent' => fn ($q) => $q->where('payment_status', 'paid')], 'total')
            ->when($this->search !== '', function ($q) {
                $like = '%'.$this->search.'%';
                $q->where(fn ($w) => $w->where('email', 'like', $like)->orWhere('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)->orWhere('company', 'like', $like)->orWhere('phone', 'like', $like));
            })
            ->when($this->type === 'account', fn ($q) => $q->whereNotNull('password'))
            ->when($this->type === 'guest', fn ($q) => $q->whereNull('password'))
            ->when($this->type === 'marketing', fn ($q) => $q->where('marketing_opt_in', true))
            ->latest()
            ->paginate((int) request('per_page', 25));

        $viewing = $this->viewingId
            ? ShopCustomer::where('store_id', $this->store()->id)->with(['orders' => fn ($q) => $q->limit(15), 'addresses'])->find($this->viewingId)
            : null;

        return $this->adminPage('livewire.shop.admin.customers', compact('customers', 'viewing'), __('Customers'));
    }
}
