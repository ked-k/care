<?php

namespace App\Models\care;

use App\Models\care\Agency;
use App\Models\care\Assessment;
use App\Models\care\CarePlan;
use App\Models\care\Consent;
use App\Models\care\Medication;
use App\Models\care\SafeguardingReport;
use App\Models\care\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceUser extends Model
{
    use HasUuids, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['agency_id','name','dob','gender','address','nhs_number','next_of_kin_name','next_of_kin_contact','metadata','consent_status'];
    protected $casts = ['metadata' => 'array','consent_status' => 'boolean','dob' => 'date'];




    // Relationships
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function carePlans(): HasMany
    {
        return $this->hasMany(CarePlan::class, 'service_user_id');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class, 'service_user_id');
    }

    public function medications(): HasMany
    {
        return $this->hasMany(Medication::class, 'service_user_id');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class, 'service_user_id');
    }

    // Accessors
    public function getAgeAttribute(): ?int
    {
        return $this->dob?->age;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name . ($this->dob ? ' (' . $this->age . ' yrs)' : '');
    }

    public function getFormattedAddressAttribute(): string
    {
        return $this->address ?? 'No address provided';
    }

    public function getGenderBadgeAttribute(): string
    {
        $colors = [
            'Male' => 'primary',
            'Female' => 'danger',
            'Other' => 'secondary',
        ];

        $color = $colors[$this->gender] ?? 'secondary';

        return '<span class="badge bg-' . $color . '">' . e($this->gender ?? 'Not specified') . '</span>';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('consent_status', true);
    }

    public function scopeByAgency($query, $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeNeedsReview($query)
    {
        // Service users whose care plans need review
        return $query->whereHas('carePlans', function($q) {
            $q->where('review_date', '<=', now())
              ->where('is_active', true);
        });
    }

    // Helper Methods
    public function getCareNeedsSummary(): array
    {
        $activeCarePlan = $this->carePlans()
            ->where('is_active', true)
            ->first();

        if (!$activeCarePlan || !$activeCarePlan->plan_data) {
            return [];
        }

        return $activeCarePlan->plan_data['care_needs'] ?? [];
    }

    public function getUpcomingShiftsCount(): int
    {
        return $this->shifts()
            ->where('scheduled_start', '>', now())
            ->where('status', 'scheduled')
            ->count();
    }

    public function getActiveMedicationsCount(): int
    {
        return $this->medications()
            ->where('is_active', true)
            ->count();
    }


    public function consents() { return $this->hasMany(Consent::class); }
    public function safeguardingReports() { return $this->hasMany(SafeguardingReport::class); }
}
