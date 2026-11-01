<?php

namespace App\Livewire\Accounting;

use App\Models\Accounting\Document;
use App\Models\Accounting\Payment;
use App\Services\Accounting\Payments;
use Illuminate\Support\Facades\Auth;

/** Batch 43: one receipt or supplier payment — what it paid, match leftovers, void, print. */
class PaymentShowComponent extends AccountsComponent
{
    public string $paymentId;
    public array $alloc = [];
    public string $voidReason = '';

    public function mount(string $paymentId): void
    {
        $this->paymentId = Payment::where('agency_id', $this->agencyId())->findOrFail($paymentId)->id;
    }

    private function payment(): Payment
    {
        return Payment::where('agency_id', $this->agencyId())->with('contact', 'bankAccount', 'journal', 'allocations.document', 'creator')->findOrFail($this->paymentId);
    }

    public function allocate(): void
    {
        $p = $this->payment();
        $this->validate(['alloc.*' => 'nullable|numeric|min:0']);
        $done = $this->attempt(function () use ($p) {
            foreach (array_filter($this->alloc, fn ($v) => (float) $v > 0) as $docId => $amt) {
                Payments::allocate($p, Document::where('agency_id', $this->agencyId())->findOrFail($docId), (float) $amt);
            }

            return true;
        }, __('Matched.'));
        if ($done) {
            $this->alloc = [];
        }
    }

    public function void(): void
    {
        $this->validate(['voidReason' => 'required|string|min:3|max:500'], ['voidReason.required' => __('Say why — it goes in the audit trail.')]);
        if ($this->attempt(fn () => Payments::void($this->payment(), $this->voidReason, Auth::id()), __('Voided. The invoices/bills it paid are open again.'))) {
            $this->js("window.dispatchEvent(new CustomEvent('close-modal',{detail:'void-pay'}))");
            $this->voidReason = '';
        }
    }

    public function render()
    {
        $p = $this->payment();
        $open = $p->isVoid() || $p->unallocated() <= 0 ? collect() : Document::where('agency_id', $this->agencyId())
            ->where('contact_id', $p->contact_id)->where('kind', $p->kind === 'receipt' ? 'invoice' : 'bill')
            ->whereIn('status', ['posted', 'partial'])->orderBy('due_date')->get();

        return $this->page('livewire.accounting.payment-show', ['p' => $p, 'open' => $open], $p->kindLabel().' '.$p->number);
    }
}
