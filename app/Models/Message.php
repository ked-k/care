<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single message. Originally a flat direct message between two staff
 * members (sender_id/receiver_id); Batch 10 extends it to also carry
 * messages that belong to a ChatSession (a family <-> staff conversation
 * about one service user) instead of a single receiver — for those,
 * chat_session_id is set and receiver_id is left null, since a session can
 * have more than one person on each side. The schema also has an
 * `encrypted` flag, presumably intended for a future end-to-end-encryption
 * feature — no such encryption is implemented here, so callers should
 * write `false` rather than let it default to a claim that isn't true.
 */
class Message extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['chat_session_id', 'sender_id', 'receiver_id', 'message', 'encrypted', 'attachment_id', 'read_at'];

    protected $casts = [
        'encrypted' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'attachment_id');
    }

    public function chatSession(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
