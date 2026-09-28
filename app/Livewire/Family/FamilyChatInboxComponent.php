<?php

namespace App\Livewire\Family;

use App\Models\ChatSession;
use App\Models\FamilyMember;
use App\Models\Message;
use App\Models\ServiceUser;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Staff-side counterpart to FamilyChatComponent: lets whoever can manage
 * this service user's family access (the same gate
 * FamilyMemberManagerComponent::canManage() uses) read and reply to that
 * family's chat sessions. A page per service user, reached the same way
 * "Family" access itself already is (a link on the Service Users list) —
 * not one combined inbox across every service user in the agency.
 *
 * Viewing this page, not just replying, is gated behind canManage() —
 * stricter than FamilyMemberManagerComponent, where any staff member can
 * see who's linked (just not edit it). Conversation content is more
 * sensitive than a name-and-relationship list, so it's worth the extra
 * restriction.
 */
class FamilyChatInboxComponent extends Component
{
    public string $serviceUserId;

    public ?string $activeSessionId = null;
    public string $draft = '';
    public string $statusFilter = 'open';

    public function mount(string $serviceUserId): void
    {
        $this->serviceUserId = $serviceUserId;
        $this->authorizeManage();
    }

    protected function serviceUser(): ServiceUser
    {
        return ServiceUser::where('agency_id', Auth::user()->agency_id)->findOrFail($this->serviceUserId);
    }

    public function canManage(): bool
    {
        $user = Auth::user();

        return $user->can('family.manage') || $user->hasRole(['Admin', 'Super Admin']);
    }

    protected function authorizeManage(): void
    {
        abort_unless($this->canManage(), 403, 'Only a manager can view family messages.');
    }

    protected function session(string $sessionId): ChatSession
    {
        return ChatSession::where('service_user_id', $this->serviceUserId)->findOrFail($sessionId);
    }

    public function openSession(string $sessionId): void
    {
        $session = $this->session($sessionId);
        $session->markReadByStaff();
        $this->activeSessionId = $sessionId;
        $this->draft = '';
    }

    public function closeThread(): void
    {
        $this->activeSessionId = null;
    }

    public function send(): void
    {
        $this->authorizeManage();

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

        $session->update(['last_message_at' => now()]);

        $this->notifyFamily($session, $text);

        $this->draft = '';
    }

    public function toggleStatus(string $sessionId): void
    {
        $this->authorizeManage();

        $session = $this->session($sessionId);
        $session->update(['status' => $session->isOpen() ? ChatSession::STATUS_CLOSED : ChatSession::STATUS_OPEN]);

        if ($this->activeSessionId === $sessionId && $session->status === ChatSession::STATUS_CLOSED) {
            $this->activeSessionId = null;
        }
    }

    protected function notifyFamily(ChatSession $session, string $text): void
    {
        $familyUserIds = FamilyMember::where('service_user_id', $session->service_user_id)->pluck('user_id');

        foreach ($familyUserIds as $userId) {
            NotificationService::send(
                userId: $userId,
                type: 'family_message',
                title: 'New message from the care team',
                message: Auth::user()->name.': '.Str::limit($text, 80),
                data: ['chat_session_id' => $session->id, 'service_user_id' => $session->service_user_id],
            );
        }
    }

    public function render()
    {
        $serviceUser = $this->serviceUser();

        $sessions = ChatSession::where('service_user_id', $this->serviceUserId)
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByDesc('last_message_at')
            ->get()
            ->map(function (ChatSession $session) {
                $session->setAttribute('unread_count', $session->unreadCountForStaff());

                return $session;
            });

        $activeSession = $this->activeSessionId
            ? ChatSession::where('service_user_id', $this->serviceUserId)->find($this->activeSessionId)
            : null;

        $thread = $activeSession
            ? $activeSession->messages()->with('sender')->get()
            : collect();

        return view('livewire.family.family-chat-inbox', [
            'serviceUser' => $serviceUser,
            'sessions' => $sessions,
            'activeSession' => $activeSession,
            'thread' => $thread,
        ]);
    }
}
