<?php

namespace App\Livewire\Family;

use App\Models\ChatSession;
use App\Models\Message;
use App\Models\ServiceUser;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Nested inside the family portal's "Messages" tab (family-service-user
 * component). Lets a family member start a new chat session with the care
 * team and reply within an existing one. Sessions for a service user are
 * shared across every family member linked to that person (not private to
 * whoever opened it), mirroring how family access itself is already scoped
 * to the service user rather than to one individual login.
 *
 * A separate, self-contained Livewire component rather than folding this
 * into FamilyServiceUserComponent directly — it's mounted inside an
 * `x-show` panel (not `@if`), so it's present in the DOM from first load
 * the same as the other four tabs, but keeps its own state and polling
 * without bloating the parent component.
 */
class FamilyChatComponent extends Component
{
    public string $serviceUserId;

    public ?string $activeSessionId = null;
    public string $draft = '';

    public bool $showNewForm = false;
    public string $newSubject = '';
    public string $newMessage = '';

    public function mount(string $serviceUserId): void
    {
        $this->serviceUserId = $serviceUserId;

        abort_unless(
            Auth::user()->familyLinks()->where('service_user_id', $serviceUserId)->exists(),
            403,
            "You don't have access to this person's record."
        );
    }

    protected function serviceUser(): ServiceUser
    {
        return ServiceUser::findOrFail($this->serviceUserId);
    }

    protected function session(string $sessionId): ChatSession
    {
        return ChatSession::where('service_user_id', $this->serviceUserId)->findOrFail($sessionId);
    }

    public function openNewForm(): void
    {
        $this->showNewForm = true;
        $this->newSubject = '';
        $this->newMessage = '';
        $this->resetErrorBag();
    }

    public function cancelNewForm(): void
    {
        $this->showNewForm = false;
    }

    public function startSession(): void
    {
        $this->validate([
            'newSubject' => 'required|string|max:150',
            'newMessage' => 'required|string|max:2000',
        ]);

        $serviceUser = $this->serviceUser();

        $session = ChatSession::create([
            'service_user_id' => $serviceUser->id,
            'agency_id' => $serviceUser->agency_id,
            'opened_by' => Auth::id(),
            'subject' => $this->newSubject,
            'status' => ChatSession::STATUS_OPEN,
            'last_message_at' => now(),
        ]);

        Message::create([
            'chat_session_id' => $session->id,
            'sender_id' => Auth::id(),
            'message' => $this->newMessage,
            'encrypted' => false,
        ]);

        $this->notifyStaff($session, $this->newMessage);

        $this->showNewForm = false;
        $this->newSubject = '';
        $this->newMessage = '';
        $this->openSession($session->id);
    }

    public function openSession(string $sessionId): void
    {
        $session = $this->session($sessionId);
        $session->markReadByFamily();
        $this->activeSessionId = $sessionId;
        $this->draft = '';
    }

    public function closeSession(): void
    {
        $this->activeSessionId = null;
    }

    public function send(): void
    {
        $text = trim($this->draft);

        if ($text === '' || ! $this->activeSessionId) {
            return;
        }

        $session = $this->session($this->activeSessionId);

        Message::create([
            'chat_session_id' => $session->id,
            'sender_id' => Auth::id(),
            'message' => $text,
            'encrypted' => false,
        ]);

        // A family reply re-opens a session staff had marked resolved —
        // "resolved" is staff saying "nothing more to do for now", not
        // "this family can't message again".
        $session->update(['status' => ChatSession::STATUS_OPEN, 'last_message_at' => now()]);

        $this->notifyStaff($session, $text);

        $this->draft = '';
    }

    /**
     * Notifies the staff who can act on family messages for this agency —
     * exactly the population FamilyMemberManagerComponent::canManage()
     * covers (Admin/Super Admin, or anyone individually granted the
     * family.manage permission), evaluated per user since we need every
     * matching person, not just whichever one is logged in.
     */
    protected function notifyStaff(ChatSession $session, string $text): void
    {
        $serviceUser = $session->serviceUser ?? $this->serviceUser();

        $staff = User::where('agency_id', $serviceUser->agency_id)
            ->where('is_active', true)
            ->get()
            ->filter(fn (User $u) => $u->can('family.manage') || $u->hasRole(['Admin', 'Super Admin']));

        foreach ($staff as $member) {
            NotificationService::send(
                userId: $member->id,
                type: 'family_message',
                title: 'New family message',
                message: Auth::user()->name.' ('.$serviceUser->name.'): '.Str::limit($text, 80),
                data: ['chat_session_id' => $session->id, 'service_user_id' => $serviceUser->id],
            );
        }
    }

    public function render()
    {
        $sessions = ChatSession::where('service_user_id', $this->serviceUserId)
            ->orderByDesc('last_message_at')
            ->get()
            ->map(function (ChatSession $session) {
                $session->setAttribute('unread_count', $session->unreadCountForFamily());

                return $session;
            });

        $activeSession = $this->activeSessionId ? $sessions->firstWhere('id', $this->activeSessionId) : null;

        $thread = $activeSession
            ? $activeSession->messages()->with('sender')->get()
            : collect();

        return view('livewire.family.family-chat', compact('sessions', 'activeSession', 'thread'));
    }
}
