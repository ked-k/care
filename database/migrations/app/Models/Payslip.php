<?php

namespace App\Models;

use App\Models\PayrollRun;
use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payslip extends Model
{
    use HasUuids;

    protected $fillable = [
        'payroll_run_id', 'user_id', 'timesheet_id',
        'regular_hours', 'overtime_hours', 'regular_rate', 'overtime_rate',
        'gross_pay', 'total_earnings_other', 'total_deductions', 'net_pay',
        'status', 'payment_method', 'payment_reference', 'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'regular_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'regular_rate' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'gross_pay' => 'decimal:2',
        'total_earnings_other' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
    ];

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function timesheet(): BelongsTo
    {
        return $this->belongsTo(Timesheet::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PayslipLine::class);
    }

    /**
     * Re-derive earnings/deductions totals and net pay from the line items,
     * on top of the base gross pay from hours worked.
     */
    public function recalculateNet(): void
    {
        $this->total_earnings_other = $this->lines()->where('line_type', 'earning')->sum('amount');
        $this->total_deductions = $this->lines()->where('line_type', 'deduction')->sum('amount');
        $this->net_pay = $this->gross_pay + $this->total_earnings_other - $this->total_deductions;
        $this->save();
    }

    public function addDeduction(string $category, float $amount, ?string $description = null): PayslipLine
    {
        $line = $this->lines()->create([
            'line_type' => 'deduction',
            'category' => $category,
            'description' => $description,
            'amount' => $amount,
        ]);
        $this->recalculateNet();

        return $line;
    }

    public function addEarning(string $category, float $amount, ?string $description = null): PayslipLine
    {
        $line = $this->lines()->create([
            'line_type' => 'earning',
            'category' => $category,
            'description' => $description,
            'amount' => $amount,
        ]);
        $this->recalculateNet();

        return $line;
    }
}
