<?php

namespace App\Livewire\Notification;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationCenterComponent extends Component
{
    use WithPagination;

    public string $filter = 'all'; // all|unread

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function markRead(string $notificationId): void
    {
        Notification::where('id', $notificationId)->where('user_id', Auth::id())->update(['read_at' => now()]);
    }

    public function markAllRead(): void
    {
        Notification::where('user_id', Auth::id())->whereNull('read_at')->update(['read_at' => now()]);
        $this->dispatch('toast', message: 'All notifications marked as read.', type: 'success');
    }

    public function render()
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->when($this->filter === 'unread', fn ($q) => $q->whereNull('read_at'))
            ->orderByDesc('created_at')
            ->paginate(20);

        // Batch 12: mobile carer shell for a plain carer, same as Dashboard
        // and Profile — see App\Models\User::isCarerOnly(). This list was
        // already scoped to Auth::id() only, so the content needs no change.
        return view('livewire.notification.notification-center', compact('notifications'))
            ->layout(Auth::user()->isCarerOnly() ? 'layouts.carer' : 'layouts.admin-layout');
    }
}
