<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceUser extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'agency_id', 'name', 'dob', 'gender', 'address', 'nhs_number',
        'next_of_kin_name', 'next_of_kin_contact', 'metadata', 'consent_status',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'dob' => 'date',
        'metadata' => 'array',
        'consent_status' => 'boolean',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function timesheetEntries(): HasMany
    {
        return $this->hasMany(TimesheetEntry::class);
    }

    public function medications(): HasMany
    {
        return $this->hasMany(Medication::class);
    }

    public function carers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'service_user_carer')
            ->withPivot('is_primary_carer')
            ->withTimestamps();
    }

    public function safeguardingReports(): HasMany
    {
        return $this->hasMany(SafeguardingReport::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class);
    }

    public function familyMembers(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    public function careTimelineEntries(): HasMany
    {
        return $this->hasMany(CareTimelineEntry::class);
    }

    public function carePlans(): HasMany
    {
        return $this->hasMany(CarePlan::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }
}
