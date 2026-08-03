<?php

namespace App\Livewire\Payroll;

use App\Models\Payslip;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PayslipShowComponent extends Component
{
    public string $payslipId;

    public string $lineType = 'deduction';
    public string $category = '';
    public string $description = '';
    public ?float $amount = null;

    protected array $commonCategories = [
        'earning' => ['bonus', 'allowance', 'travel_reimbursement', 'other'],
        'deduction' => ['paye', 'nssf', 'loan_repayment', 'other'],
    ];

    public function mount(string $payslipId): void
    {
        $this->payslipId = $payslipId;
    }

    protected function payslip(): Payslip
    {
        return Payslip::with(['lines', 'user', 'payrollRun'])->findOrFail($this->payslipId);
    }

    public function categoryOptions(): array
    {
        return $this->commonCategories[$this->lineType] ?? [];
    }

    public function addLine(): void
    {
        $this->validate([
            'lineType' => 'required|in:earning,deduction',
            'category' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $payslip = $this->payslip();

        if ($this->lineType === 'earning') {
            $payslip->addEarning($this->category, $this->amount, $this->description ?: null);
        } else {
            $payslip->addDeduction($this->category, $this->amount, $this->description ?: null);
        }

        $this->reset(['category', 'description', 'amount']);
        $this->dispatch('close-drawer', 'payslip-line-form');
        $this->dispatch('toast', message: 'Line item added.', type: 'success');
    }

    public function removeLine(string $lineId): void
    {
        $payslip = $this->payslip();
        $payslip->lines()->whereKey($lineId)->delete();
        $payslip->recalculateNet();
        $this->dispatch('toast', message: 'Line item removed.', type: 'warning');
    }

    public function approve(): void
    {
        abort_unless(Auth::user()->can('approve_payroll') ?? false, 403);

        $this->payslip()->update(['status' => 'approved']);
        $this->dispatch('toast', message: 'Payslip approved.', type: 'success');
    }

    public function render()
    {
        return view('livewire.payroll.payslip-show', ['payslip' => $this->payslip()]);
    }
}
