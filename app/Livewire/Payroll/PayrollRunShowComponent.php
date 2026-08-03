<?php

namespace App\Livewire\Payroll;

use App\Models\PayrollRun;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PayrollRunShowComponent extends Component
{
    public string $payrollRunId;

    public function mount(string $payrollRunId): void
    {
        $this->payrollRunId = $payrollRunId;
    }

    protected function run(): PayrollRun
    {
        return PayrollRun::with('payslips.user')->findOrFail($this->payrollRunId);
    }

    public function generatePayslips(): void
    {
        $this->run()->generateFromApprovedTimesheets();
        $this->dispatch('toast', message: 'Payslips generated from approved timesheets.', type: 'success');
    }

    public function approveRun(): void
    {
        abort_unless(Auth::user()->can('approve_payroll') ?? false, 403);

        $this->run()->approve(Auth::id());
        $this->dispatch('toast', message: 'Payroll run approved.', type: 'success');
    }

    public function markPaid(): void
    {
        abort_unless(Auth::user()->can('approve_payroll') ?? false, 403);

        $this->run()->markPaid();
        $this->dispatch('toast', message: 'Payroll run marked as paid.', type: 'success');
    }

    public function render()
    {
        return view('livewire.payroll.payroll-run-show', ['run' => $this->run()]);
    }
}
