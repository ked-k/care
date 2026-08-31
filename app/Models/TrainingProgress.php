<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingProgress extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    public const STATUS_STARTED = 'started';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'user_id', 'module_id', 'status', 'score', 'completed_at', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'score' => 'decimal:2',
    ];

    protected $attributes = [
        'status' => self::STATUS_STARTED,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(TrainingModule::class, 'module_id');
    }
}
