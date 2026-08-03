<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medication extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'service_user_id', 'medication_name', 'dosage', 'frequency', 'administration_route',
        'scheduled_times', 'start_date', 'end_date', 'is_prn', 'instructions', 'side_effects', 'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_prn' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function serviceUser(): BelongsTo
    {
        return $this->belongsTo(ServiceUser::class);
    }

    public function administrations(): HasMany
    {
        return $this->hasMany(MedicationAdministration::class);
    }

    public function scheduledTimeFormatted(): ?string
    {
        return $this->scheduled_times ? substr((string) $this->scheduled_times, 0, 5) : null;
    }

    /**
     * Whether this medication is within its prescribed date range on a given day
     * (ignores is_prn — PRN meds are still "active" for as-needed logging).
     */
    public function isActiveOn(Carbon $date): bool
    {
        if (! $this->is_active) {
            return false;
        }
        if ($this->start_date && $date->lt($this->start_date)) {
            return false;
        }
        if ($this->end_date && $date->gt($this->end_date)) {
            return false;
        }

        return true;
    }
}
