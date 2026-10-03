<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicationAdministration extends Model
{
    use HasUuids;

    // See App\Models\TaskLog's version of this comment — same UUID-PK gap,
    // harmless so far only because nothing read ->id immediately after
    // MarChartComponent::recordAdministration()'s create() call.
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'medication_id', 'administered_by', 'shift_id', 'shift_takeover_id', 'scheduled_time', 'actual_time',
        'status', 'refusal_reason', 'notes', 'witness_signature', 'photo_id',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'scheduled_time' => 'datetime',
        'actual_time' => 'datetime',
    ];

    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }

    public function administeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administered_by');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'photo_id');
    }

    public function takeover(): BelongsTo
    {
        return $this->belongsTo(ShiftTakeover::class, 'shift_takeover_id');
    }

    public function wasEnteredByProxy(): bool
    {
        return $this->shift_takeover_id !== null;
    }
}
