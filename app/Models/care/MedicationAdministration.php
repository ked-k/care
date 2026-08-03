<?php

namespace App\Models\care;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicationAdministration extends Model
{
    protected $table = 'medication_administrations';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'medication_id', 'administered_by', 'shift_id',
        'scheduled_time', 'actual_time', 'status', 'refusal_reason',
        'notes', 'witness_signature', 'photo_id', 'created_by', 'updated_by'
    ];

    protected $casts = [
        'id' => 'string',
        'medication_id' => 'string',
        'administered_by' => 'integer',
        'shift_id' => 'string',
        'scheduled_time' => 'datetime',
        'actual_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status constants
    const STATUS_GIVEN = 'given';
    const STATUS_REFUSED = 'refused';
    const STATUS_MISSED = 'missed';
    const STATUS_UNAVAILABLE = 'unavailable';
    const STATUS_PRN_GIVEN = 'prn_given';
    const STATUS_PRN_REFUSED = 'prn_refused';

    const STATUS_COLORS = [
        'given' => 'success',
        'refused' => 'danger',
        'missed' => 'secondary',
        'unavailable' => 'warning',
        'prn_given' => 'info',
        'prn_refused' => 'dark',
    ];

    const STATUS_ICONS = [
        'given' => 'bi-check-circle-fill',
        'refused' => 'bi-x-circle-fill',
        'missed' => 'bi-clock-fill',
        'unavailable' => 'bi-exclamation-triangle-fill',
        'prn_given' => 'bi-capsule',
        'prn_refused' => 'bi-slash-circle',
    ];

    // Relationships
    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class, 'medication_id');
    }

    public function administeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administered_by');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'photo_id');
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }

    public function getStatusIconAttribute(): string
    {
        return self::STATUS_ICONS[$this->status] ?? 'bi-question-circle';
    }

    public function getIsLateAttribute(): bool
    {
        if ($this->actual_time && $this->scheduled_time) {
            return $this->actual_time->diffInMinutes($this->scheduled_time) > 30;
        }
        return false;
    }

    // Scopes
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('scheduled_time', $date);
    }

    public function scopeForShift($query, $shiftId)
    {
        return $query->where('shift_id', $shiftId);
    }

    public function scopePending($query)
    {
        return $query->whereNull('actual_time');
    }
}
