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

        if ($status === 'completed' && $this->recurring_pattern) {
            $this->generateNextOccurrence();
        }

        return $log;
    }

    /**
     * Creates the next occurrence of a recurring task, anchored off this task's own
     * scheduled_at/due_at (not "today") so a late completion doesn't compound drift.
     * Supports 'daily', 'weekly', or a raw 'every_n_days:N' pattern string.
     * Idempotent: won't create a second occurrence if one already exists.
     */
    public function generateNextOccurrence(): ?Task
    {
        $interval = match (true) {
            $this->recurring_pattern === 'daily' => 1,
            $this->recurring_pattern === 'weekly' => 7,
            str_starts_with((string) $this->recurring_pattern, 'every_n_days:') => (int) substr($this->recurring_pattern, 13),
            default => null,
        };

        if (! $interval || $this->childTasks()->exists()) {
            return null;
        }

        return static::create([
            'care_plan_id' => $this->care_plan_id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'scheduled_at' => $this->scheduled_at?->copy()->addDays($interval),
            'due_at' => $this->due_at?->copy()->addDays($interval),
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
