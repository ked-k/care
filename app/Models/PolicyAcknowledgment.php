<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PolicyAcknowledgment extends Model
{
    use HasUuids, LogsActivity;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['policy_id', 'user_id', 'acknowledged_at', 'ip_address', 'created_by', 'updated_by'];

    protected $casts = [
        'acknowledged_at' => 'datetime',
    ];

    /**
     * Batch 20: replaces the explicit AuditLogger::log('POLICY_ACKNOWLEDGED',
     * $policy) call that used to live in PolicyIndexComponent::acknowledge()
     * — logged against $policy there, now logged against the acknowledgment
     * row itself (which still carries policy_id and user_id), which is more
     * precise since a policy can be acknowledged by many different staff.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('Policy Acknowledgment')
            ->dontSubmitEmptyLogs();
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
