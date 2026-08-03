<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskLog extends Model
{
    use HasUuids;

    protected $fillable = ['task_id', 'completed_by', 'status', 'notes', 'photo_id', 'completed_at'];

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
}
