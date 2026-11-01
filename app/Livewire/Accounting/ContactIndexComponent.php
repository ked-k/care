<?php

namespace App\Livewire\Accounting;

use App\Models\Accounting\Contact;
use App\Models\ServiceUser;
use App\Services\Accounting\Reports;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Url;

/** Batch 43: customers or suppliers, with what each owes / is owed. */
class ContactIndexComponent extends AccountsComponent
{
    public string $role = 'customer';

    #[Url]
    public string $search = '';

    #[Url]
    public bool $owing = false;

    public ?string $editingId = null;
    public array $form = [];

    public function mount(): void
    {
        $this->role = Route::currentRouteName() === 'accounts.suppliers' ? 'supplier' : 'customer';
        $this->blank();
    }

    private function blank(): void
    {
        $this->editingId = null;
        $this->form = [
            'name' => '', 'contact_name' => '', 'email' => '', 'phone' => '', 'address' => '', 'tax_number' => '',
            'payment_terms_days' => '', 'service_user_id' => '', 'notes' => '',
            'is_customer' => $this->role === 'customer', 'is_supplier' => $this->role === 'supplier', 'is_active' => true,
        ];
        $this->resetErrorBag();
    }

    public function create(): void
    {
        $this->blank();
        $this->dispatch('open-drawer', 'contact-form');
    }

    public function edit(string $id): void
    {
        $this->blank();
        $c = Contact::where('agency_id', $this->agencyId())->findOrFail($id);
        $this->editingId = $c->id;
        foreach (array_keys($this->form) as $k) {
            $this->form[$k] = is_bool($c->{$k}) ? $c->{$k} : (string) $c->{$k};
        }
        $this->dispatch('open-drawer', 'contact-form');
    }

    public function save(): void
    {
        $this->validate([
            'form.name' => 'required|string|max:255',
            'form.contact_name' => 'nullable|string|max:255',
            'form.email' => 'nullable|email|max:255',
            'form.phone' => 'nullable|string|max:40',
            'form.address' => 'nullable|string|max:1000',
            'form.tax_number' => 'nullable|string|max:60',
            'form.payment_terms_days' => 'nullable|integer|min:0|max:365',
            'form.service_user_id' => 'nullable|string',
            'form.notes' => 'nullable|string|max:2000',
        ]);
        if (! $this->form['is_customer'] && ! $this->form['is_supplier']) {
            $this->addError('form.is_customer', __('Tick customer, supplier, or both.'));

            return;
        }
        $data = array_map(fn ($v) => is_string($v) && trim($v) === '' ? null : $v, $this->form);
        foreach (['is_customer', 'is_supplier', 'is_active'] as $b) {
            $data[$b] = (bool) $this->form[$b];
        }
        if ($data['service_user_id'] && ! ServiceUser::where('agency_id', $this->agencyId())->whereKey($data['service_user_id'])->exists()) {
            $data['service_user_id'] = null;
        }
        $this->editingId
            ? Contact::where('agency_id', $this->agencyId())->findOrFail($this->editingId)->update($data)
            : Contact::create($data + ['agency_id' => $this->agencyId()]);
        $this->dispatch('close-drawer', 'contact-form');
        $this->toast(__('Saved.'));
    }

    public function render()
    {
        $flag = $this->role === 'customer' ? 'is_customer' : 'is_supplier';
        $contacts = Contact::where('agency_id', $this->agencyId())->where($flag, true)
            ->when(trim($this->search) !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', '%'.trim($this->search).'%')->orWhere('email', 'like', '%'.trim($this->search).'%')))
            ->withCount(['documents as open_count' => fn ($q) => $q->whereIn('status', ['posted', 'partial'])])
            ->orderByDesc('is_active')->orderBy('name')->get();
        $balances = (new Reports($this->settings()))->contactBalances($contacts->pluck('id')->all());
        $sign = $this->role === 'customer' ? 1 : -1;
        $contacts->each(fn ($c) => $c->balance = ($balances[$c->id] ?? 0) * $sign);
        if ($this->owing) {
            $contacts = $contacts->filter(fn ($c) => abs($c->balance) >= 0.01)->values();
        }
        $title = $this->role === 'customer' ? __('Customers') : __('Suppliers');

        return $this->page('livewire.accounting.contacts', [
            'title' => $title,
            'contacts' => $contacts,
            'total' => $contacts->sum('balance'),
            'serviceUsers' => $this->role === 'customer' ? ServiceUser::where('agency_id', $this->agencyId())->orderBy('name')->get(['id', 'name']) : collect(),
        ], $title);
    }
}
