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
     * Generate (or refresh) a draft timesheet per carer scheduled in this
     * rota period, pre-filling daily entries from their day/night shifts
     * plus — Batch 18 — any live-in placement overlapping this week.
     *
     * Known pre-existing limitation, unchanged by Batch 18: this still
     * picks firstWhere('shift_type', 'day'|'night') per carer per date,
     * so a carer with two separate (non-double-handed) day-type shifts
     * on the same date — e.g. two distinct single-handed visits for two
     * different service users — has the second one's hours silently
     * dropped here, same as before this batch. Double-handed visits
     * don't trip this: each carer in a linked pair still has exactly one
     * shift that date. See CHANGES18.md; App\Livewire\Rota\RotaBuilder's
     * new conflict flag surfaces this case so a manager notices it
     * before it reaches payroll, but doesn't fix the underlying model.
     */
    public function generateTimesheets(): void
    {
        $weekStart = $this->week_commencing->toDateString();
        $weekEnd = $this->week_commencing->copy()->addDays(6)->toDateString();

        $dayNightUserIds = $this->shifts()->whereNotNull('assigned_to')->distinct()->pluck('assigned_to');

        // Live-in shifts aren't tied to a rota_period_id — a placement can
        // span many weeks — so they're swept by date overlap with this
        // period's week instead of $this->shifts().
        $liveInShifts = Shift::where('agency_id', $this->agency_id)
            ->where('shift_type', Shift::TYPE_LIVE_IN)
            ->whereNotNull('assigned_to')
            ->where('scheduled_start', '<=', $weekEnd.' 23:59:59')
            ->where('scheduled_end', '>=', $weekStart.' 00:00:00')
            ->get();

        $userIds = $dayNightUserIds->merge($liveInShifts->pluck('assigned_to'))->unique();

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

            $this->generateLiveInEntries($timesheet, $userId, $liveInShifts->where('assigned_to', $userId), $weekStart, $weekEnd);

            $timesheet->recalculateTotals();
        }
    }

    /**
     * One TimesheetEntry per calendar day (clamped to this period's own
     * week) that a live-in placement covers, paid at the carer's
     * live_in_daily_rate snapshotted right now — never re-read later even
     * if the pay profile's rate subsequently changes, same "summary at
     * creation time" principle Alert::summary already uses. Skips a date
     * that already has a day/night shift recorded for this carer that
     * week rather than overwriting it; the two shouldn't normally
     * overlap, but if they do, the day/night entry wins and the clash is
     * left for a manager to sort out by hand.
     */
    protected function generateLiveInEntries(Timesheet $timesheet, int $userId, \Illuminate\Support\Collection $liveInShifts, string $weekStart, string $weekEnd): void
    {
        if ($liveInShifts->isEmpty()) {
            return;
        }

        $rate = EmployeePayProfile::where('user_id', $userId)->first()?->live_in_daily_rate;

        foreach ($liveInShifts as $shift) {
            $start = max($shift->scheduled_start->toDateString(), $weekStart);
            $end = min($shift->scheduled_end->toDateString(), $weekEnd);

            foreach (\Carbon\CarbonPeriod::create($start, $end) as $date) {
                $dateString = $date->toDateString();

                $existing = TimesheetEntry::where('timesheet_id', $timesheet->id)->where('entry_date', $dateString)->first();
                if ($existing && ($existing->day_shift_id || $existing->night_shift_id)) {
                    continue;
                }

                TimesheetEntry::updateOrCreate(
                    ['timesheet_id' => $timesheet->id, 'entry_date' => $dateString],
                    [
                        'day_of_week' => strtolower($date->englishDayOfWeek),
                        'live_in_shift_id' => $shift->id,
                        'live_in_rate' => $rate,
                        'service_user_id' => $shift->service_user_id,
                    ]
                );
            }
        }
    }
}
