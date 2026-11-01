<?php

namespace App\Livewire\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\Contact;
use App\Services\Accounting\Ledger;
use Illuminate\Support\Facades\Auth;

/**
 * Batch 43: a hand-written journal — opening balances, accruals,
 * corrections, depreciation until Batch 44 automates it.
 */
class JournalFormComponent extends AccountsComponent
{
    public array $form = [];
    public array $lines = [];

    public function mount(): void
    {
        $this->form = ['date' => today()->toDateString(), 'type' => request('opening') ? 'opening' : 'manual', 'reference' => '', 'memo' => ''];
        $this->lines = [$this->blank(), $this->blank()];
    }

    private function blank(): array
    {
        return ['account_id' => '', 'contact_id' => '', 'description' => '', 'debit' => '', 'credit' => ''];
    }

    public function addLine(): void
    {
        $this->lines[] = $this->blank();
    }

    public function removeLine(int $i): void
    {
        unset($this->lines[$i]);
        $this->lines = array_values($this->lines);
        while (count($this->lines) < 2) {
            $this->lines[] = $this->blank();
        }
    }

    public function save(): void
    {
        $this->validate([
            'form.date' => 'required|date',
            'form.type' => 'required|in:manual,opening',
            'form.reference' => 'nullable|string|max:255',
            'form.memo' => 'required|string|max:1000',
            'lines.*.account_id' => 'nullable|string',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
        ], ['form.memo.required' => __('Say what this entry is for — it’s what auditors read first.')]);

        $accounts = Account::where('agency_id', $this->agencyId())->pluck('id')->all();
        $controls = [$this->settings()->account('receivables')->id, $this->settings()->account('payables')->id];
        $lines = [];
        foreach ($this->lines as $i => $l) {
            if ((float) $l['debit'] == 0 && (float) $l['credit'] == 0) {
                continue;
            }
            if (! in_array($l['account_id'], $accounts, true)) {
                $this->addError("lines.$i.account_id", __('Choose an account.'));

                continue;
            }
            if (in_array($l['account_id'], $controls, true) && ! $l['contact_id']) {
                $this->addError("lines.$i.contact_id", __('Say which customer / supplier this is for.'));

                continue;
            }
            $lines[] = ['account' => $l['account_id'], 'contact_id' => $l['contact_id'] ?: null, 'description' => $l['description'] ?: null,
                'debit' => (float) $l['debit'], 'credit' => (float) $l['credit']];
        }
        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }
        $j = $this->attempt(fn () => Ledger::post($this->agencyId(), [
            'date' => $this->form['date'], 'type' => $this->form['type'],
            'reference' => $this->form['reference'] ?: null, 'memo' => $this->form['memo'], 'created_by' => Auth::id(),
        ], $lines));
        if ($j) {
            activity('accounts')->performedOn($j)->causedBy(Auth::user())->log(__('Journal :n posted', ['n' => $j->number]));
            session()->flash('success', __('Journal :n posted.', ['n' => $j->number]));
            $this->redirectRoute('accounts.journals.show', $j->id, navigate: true);
        }
    }

    public function render()
    {
        return $this->page('livewire.accounting.journal-form', [
            'accountGroups' => $this->accountOptions()->groupBy('type'),
            'contacts' => Contact::where('agency_id', $this->agencyId())->orderBy('name')->get(['id', 'name']),
            'controls' => [$this->settings()->account('receivables')->id, $this->settings()->account('payables')->id],
        ], __('Journal entry'));
    }
}
