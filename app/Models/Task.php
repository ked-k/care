<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class Task extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'care_plan_id', 'title', 'description', 'type', 'scheduled_at', 'due_at',
        'assigned_to', 'meta', 'shift_id', 'priority', 'requires_photo',
        'requires_signature', 'recurring_pattern', 'parent_task_id',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'due_at' => 'datetime',
        'meta' => 'array',
        'priority' => 'integer',
        'requires_photo' => 'boolean',
        'requires_signature' => 'boolean',
    ];

    public function carePlan(): BelongsTo
    {
        return $this->belongsTo(CarePlan::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    public function childTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TaskLog::class);
    }

    public function latestLog(): HasOne
    {
        return $this->hasOne(TaskLog::class)->latestOfMany('completed_at');
    }

    /**
     * Batch 25: human-readable form of recurring_pattern for display — the
     * raw 'every_n_days:3' string previously leaked straight into the UI
     * (ucfirst/str_replace on it produced "Every n days:3").
     */
    public function recurringPatternLabel(): ?string
    {
        if (! $this->recurring_pattern) {
            return null;
        }

        if (str_starts_with($this->recurring_pattern, 'every_n_days:')) {
            $n = (int) substr($this->recurring_pattern, 13);

            return $n === 1 ? __('Daily') : __('Every :n days', ['n' => $n]);
        }

        return ucfirst($this->recurring_pattern);
    }

    /**
     * Status is derived, not stored: a task is only ever "completed/refused/skipped"
     * once a TaskLog exists; otherwise it's overdue (past due_at) or still pending.
     */
    public function status(): string
    {
        if ($log = $this->latestLog) {
            return $log->status;
        }

        if ($this->due_at && $this->due_at->isPast()) {
            return 'overdue';
        }

        return 'pending';
    }

    public function isComplete(): bool
    {
        return $this->logs()->exists();
    }

    /**
     * Batch 31: single source for status() -> badge color, so the care
     * plan's task list and the new weekly task chart show the exact same
     * colors for the exact same statuses instead of each view keeping its
     * own copy of this mapping.
     */
    public function statusBadgeColor(): string
    {
        return match ($this->status()) {
            'completed' => 'success',
            'refused', 'overdue' => 'danger',
            'skipped' => 'secondary',
            default => 'primary',
        };
    }

    public function complete(int $completedByUserId, string $status, ?string $notes = null, ?string $photoId = null): TaskLog
    {
        $log = $this->logs()->create([
            'completed_by' => $completedByUserId,
            'status' => $status,
            'notes' => $notes,
            'photo_id' => $photoId,
            'completed_at' => now(),
        ]);

        // Batch 25: generate the next occurrence on ANY outcome (completed,
        // partly_completed, refused, or skipped), not just a clean
        // "completed". A repeating task (medication, a wellbeing check...)
        // still needs to exist next week even if this week's was refused or
        // skipped — the old completed-only check silently broke the chain
        // the first time a carer logged anything else, which is what made
        // "weekly" tasks stop repeating after one awkward visit.
        if ($this->recurring_pattern) {
            $this->generateNextOccurrence();
        }

        return $log;
    }

    /**
     * Creates the next occurrence of a recurring task, anchored off this task's own
     * scheduled_at/due_at (not "today") so a late completion doesn't compound drift.
     * Supports 'daily', 'weekly', 'monthly', or a raw 'every_n_days:N' pattern string.
     * Idempotent: won't create a second occurrence if one already exists.
     */
    public function generateNextOccurrence(): ?Task
    {
        // Batch 25: monthly shifts by calendar month (addMonthsNoOverflow,
        // so a task due on the 31st doesn't skip into the next month when
        // the next month is shorter), not by a fixed day count — a real
        // 28-31 day swing is too inaccurate for "monthly" to mean anything.
        $shift = match (true) {
            $this->recurring_pattern === 'daily' => fn ($date) => $date->addDays(1),
            $this->recurring_pattern === 'weekly' => fn ($date) => $date->addDays(7),
            $this->recurring_pattern === 'monthly' => fn ($date) => $date->addMonthsNoOverflow(1),
            str_starts_with((string) $this->recurring_pattern, 'every_n_days:') => fn ($date) => $date->addDays(max(1, (int) substr($this->recurring_pattern, 13))),
            default => null,
        };

        if (! $shift || $this->childTasks()->exists()) {
            return null;
        }

        return static::create([
            'care_plan_id' => $this->care_plan_id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'scheduled_at' => $this->scheduled_at ? $shift($this->scheduled_at->copy()) : null,
            'due_at' => $this->due_at ? $shift($this->due_at->copy()) : null,
            'assigned_to' => $this->assigned_to,
            'meta' => $this->meta,
            'priority' => $this->priority,
            'requires_photo' => $this->requires_photo,
            'requires_signature' => $this->requires_signature,
            'recurring_pattern' => $this->recurring_pattern,
            'parent_task_id' => $this->id,
            'created_by' => $this->created_by,
        ]);
    }

    /**
     * Batch 30: walks this recurring chain's tip forward, creating
     * occurrences until the next one would land on or past $horizon.
     *
     * generateNextOccurrence() above only ever creates ONE occurrence, and
     * only gets called from complete() — i.e. only once a carer actually
     * marks the current one off. That's why a task could show up fine on
     * the day someone typed it, then simply not exist on any day after:
     * nothing ever generated tomorrow's row until today's was actioned,
     * so a task nobody got to yet silently stalled every date after it
     * too. This method is what lets a scheduled command
     * (tasks:generate-occurrences) keep a rolling window of future Task
     * rows actually existing, independent of whether anyone has
     * completed, refused, or even looked at anything yet.
     *
     * Call this on a chain's tip (a task with recurring_pattern set and no
     * childTasks yet) — that's exactly what the command's query selects.
     */
    public function backfillOccurrencesUntil(\Illuminate\Support\Carbon $horizon, int $maxIterations = 90): int
    {
        $tip = $this;
        $created = 0;

        for ($i = 0; $i < $maxIterations; $i++) {
            $next = $tip->generateNextOccurrence();

            if (! $next) {
                break; // no recognised recurring_pattern — nothing to project forward
            }

            $created++;
            $tip = $next;

            $reference = $tip->due_at ?? $tip->scheduled_at;

            // Neither date is set, so there's nothing to compare against the
            // horizon — generate this one step and stop, same as the old
            // single-step behaviour, rather than looping without an end
            // condition.
            if (! $reference || $reference->greaterThanOrEqualTo($horizon)) {
                break;
            }
        }

        return $created;
    }

    /**
     * Batch 33: who actually covers this task TODAY, per the rota — not
     * whatever was last typed into assigned_to when this task (or the
     * recurring parent it was cloned from) was created.
     *
     * The bug this fixes: generateNextOccurrence() above copies
     * assigned_to forward unchanged on every auto-generated occurrence,
     * so a recurring task created with carer X stayed "X's task" forever,
     * even though the rota has a different carer covering that client on
     * most days (the real example that surfaced this: Chester has a
     * different carer different days, but his recurring tasks all kept
     * showing carer X). assigned_to is kept around, but from here on it's
     * only a FALLBACK — used when no Shift exists yet for this service
     * user on this date (rota not built that far out, or an ad-hoc task
     * with no rota link at all).
     *
     * Pass $shiftsForDate already scoped to this task's service user +
     * date to avoid a Shift query per task — see effectiveAssignedToMany()
     * for the batch-safe way to resolve a whole list of tasks at once.
     */
    public function effectiveAssignedTo(?Collection $shiftsForDate = null): ?string
    {
        $date = $this->due_at ?? $this->scheduled_at;
        $serviceUserId = $this->carePlan?->service_user_id;

        if (! $date || ! $serviceUserId) {
            return $this->assigned_to;
        }

        $shifts = $shiftsForDate ?? Shift::where('service_user_id', $serviceUserId)
            ->whereDate('scheduled_start', $date)
            ->orderBy('scheduled_start')
            ->get();

        if ($shifts->isEmpty()) {
            return $this->assigned_to;
        }

        // Several shifts can cover the same client the same day (AM/PM
        // split, double-handed visits) — prefer the one whose own window
        // actually contains the task's time, falling back to the day's
        // first shift when the task has no precise time to match against.
        $withinWindow = $shifts->first(fn (Shift $s) => $s->scheduled_start && $s->scheduled_end
            && $date->betweenIncluded($s->scheduled_start, $s->scheduled_end));

        return ($withinWindow ?? $shifts->first())->assigned_to ?? $this->assigned_to;
    }

    /**
     * Batch 33: resolves effectiveAssignedTo() for a whole collection of
     * tasks in one Shift query instead of one per task. Returns
     * [task_id => assigned_to-or-null].
     */
    public static function effectiveAssignedToMany(Collection $tasks): array
    {
        $eligible = $tasks->filter(fn (Task $t) => $t->carePlan?->service_user_id && ($t->due_at ?? $t->scheduled_at));

        if ($eligible->isEmpty()) {
            return [];
        }

        $serviceUserIds = $eligible->map(fn (Task $t) => $t->carePlan->service_user_id)->unique()->values();
        $dates = $eligible->map(fn (Task $t) => ($t->due_at ?? $t->scheduled_at)->toDateString());

        $shiftsByKey = Shift::whereIn('service_user_id', $serviceUserIds)
            ->whereDate('scheduled_start', '>=', $dates->min())
            ->whereDate('scheduled_start', '<=', $dates->max())
            ->orderBy('scheduled_start')
            ->get()
            ->groupBy(fn (Shift $s) => $s->service_user_id.'|'.$s->scheduled_start->toDateString());

        $result = [];
        foreach ($tasks as $task) {
            $date = $task->due_at ?? $task->scheduled_at;
            $serviceUserId = $task->carePlan?->service_user_id;
            $key = $serviceUserId && $date ? $serviceUserId.'|'.$date->toDateString() : null;

            $result[$task->id] = $task->effectiveAssignedTo($key ? $shiftsByKey->get($key, new Collection) : new Collection);
        }

        return $result;
    }

    /**
     * Batch 33: resolves and overwrites the assignee() relation in-memory
     * on every task in $tasks with the rota-derived carer, so every view
     * that already reads $task->assignee->name (task list, week chart,
     * caseload task chart) shows the right person with no blade changes —
     * it just stops trusting the stored column and starts trusting the
     * rota. Also sets effective_assigned_to on each task for anywhere
     * that needs the raw id (e.g. filtering a carer's own task list).
     */
    public static function attachEffectiveCarers(Collection $tasks): Collection
    {
        $map = static::effectiveAssignedToMany($tasks);

        $carerIds = collect($map)->filter()->unique()->values();
        $carers = $carerIds->isEmpty() ? collect() : User::whereIn('id', $carerIds)->get()->keyBy('id');

        foreach ($tasks as $task) {
            $effectiveId = $map[$task->id] ?? $task->assigned_to;
            $task->setAttribute('effective_assigned_to', $effectiveId);
            $task->setRelation('assignee', $effectiveId ? $carers->get($effectiveId) : null);
        }

        return $tasks;
    }
}
