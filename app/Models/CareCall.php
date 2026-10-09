<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Batch 14: a scheduled welfare-check visit (morning/lunch/evening/bedtime
 * style) for a service user, independent of staffing Shifts and payroll —
 * see the create_care_calls_table migration for why these had to be a
 * separate table rather than extra Shift rows.
 *
 * Day-to-day state (Upcoming/In Progress/Completed/Late/Missed) is never
 * stored here — it's derived live from scheduled_start/scheduled_end vs
 * actual_start/actual_end by App\Support\CareCallStatus, so it can never
 * drift out of sync with the clock. `status` only carries the one state
 * that genuinely needs a human decision: STATUS_CANCELLED.
 */
class CareCall extends Model
{
    use HasUuids, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'agency_id', 'service_user_id', 'assigned_to', 'call_label',
        'scheduled_start', 'scheduled_end', 'actual_start', 'actual_end',
        'status', 'notes', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_SCHEDULED,
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function serviceUser(): BelongsTo
    {
        return $this->belongsTo(ServiceUser::class);
    }

    public function carer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Falls back to a time-of-day guess (matching the hours used in every
     * example so far: ~08:00 morning, ~13:00 lunch, ~16:00 evening, ~20:00
     * bedtime) when nobody set an explicit label when scheduling the call.
     */
    public function displayLabel(): string
    {
        if ($this->call_label) {
            return $this->call_label;
        }

        $hour = $this->scheduled_start->hour;

        return match (true) {
            $hour < 11 => __('Morning'),
            $hour < 14 => __('Lunch'),
            $hour < 18 => __('Evening'),
            default => __('Bedtime'),
        };
    }

    public function checkIn(): void
    {
        $this->update(['actual_start' => now()]);
    }

    public function checkOut(): void
    {
        $this->update(['actual_end' => now()]);
    }
}
