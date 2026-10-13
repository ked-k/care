<?php

namespace App\Livewire\Messaging;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\ServiceUser;
use App\Models\Shift;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Batch 23: the floating "New conversation" button available to
 * Admin/Manager/Carer/Super Admin (see App\Models\User::canStartConversations())
 * — Family does not get this button, though a family member can be picked
 * as a participant by someone who does. Opens a picker (staff colleagues,
 * family members/clients, and service users tagged for reference only),
 * then creates a App\Models\Conversation that can freely mix staff and
 * family in one thread, unlike the existing two-sided ChatSession model.
 *
 * Embedded from layouts.admin-layout and layouts.carer, each guarding the
 * @livewire call itself with canStartConversations() — mount() re-checks
 * since a Livewire component is still a reachable endpoint on its own.
 */
class NewConversationComponent extends Component
{
    public string $search = '';
    public array $selectedStaffIds = [];
    public array $selectedFamilyUserIds = [];
    public array $selectedServiceUserTagIds = [];
    public string $subject = '';
    public string $firstMessage = '';

    public function mount(): void
    {
        abort_unless(Auth::user()->canStartConversations(), 403);
    }

    protected function assignedServiceUserIds()
    {
        return Shift::where('assigned_to', Auth::id())->distinct()->pluck('service_user_id');
    }

    public function startConversation(): void
    {
        abort_unless(Auth::user()->canStartConversations(), 403);

        $this->validate([
            'firstMessage' => 'required|string|max:2000',
            'subject' => 'nullable|string|max:150',
        ]);

        $staffIds = array_values(array_unique($this->selectedStaffIds));
        $familyIds = array_values(array_unique($this->selectedFamilyUserIds));
        $serviceUserTagIds = array_values(array_unique($this->selectedServiceUserTagIds));

        if (empty($staffIds) && empty($familyIds)) {
            $this->addError('participants', __('Select at least one person to message.'));

            return;
        }

        $user = Auth::user();

        $conversation = DB::transaction(function () use ($user, $staffIds, $familyIds, $serviceUserTagIds) {
            $conversation = Conversation::create([
                'agency_id' => $user->agency_id,
                'created_by' => $user->id,
                'subject' => $this->subject !== '' ? $this->subject : null,
                'last_message_at' => now(),
            ]);

            $participantUserIds = array_unique(array_merge([(string) $user->id], $staffIds, $familyIds));

            foreach ($participantUserIds as $participantUserId) {
                ConversationParticipant::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $participantUserId,
                ]);
            }

            foreach ($serviceUserTagIds as $serviceUserId) {
                ConversationParticipant::create([
                    'conversation_id' => $conversation->id,
                    'service_user_id' => $serviceUserId,
                ]);
            }

            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'message' => $this->firstMessage,
                'encrypted' => false,
            ]);

            return $conversation;
        });

        $this->notifyParticipants($conversation, $user, $this->firstMessage);

        $this->reset(['search', 'selectedStaffIds', 'selectedFamilyUserIds', 'selectedServiceUserTagIds', 'subject', 'firstMessage']);
        $this->dispatch('close-modal', 'new-conversation');
        $this->redirect(route('conversations.show', $conversation->id), navigate: true);
    }

    protected function notifyParticipants(Conversation $conversation, User $sender, string $text): void
    {
        foreach ($conversation->userParticipants() as $participant) {
            if ((int) $participant->id === (int) $sender->id) {
                continue;
            }

            NotificationService::send(
                userId: $participant->id,
                type: 'conversation_message',
                title: $conversation->subject ?: 'New conversation',
                message: $sender->name.': '.Str::limit($text, 80),
                data: ['conversation_id' => $conversation->id],
            );
        }
    }

    public function render()
    {
        $user = Auth::user();
        $agencyId = $user->agency_id;
        $isCarer = $user->isCarerOnly();

        $staff = User::where('agency_id', $agencyId)
            ->where('id', '!=', $user->id)
            ->where('is_active', true)
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'Family'))
            ->orderBy('name')
            ->get();

        $familyQuery = User::where('agency_id', $agencyId)
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('name', 'Family'));

        $serviceUserQuery = ServiceUser::where('agency_id', $agencyId);

        // A plain carer only gets to pick from (and tag) the people they're
        // actually assigned to — same restriction CarerMessagesComponent
        // already applies to its own, narrower family messaging. Admin/
        // Manager/Super Admin keep the full agency roster, matching how
        // much they can already see on the Service Users list.
        if ($isCarer) {
            $assignedIds = $this->assignedServiceUserIds();
            $familyQuery->whereHas('familyLinks', fn ($q) => $q->whereIn('service_user_id', $assignedIds));
            $serviceUserQuery->whereIn('id', $assignedIds);
        }

        $family = $familyQuery->orderBy('name')->get();
        $serviceUsers = $serviceUserQuery->orderBy('name')->get();

        if ($this->search !== '') {
            $needle = strtolower($this->search);
            $staff = $staff->filter(fn (User $u) => str_contains(strtolower($u->name), $needle))->values();
            $family = $family->filter(fn (User $u) => str_contains(strtolower($u->name), $needle))->values();
            $serviceUsers = $serviceUsers->filter(fn (ServiceUser $su) => str_contains(strtolower($su->name), $needle))->values();
        }

        return view('livewire.messaging.new-conversation', [
            'staff' => $staff,
            'family' => $family,
            'serviceUsers' => $serviceUsers,
        ]);
    }
}
