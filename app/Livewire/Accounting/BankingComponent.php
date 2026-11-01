<?php

namespace App\Livewire\Accounting;

use App\Models\Accounting\Account;
use App\Services\Accounting\Ledger;
use App\Services\Accounting\Reports;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/** Batch 43: bank, cash and mobile-money accounts — balances, add accounts, move money between them. */
class BankingComponent extends AccountsComponent
{
    public ?string $editingId = null;
    public array $form = [];
    public array $transfer = [];

    public function mount(): void
    {
        $this->blank();
        $this->blankTransfer();
    }

    private function blank(): void
    {
        $this->editingId = null;
        $this->form = ['name' => '', 'code' => $this->nextCode(), 'subtype' => 'bank', 'bank_name' => '', 'account_number' => '', 'sort_code' => '', 'description' => '', 'is_active' => true];
        $this->resetErrorBag();
    }

    private function blankTransfer(): void
    {
        $this->transfer = ['from' => '', 'to' => '', 'date' => today()->toDateString(), 'amount' => '', 'reference' => ''];
    }

    private function nextCode(): string
    {
        $codes = Account::where('agency_id', $this->agencyId())->where('code', 'like', '10%')->pluck('code')->map(fn ($c) => (int) $c);
        $n = 1061;
        while ($codes->contains($n)) {
            $n++;
        }

        return (string) $n;
    }

    public function create(): void
    {
        $this->blank();
        $this->dispatch('open-drawer', 'bank-form');
    }

    public function edit(string $id): void
    {
        $this->blank();
        $a = Account::where('agency_id', $this->agencyId())->money()->findOrFail($id);
        $this->editingId = $a->id;
        foreach (array_keys($this->form) as $k) {
            $this->form[$k] = is_bool($a->{$k}) ? $a->{$k} : (string) $a->{$k};
        }
        $this->dispatch('open-drawer', 'bank-form');
    }

    public function save(): void
    {
        $this->validate([
            'form.name' => 'required|string|max:255',
            'form.code' => ['required', 'string', 'max:20', Rule::unique('acc_accounts', 'code')->where('agency_id', $this->agencyId())->ignore($this->editingId)],
            'form.subtype' => 'required|in:bank,cash,mobile_money',
            'form.bank_name' => 'nullable|string|max:255',
            'form.account_number' => 'nullable|string|max:60',
            'form.sort_code' => 'nullable|string|max:30',
            'form.description' => 'nullable|string|max:1000',
        ]);
        $data = array_map(fn ($v) => is_string($v) && trim($v) === '' ? null : $v, $this->form) + ['type' => 'asset'];
        $data['is_active'] = (bool) $this->form['is_active'];
        $this->editingId
            ? Account::where('agency_id', $this->agencyId())->findOrFail($this->editingId)->update($data)
            : Account::create($data + ['agency_id' => $this->agencyId()]);
        $this->dispatch('close-drawer', 'bank-form');
        $this->toast(__('Saved.'));
    }

    public function saveTransfer(): void
    {
        $ids = Account::where('agency_id', $this->agencyId())->money()->pluck('id')->all();
        $this->validate([
            'transfer.from' => ['required', Rule::in($ids)],
            'transfer.to' => ['required', Rule::in($ids), 'different:transfer.from'],
            'transfer.date' => 'required|date',
            'transfer.amount' => 'required|numeric|min:0.01',
            'transfer.reference' => 'nullable|string|max:255',
        ], ['transfer.to.different' => __('Choose two different accounts.')]);
        $from = Account::find($this->transfer['from']);
        $to = Account::find($this->transfer['to']);
        $j = $this->attempt(fn () => Ledger::post($this->agencyId(), [
            'date' => $this->transfer['date'], 'type' => 'transfer',
            'reference' => $this->transfer['reference'] ?: null,
            'memo' => __('Transfer from :a to :b', ['a' => $from->name, 'b' => $to->name]),
            'created_by' => Auth::id(),
        ], [
            ['account' => $to, 'debit' => (float) $this->transfer['amount'], 'description' => __('From :a', ['a' => $from->name])],
            ['account' => $from, 'credit' => (float) $this->transfer['amount'], 'description' => __('To :b', ['b' => $to->name])],
        ]), __('Transfer posted.'));
        if ($j) {
            $this->js("window.dispatchEvent(new CustomEvent('close-modal',{detail:'transfer'}))");
            $this->blankTransfer();
        }
    }

    public function render()
    {
        $reports = new Reports($this->settings());
        $all = Account::where('agency_id', $this->agencyId())->money()->orderByDesc('is_active')->orderBy('code')->get();
        $nets = $reports->nets(null, today()->toDateString());
        $monthIn = \Illuminate\Support\Facades\DB::table('acc_journal_lines')->where('agency_id', $this->agencyId())
            ->whereIn('account_id', $all->pluck('id'))->where('date', '>=', today()->startOfMonth()->toDateString())
            ->groupBy('account_id')->selectRaw('account_id, sum(debit) as i, sum(credit) as o')->get()->keyBy('account_id');

        return $this->page('livewire.accounting.banking', [
            'accounts' => $all->map(fn ($a) => ['account' => $a, 'balance' => $nets[$a->id] ?? 0,
                'in' => (float) ($monthIn[$a->id]->i ?? 0), 'out' => (float) ($monthIn[$a->id]->o ?? 0)]),
            'active' => $all->where('is_active', true),
        ], __('Bank & cash'));
    }
}
