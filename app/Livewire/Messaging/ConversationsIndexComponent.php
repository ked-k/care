<?php

namespace App\Livewire\Messaging;

use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Batch 23: "My conversations" — every App\Models\Conversation the current
 * user is a real participant in (not just tagged as a reference-only
 * service user). One shared component for staff (routes/operational.php,
 * layouts.admin-layout or layouts.carer) and family (routes/family-portal.php,
 * layouts.family): access is "you're a participant", not a role check, so
 * the same query and the same view work for both — only the layout
 * returned by render() differs.
 */
class ConversationsIndexComponent extends Component
{
    public function render()
    {
        $user = Auth::user();

        $conversations = Conversation::whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
            ->orderByDesc('last_message_at')
            ->get()
            ->map(function (Conversation $conversation) use ($user) {
                $conversation->setAttribute('unread_count', $conversation->unreadCountFor($user));
                $conversation->setAttribute('other_participants', $conversation->userParticipants()->reject(fn ($u) => $u->id === $user->id)->values());
                $conversation->setAttribute('tagged_service_users', $conversation->taggedServiceUsers());

                return $conversation;
            });

        $view = view('livewire.messaging.conversations-index', compact('conversations'));

        if ($user->hasRole('Family')) {
            return $view->layout('layouts.family');
        }

        return $view->layout($user->isCarerOnly() ? 'layouts.carer' : 'layouts.admin-layout');
    }
}
