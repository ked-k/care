<?php

namespace App\Livewire\Invoicing;

use App\Models\InvoiceRun;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class InvoiceRunShowComponent extends Component
{
    public string $invoiceRunId;

    public function mount(string $invoiceRunId): void
    {
        abort_unless(Auth::user()->canManageInvoicing(), 403);

        $this->invoiceRunId = $invoiceRunId;
        $this->run();
    }

    protected function run(): InvoiceRun
    {
        return InvoiceRun::with('invoices.serviceUser')
            ->where('agency_id', Auth::user()->agency_id)
            ->findOrFail($this->invoiceRunId);
    }

    public function generateInvoices(): void
    {
        abort_unless(Auth::user()->canManageInvoicing(), 403);

        $result = $this->run()->generateInvoices();

        if ($result['invoices'] > 0) {
            $this->dispatch('toast', message: trans_choice(
                'Generated :count invoice from completed visits and care packages.|Generated :count invoices from completed visits and care packages.',
                $result['invoices'],
                ['count' => $result['invoices']]
            ), type: 'success');

            return;
        }

        // Nothing was created — tell the user why rather than a blanket
        // "generated" toast that reads as broken when nothing shows up.
        $message = $result['service_users_skipped_no_billing'] > 0
            ? __(':count service user(s) in this agency have no billing rate or care package amount set up yet, so nothing could be invoiced. Add billing details on their profile, then generate again.', ['count' => $result['service_users_skipped_no_billing']])
            : __('No new invoices — everyone with billing configured already has one for this period, or had no billable visits in it.');

        $this->dispatch('toast', message: $message, type: 'warning');
    }

    public function issueRun(): void
    {
        abort_unless(Auth::user()->canManageInvoicing(), 403);

        $this->run()->issue();
        $this->dispatch('toast', message: 'Invoices issued.', type: 'success');
    }

    public function markAllPaid(): void
    {
        abort_unless(Auth::user()->canManageInvoicing(), 403);

        $this->run()->markAllPaid();
        $this->dispatch('toast', message: 'Invoices marked as paid.', type: 'success');
    }

    public function render()
    {
        return view('livewire.invoicing.invoice-run-show', ['run' => $this->run()]);
    }
}
