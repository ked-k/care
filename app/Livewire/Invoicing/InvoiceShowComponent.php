<?php

namespace App\Livewire\Invoicing;

use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class InvoiceShowComponent extends Component
{
    public string $invoiceId;

    public string $adjustmentDescription = '';
    public ?float $adjustmentAmount = null;

    public string $paymentReference = '';

    public function mount(string $invoice): void
    {
        $this->invoiceId = $invoice;

        $inv = $this->invoice();
        $user = Auth::user();
        abort_unless($inv->serviceUser->agency_id === $user->agency_id && $user->canManageInvoicing(), 403);
    }

    public function canManage(): bool
    {
        return Auth::user()->canManageInvoicing();
    }

    protected function invoice(): Invoice
    {
        return Invoice::with(['lines', 'serviceUser.agency', 'invoiceRun.agency'])
            ->findOrFail($this->invoiceId);
    }

    public function addAdjustment(): void
    {
        abort_unless($this->canManage(), 403);

        $this->validate([
            'adjustmentDescription' => 'required|string|max:255',
            'adjustmentAmount' => 'required|numeric',
        ]);

        $this->invoice()->addAdjustment($this->adjustmentDescription, $this->adjustmentAmount);

        $this->reset(['adjustmentDescription', 'adjustmentAmount']);
        $this->dispatch('close-drawer', 'invoice-adjustment-form');
        $this->dispatch('toast', message: 'Adjustment added.', type: 'success');
    }

    public function removeLine(string $lineId): void
    {
        abort_unless($this->canManage(), 403);

        // Only an adjustment line can be removed — the auto-generated
        // visit/package lines are the billing basis itself.
        $invoice = $this->invoice();
        $invoice->lines()->whereKey($lineId)->where('line_type', 'adjustment')->delete();
        $invoice->recalculateTotal();
        $this->dispatch('toast', message: 'Line removed.', type: 'warning');
    }

    public function issue(): void
    {
        abort_unless($this->canManage(), 403);

        $this->invoice()->issue();
        $this->dispatch('toast', message: 'Invoice issued.', type: 'success');
    }

    public function markPaid(): void
    {
        abort_unless($this->canManage(), 403);

        $this->invoice()->markPaid($this->paymentReference ?: null);
        $this->reset(['paymentReference']);
        $this->dispatch('close-drawer', 'invoice-paid-form');
        $this->dispatch('toast', message: 'Invoice marked as paid.', type: 'success');
    }

    public function render()
    {
        return view('livewire.invoicing.invoice-show', ['invoice' => $this->invoice()]);
    }
}
