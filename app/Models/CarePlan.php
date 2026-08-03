<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarePlan extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'service_user_id', 'created_by', 'updated_by', 'title', 'summary',
        'review_date', 'is_active', 'plan_data',
    ];

    protected $casts = [
        'review_date' => 'date',
        'is_active' => 'boolean',
        'plan_data' => 'array',
    ];

    public function serviceUser(): BelongsTo
    {
        return $this->belongsTo(ServiceUser::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
