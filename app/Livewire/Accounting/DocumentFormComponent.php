<?php

namespace App\Livewire\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\Contact;
use App\Models\Accounting\Document;
use App\Services\Accounting\Documents;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Batch 43: create or edit a draft invoice, bill, or money in / out, with
 * any number of lines. "Save and approve" posts it straight away.
 */
class DocumentFormComponent extends AccountsComponent
{
    public ?string $documentId = null;
    public string $kind = 'invoice';
    public array $form = [];
    public array $lines = [];
    public array $newContact = ['name' => '', 'email' => ''];

    public function mount(?string $kind = null, ?string $documentId = null): void
    {
        $s = $this->settings();
        if ($documentId) {
            $doc = Document::where('agency_id', $this->agencyId())->with('lines')->findOrFail($documentId);
            if (! $doc->isDraft()) {
                $this->redirectRoute('accounts.documents.show', $doc->id, navigate: true);

                return;
            }
            $this->documentId = $doc->id;
            $this->kind = $doc->kind;
            $this->form = [
                'contact_id' => (string) $doc->contact_id,
                'bank_account_id' => (string) $doc->bank_account_id,
                'issue_date' => $doc->issue_date->toDateString(),
                'due_date' => $doc->due_date?->toDateString() ?? '',
                'reference' => (string) $doc->reference,
                'notes' => (string) $doc->notes,
                'prices_include_vat' => (bool) $doc->prices_include_vat,
            ];
            $this->lines = $doc->lines->map(fn ($l) => [
                'account_id' => (string) $l->account_id, 'description' => (string) $l->description,
                'qty' => (float) $l->qty, 'unit_price' => (float) $l->unit_price,
                'discount' => (float) $l->discount, 'vat_rate' => (float) $l->vat_rate,
            ])->all();
        } else {
            $this->kind = in_array($kind, ['invoice', 'bill', 'money_in', 'money_out'], true) ? $kind : 'invoice';
            $this->form = [
                'contact_id' => (string) request('contact', ''),
                'bank_account_id' => in_array($this->kind, ['money_in', 'money_out'], true) ? $s->account('bank')->id : '',
                'issue_date' => today()->toDateString(),
                'due_date' => in_array($this->kind, ['invoice', 'bill'], true) ? today()->addDays($s->payment_terms_days)->toDateString() : '',
                'reference' => '',
                'notes' => '',
                'prices_include_vat' => false,
            ];
            $this->lines = [$this->blankLine()];
        }
    }

    private function blankLine(): array
    {
        $s = $this->settings();
        $account = $this->kind === 'invoice' ? $s->account('care_income')->id : '';

        return ['account_id' => $account, 'description' => '', 'qty' => 1, 'unit_price' => 0, 'discount' => 0,
            'vat_rate' => $s->vat_registered ? (float) $s->default_vat_rate : 0];
    }

    public function addLine(): void
    {
        $last = end($this->lines) ?: null;
        $line = $this->blankLine();
        if ($last && $last['account_id']) {
            $line['account_id'] = $last['account_id'];
            $line['vat_rate'] = $last['vat_rate'];
        }
        $this->lines[] = $line;
    }

    public function removeLine(int $i): void
    {
        unset($this->lines[$i]);
        $this->lines = array_values($this->lines) ?: [$this->blankLine()];
    }

    public function updatedFormContactId($id): void
    {
        // Contact's own payment terms.
        if (in_array($this->kind, ['invoice', 'bill'], true) && $id && ($c = Contact::where('agency_id', $this->agencyId())->find($id)) && $c->payment_terms_days !== null) {
            $this->form['due_date'] = Carbon::parse($this->form['issue_date'] ?: today())->addDays($c->payment_terms_days)->toDateString();
        }
    }

    public function addContact(): void
    {
        $this->validate(['newContact.name' => 'required|string|max:255', 'newContact.email' => 'nullable|email|max:255']);
        $c = Contact::create([
            'agency_id' => $this->agencyId(),
            'name' => trim($this->newContact['name']),
            'email' => $this->newContact['email'] ?: null,
            'is_customer' => in_array($this->kind, ['invoice', 'money_in'], true),
            'is_supplier' => in_array($this->kind, ['bill', 'money_out'], true),
        ]);
        $this->form['contact_id'] = $c->id;
        $this->newContact = ['name' => '', 'email' => ''];
        $this->js("window.dispatchEvent(new CustomEvent('close-modal',{detail:'new-contact'}))");
        $this->toast(__(':n added.', ['n' => $c->name]));
    }

