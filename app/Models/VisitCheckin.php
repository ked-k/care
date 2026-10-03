<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Previously schema-only — nothing in the app ever created a VisitCheckin
 * row. App\Livewire\Rota\ShiftVisitComponent is the first real writer,
 * covering the simplest case (a manual check-in/out button) rather than
 * the GPS/QR/OTP methods the column comments on checkin_method originally
 * anticipated; those remain possible future values of that same plain
 * string column.
 */
class VisitCheckin extends Model
{
    use HasUuids;

    // See App\Models\TaskLog's version of this comment — visit_checkins.id
    // is a UUID primary key, and without this, Eloquent's post-insert
    // getIncrementing() path would try to overwrite it with a meaningless
    // lastInsertId() value. Never triggered before since nothing created
    // one of these rows until now.
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'shift_id', 'shift_takeover_id', 'user_id', 'checkin_method', 'checkin_time', 'checkout_time',
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

    public function takeover(): BelongsTo
    {
        return $this->belongsTo(ShiftTakeover::class, 'shift_takeover_id');
    }

    public function isOpen(): bool
    {
        return $this->checkout_time === null;
    }
}
