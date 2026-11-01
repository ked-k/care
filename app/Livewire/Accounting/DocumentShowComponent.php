<?php

namespace App\Livewire\Accounting;

use App\Models\Accounting\Document;
use App\Models\Accounting\Payment;
use App\Services\Accounting\Documents;
use App\Services\Accounting\Payments;
use Illuminate\Support\Facades\Auth;

/** Batch 43: one invoice / bill / money in-out — approve, take payment, void, print. */
class DocumentShowComponent extends AccountsComponent
{
    public string $documentId;
    public array $pay = [];
    public string $voidReason = '';

    public function mount(string $documentId): void
    {
        $this->documentId = Document::where('agency_id', $this->agencyId())->findOrFail($documentId)->id;
        $this->resetPay();
    }

    private function doc(): Document
    {
        return Document::where('agency_id', $this->agencyId())->with('lines.account', 'contact', 'bankAccount', 'journal', 'creator')->findOrFail($this->documentId);
    }

    private function resetPay(): void
    {
        $doc = $this->doc();
        $this->pay = [
            'bank_account_id' => $this->settings()->account('bank')->id,
            'date' => today()->toDateString(),
            'amount' => $doc->isOpen() ? $doc->balance() : 0,
            'method' => 'bank_transfer',
            'reference' => '',
        ];
    }

    public function approve(): void
    {
        if ($this->attempt(fn () => Documents::post($this->doc(), Auth::id()), __('Approved and posted.'))) {
            $this->resetPay();
        }
    }

    public function deleteDraft(): void
    {
        $doc = $this->doc();
        if (! $doc->isDraft()) {
            return;
        }
        $kind = $doc->kind;
        $doc->delete();
        session()->flash('success', __('Draft deleted.'));
        $this->redirectRoute(match ($kind) { 'bill' => 'accounts.bills', 'invoice' => 'accounts.invoices', default => 'accounts.money' }, navigate: true);
    }

    public function recordPayment(): void
    {
        $doc = $this->doc();
        $this->validate([
            'pay.bank_account_id' => 'required|string',
            'pay.date' => 'required|date',
            'pay.amount' => 'required|numeric|min:0.01|max:'.max(0.01, $doc->balance()),
            'pay.method' => 'required|string',
            'pay.reference' => 'nullable|string|max:255',
        ], ['pay.amount.max' => __('That’s more than is left to pay (:b).', ['b' => $this->settings()->money($doc->balance())])]);

        $ok = $this->attempt(fn () => Payments::record($this->agencyId(), [
            'kind' => $doc->kind === 'invoice' ? 'receipt' : 'payment',
            'contact_id' => $doc->contact_id,
            'bank_account_id' => $this->pay['bank_account_id'],
            'date' => $this->pay['date'],
            'amount' => (float) $this->pay['amount'],
            'method' => $this->pay['method'],
            'reference' => $this->pay['reference'] ?: null,
        ], [$doc->id => (float) $this->pay['amount']], Auth::id()), $doc->kind === 'invoice' ? __('Payment received.') : __('Payment recorded.'));
        if ($ok) {
            $this->dispatch('close-drawer', 'pay-form');
            $this->resetPay();
        }
    }

    public function applyCredit(): void
    {
        $doc = $this->doc();
        $applied = $this->attempt(fn () => Payments::applyCredit($doc, $doc->balance()));
        if ($applied) {
            $this->toast(__(':a of credit used.', ['a' => $this->settings()->money($applied)]));
            $this->resetPay();
        }
    }

    public function void(): void
    {
        $this->validate(['voidReason' => 'required|string|min:3|max:500'], ['voidReason.required' => __('Say why — it goes in the audit trail.')]);
        $released = $this->attempt(fn () => Documents::void($this->doc(), $this->voidReason, Auth::id()));
        if ($released !== null) {
            $this->js("window.dispatchEvent(new CustomEvent('close-modal',{detail:'void-doc'}))");
            $this->toast($released ? __('Voided. The :n payment(s) matched to it are now credit on the account.', ['n' => $released]) : __('Voided.'));
            $this->voidReason = '';
        }
    }

    public function render()
    {
        $doc = $this->doc();
        $credit = $doc->isOpen() && $doc->contact_id
            ? Payments::unallocatedFor($doc->contact_id, $doc->kind === 'invoice' ? 'receipt' : 'payment') : 0;

        return $this->page('livewire.accounting.document-show', [
            'doc' => $doc,
            'allocations' => $doc->allocations()->with('payment.bankAccount')->get(),
            'credit' => $credit,
            'banks' => $this->moneyAccounts(),
            'source' => $this->sourceLink($doc),
        ], $doc->kindLabel().' '.$doc->number);
    }

    private function sourceLink(Document $doc): ?array
    {
        return match ($doc->source_type) {
            'care_invoice' => \Illuminate\Support\Facades\Route::has('invoices.show') ? [__('Open the care invoice'), route('invoices.show', $doc->source_id)] : null,
            'shop_grn' => \Illuminate\Support\Facades\Route::has('shop.admin.receiving.show') ? [__('Open the shop delivery'), route('shop.admin.receiving.show', $doc->source_id)] : null,
            default => null,
        };
    }
}
