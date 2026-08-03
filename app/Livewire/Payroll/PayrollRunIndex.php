<?php

namespace App\Livewire\Payroll;

use App\Models\PayrollRun;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class PayrollRunIndex extends Component
{
    use WithPagination;

    public string $newPeriodStart = '';
    public string $newPeriodEnd = '';
    public string $newFrequency = 'weekly';

    public function mount(): void
    {
        // Default a new run to "this week" (Mon-Sun) so the form isn't empty on open.
        $this->newPeriodStart = now()->startOfWeek()->toDateString();
        $this->newPeriodEnd = now()->endOfWeek()->toDateString();
    }

    public function createRun(): void
    {
        $this->validate([
            'newPeriodStart' => 'required|date',
            'newPeriodEnd' => 'required|date|after_or_equal:newPeriodStart',
            'newFrequency' => 'required|in:weekly,biweekly,monthly',
        ]);

        $agencyId = Auth::user()->agency_id;
        $weekNo = Carbon::parse($this->newPeriodStart)->isoWeek();
        $reference = 'PR-'.Carbon::parse($this->newPeriodStart)->year."-W{$weekNo}";

        // Guard against a duplicate reference for the same agency (e.g. two runs started same week).
        $suffix = 1;
        $baseReference = $reference;
        while (PayrollRun::where('agency_id', $agencyId)->where('reference', $reference)->exists()) {
            $reference = $baseReference.'-'.(++$suffix);
        }

        $run = PayrollRun::create([
            'agency_id' => $agencyId,
            'reference' => $reference,
            'pay_period_start' => $this->newPeriodStart,
            'pay_period_end' => $this->newPeriodEnd,
            'frequency' => $this->newFrequency,
            'status' => 'draft',
            'processed_by' => Auth::id(),
        ]);

        $this->redirect(route('payroll.show', $run), navigate: true);
    }

    public function render()
    {
        $runs = PayrollRun::where('agency_id', Auth::user()->agency_id)
            ->withCount('payslips')
            ->orderByDesc('pay_period_start')
            ->paginate(10);

        return view('livewire.payroll.payroll-run-index', compact('runs'));
    }
}
