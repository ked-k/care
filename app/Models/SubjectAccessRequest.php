<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SubjectAccessRequest extends Model
{
    use HasUuids, LogsActivity;

    public $incrementing = false;
    protected $keyType = 'string';

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_FULFILLED = 'fulfilled';
    public const STATUS_REJECTED = 'rejected';

    // The UK GDPR rights this covers, per the vision doc.
    public const TYPES = [
        'access' => 'Right of access',
        'rectification' => 'Rectification',
        'erasure' => 'Erasure',
        'portability' => 'Portability',
        'restriction' => 'Restrict processing',
        'objection' => 'Object to processing',
    ];

    protected $fillable = [
        'requested_by', 'service_user_id', 'type', 'status', 'fulfilled_by',
        'notes', 'created_by', 'updated_by',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    /**
     * Batch 20: replaces the explicit AuditLogger::log('SAR_SUBMITTED'/
     * 'SAR_'.strtoupper($status), ...) calls that used to live in
     * SarIndexComponent::submitRequest()/resolve().
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('Subject Access Request')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function serviceUser(): BelongsTo
    {
        return $this->belongsTo(ServiceUser::class);
    }

    public function fulfiller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fulfilled_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_FULFILLED => 'success',
            self::STATUS_IN_PROGRESS => 'primary',
            self::STATUS_REJECTED => 'danger',
            default => 'amber',
        };
    }
}
