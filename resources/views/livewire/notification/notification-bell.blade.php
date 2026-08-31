<div wire:poll.20s>
    <x-dropdown width="w-80">
        <x-slot:trigger>
            <button class="relative flex h-9 w-9 items-center justify-center rounded-lg text-topbar-text/70 hover:bg-gray-500/10">
                <i class="ik ik-bell"></i>
                @if ($unreadCount > 0)
                    <span class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-accent-500 px-1 text-[10px] font-semibold text-white">
                        {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                    </span>
                @endif
            </button>
        </x-slot:trigger>

        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-2">
            <span class="text-sm font-semibold text-gray-700">{{ __('Notifications') }}</span>
            @if ($unreadCount > 0)
                <button type="button" wire:click="markAllRead" class="text-xs font-medium text-primary-600 hover:underline">
                    {{ __('Mark all read') }}
                </button>
            @endif
        </div>
        <div class="max-h-80 overflow-y-auto">
            @forelse ($recent as $notification)
                <button type="button" wire:click="markRead('{{ $notification->id }}')"
                    class="flex w-full items-start gap-3 px-4 py-3 text-left hover:bg-gray-50 {{ $notification->isRead() ? '' : 'bg-primary-50/40' }}">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-100 text-primary-600">
                        <i class="{{ $notification->icon() }}"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-semibold text-gray-700">{{ $notification->title }}</span>
                        <span class="block truncate text-xs text-gray-500">{{ $notification->message }}</span>
                        <span class="block text-[10px] text-gray-400">{{ $notification->created_at->diffForHumans() }}</span>
                    </span>
                </button>
            @empty
                <p class="px-4 py-8 text-center text-sm text-gray-400">{{ __('No notifications yet') }}</p>
            @endforelse
        </div>
        <a href="{{ route('notifications.index') }}" wire:navigate
            class="block border-t border-gray-100 px-4 py-2 text-center text-sm font-medium text-primary-600 hover:bg-gray-50">
            {{ __('See all notifications') }}
        </a>
    </x-dropdown>
</div>
