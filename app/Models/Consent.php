<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consent extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    // Standard consent types from the vision doc — free text underneath, but
    // the UI only offers these so records stay comparable across service users.
    public const TYPES = [
        'information_sharing' => 'Consent to share information',
        'photography' => 'Consent for photographs',
        'family_updates' => 'Consent for family updates',
        'medication_communication' => 'Consent for medication-related communication',
        'professional_sharing' => 'Consent for professional information sharing',
    ];

    protected $fillable = [
        'service_user_id', 'consent_type', 'granted', 'granted_by',
        'granted_at', 'expires_at', 'revoked_at', 'notes',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'granted' => 'boolean',
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function serviceUser(): BelongsTo
    {
        return $this->belongsTo(ServiceUser::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function isActive(): bool
    {
        if (! $this->granted || $this->revoked_at) {
            return false;
        }

        return ! $this->expires_at || $this->expires_at->isFuture();
    }

    public function statusLabel(): string
    {
        if ($this->revoked_at) {
            return 'Revoked';
        }
        if (! $this->granted) {
            return 'Declined';
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return 'Expired';
        }
        return 'Active';
    }

    public function statusColor(): string
    {
        return match ($this->statusLabel()) {
            'Active' => 'success',
            'Expired' => 'amber',
            'Revoked' => 'danger',
            default => 'secondary',
        };
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->consent_type] ?? $this->consent_type;
    }
}
