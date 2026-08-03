<?php

namespace App\Models\care;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medication extends Model
{
    use SoftDeletes;

    protected $table = 'medications';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'service_user_id', 'medication_name', 'dosage', 'frequency',
        'administration_route', 'scheduled_times', 'start_date', 'end_date',
        'is_prn', 'instructions', 'side_effects', 'is_active'
    ];

    protected $casts = [
        'id' => 'string',
        'service_user_id' => 'string',
        'scheduled_times' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_prn' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Administration routes
    const ROUTES = [
        'oral' => 'Oral',
        'topical' => 'Topical',
        'inhalation' => 'Inhalation',
        'injection' => 'Injection',
        'sublingual' => 'Sublingual',
        'rectal' => 'Rectal',
        'ophthalmic' => 'Ophthalmic (Eye)',
        'otic' => 'Otic (Ear)',
        'nasal' => 'Nasal',
    ];

    // Frequency options
    const FREQUENCIES = [
        'once_daily' => 'Once Daily',
        'twice_daily' => 'Twice Daily',
        'three_times_daily' => 'Three Times Daily',
        'four_times_daily' => 'Four Times Daily',
        'every_4_hours' => 'Every 4 Hours',
        'every_6_hours' => 'Every 6 Hours',
        'every_8_hours' => 'Every 8 Hours',
        'weekly' => 'Weekly',
        'as_required' => 'As Required (PRN)',
    ];

    // Relationships
    public function serviceUser(): BelongsTo
    {
        return $this->belongsTo(ServiceUser::class, 'service_user_id');
    }

    public function administrations(): HasMany
    {
        return $this->hasMany(MedicationAdministration::class, 'medication_id');
    }

    // Accessors
    public function getRouteLabelAttribute(): string
    {
        return self::ROUTES[$this->administration_route] ?? $this->administration_route;
    }

    public function getFrequencyLabelAttribute(): string
    {
        return self::FREQUENCIES[$this->frequency] ?? $this->frequency;
    }

    public function getIsActiveLabelAttribute(): string
    {
        return $this->is_active ? 'Active' : 'Discontinued';
    }

    public function getIsActiveColorAttribute(): string
    {
        return $this->is_active ? 'success' : 'secondary';
    }

    public function getStatusAttribute(): string
    {
        if (!$this->is_active) return 'Discontinued';
        if ($this->end_date && $this->end_date->isPast()) return 'Expired';
        return 'Active';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', today());
            });
    }

    public function scopeForServiceUser($query, $serviceUserId)
    {
        return $query->where('service_user_id', $serviceUserId);
    }

    public function scopePrn($query)
    {
        return $query->where('is_prn', true);
    }

    public function scopeRegular($query)
    {
        return $query->where('is_prn', false);
    }

    // Helper Methods
    public function getTodaysAdministrations()
    {
        return $this->administrations()
            ->whereDate('scheduled_time', today())
            ->get();
    }

    public function getLastSevenDaysCompliance(): array
    {
        $results = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $scheduledCount = $this->getScheduledCountForDate($date);
            $givenCount = $this->administrations()
                ->whereDate('scheduled_time', $date)
                ->where('status', 'given')
                ->count();

            $results[$date->format('Y-m-d')] = [
                'date' => $date->format('D, M j'),
                'scheduled' => $scheduledCount,
                'given' => $givenCount,
                'compliance' => $scheduledCount > 0 ? round(($givenCount / $scheduledCount) * 100) : 100,
            ];
        }
        return $results;
    }

    protected function getScheduledCountForDate($date): int
    {
        if ($this->is_prn) return 0;

        $times = $this->scheduled_times ?? [];
        return count($times);
    }
}
