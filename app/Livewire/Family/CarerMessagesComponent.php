<?php

namespace App\Livewire\Family;

use App\Models\ChatSession;
use App\Models\FamilyMember;
use App\Models\Message;
use App\Models\ServiceUser;
use App\Models\Shift;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Batch 13: "messaging the next of kin" for carers. A carer-only
 * counterpart to FamilyChatInboxComponent (the Admin/Manager "Messages"
 * page reached from the Service Users list) and to FamilyChatComponent
 * (the family-portal side) — all three share the same ChatSession/Message
 * data, just with different audiences and access rules:
 *
 *  - FamilyChatInboxComponent: gated to family.manage / Admin / Super Admin,
 *    any service user in the agency.
 *  - FamilyChatComponent: gated to a family member's own linked service
 *    users.
 *  - This component: gated to service users the carer is actually
 *    assigned to (at least one Shift with assigned_to = them) — carers
 *    don't get FamilyChatInboxComponent's agency-wide reach, only the
 *    people they actually look after.
 *
 * Picker-then-detail, same shape as CarerQuickNoteComponent: no service
 * user in the route, just a list of the carer's assigned people (with an
 * unread badge), then the session list + thread for whichever one they
 * tap. Carers can also start a new conversation, which
 * FamilyChatInboxComponent's staff view doesn't offer — a carer is often
 * the one who needs to reach out first ("mum had a lovely afternoon"),
 * not just reply.
 */
class CarerMessagesComponent extends Component
{
    public ?string $serviceUserId = null;
    public ?string $activeSessionId = null;
    public string $draft = '';

    public bool $showNewForm = false;
    public string $newSubject = '';
    public string $newMessage = '';

    public function mount(): void
    {
        abort_unless(Auth::user()->isCarerOnly(), 403, __('This page is for carers.'));
    }

    protected function assignedServiceUserIds()
    {
        return Shift::where('assigned_to', Auth::id())->distinct()->pluck('service_user_id');
    }

    protected function isAssigned(string $serviceUserId): bool
    {
        return Shift::where('assigned_to', Auth::id())->where('service_user_id', $serviceUserId)->exists();
    }

    public function selectServiceUser(string $serviceUserId): void
    {
        abort_unless($this->isAssigned($serviceUserId), 403, __("You're not assigned to this person."));

        $this->serviceUserId = $serviceUserId;
        $this->activeSessionId = null;
        $this->showNewForm = false;
    }

    public function backToList(): void
    {
        $this->serviceUserId = null;
        $this->activeSessionId = null;
        $this->showNewForm = false;
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
        abort_unless($this->serviceUserId && $this->isAssigned($this->serviceUserId), 403, __("You're not assigned to this person."));

        $this->validate([
            'newSubject' => 'required|string|max:150',
            'newMessage' => 'required|string|max:2000',
        ]);

        $serviceUser = ServiceUser::findOrFail($this->serviceUserId);

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

        $this->notifyFamily($session, $this->newMessage);

        $this->showNewForm = false;
        $this->newSubject = '';
        $this->newMessage = '';
        $this->openSession($session->id);
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

        $session->update(['status' => ChatSession::STATUS_OPEN, 'last_message_at' => now()]);

        $this->notifyFamily($session, $text);

        $this->draft = '';
    }

    public function toggleStatus(string $sessionId): void
    {
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
        $serviceUsers = ServiceUser::whereIn('id', $this->assignedServiceUserIds())
            ->orderBy('name')
            ->get()
            ->map(function (ServiceUser $su) {
                $su->setAttribute(
                    'unread_count',
                    ChatSession::where('service_user_id', $su->id)->get()->sum(fn (ChatSession $s) => $s->unreadCountForStaff())
                );

                return $su;
            });

        $serviceUser = $this->serviceUserId ? $serviceUsers->firstWhere('id', $this->serviceUserId) : null;

        $sessions = $serviceUser
            ? ChatSession::where('service_user_id', $this->serviceUserId)
                ->orderByDesc('last_message_at')
                ->get()
                ->map(function (ChatSession $session) {
                    $session->setAttribute('unread_count', $session->unreadCountForStaff());

                    return $session;
                })
            : collect();

        $activeSession = $this->activeSessionId ? $sessions->firstWhere('id', $this->activeSessionId) : null;

        $thread = $activeSession ? $activeSession->messages()->with('sender')->get() : collect();

        return view('livewire.family.carer-messages', [
            'serviceUsers' => $serviceUsers,
            'serviceUser' => $serviceUser,
            'sessions' => $sessions,
            'activeSession' => $activeSession,
            'thread' => $thread,
        ])->layout('layouts.carer');
    }
}
