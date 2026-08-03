<?php

namespace App\Models\care;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitCheckin extends Model
{
    protected $table = 'visit_checkins';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'shift_id', 'user_id', 'checkin_method', 'checkin_time',
        'checkout_time', 'latitude', 'longitude', 'qr_code_scanned',
        'otp_used', 'location_verified', 'distance_from_location',
        'deviation_reason', 'device_info', 'created_by', 'updated_by'
    ];

    protected $casts = [
        'id' => 'string',
        'shift_id' => 'string',
        'user_id' => 'integer',
        'checkin_time' => 'datetime',
        'checkout_time' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'distance_from_location' => 'decimal:2',
        'location_verified' => 'boolean',
        'device_info' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const METHODS = [
        'gps' => 'GPS Location',
        'qr_code' => 'QR Code Scan',
        'otp' => 'One-Time PIN',
        'manual' => 'Manual Entry',
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getDurationAttribute(): ?float
    {
        if ($this->checkin_time && $this->checkout_time) {
            return $this->checkout_time->diffInMinutes($this->checkin_time) / 60;
        }
        return null;
    }

    public function getMethodLabelAttribute(): string
    {
        return self::METHODS[$this->checkin_method] ?? $this->checkin_method;
    }
}
