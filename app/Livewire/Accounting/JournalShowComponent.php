<?php

namespace App\Livewire\Accounting;

use App\Models\Accounting\Journal;
use App\Services\Accounting\Ledger;
use Illuminate\Support\Facades\Auth;

/** Batch 43: one journal and its lines; manual ones can be reversed here. */
class JournalShowComponent extends AccountsComponent
{
    public string $journalId;
    public string $reason = '';

    public function mount(string $journalId): void
    {
        $this->journalId = Journal::where('agency_id', $this->agencyId())->findOrFail($journalId)->id;
    }

    public function reverse(): void
    {
        $j = Journal::where('agency_id', $this->agencyId())->findOrFail($this->journalId);
        if (! in_array($j->type, ['manual', 'opening', 'transfer'], true) || $j->isReversed()) {
            $this->toast(__('Entries made by invoices, payments, payroll or the shop are undone from there (void the invoice or payment).'), 'warning');

            return;
        }
        $this->validate(['reason' => 'required|string|min:3|max:500']);
        $r = $this->attempt(fn () => Ledger::reverse($j, $this->reason));
        if ($r) {
            activity('accounts')->performedOn($j)->causedBy(Auth::user())->withProperties(['reason' => $this->reason])->log(__('Journal :n reversed', ['n' => $j->number]));
            session()->flash('success', __('Reversed by :n.', ['n' => $r->number]));
            $this->redirectRoute('accounts.journals.show', $r->id, navigate: true);
        }
    }

    public function render()
    {
        $j = Journal::where('agency_id', $this->agencyId())->with('lines.account', 'lines.contact', 'creator', 'reverses', 'reversedBy', 'financialYear')->findOrFail($this->journalId);

        return $this->page('livewire.accounting.journal-show', ['j' => $j], $j->number);
    }
}
