<?php

namespace App\Livewire\Accounting;

use App\Models\Accounting\Contact;
use App\Models\Accounting\Document;
use App\Models\Accounting\Payment;
use App\Services\Accounting\Payments;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/**
 * Batch 43: customer receipts or supplier payments. Recording one lets you
 * spread the money across that contact's open invoices/bills (oldest first
 * by default); anything left over stays on their account as credit.
 */
class PaymentIndexComponent extends AccountsComponent
{
    use WithPagination;

    public string $kind = 'receipt';

    #[Url]
    public string $search = '';

    public array $form = [];
    public array $alloc = [];

    public function mount(): void
    {
        $this->kind = Route::currentRouteName() === 'accounts.payments' ? 'payment' : 'receipt';
        $this->blank();
        if (request('new')) {
            $this->form['contact_id'] = (string) request('contact', '');
            $this->updatedFormContactId($this->form['contact_id']);
            $this->dispatch('open-drawer', 'payment-form');
        }
    }

    private function blank(): void
    {
        $this->form = [
            'contact_id' => '', 'bank_account_id' => $this->settings()->account('bank')->id, 'date' => today()->toDateString(),
            'amount' => '', 'method' => 'bank_transfer', 'reference' => '', 'notes' => '',
        ];
        $this->alloc = [];
        $this->resetErrorBag();
    }

    public function create(): void
    {
        $this->blank();
        $this->dispatch('open-drawer', 'payment-form');
    }

    public function updatedFormContactId($id): void
    {
        $this->alloc = [];
        foreach ($this->openDocs($id) as $d) {
            $this->alloc[$d->id] = '';
        }
    }

    /** Spread the amount over the open documents, oldest first. */
    public function spread(): void
    {
        $left = round((float) $this->form['amount'], 2);
        foreach ($this->openDocs($this->form['contact_id']) as $d) {
            $take = round(min($left, $d->balance()), 2);
            $this->alloc[$d->id] = $take > 0 ? $take : '';
            $left = round($left - max(0, $take), 2);
        }
    }

    private function openDocs(?string $contactId)
    {
        if (! $contactId) {
            return collect();
        }

        return Document::where('agency_id', $this->agencyId())->where('contact_id', $contactId)
            ->where('kind', $this->kind === 'receipt' ? 'invoice' : 'bill')->whereIn('status', ['posted', 'partial'])
            ->orderBy('due_date')->orderBy('issue_date')->get();
    }

    public function save(): void
    {
        $this->validate([
            'form.contact_id' => 'required|string',
            'form.bank_account_id' => 'required|string',
            'form.date' => 'required|date',
            'form.amount' => 'required|numeric|min:0.01',
            'form.method' => 'required|string',
            'form.reference' => 'nullable|string|max:255',
            'form.notes' => 'nullable|string|max:2000',
            'alloc.*' => 'nullable|numeric|min:0',
        ], ['form.contact_id.required' => $this->kind === 'receipt' ? __('Choose who paid you.') : __('Choose who you paid.')]);

        $payment = $this->attempt(fn () => Payments::record($this->agencyId(), [
            'kind' => $this->kind,
            'contact_id' => $this->form['contact_id'],
            'bank_account_id' => $this->form['bank_account_id'],
            'date' => $this->form['date'],
            'amount' => (float) $this->form['amount'],
            'method' => $this->form['method'],
            'reference' => $this->form['reference'] ?: null,
            'notes' => $this->form['notes'] ?: null,
        ], array_map('floatval', array_filter($this->alloc, fn ($v) => $v !== '' && $v !== null)), Auth::id()));

        if ($payment) {
            $this->dispatch('close-drawer', 'payment-form');
            $left = $payment->unallocated();
            $this->toast($left > 0
                ? __(':n saved. :a is left on the account as credit.', ['n' => $payment->number, 'a' => $this->settings()->money($left)])
                : __(':n saved.', ['n' => $payment->number]));
            $this->blank();
        }
    }

    public function render()
    {
        $title = $this->kind === 'receipt' ? __('Customer receipts') : __('Supplier payments');

        return $this->page('livewire.accounting.payments', [
            'title' => $title,
            'payments' => Payment::where('agency_id', $this->agencyId())->where('kind', $this->kind)
                ->when(trim($this->search) !== '', function ($q) {
                    $t = '%'.trim($this->search).'%';
                    $q->where(fn ($w) => $w->where('number', 'like', $t)->orWhere('reference', 'like', $t)
                        ->orWhereHas('contact', fn ($c) => $c->where('name', 'like', $t)));
                })
                ->with('contact:id,name', 'bankAccount:id,name')->withSum('allocations', 'amount')
                ->orderByDesc('date')->orderByDesc('number')->paginate(25),
            'contacts' => Contact::where('agency_id', $this->agencyId())->where('is_active', true)
                ->where($this->kind === 'receipt' ? 'is_customer' : 'is_supplier', true)->orderBy('name')->get(['id', 'name']),
            'banks' => $this->moneyAccounts(),
            'openDocs' => $this->openDocs($this->form['contact_id'] ?? null),
        ], $title);
    }
}
