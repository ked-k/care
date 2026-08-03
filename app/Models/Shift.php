<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'agency_id', 'rota_period_id', 'service_user_id', 'assigned_to',
        'scheduled_start', 'scheduled_end', 'actual_start', 'actual_end',
        'shift_type', 'break_minutes', 'status', 'notes',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function rotaPeriod(): BelongsTo
    {
        return $this->belongsTo(RotaPeriod::class);
    }

    public function serviceUser(): BelongsTo
    {
        return $this->belongsTo(ServiceUser::class);
    }

    public function carer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function visitCheckins(): HasMany
    {
        return $this->hasMany(VisitCheckin::class);
    }
}
