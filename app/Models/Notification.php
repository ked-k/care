<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single in-app notification for one user. This is a plain custom model,
 * deliberately NOT Laravel's built-in notifications system — the `notifications`
 * table here has its own shape (user_id, title, message, priority,
 * action_taken_at) rather than the framework's notifiable-morph/data-blob
 * convention, so `Illuminate\Notifications\DatabaseNotification` doesn't fit
 * it. `User` already has the `Notifiable` trait (for a future/unused
 * notification channel), which defines its own `notifications()` relation
 * against that different class — to avoid colliding with it, the relation
 * to this model on User is named `appNotifications()`, not `notifications()`.
 */
class Notification extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';

    protected $fillable = [
        'user_id', 'type', 'title', 'message', 'priority', 'data', 'read_at', 'action_taken_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'action_taken_at' => 'datetime',
    ];

    protected $attributes = [
        'priority' => self::PRIORITY_NORMAL,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markRead(): void
    {
        if (! $this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    public function priorityColor(): string
    {
        return match ($this->priority) {
            'high' => 'danger',
            'low' => 'secondary',
            default => 'primary',
        };
    }

    public function icon(): string
    {
        return match ($this->type) {
            'shift_assigned' => 'ik ik-calendar',
            'safeguarding_escalated' => 'ik ik-shield',
            'new_message' => 'ik ik-message-square',
            default => 'ik ik-bell',
        };
    }
}
