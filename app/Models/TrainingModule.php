<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingModule extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['title', 'description', 'url', 'duration_minutes', 'category', 'is_mandatory', 'renewal_period_days'];

    protected $casts = [
        'duration_minutes' => 'integer',
        'is_mandatory' => 'boolean',
        'renewal_period_days' => 'integer',
    ];

    public function progress(): HasMany
    {
        return $this->hasMany(TrainingProgress::class, 'module_id');
    }

    public function completedByCount(): int
    {
        return $this->progress()->where('status', 'completed')->count();
    }

    /**
     * Batch 36: when this module expires on a fixed cycle (e.g. an annual
     * safeguarding refresher), the default expiry for a given completion
     * date — used to pre-fill TrainingProgress::expires_at when a manager
     * doesn't type one in explicitly. Null for a module that doesn't
     * expire at all.
     */
    public function defaultExpiryFrom(\Illuminate\Support\Carbon $completedAt): ?\Illuminate\Support\Carbon
    {
        return $this->renewal_period_days ? $completedAt->copy()->addDays($this->renewal_period_days) : null;
    }
}
