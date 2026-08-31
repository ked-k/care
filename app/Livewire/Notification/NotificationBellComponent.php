<?php

namespace App\Livewire\Notification;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Small widget embedded in the shared header (see include/header.blade.php),
 * replacing the template's hardcoded demo notification dropdown. Polls
 * every 20s for a near-live unread count — there's no websocket/broadcast
 * layer (Reverb/Pusher) configured in this app, so this is deliberately
 * poll-based rather than push-based.
 */
class NotificationBellComponent extends Component
{
    public function markRead(string $notificationId): void
    {
        Notification::where('id', $notificationId)->where('user_id', Auth::id())->update(['read_at' => now()]);
    }

    public function markAllRead(): void
    {
        Notification::where('user_id', Auth::id())->whereNull('read_at')->update(['read_at' => now()]);
    }

    public function render()
    {
        $userId = Auth::id();

        $recent = Notification::where('user_id', $userId)->orderByDesc('created_at')->limit(6)->get();
        $unreadCount = Notification::where('user_id', $userId)->whereNull('read_at')->count();

        return view('livewire.notification.notification-bell', compact('recent', 'unreadCount'));
    }
}
