<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    <x-page-header title="{{ __('Notifications') }}" subtitle="{{ __('Everything sent to you in-app') }}"
        icon="ik ik-bell" :breadcrumbs="['Home' => url('dashboard'), 'Notifications' => null]">
        <div class="flex items-center gap-3">
            <select wire:model.live="filter"
                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <option value="all">{{ __('All') }}</option>
                <option value="unread">{{ __('Unread only') }}</option>
            </select>
            <x-button variant="outline" size="sm" wire:click="markAllRead">{{ __('Mark all read') }}</x-button>
        </div>
    </x-page-header>

    <x-card no-padding hover>
        <div class="divide-y divide-gray-50 dark:divide-gray-800">
            @forelse ($notifications as $notification)
                <div class="flex items-start gap-3 px-5 py-4 {{ $notification->isRead() ? '' : 'bg-primary-50/40' }}"
                    wire:key="notif-{{ $notification->id }}">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-100 text-primary-600">
                        <i class="{{ $notification->icon() }}"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $notification->title }}</span>
                            <span class="flex items-center gap-2">
                                <x-badge color="{{ $notification->priorityColor() }}">{{ ucfirst($notification->priority) }}</x-badge>
                                <span class="text-xs text-gray-400">{{ $notification->created_at->format('d M Y, H:i') }}</span>
                            </span>
                        </div>
                        <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-300">{{ $notification->message }}</p>
                        @unless ($notification->isRead())
                            <button type="button" wire:click="markRead('{{ $notification->id }}')"
                                class="mt-1 text-xs font-medium text-primary-600 hover:underline">
                                {{ __('Mark as read') }}
                            </button>
                        @endunless
                    </div>
                </div>
            @empty
                <div class="px-5 py-10">
                    <x-empty-state title="{{ __('Nothing here') }}" icon="ik ik-bell" />
                </div>
            @endforelse
        </div>
        @if ($notifications->hasPages())
            <div class="border-t border-gray-100 px-5 py-3 dark:border-gray-800">
                {{ $notifications->links() }}
            </div>
        @endif
    </x-card>
</div>
