<?php

namespace App\Livewire\Messaging;

use App\Models\Message;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Backs the shared header's chat drawer (see include/chat.blade.php), which
 * previously held only hardcoded demo contacts/messages. Real staff
 * colleagues (same agency, excluding Family-role accounts, who never reach
 * this layout anyway) and real Message rows. Polling, not push — no
 * websocket/broadcast layer is configured in this app.
 */
class ChatDrawerComponent extends Component
{
    public string $search = '';
    public ?int $activeUserId = null;
    public string $draft = '';

    public function openThread(int $userId): void
    {
        $this->activeUserId = $userId;
        $this->draft = '';

        Message::where('sender_id', $userId)
            ->where('receiver_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function closeThread(): void
    {
        $this->activeUserId = null;
    }

    public function send(): void
    {
        $text = trim($this->draft);

        if ($text === '' || ! $this->activeUserId) {
            return;
        }

        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $this->activeUserId,
            'message' => $text,
            // See App\Models\Message: no real encryption is implemented, so
            // this is explicitly false rather than trusting the column's
            // default (which is true on the schema).
            'encrypted' => false,
        ]);

        NotificationService::send(
            userId: $this->activeUserId,
            type: 'new_message',
            title: 'New message',
            message: Auth::user()->name.': '.Str::limit($text, 80),
            data: ['message_id' => $message->id, 'sender_id' => Auth::id()],
        );

        $this->draft = '';
    }

    public function render()
    {
        $agencyId = Auth::user()->agency_id;
        $myId = Auth::id();

        $contacts = User::where('agency_id', $agencyId)
            ->where('id', '!=', $myId)
            ->where('is_active', true)
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'Family'))
            ->orderBy('name')
            ->get()
            ->map(function (User $u) use ($myId) {
                $last = Message::where(function ($q) use ($u, $myId) {
                    $q->where('sender_id', $myId)->where('receiver_id', $u->id);
                })->orWhere(function ($q) use ($u, $myId) {
                    $q->where('sender_id', $u->id)->where('receiver_id', $myId);
                })->latest('created_at')->first();

                $unread = Message::where('sender_id', $u->id)
                    ->where('receiver_id', $myId)
                    ->whereNull('read_at')
                    ->count();

                $u->setAttribute('last_message', $last);
                $u->setAttribute('unread_count', $unread);

                return $u;
            })
            ->when($this->search !== '', fn ($c) => $c->filter(
                fn (User $u) => str_contains(strtolower($u->name), strtolower($this->search))
            ))
            ->sortByDesc(fn (User $u) => $u->last_message?->created_at ?? $u->created_at)
            ->values();

        $activeContact = null;
        $thread = collect();

        if ($this->activeUserId) {
            $activeContact = User::find($this->activeUserId);

            $thread = Message::where(function ($q) use ($myId) {
                $q->where('sender_id', $myId)->where('receiver_id', $this->activeUserId);
            })->orWhere(function ($q) use ($myId) {
                $q->where('sender_id', $this->activeUserId)->where('receiver_id', $myId);
            })->orderBy('created_at')->get();
        }

        return view('livewire.messaging.chat-drawer', compact('contacts', 'activeContact', 'thread'));
    }
}
