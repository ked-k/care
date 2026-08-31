<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolicyAcknowledgment extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['policy_id', 'user_id', 'acknowledged_at', 'ip_address', 'created_by', 'updated_by'];

    protected $casts = [
        'acknowledged_at' => 'datetime',
    ];

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
