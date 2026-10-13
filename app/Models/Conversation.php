<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Batch 23: a free-form conversation thread that can mix any combination
 * of staff and family participants, plus any number of service users
 * tagged for reference only (see ConversationParticipant). Started from the
 * floating "New conversation" button (App\Livewire\Messaging\NewConversationComponent).
 * Deliberately a new, separate model rather than a rework of ChatSession —
 * existing per-service-user family<->staff chat keeps working exactly as
 * it did before this batch.
 */
class Conversation extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['agency_id', 'created_by', 'subject', 'last_message_at'];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    /** Real (user_id-backed) participants only — not the reference-only service-user tags below. */
    public function userParticipants()
    {
        return $this->participants()->whereNotNull('user_id')->with('user')
            ->get()->pluck('user')->filter()->values();
    }

    /** Service users tagged for reference only — see the migration's doc comment. */
    public function taggedServiceUsers()
    {
        return $this->participants()->whereNotNull('service_user_id')->with('serviceUser')
            ->get()->pluck('serviceUser')->filter()->values();
    }

    public function isParticipant(User $user): bool
    {
        return $this->participants()->where('user_id', $user->id)->exists();
    }

    public function participantFor(User $user): ?ConversationParticipant
    {
        return $this->participants()->where('user_id', $user->id)->first();
    }

    /**
     * Unread for this user: other people's messages sent after their own
     * last_read_at (or all of them, if they've never opened the thread).
     */
    public function unreadCountFor(User $user): int
    {
        $participant = $this->participantFor($user);

        if (! $participant) {
            return 0;
        }

        return $this->messages()
            ->where('sender_id', '!=', $user->id)
            ->when($participant->last_read_at, fn ($q) => $q->where('created_at', '>', $participant->last_read_at))
            ->count();
    }

    public function markReadBy(User $user): void
    {
        $this->participantFor($user)?->update(['last_read_at' => now()]);
    }
}
