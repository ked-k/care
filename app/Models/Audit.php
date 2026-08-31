<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single append-only audit record. Written by App\Services\AuditLogger —
 * never update an existing row. The table has no updated_at column, hence
 * disabling it below rather than the usual $timestamps = false (created_at
 * still needs to be settable/readable).
 */
class Audit extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'entity_type', 'entity_id', 'action', 'metadata',
        'ip_address', 'user_agent', 'session_id', 'device_info',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actionLabel(): string
    {
        return ucfirst(strtolower(str_replace('_', ' ', $this->action)));
    }

    public function entityLabel(): string
    {
        if (! $this->entity_type) {
            return '—';
        }
        return class_basename($this->entity_type);
    }
}
