<?php

namespace App\Livewire\Messaging;

use App\Models\Conversation;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Batch 23: the thread view for one App\Models\Conversation. Shared by
 * staff and family the same way ConversationsIndexComponent is — access is
 * "you're a participant" (or Super Admin, mirroring the bypass
 * User::canAccessServiceUser() already uses), not a role check, since a
 * conversation's whole point is an explicitly chosen guest list rather
 * than an agency-wide or service-user-wide audience.
 */
class ConversationShowComponent extends Component
{
    public string $conversationId;
    public string $draft = '';

    public function mount(string $conversationId): void
    {
        $this->conversationId = $conversationId;

        $conversation = Conversation::findOrFail($conversationId);
        $user = Auth::user();

        abort_unless(
            $conversation->isParticipant($user) || $user->hasRole('Super Admin'),
            403,
            __("You're not part of this conversation.")
        );

        $conversation->markReadBy($user);
    }

    protected function conversation(): Conversation
    {
        return Conversation::findOrFail($this->conversationId);
    }

    public function send(): void
    {
        $text = trim($this->draft);

        if ($text === '') {
            return;
        }

        $conversation = $this->conversation();
        $user = Auth::user();

        abort_unless($conversation->isParticipant($user) || $user->hasRole('Super Admin'), 403);

        $conversation->messages()->create([
            'sender_id' => $user->id,
            'message' => $text,
            'encrypted' => false,
        ]);

        $conversation->update(['last_message_at' => now()]);
        $conversation->markReadBy($user);

        foreach ($conversation->userParticipants() as $participant) {
            if ((int) $participant->id === (int) $user->id) {
                continue;
            }

            NotificationService::send(
                userId: $participant->id,
                type: 'conversation_message',
                title: $conversation->subject ?: 'New message',
                message: $user->name.': '.Str::limit($text, 80),
                data: ['conversation_id' => $conversation->id],
            );
        }

        $this->draft = '';
    }

    public function render()
    {
        $user = Auth::user();
        $conversation = $this->conversation();

        $view = view('livewire.messaging.conversation-show', [
            'conversation' => $conversation,
            'otherParticipants' => $conversation->userParticipants()->reject(fn ($u) => $u->id === $user->id)->values(),
            'taggedServiceUsers' => $conversation->taggedServiceUsers(),
            'thread' => $conversation->messages()->with('sender')->get(),
        ]);

        if ($user->hasRole('Family')) {
            return $view->layout('layouts.family');
        }

        return $view->layout($user->isCarerOnly() ? 'layouts.carer' : 'layouts.admin-layout');
    }
}
