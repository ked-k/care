<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MedicationAdministration extends Model
{
    use HasUuids, LogsActivity;

    // See App\Models\TaskLog's version of this comment — same UUID-PK gap,
    // harmless so far only because nothing read ->id immediately after
    // MarChartComponent::recordAdministration()'s create() call.
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'medication_id', 'administered_by', 'shift_id', 'shift_takeover_id', 'scheduled_time', 'actual_time',
        'status', 'refusal_reason', 'notes', 'witness_signature', 'photo_id',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'scheduled_time' => 'datetime',
        'actual_time' => 'datetime',
    ];

    /**
     * Batch 20: new instrumentation — every dose given/refused/missed is
     * now a permanent activity_log row, not just a MedicationAdministration
     * row that could theoretically be edited later. logFillable() rather
     * than logOnlyDirty() since these rows are created once and essentially
     * never updated.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('Medication Administration')
            ->dontSubmitEmptyLogs();
    }

    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }

    public function administeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administered_by');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'photo_id');
    }

    public function takeover(): BelongsTo
    {
        return $this->belongsTo(ShiftTakeover::class, 'shift_takeover_id');
    }

    public function wasEnteredByProxy(): bool
    {
        return $this->shift_takeover_id !== null;
    }

    /**
     * Batch 15: true if a scheduled (non-PRN) dose of this medication has
     * already been recorded for the given day — the duplicate-recording
     * guard called from MarChartComponent::recordAdministration() and
     * ShiftVisitComponent::recordAdministration() before creating a new
     * row. PRN medications are never checked here: more than one dose in a
     * day is normal and expected for them, so callers should only use this
     * for a medication's scheduled doses.
     */
    public static function alreadyRecordedOn(string $medicationId, \Carbon\Carbon|string $date): bool
    {
        return static::where('medication_id', $medicationId)
            ->whereDate('scheduled_time', $date)
            ->exists();
    }

    /**
     * Batch 15: refused/missed dose counts in the trailing 7 days, batched
     * across several medications at once — used to flag a possible
     * medication concern (e.g. a pattern of refusals) on the MAR chart and
     * the shift visit screen. Only medications meeting the concern
     * threshold (2 or more in the window) are included in the result;
     * nothing here is ever stored, it's computed fresh every time.
     *
     * @param iterable<string> $medicationIds
     * @return array<string, int> medication id => count
     */
    public static function recentConcernCounts(iterable $medicationIds): array
    {
        return static::whereIn('medication_id', $medicationIds)
            ->where('scheduled_time', '>=', now()->subDays(7))
            ->whereIn('status', ['refused', 'missed'])
            ->get()
            ->groupBy('medication_id')
            ->map(fn ($group) => $group->count())
            ->filter(fn (int $count) => $count >= 2)
            ->toArray();
    }
}
