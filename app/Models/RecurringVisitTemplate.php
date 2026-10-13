<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Batch 18: "set a repeating pattern once" for a service user's visits,
 * instead of re-entering the same Shift or CareCall every week. One
 * template generates either real day/night Shift rows (feeds payroll —
 * template_type = TYPE_SHIFT) or CareCall welfare-check rows (never
 * touches payroll — template_type = TYPE_CARE_CALL); see generate().
 *
 * There's no cron/queue in this app, so nothing generates on its own —
 * a manager explicitly runs generate() for a date range (from the
 * template list, or the Rota Builder for shift templates), same
 * "compute it where it's needed" approach as every other batch's
 * scheduled-feeling feature. generate() is idempotent: re-running it
 * over a range it's already covered creates nothing new for the slots
 * that already have a row, whether that row came from this template or
 * was entered by hand.
 */
class RecurringVisitTemplate extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    public const TYPE_SHIFT = 'shift';
    public const TYPE_CARE_CALL = 'care_call';

    protected $fillable = [
        'agency_id', 'service_user_id', 'assigned_to',
        'template_type', 'shift_type', 'call_label',
        'days_of_week', 'start_time', 'end_time', 'break_minutes',
        'starts_on', 'ends_on', 'is_active', 'notes',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'starts_on' => 'date',
        'ends_on' => 'date',
        'is_active' => 'boolean',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function serviceUser(): BelongsTo
    {
        return $this->belongsTo(ServiceUser::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function isShiftTemplate(): bool
    {
        return $this->template_type === self::TYPE_SHIFT;
    }

    /**
     * Creates the real Shift/CareCall rows for every matching weekday
     * between $from and $to (both inclusive), clamped to this template's
     * own starts_on/ends_on window. Returns how many rows were actually
     * created, so the caller can tell the user "generated 4, skipped 3
     * (already scheduled)" rather than a silent no-op.
     */
    public function generate(Carbon $from, Carbon $to): int
    {
        $from = $from->copy()->max($this->starts_on);
        if ($this->ends_on) {
            $to = $to->copy()->min($this->ends_on);
        }

        if ($from->greaterThan($to)) {
            return 0;
        }

        $created = 0;

        foreach (CarbonPeriod::create($from, $to) as $date) {
            if (! in_array($date->dayOfWeek, $this->days_of_week ?? [], false)) {
                continue;
            }

            $created += $this->isShiftTemplate()
                ? $this->generateShiftFor($date)
                : $this->generateCareCallFor($date);
        }

        return $created;
    }

    protected function generateShiftFor(Carbon $date): int
    {
        $dateString = $date->toDateString();

        // Same slot key App\Livewire\Rota\RotaBuilder's grid uses — skips
        // if anything (this template or a manual entry) already occupies
        // this service user's day/night slot that date.
        $exists = Shift::where('service_user_id', $this->service_user_id)
            ->where('shift_type', $this->shift_type)
            ->whereDate('scheduled_start', $dateString)
            ->exists();

        if ($exists) {
            return 0;
        }

        $start = Carbon::parse("{$dateString} {$this->start_time}");
        $end = Carbon::parse("{$dateString} {$this->end_time}");
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        $rotaPeriod = RotaPeriod::where('agency_id', $this->agency_id)
            ->whereDate('week_commencing', '<=', $dateString)
            ->whereRaw('DATE_ADD(week_commencing, INTERVAL 6 DAY) >= ?', [$dateString])
            ->first();

        Shift::create([
            'agency_id' => $this->agency_id,
            'rota_period_id' => $rotaPeriod?->id,
            'service_user_id' => $this->service_user_id,
            'assigned_to' => $this->assigned_to,
            'shift_type' => $this->shift_type,
            'scheduled_start' => $start,
            'scheduled_end' => $end,
            'break_minutes' => $this->break_minutes,
            'status' => 'scheduled',
            'notes' => $this->notes,
            'created_by' => $this->created_by,
        ]);

        return 1;
    }

    protected function generateCareCallFor(Carbon $date): int
    {
        $dateString = $date->toDateString();
        $start = Carbon::parse("{$dateString} {$this->start_time}");

        $exists = CareCall::where('service_user_id', $this->service_user_id)
            ->whereDate('scheduled_start', $dateString)
            ->whereTime('scheduled_start', $start->format('H:i:s'))
            ->exists();

        if ($exists) {
            return 0;
        }

        $end = Carbon::parse("{$dateString} {$this->end_time}");
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        CareCall::create([
            'agency_id' => $this->agency_id,
            'service_user_id' => $this->service_user_id,
            'assigned_to' => $this->assigned_to,
            'call_label' => $this->call_label,
            'scheduled_start' => $start,
            'scheduled_end' => $end,
            'status' => CareCall::STATUS_SCHEDULED,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
        ]);

        return 1;
    }
}
