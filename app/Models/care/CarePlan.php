<?php

namespace App\Models\care;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CarePlan extends Model
{
    use HasUuids, SoftDeletes;

    // public $incrementing = false;
    // protected $keyType = 'string';

    // protected $fillable = ['service_user_id','created_by','title','summary','review_date','is_active','plan_data'];
    // protected $casts = [
    //     'plan_data' => 'array',
    //     'review_date' => 'date',
    //     'is_active' => 'boolean',
    // ];

    // public function serviceUser() { return $this->belongsTo(ServiceUser::class); }
    // public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    // public function tasks() { return $this->hasMany(Task::class); }
    protected $table = 'care_plans';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'service_user_id',
        'title',
        'summary',
        'review_date',
        'is_active',
        'plan_data',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'id' => 'string',
        'service_user_id' => 'string',
        'plan_data' => 'array',
        'is_active' => 'boolean',
        'review_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function serviceUser(): BelongsTo
    {
        return $this->belongsTo(ServiceUser::class, 'service_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'care_plan_id');
    }

    // Helper methods for plan_data JSON
    public function getCareNeedsAttribute(): array
    {
        return $this->plan_data['care_needs'] ?? [];
    }

    public function getRiskAssessmentsAttribute(): array
    {
        return $this->plan_data['risk_assessments'] ?? [];
    }

    public function getDailyRoutineAttribute(): array
    {
        return $this->plan_data['daily_routine'] ?? [];
    }

    public function getEmergencyContactsAttribute(): array
    {
        return $this->plan_data['emergency_contacts'] ?? [];
    }

    public function getEquipmentAttribute(): array
    {
        return $this->plan_data['equipment'] ?? [];
    }

    public function getDietaryRequirementsAttribute(): array
    {
        return $this->plan_data['dietary_requirements'] ?? [];
    }

    public function getCommunicationNeedsAttribute(): array
    {
        return $this->plan_data['communication_needs'] ?? [];
    }

    // Scope for active care plans
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope for pending review
    public function scopeNeedsReview($query)
    {
        return $query->where('review_date', '<=', now())
                     ->where('is_active', true);
    }
}
