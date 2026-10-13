<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Batch 18: a date-range window during which a carer shouldn't be
 * scheduled — leave, sickness, or any other reason. Deliberately simple
 * (a start date and an end date, inclusive) rather than a recurring
 * weekly pattern. App\Livewire\Rota\RotaBuilder checks against this when
 * assigning a carer and warns, but doesn't block — a manager sometimes
 * knows better than the record (e.g. the carer agreed to come in anyway).
 */
class CarerUnavailability extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'agency_id', 'user_id', 'start_date', 'end_date', 'reason',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function coversDate(\Carbon\Carbon|string $date): bool
    {
        $date = \Carbon\Carbon::parse($date)->toDateString();

        return $date >= $this->start_date->toDateString() && $date <= $this->end_date->toDateString();
    }
}
