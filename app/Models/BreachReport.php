<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BreachReport extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'reported_by', 'agency_id', 'description', 'severity', 'action_taken',
        'reported_to_ico', 'evidence', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'reported_to_ico' => 'boolean',
        'evidence' => 'array',
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    /**
     * There's no dedicated status column on this table — "open" simply means
     * no action has been recorded against it yet.
     */
    public function isOpen(): bool
    {
        return empty($this->action_taken);
    }

    public function severityColor(): string
    {
        return match ($this->severity) {
            'critical', 'high' => 'danger',
            'medium' => 'amber',
            'low' => 'secondary',
            default => 'secondary',
        };
    }
}