    private function validated(): array
    {
        $money = in_array($this->kind, ['money_in', 'money_out'], true);
        $this->validate([
            'form.contact_id' => [$money ? 'nullable' : 'required', 'string'],
            'form.bank_account_id' => [$money ? 'required' : 'nullable', 'string'],
            'form.issue_date' => 'required|date',
            'form.due_date' => 'nullable|date|after_or_equal:form.issue_date',
            'form.reference' => 'nullable|string|max:255',
            'form.notes' => 'nullable|string|max:5000',
            'lines' => 'required|array|min:1',
            'lines.*.account_id' => 'nullable|string',
            'lines.*.description' => 'nullable|string|max:5000',
            'lines.*.qty' => 'required|numeric',
            'lines.*.unit_price' => 'required|numeric',
            'lines.*.discount' => 'nullable|numeric|min:0',
            'lines.*.vat_rate' => 'nullable|numeric|min:0|max:100',
        ], [
            'form.contact_id.required' => $this->kind === 'bill' ? __('Choose the supplier.') : __('Choose the customer.'),
            'form.bank_account_id.required' => __('Choose the bank or cash account.'),
        ]);
        $valid = Account::where('agency_id', $this->agencyId())->pluck('id')->all();
        foreach ($this->lines as $i => $l) {
            if ($l['account_id'] && ! in_array($l['account_id'], $valid, true)) {
                $this->addError("lines.$i.account_id", __('Unknown account.'));
            }
        }

        return [
            'contact_id' => $this->form['contact_id'] ?: null,
            'bank_account_id' => $money ? ($this->form['bank_account_id'] ?: null) : null,
            'issue_date' => $this->form['issue_date'],
            'due_date' => $money ? null : ($this->form['due_date'] ?: null),
            'reference' => trim((string) $this->form['reference']) ?: null,
            'notes' => trim((string) $this->form['notes']) ?: null,
            'prices_include_vat' => (bool) $this->form['prices_include_vat'],
        ];
    }

    public function save(bool $post = false): void
    {
        $header = $this->validated();
        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }
        $doc = $this->documentId ? Document::where('agency_id', $this->agencyId())->findOrFail($this->documentId) : null;
        $saved = $this->attempt(fn () => Documents::saveDraft($doc, $this->agencyId(), $this->kind, $header, $this->lines, Auth::id()));
        if (! $saved) {
            return;
        }
        $this->documentId = $saved->id;
        if ($post) {
            if (! $this->attempt(fn () => Documents::post($saved, Auth::id()))) {
                return; // stays a draft; the toast says why
            }
            session()->flash('success', __(':k :n approved and posted.', ['k' => $saved->kindLabel(), 'n' => $saved->number]));
        } else {
            session()->flash('success', __('Draft :n saved.', ['n' => $saved->number]));
        }
        $this->redirectRoute('accounts.documents.show', $saved->id, navigate: true);
    }

    public function render()
    {
        $money = in_array($this->kind, ['money_in', 'money_out'], true);
        $accounts = $this->accountOptions(null, false)->groupBy('type');
        $order = in_array($this->kind, ['invoice', 'money_in'], true)
            ? ['income', 'liability', 'asset', 'equity', 'expense']
            : ['expense', 'asset', 'liability', 'equity', 'income'];

        return $this->page('livewire.accounting.document-form', [
            'label' => Document::KINDS[$this->kind][0],
            'money' => $money,
            'number' => $this->documentId ? Document::find($this->documentId)?->number : $this->settings()->nextNumberPreview($this->kind),
            'contacts' => Contact::where('agency_id', $this->agencyId())->where('is_active', true)
                ->when(! $money, fn ($q) => $q->where($this->kind === 'invoice' ? 'is_customer' : 'is_supplier', true))
                ->orderBy('name')->get(['id', 'name']),
            'accountGroups' => collect($order)->mapWithKeys(fn ($t) => [$t => $accounts[$t] ?? collect()])->filter->isNotEmpty(),
            'banks' => $this->moneyAccounts(),
        ], __($this->documentId ? 'Edit :k' : 'New :k', ['k' => mb_strtolower(__(Document::KINDS[$this->kind][0]))]));
    }
}
