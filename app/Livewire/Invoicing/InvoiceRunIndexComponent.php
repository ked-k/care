<?php

namespace App\Livewire\Invoicing;

use App\Models\InvoiceRun;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class InvoiceRunIndexComponent extends Component
{
    use WithPagination;

    public string $newPeriodStart = '';
    public string $newPeriodEnd = '';
    public string $newFrequency = 'weekly';

    public function mount(): void
    {
        abort_unless(Auth::user()->canManageInvoicing(), 403);

        // Default a new run to "this week" (Mon-Sun) so the form isn't empty on open.
        $this->newPeriodStart = now()->startOfWeek()->toDateString();
        $this->newPeriodEnd = now()->endOfWeek()->toDateString();
    }

    public function createRun(): void
    {
        abort_unless(Auth::user()->canManageInvoicing(), 403);

        $this->validate([
            'newPeriodStart' => 'required|date',
            'newPeriodEnd' => 'required|date|after_or_equal:newPeriodStart',
            'newFrequency' => 'required|in:weekly,monthly',
        ]);

        $agencyId = Auth::user()->agency_id;
        $weekNo = Carbon::parse($this->newPeriodStart)->isoWeek();
        $reference = 'IR-'.Carbon::parse($this->newPeriodStart)->year."-W{$weekNo}";

        // Guard against a duplicate reference for the same agency (e.g. two runs started same week).
        $suffix = 1;
        $baseReference = $reference;
        while (InvoiceRun::where('agency_id', $agencyId)->where('reference', $reference)->exists()) {
            $reference = $baseReference.'-'.(++$suffix);
        }

        $run = InvoiceRun::create([
            'agency_id' => $agencyId,
            'reference' => $reference,
            'billing_period_start' => $this->newPeriodStart,
            'billing_period_end' => $this->newPeriodEnd,
            'frequency' => $this->newFrequency,
            'status' => 'draft',
            'processed_by' => Auth::id(),
        ]);

        $this->redirect(route('invoicing.show', $run), navigate: true);
    }

    public function render()
    {
        $runs = InvoiceRun::where('agency_id', Auth::user()->agency_id)
            ->withCount('invoices')
            ->orderByDesc('billing_period_start')
            ->paginate(10);

        return view('livewire.invoicing.invoice-run-index', compact('runs'));
    }
}
