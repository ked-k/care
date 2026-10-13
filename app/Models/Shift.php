<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasUuids, SoftDeletes;

    public const TYPE_DAY = 'day';
    public const TYPE_NIGHT = 'night';
    public const TYPE_LIVE_IN = 'live_in';

    protected $fillable = [
        'agency_id', 'rota_period_id', 'service_user_id', 'assigned_to',
        'scheduled_start', 'scheduled_end', 'actual_start', 'actual_end',
        'shift_type', 'linked_shift_id', 'break_minutes', 'status', 'notes',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function rotaPeriod(): BelongsTo
    {
        return $this->belongsTo(RotaPeriod::class);
    }

    public function serviceUser(): BelongsTo
    {
        return $this->belongsTo(ServiceUser::class);
    }

    public function carer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function visitCheckins(): HasMany
    {
        return $this->hasMany(VisitCheckin::class);
    }

    public function takeovers(): HasMany
    {
        return $this->hasMany(ShiftTakeover::class);
    }

    /**
     * The in-progress takeover for this shift, if a manager/admin is
     * currently covering it (App\Models\ShiftTakeover::end() clears this by
     * setting ended_at). A shift can have several takeovers over its
     * lifetime (one admin stops, another picks it up later) but at most one
     * active at a time in normal use.
     */
    public function activeTakeover(): ?ShiftTakeover
    {
        return $this->takeovers()->whereNull('ended_at')->latest('started_at')->first();
    }

    public function openCheckin(): ?VisitCheckin
    {
        return $this->visitCheckins()->whereNull('checkout_time')->latest('checkin_time')->first();
    }

    /**
     * Batch 18: a double-handed visit — the other carer's Shift row for
     * the same service user and time window. Self-referencing and set on
     * both rows when the second carer is added (see linkWith()), so
     * either row finds the other. No payroll impact: each carer's own
     * row still feeds their own timesheet exactly as before.
     */
    public function linkedShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'linked_shift_id');
    }

    public function isDoubleHanded(): bool
    {
        return $this->linked_shift_id !== null;
    }

    public function isLiveIn(): bool
    {
        return $this->shift_type === self::TYPE_LIVE_IN;
    }

    /** Links two Shift rows together as one double-handed visit, both ways. */
    public function linkWith(Shift $other): void
    {
        $this->update(['linked_shift_id' => $other->id]);
        $other->update(['linked_shift_id' => $this->id]);
    }

    /**
     * Batch 21: hours actually delivered on this visit, for invoicing
     * (InvoiceRun::generateInvoices() per-visit billing). Prefers actual
     * check-in/out times over the scheduled window — the same preference
     * TimesheetEntry::reconcileWithCheckins() gives attendance data for
     * payroll — and subtracts the same break_minutes column timesheets
     * use, so a billed visit's hours track what was actually paid for it.
     */
    public function hoursWorked(): float
    {
        $start = $this->actual_start ?: $this->scheduled_start;
        $end = $this->actual_end ?: $this->scheduled_end;

        if (! $start || ! $end) {
            return 0.0;
        }

        $minutes = $end->diffInMinutes($start, true) - $this->break_minutes;

        return round(max($minutes, 0) / 60, 2);
    }
}
