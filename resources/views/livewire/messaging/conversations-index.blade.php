<div>
    <x-page-header :title="__('Conversations')" icon="ik ik-message-square" />

    <div class="divide-y divide-gray-100 rounded-xl border border-gray-100 bg-white">
        @forelse ($conversations as $conversation)
            <a href="{{ route(auth()->user()->hasRole('Family') ? 'family.conversations.show' : 'conversations.show', $conversation->id) }}"
                wire:navigate
                class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-100 text-primary-600">
                    <i class="ik ik-users"></i>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="flex items-center justify-between gap-2">
                        <span class="truncate text-sm font-semibold text-gray-700">
                            {{ $conversation->subject ?: ($conversation->other_participants->pluck('name')->join(', ') ?: __('Conversation')) }}
                        </span>
                        @if ($conversation->last_message_at)
                            <span class="shrink-0 text-[11px] text-gray-400">{{ $conversation->last_message_at->diffForHumans(null, true) }}</span>
                        @endif
                    </span>
                    <span class="flex items-center justify-between gap-2">
                        <span class="truncate text-xs text-gray-400">
                            {{ $conversation->other_participants->pluck('name')->join(', ') ?: __('Just you') }}
                            @if ($conversation->tagged_service_users->isNotEmpty())
                                &middot; {{ __('Re:') }} {{ $conversation->tagged_service_users->pluck('name')->join(', ') }}
                            @endif
                        </span>
                        @if ($conversation->unread_count)
                            <span class="flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-primary-500 px-1 text-[11px] font-semibold text-white">{{ $conversation->unread_count }}</span>
                        @endif
                    </span>
                </span>
            </a>
        @empty
            <p class="px-4 py-10 text-center text-sm text-gray-400">{{ __("You don't have any conversations yet.") }}</p>
        @endforelse
    </div>
</div>
