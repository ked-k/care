<?php

namespace App\Models;

use App\Models\Shift;
use App\Models\ServiceUser;
use App\Models\VisitCheckin;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimesheetEntry extends Model
{
    use HasUuids;

    protected $fillable = [
        'timesheet_id', 'entry_date', 'day_of_week',
        'day_shift_id', 'day_shift_start', 'day_shift_end',
        'night_shift_id', 'night_shift_start', 'night_shift_end',
        'break_minutes', 'total_hours', 'service_user_id', 'initials',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'total_hours' => 'decimal:2',
    ];

    public function timesheet(): BelongsTo
    {
        return $this->belongsTo(Timesheet::class);
    }

    public function dayShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'day_shift_id');
    }

    public function nightShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'night_shift_id');
    }

    public function serviceUser(): BelongsTo
    {
        return $this->belongsTo(ServiceUser::class);
    }

    /**
     * Pull actual clocked times from visit_checkins for the linked shift(s), where available,
     * so payroll is based on verified attendance rather than the scheduled rota.
     * Falls back to whatever start/end is already on the entry (e.g. scheduled times, or
     * a manually filled-in paper timesheet for a carer without app check-in).
     */
    public function reconcileWithCheckins(): void
    {
        foreach (['day' => $this->day_shift_id, 'night' => $this->night_shift_id] as $slot => $shiftId) {
            if (! $shiftId) {
                continue;
            }

            $checkin = VisitCheckin::where('shift_id', $shiftId)->latest('checkin_time')->first();
            if (! $checkin || ! $checkin->checkout_time) {
                continue;
            }

            $this->{"{$slot}_shift_start"} = $checkin->checkin_time->format('H:i:s');
            $this->{"{$slot}_shift_end"} = $checkin->checkout_time->format('H:i:s');
        }

        $this->save();
    }

    /**
     * Sum worked minutes across the day-shift and night-shift columns
     * (handles a night shift crossing midnight), minus break, in hours.
     */
    public function recalculateHours(): void
    {
        $minutes = 0;

        if ($this->day_shift_start && $this->day_shift_end) {
            $start = \Carbon\Carbon::parse($this->day_shift_start);
            $end = \Carbon\Carbon::parse($this->day_shift_end);
            if ($end->lessThanOrEqualTo($start)) {
                $end->addDay();
            }
            $minutes += $end->diffInMinutes($start);
        }

        if ($this->night_shift_start && $this->night_shift_end) {
            $start = \Carbon\Carbon::parse($this->night_shift_start);
            $end = \Carbon\Carbon::parse($this->night_shift_end);
            if ($end->lessThanOrEqualTo($start)) {
                $end->addDay();
            }
            $minutes += $end->diffInMinutes($start);
        }

        $minutes -= $this->break_minutes;

        $this->total_hours = round(max($minutes, 0) / 60, 2);
        $this->save();
    }
}
