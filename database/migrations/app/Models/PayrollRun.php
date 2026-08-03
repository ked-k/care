<?php

namespace App\Models;

use App\Models\Agency;
use App\Models\EmployeePayProfile;
use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    use HasUuids;

    protected $fillable = [
        'agency_id', 'reference', 'pay_period_start', 'pay_period_end', 'frequency', 'status',
        'total_gross', 'total_deductions', 'total_net',
        'processed_by', 'approved_by', 'approved_at', 'paid_at',
    ];

    protected $casts = [
        'pay_period_start' => 'date',
        'pay_period_end' => 'date',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'total_gross' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'total_net' => 'decimal:2',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    /**
     * Build one payslip per carer from every approved, not-yet-paid timesheet
     * in this agency whose week falls inside this run's pay period.
     */
    public function generateFromApprovedTimesheets(): void
    {
        $timesheets = Timesheet::query()
            ->where('agency_id', $this->agency_id)
            ->where('status', 'approved')
            ->whereBetween('week_commencing', [$this->pay_period_start, $this->pay_period_end])
            ->whereDoesntHave('payslip')
            ->get();

        foreach ($timesheets as $timesheet) {
            $payProfile = EmployeePayProfile::where('user_id', $timesheet->user_id)->first();
            if (! $payProfile) {
                continue; // no pay profile set up yet — skip rather than guess a rate
            }

            $regularHours = (float) $timesheet->total_regular_hours;
            $overtimeHours = (float) $timesheet->total_overtime_hours;
            $regularRate = (float) $payProfile->hourly_rate;
            $overtimeRate = $payProfile->overtimeRate();

            $gross = ($regularHours * $regularRate) + ($overtimeHours * $overtimeRate);

            $payslip = Payslip::create([
                'payroll_run_id' => $this->id,
                'user_id' => $timesheet->user_id,
                'timesheet_id' => $timesheet->id,
                'regular_hours' => $regularHours,
                'overtime_hours' => $overtimeHours,
                'regular_rate' => $regularRate,
                'overtime_rate' => $overtimeRate,
                'gross_pay' => $gross,
                'status' => 'draft',
            ]);

            $payslip->recalculateNet();
            $timesheet->update(['status' => 'paid']);
        }

        $this->recalculateTotals();
    }

    public function recalculateTotals(): void
    {
        $this->total_gross = $this->payslips()->sum('gross_pay');
        $this->total_deductions = $this->payslips()->sum('total_deductions');
        $this->total_net = $this->payslips()->sum('net_pay');
        $this->save();
    }

    public function approve(int $approverUserId): void
    {
        $this->payslips()->update(['status' => 'approved']);
        $this->update([
            'status' => 'approved',
            'approved_by' => $approverUserId,
            'approved_at' => now(),
        ]);
    }

    public function markPaid(): void
    {
        $this->payslips()->update(['status' => 'paid', 'paid_at' => now()]);
        $this->update(['status' => 'paid', 'paid_at' => now()]);
    }
}
