<?php

namespace App\Livewire\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\JournalLine;
use App\Services\Accounting\Reports;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;

/** Batch 43: the chart of accounts — add, rename, regroup, archive. */
class ChartComponent extends AccountsComponent
{
    #[Url]
    public string $search = '';

    #[Url]
    public bool $archived = false;

    public ?string $editingId = null;
    public array $form = [];

    public function mount(): void
    {
        $this->blank();
    }

    private function blank(): void
    {
        $this->editingId = null;
        $this->form = ['code' => '', 'name' => '', 'type' => 'expense', 'subtype' => 'admin_expenses', 'description' => '', 'is_active' => true];
        $this->resetErrorBag();
    }

    public function create(?string $type = null): void
    {
        $this->blank();
        if ($type && isset(Account::TYPES[$type])) {
            $this->form['type'] = $type;
            $this->form['subtype'] = array_key_first(Account::subtypesFor($type));
        }
        $this->dispatch('open-drawer', 'account-form');
    }

    public function edit(string $id): void
    {
        $this->blank();
        $a = Account::where('agency_id', $this->agencyId())->findOrFail($id);
        $this->editingId = $a->id;
        foreach (array_keys($this->form) as $k) {
            $this->form[$k] = is_bool($a->{$k}) ? $a->{$k} : (string) $a->{$k};
        }
        $this->dispatch('open-drawer', 'account-form');
    }

    public function updatedFormType($type): void
    {
        if (! isset(Account::subtypesFor($type)[$this->form['subtype']])) {
            $this->form['subtype'] = array_key_first(Account::subtypesFor($type));
        }
    }

    public function save(): void
    {
        $this->validate([
            'form.code' => ['required', 'string', 'max:20', Rule::unique('acc_accounts', 'code')->where('agency_id', $this->agencyId())->ignore($this->editingId)],
            'form.name' => 'required|string|max:255',
            'form.type' => ['required', Rule::in(array_keys(Account::TYPES))],
            'form.subtype' => ['required', Rule::in(array_keys(Account::subtypesFor($this->form['type'] ?? '')))],
            'form.description' => 'nullable|string|max:1000',
        ]);
        $data = ['code' => trim($this->form['code']), 'name' => trim($this->form['name']), 'type' => $this->form['type'],
            'subtype' => $this->form['subtype'], 'description' => $this->form['description'] ?: null, 'is_active' => (bool) $this->form['is_active']];

        if ($this->editingId) {
            $a = Account::where('agency_id', $this->agencyId())->findOrFail($this->editingId);
            if ($a->system_key && ! $data['is_active']) {
                $this->addError('form.is_active', __('This account is used by automatic posting, so it can’t be archived. Point that job at another account in Settings first.'));

                return;
            }
            $hasLines = JournalLine::where('account_id', $a->id)->exists();
            if ($hasLines && $a->type !== $data['type']) {
                $this->addError('form.type', __('This account already has entries, so its class (asset, income…) can’t change. Move its heading instead, or archive it and add a new one.'));

                return;
            }
            $a->update($data);
        } else {
            Account::create($data + ['agency_id' => $this->agencyId()]);
        }
        $this->dispatch('close-drawer', 'account-form');
        $this->toast(__('Saved.'));
    }

    public function delete(): void
    {
        $a = Account::where('agency_id', $this->agencyId())->findOrFail($this->editingId);
        if ($a->system_key || JournalLine::where('account_id', $a->id)->exists() || \App\Models\Accounting\DocumentLine::where('account_id', $a->id)->exists()) {
            $this->toast(__('This account is in use, so it can only be archived.'), 'warning');

            return;
        }
        $a->delete();
        $this->dispatch('close-drawer', 'account-form');
        $this->toast(__('Account deleted.'));
    }

    public function render()
    {
        $balances = (new Reports($this->settings()))->nets(null, today()->toDateString());
        $accounts = Account::where('agency_id', $this->agencyId())
            ->when(! $this->archived, fn ($q) => $q->where('is_active', true))
            ->when(trim($this->search) !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', '%'.trim($this->search).'%')->orWhere('code', 'like', trim($this->search).'%')))
            ->orderBy('code')->get()->each(fn ($a) => $a->balance = ($balances[$a->id] ?? 0) * ($a->isDebitNature() ? 1 : -1));

        return $this->page('livewire.accounting.chart', [
            'groups' => collect(Account::TYPES)->map(fn ($label, $type) => ['label' => $label, 'accounts' => $accounts->where('type', $type)]),
            'subtypes' => Account::subtypesFor($this->form['type'] ?? 'expense'),
            'editing' => $this->editingId ? Account::find($this->editingId) : null,
        ], __('Chart of accounts'));
    }
}
