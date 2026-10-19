<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingProgress extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    public const STATUS_STARTED = 'started';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'user_id', 'module_id', 'status', 'score', 'completed_at',
        // Batch 36: certificate + expiry — see the migration's doc comment.
        'certificate_media_id', 'expires_at', 'last_reminded_at',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'score' => 'decimal:2',
        'expires_at' => 'date',
        'last_reminded_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_STARTED,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(TrainingModule::class, 'module_id');
    }

    /**
     * Batch 36: certificate_media_id is a plain uuid column, not a DB
     * foreign key (same convention as task_logs.photo_id) — this relation
     * still works fine without one.
     */
    public function certificate(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'certificate_media_id');
    }

    public function isExpired(): bool
    {
        return (bool) ($this->expires_at && $this->expires_at->isPast());
    }

    public function isExpiringSoon(int $withinDays = 30): bool
    {
        return (bool) ($this->expires_at && ! $this->isExpired() && now()->diffInDays($this->expires_at, false) <= $withinDays);
    }

    /**
     * Batch 36: the single status the training matrix colours each cell
     * by — folds "completed" down into expired/expiring-soon/completed
     * once a certificate has an expiry date, so a manager scanning the
     * grid sees who needs chasing without opening every row.
     */
    public function matrixStatus(): string
    {
        if ($this->status !== self::STATUS_COMPLETED) {
            return $this->status;
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        if ($this->isExpiringSoon()) {
            return 'expiring_soon';
        }

        return self::STATUS_COMPLETED;
    }
}
