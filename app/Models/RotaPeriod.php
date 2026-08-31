<?php

namespace App\Models;

use App\Models\Agency;
use App\Models\Shift;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RotaPeriod extends Model
{
    use HasUuids;

    protected $fillable = ['agency_id', 'week_commencing', 'status', 'notes', 'created_by', 'updated_by'];

    protected $casts = [
        'week_commencing' => 'date',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class);
    }

    /**
     * Publishing is the moment a rota actually becomes visible to carers —
     * App\Livewire\Rota\MyRotaComponent only ever shows published periods —
     * so this is where every scheduled carer is notified, once per publish
     * rather than once per shift edit. Centralised here since two different
     * Livewire components (RotaBuilder and RotaPeriodIndex) can trigger a publish.
     */
    public function publish(): void
    {
        $this->update(['status' => 'published']);

        $carerIds = $this->shifts()->whereNotNull('assigned_to')->distinct()->pluck('assigned_to');

        foreach ($carerIds as $carerId) {
            NotificationService::send(
                userId: $carerId,
                type: 'shift_assigned',
                title: 'Your rota has been published',
                message: 'Shifts for the week commencing '.$this->week_commencing->format('d M Y').' are now available.',
                data: ['rota_period_id' => $this->id],
            );
        }
    }

    /**
     * Generate (or refresh) a draft timesheet per carer scheduled in this rota period,
     * pre-filling daily entries from their day/night shifts.
     */
    public function generateTimesheets(): void
    {
        $userIds = $this->shifts()->whereNotNull('assigned_to')->distinct()->pluck('assigned_to');

        foreach ($userIds as $userId) {
            $timesheet = Timesheet::firstOrCreate(
                ['agency_id' => $this->agency_id, 'user_id' => $userId, 'week_commencing' => $this->week_commencing],
                ['rota_period_id' => $this->id, 'status' => 'draft']
            );

            $shifts = $this->shifts()
                ->where('assigned_to', $userId)
                ->get()
                ->groupBy(fn (Shift $s) => $s->scheduled_start->toDateString());

            foreach ($shifts as $date => $dayShifts) {
                $dayShift = $dayShifts->firstWhere('shift_type', 'day');
                $nightShift = $dayShifts->firstWhere('shift_type', 'night');
                $primary = $dayShift ?? $nightShift;

                TimesheetEntry::updateOrCreate(
                    ['timesheet_id' => $timesheet->id, 'entry_date' => $date],
                    [
                        'day_of_week' => strtolower(\Carbon\Carbon::parse($date)->englishDayOfWeek),
                        'day_shift_id' => $dayShift?->id,
                        'day_shift_start' => $dayShift?->scheduled_start?->format('H:i:s'),
                        'day_shift_end' => $dayShift?->scheduled_end?->format('H:i:s'),
                        'night_shift_id' => $nightShift?->id,
                        'night_shift_start' => $nightShift?->scheduled_start?->format('H:i:s'),
                        'night_shift_end' => $nightShift?->scheduled_end?->format('H:i:s'),
                        'break_minutes' => $dayShifts->sum('break_minutes'),
                        'service_user_id' => $primary?->service_user_id,
                    ]
                );
            }

            $timesheet->recalculateTotals();
        }
    }
}
