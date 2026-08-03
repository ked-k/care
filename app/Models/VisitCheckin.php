<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitCheckin extends Model
{
    use HasUuids;

    protected $fillable = [
        'shift_id', 'user_id', 'checkin_method', 'checkin_time', 'checkout_time',
        'latitude', 'longitude', 'qr_code_scanned', 'otp_used', 'location_verified',
        'distance_from_location', 'deviation_reason', 'device_info',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'checkin_time' => 'datetime',
        'checkout_time' => 'datetime',
        'qr_code_scanned' => 'boolean',
        'otp_used' => 'boolean',
        'location_verified' => 'boolean',
        'device_info' => 'array',
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
