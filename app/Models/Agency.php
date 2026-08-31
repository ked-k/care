<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agency extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'address', 'contact_email', 'phone', 'subscription_id',
        'logo_path', 'settings', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function serviceUsers(): HasMany
    {
        return $this->hasMany(ServiceUser::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function rotaPeriods(): HasMany
    {
        return $this->hasMany(RotaPeriod::class);
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class);
    }

    public function payrollRuns(): HasMany
    {
        return $this->hasMany(PayrollRun::class);
    }

    public function employeePayProfiles(): HasMany
    {
        return $this->hasMany(EmployeePayProfile::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function policies(): HasMany
    {
        return $this->hasMany(Policy::class);
    }

    public function complianceChecks(): HasMany
    {
        return $this->hasMany(ComplianceCheck::class);
    }

    public function breachReports(): HasMany
    {
        return $this->hasMany(BreachReport::class);
    }
}
