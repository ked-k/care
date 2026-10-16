<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
}
