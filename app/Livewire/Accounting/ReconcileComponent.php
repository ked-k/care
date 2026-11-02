<?php

namespace App\Livewire\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\Reconciliation;
use App\Models\Accounting\StatementLine;
use App\Services\Accounting\Reconciler;
use Illuminate\Support\Facades\Auth;
use Livewire\WithFileUploads;

/**
 * Batch 44a: reconcile one bank / cash account to its statement — tick the
 * entries that appear on it until the difference is zero. Optionally import
 * the bank's CSV to match most of them automatically.
 */
class ReconcileComponent extends AccountsComponent
{
    use WithFileUploads;

    public string $accountId;
    public array $start = ['date' => '', 'balance' => ''];
    public $csv = null;
    public array $create = [];      // statement line id => account id
    public bool $showLater = false;

    public function mount(string $accountId): void
    {
        $this->accountId = Account::where('agency_id', $this->agencyId())->money()->findOrFail($accountId)->id;
        $this->start['date'] = today()->subMonthNoOverflow()->endOfMonth()->toDateString();
    }

    private function account(): Account
    {
        return Account::where('agency_id', $this->agencyId())->findOrFail($this->accountId);
    }

    private function rec(): ?Reconciliation
    {
        return Reconciliation::where('account_id', $this->accountId)->where('status', 'in_progress')->first();
    }

    public function begin(): void
    {
        $this->validate(['start.date' => 'required|date|before_or_equal:today', 'start.balance' => 'required|numeric', 'csv' => 'nullable|file|mimes:csv,txt|max:5120'],
            ['start.balance.required' => __('Enter the closing balance shown on the statement.')]);
        $rec = $this->attempt(fn () => Reconciler::start($this->account(), $this->start['date'], (float) $this->start['balance'], Auth::id()));
        if ($rec && $this->csv) {
            $this->importFile($rec);
        }
    }

    public function importCsv(): void
    {
        $this->validate(['csv' => 'required|file|mimes:csv,txt|max:5120']);
        if ($rec = $this->rec()) {
            $this->importFile($rec);
        }
    }

    private function importFile(Reconciliation $rec): void
    {
        $result = $this->attempt(fn () => Reconciler::import($rec, Reconciler::parseCsv($this->csv->getRealPath()), $this->csv->getClientOriginalName()));
        if ($result) {
            $this->toast(__(':a statement lines read, :m matched automatically.', ['a' => $result['added'], 'm' => $result['matched']]));
        }
        $this->csv = null;
    }

    public function toggle(string $lineId, bool $on): void
    {
        if ($rec = $this->rec()) {
            $this->attemptOk(fn () => Reconciler::tick($rec, $lineId, $on));
        }
    }

    public function tickAll(): void
    {
        if ($rec = $this->rec()) {
            foreach (Reconciler::candidates($rec)->whereNull('reconciliation_id')->get() as $l) {
                Reconciler::tick($rec, $l->id, true);
            }
        }
    }

    public function rematch(): void
    {
        if ($rec = $this->rec()) {
            $n = Reconciler::autoMatch($rec);
            $this->toast(trans_choice(':n more line matched.|:n more lines matched.', $n, ['n' => $n]), $n ? 'success' : 'warning');
        }
    }

    public function createEntry(string $statementLineId): void
    {
        $rec = $this->rec();
        $sl = StatementLine::where('reconciliation_id', $rec?->id)->findOrFail($statementLineId);
        $account = $this->create[$statementLineId] ?? '';
        if (! Account::where('agency_id', $this->agencyId())->whereKey($account)->exists()) {
            $this->addError("create.$statementLineId", __('Choose what it was for.'));

            return;
        }
        if ($this->attempt(fn () => Reconciler::createFromLine($rec, $sl, $account, null, Auth::id()), __('Posted and matched.'))) {
            unset($this->create[$statementLineId]);
        }
    }

    public function ignore(string $statementLineId, bool $on = true): void
    {
        StatementLine::where('reconciliation_id', $this->rec()?->id)->whereKey($statementLineId)
            ->update(['status' => $on ? 'ignored' : 'unmatched']);
    }

    public function updateBalance(string $value): void
    {
        if (($rec = $this->rec()) && is_numeric($value)) {
            $rec->update(['statement_balance' => round((float) $value, 2)]);
        }
    }

    public function complete(): void
    {
        if (($rec = $this->rec()) && $this->attemptOk(fn () => Reconciler::complete($rec, Auth::id()))) {
            session()->flash('success', __(':a reconciled to :d.', ['a' => $this->account()->name, 'd' => $rec->statement_date->format('d M Y')]));
            $this->redirectRoute('accounts.reconcile', $this->accountId, navigate: true);
        }
    }

    public function discard(): void
    {
        if (($rec = $this->rec()) && $this->attemptOk(fn () => Reconciler::discard($rec), __('Reconciliation deleted.'))) {
            $this->reset('create');
        }
    }

    public function reopen(string $recId): void
    {
        $rec = Reconciliation::where('account_id', $this->accountId)->findOrFail($recId);
        $this->attemptOk(fn () => Reconciler::reopen($rec, Auth::id()), __('Re-opened.'));
    }

    public function render()
    {
        $account = $this->account();
        $rec = $this->rec();
        $data = ['account' => $account, 'rec' => $rec, 'last' => Reconciler::lastCompleted($account),
            'history' => Reconciliation::where('account_id', $account->id)->where('status', 'completed')->with('completer:id,name')
                ->orderByDesc('statement_date')->limit(12)->get()];
        if ($rec) {
            $lines = Reconciler::candidates($rec, $this->showLater)->get();
            $data += [
                'lines' => $lines,
                'statement' => $rec->lines()->with('journalLine.journal:id,number')->get(),
                'cleared' => Reconciler::clearedBalance($rec),
                'difference' => Reconciler::difference($rec),
                'tickedIn' => (float) $lines->where('reconciliation_id', $rec->id)->sum('debit'),
                'tickedOut' => (float) $lines->where('reconciliation_id', $rec->id)->sum('credit'),
                'accountGroups' => $this->accountOptions(['income', 'expense', 'liability', 'asset', 'equity'], false)->groupBy('type'),
            ];
        }

        return $this->page('livewire.accounting.reconcile', $data, __('Reconcile :a', ['a' => $account->name]));
    }
}
