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
        'total_regular_hours', 'total_overtime_hours',
        'total_live_in_days', 'total_live_in_pay', 'comments',
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
        'total_live_in_pay' => 'decimal:2',
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
     * into regular vs overtime hours against the carer's pay-profile
     * threshold. Batch 18: live-in days are summed separately
     * (total_live_in_days/total_live_in_pay) rather than folded into the
     * hourly total — a live-in day is a flat rate, not hours worked, so
     * it never counts toward the overtime threshold. A live-in entry's
     * total_hours is naturally 0 already (recalculateHours() only sums
     * day/night start/end columns, which a pure live-in day leaves null),
     * so weekTotal below is unaffected either way.
     */
    public function recalculateTotals(): void
    {
        $this->entries->each->recalculateHours();

        $weekTotal = $this->entries()->sum('total_hours');
        $threshold = (float) ($this->payProfile()?->weekly_overtime_threshold_hours ?? 40);

        $this->total_regular_hours = min($weekTotal, $threshold);
        $this->total_overtime_hours = max($weekTotal - $threshold, 0);

        $liveInEntries = $this->entries()->whereNotNull('live_in_shift_id');
        $this->total_live_in_days = $liveInEntries->count();
        $this->total_live_in_pay = $liveInEntries->sum('live_in_rate');

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
