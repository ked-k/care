<?php

namespace App\Models;

use App\Models\Agency;
use App\Models\RotaPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Timesheet extends Model
{
    use HasUuids;

    protected $fillable = [
        'agency_id', 'user_id', 'rota_period_id', 'manager_id', 'week_commencing', 'status',
        'total_regular_hours', 'total_overtime_hours', 'comments',
        'employee_signed_at', 'supervisor_signed_at', 'submitted_at',
        'approved_at', 'approved_by', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'week_commencing' => 'date',
        'employee_signed_at' => 'datetime',
        'supervisor_signed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'total_regular_hours' => 'decimal:2',
        'total_overtime_hours' => 'decimal:2',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rotaPeriod(): BelongsTo
    {
        return $this->belongsTo(RotaPeriod::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(TimesheetEntry::class);
    }

    public function payslip(): HasOne
    {
        return $this->hasOne(Payslip::class);
    }

    public function payProfile(): ?EmployeePayProfile
    {
        return EmployeePayProfile::where('user_id', $this->user_id)->first();
    }

    /**
     * Recompute each entry's total_hours, then split the week's total
     * into regular vs overtime hours against the carer's pay-profile threshold.
     */
    public function recalculateTotals(): void
    {
        $this->entries->each->recalculateHours();

        $weekTotal = $this->entries()->sum('total_hours');
        $threshold = (float) ($this->payProfile()?->weekly_overtime_threshold_hours ?? 40);

        $this->total_regular_hours = min($weekTotal, $threshold);
        $this->total_overtime_hours = max($weekTotal - $threshold, 0);
        $this->save();
    }

    public function submit(): void
    {
        $this->update(['status' => 'submitted', 'submitted_at' => now()]);
    }

    public function approve(int $approverUserId): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approverUserId,
            'approved_at' => now(),
        ]);
    }
}
