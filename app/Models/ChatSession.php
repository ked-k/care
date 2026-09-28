<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A named conversation thread between a service user's family and their
 * care team (staff), grouping Message rows the same way an email thread or
 * support ticket does. Deliberately two-sided rather than one-to-one: any
 * family member linked to the service user can read/reply to any session
 * for that person (a shared family inbox), and any staff member who can
 * manage that service user's family access can do the same on the other
 * side (a shared staff inbox) — mirroring how FamilyMemberManagerComponent
 * already treats "family access" as a property of the service user, not
 * of one individual family member.
 *
 * Message.read_at is reused here to mean "seen by the other side" rather
 * than "seen by the receiver" — there's no longer a single receiver once a
 * message belongs to a session — see markReadByFamily()/markReadByStaff().
 */
class ChatSession extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'service_user_id', 'agency_id', 'opened_by', 'subject', 'status', 'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_OPEN,
    ];

    public function serviceUser(): BelongsTo
    {
        return $this->belongsTo(ServiceUser::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function unreadCountForStaff(): int
    {
        return $this->messages()
            ->whereNull('read_at')
            ->whereHas('sender.roles', fn ($q) => $q->where('name', 'Family'))
            ->count();
    }

    public function unreadCountForFamily(): int
    {
        return $this->messages()
            ->whereNull('read_at')
            ->whereDoesntHave('sender.roles', fn ($q) => $q->where('name', 'Family'))
            ->count();
    }

    public function markReadByStaff(): void
    {
        $this->messages()
            ->whereNull('read_at')
            ->whereHas('sender.roles', fn ($q) => $q->where('name', 'Family'))
            ->update(['read_at' => now()]);
    }

    public function markReadByFamily(): void
    {
        $this->messages()
            ->whereNull('read_at')
            ->whereDoesntHave('sender.roles', fn ($q) => $q->where('name', 'Family'))
            ->update(['read_at' => now()]);
    }
}
