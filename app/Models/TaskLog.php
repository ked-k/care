<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskLog extends Model
{
    use HasUuids;

    // task_logs.id is a UUID primary key (see its migration) — without
    // these two, Eloquent's default insert path still treats the key as
    // auto-incrementing and overwrites the UUID HasUuids just set with
    // whatever the DB driver's lastInsertId() returns for a non-AUTO_INCREMENT
    // column (typically 0) immediately after create(). The row saved to the
    // database is unaffected either way, but `TaskLog::create([...])->id`
    // used right after (as TaskListComponent::completeTask() and the new
    // ShiftVisitComponent both do, to attach a photo/signature to the log
    // they just created) would silently get the wrong id. Pre-existing gap,
    // fixed here while this file was already being touched for shift_takeover_id.
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['task_id', 'shift_takeover_id', 'completed_by', 'status', 'notes', 'photo_id', 'completed_at'];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'photo_id');
    }

    /**
     * Set only when this completion was entered by a manager/admin covering
     * the shift rather than the assigned carer themselves — see
     * App\Models\ShiftTakeover.
     */
    public function takeover(): BelongsTo
    {
        return $this->belongsTo(ShiftTakeover::class, 'shift_takeover_id');
    }

    public function wasEnteredByProxy(): bool
    {
        return $this->shift_takeover_id !== null;
    }
}
