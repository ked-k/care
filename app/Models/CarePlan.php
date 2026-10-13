<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CarePlan extends Model
{
    use HasUuids, SoftDeletes, LogsActivity;

    protected $fillable = [
        'service_user_id', 'created_by', 'updated_by', 'title', 'summary',
        'review_date', 'is_active', 'plan_data',
    ];

    protected $casts = [
        'review_date' => 'date',
        'is_active' => 'boolean',
        'plan_data' => 'array',
    ];

    /**
     * Batch 20: new instrumentation — care plans had no audit logging at
     * all before this batch, despite the roadmap specifically calling out
     * "care plans, medication, consent" as the audit-depth gap to close.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('Care Plan')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function serviceUser(): BelongsTo
    {
        return $this->belongsTo(ServiceUser::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
