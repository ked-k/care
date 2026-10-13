<div>
    <div class="mb-4">
        <a href="{{ route(auth()->user()->hasRole('Family') ? 'family.conversations.index' : 'conversations.index') }}" wire:navigate
            class="flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-primary-600">
            <i class="ik ik-arrow-left"></i> {{ __('Conversations') }}
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-100 bg-white">
        <div class="border-b border-gray-100 px-4 py-3">
            <h5 class="font-semibold text-gray-700">{{ $conversation->subject ?: __('Conversation') }}</h5>
            <p class="mt-0.5 text-xs text-gray-400">
                {{ __('With:') }} {{ $otherParticipants->pluck('name')->join(', ') ?: __('just you') }}
                @if ($taggedServiceUsers->isNotEmpty())
                    &middot; {{ __('Re:') }} {{ $taggedServiceUsers->pluck('name')->join(', ') }}
                @endif
            </p>
        </div>

        <div class="h-[26rem] space-y-2.5 overflow-y-auto bg-gray-50 px-4 py-4" x-data
            x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)" wire:poll.10s>
            @forelse ($thread as $message)
                <div class="flex {{ $message->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[75%]">
                        @if ($message->sender_id !== auth()->id())
                            <p class="mb-0.5 text-[11px] font-medium text-gray-400">{{ $message->sender->name ?? __('Unknown') }}</p>
                        @endif
                        <div class="rounded-2xl px-3.5 py-2 text-sm leading-snug {{ $message->sender_id === auth()->id() ? 'rounded-br-sm bg-primary-500 text-white' : 'rounded-bl-sm bg-white text-gray-700 shadow-sm' }}">
                            {{ $message->message }}
                        </div>
                        <p class="mt-0.5 text-[10px] text-gray-400 {{ $message->sender_id === auth()->id() ? 'text-right' : '' }}">
                            {{ $message->created_at->format('D, H:i') }}
                        </p>
                    </div>
                </div>
            @empty
                <p class="py-10 text-center text-sm text-gray-400">{{ __('No messages yet') }}</p>
            @endforelse
        </div>

        <form wire:submit.prevent="send" class="flex items-center gap-2 border-t border-gray-100 px-3 py-3">
            <input type="text" wire:model="draft" placeholder="{{ __('Type a message...') }}"
                class="flex-1 rounded-full border border-gray-200 bg-gray-50 px-4 py-2 text-sm outline-none focus:border-primary-400 focus:bg-white focus:ring-2 focus:ring-primary-100">
            <button type="submit" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-500 text-white hover:bg-primary-600">
                <i class="ik ik-send text-sm"></i>
            </button>
        </form>
    </div>
</div>
