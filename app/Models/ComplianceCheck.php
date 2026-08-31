<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A manual compliance checklist item (e.g. "CQC registration renewal") — not
 * to be confused with the automated metrics on the Compliance Dashboard.
 */
class ComplianceCheck extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETE = 'complete';

    protected $fillable = [
        'agency_id', 'category', 'status', 'notes', 'next_due_at', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'next_due_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_NOT_STARTED,
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function isOverdue(): bool
    {
        return $this->status !== self::STATUS_COMPLETE
            && $this->next_due_at
            && $this->next_due_at->isPast();
    }

    public function statusColor(): string
    {
        if ($this->isOverdue()) {
            return 'danger';
        }
        return match ($this->status) {
            self::STATUS_COMPLETE => 'success',
            self::STATUS_IN_PROGRESS => 'primary',
            default => 'secondary',
        };
    }
}
