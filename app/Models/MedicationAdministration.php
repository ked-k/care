<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicationAdministration extends Model
{
    use HasUuids;

    protected $fillable = [
        'medication_id', 'administered_by', 'shift_id', 'scheduled_time', 'actual_time',
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
}
