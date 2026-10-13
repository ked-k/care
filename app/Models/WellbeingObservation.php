<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Batch 17: a structured wellbeing observation — skin concerns, mood or
 * behaviour changes, nutrition/hydration, mobility, sleep, continence, or
 * something else worth tracking for patterns over time. Created from
 * App\Livewire\Wellbeing\WellbeingObservationComponent, nested on both the
 * carer's shift-visit screen and client-profile page.
 *
 * Deliberately distinct from a free-text App\Models\CareTimelineEntry note
 * (structured category/severity instead of prose, so a manager can scan
 * for patterns) and from a full App\Models\SafeguardingReport (this is for
 * routine observations, not actual or suspected harm — a carer who
 * suspects harm should raise a safeguarding report instead). Every
 * observation still gets cross-posted to the Timeline so it shows up
 * alongside everything else for this person without a separate screen.
 */
class WellbeingObservation extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    public const SEVERITY_ROUTINE = 'routine';
    public const SEVERITY_CONCERN = 'concern';
    public const SEVERITY_SIGNIFICANT = 'significant';

    public const CATEGORIES = [
        'skin_integrity' => 'Skin integrity',
        'mood_behaviour' => 'Mood / behaviour',
        'nutrition_hydration' => 'Nutrition / hydration',
        'mobility' => 'Mobility',
        'sleep' => 'Sleep',
        'continence' => 'Continence',
        'other' => 'Other',
    ];

    protected $fillable = [
        'agency_id', 'service_user_id', 'shift_takeover_id',
        'category', 'severity', 'description', 'action_taken', 'informed_person',
        'follow_up_required', 'follow_up_notes', 'media_id', 'alert_id',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'follow_up_required' => 'boolean',
    ];

    protected $attributes = [
        'severity' => self::SEVERITY_ROUTINE,
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function serviceUser(): BelongsTo
    {
        return $this->belongsTo(ServiceUser::class);
    }

    public function takeover(): BelongsTo
    {
        return $this->belongsTo(ShiftTakeover::class, 'shift_takeover_id');
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'media_id');
    }

    public function alert(): BelongsTo
    {
        return $this->belongsTo(Alert::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst(str_replace('_', ' ', $this->category));
    }

    public function isSignificant(): bool
    {
        return $this->severity === self::SEVERITY_SIGNIFICANT;
    }

    /** Maps to resources/views/components/badge.blade.php's `color` prop. */
    public function severityColor(): string
    {
        return match ($this->severity) {
            self::SEVERITY_SIGNIFICANT => 'danger',
            self::SEVERITY_CONCERN => 'amber',
            default => 'secondary',
        };
    }

    public function severityLabel(): string
    {
        return match ($this->severity) {
            self::SEVERITY_SIGNIFICANT => __('Significant'),
            self::SEVERITY_CONCERN => __('Concern'),
            default => __('Routine'),
        };
    }
}
