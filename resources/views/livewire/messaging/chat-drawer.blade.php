<div wire:poll.10s>
    <!-- Trigger button, sits in the header's icon row -->
    <button type="button" @click="chatOpen = true"
        class="relative flex h-9 w-9 items-center justify-center rounded-lg text-topbar-text/70 hover:bg-gray-500/10">
        <i class="ik ik-message-square"></i>
        @php $totalUnread = $contacts->sum('unread_count'); @endphp
        @if ($totalUnread > 0)
            <span class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-green-500 px-1 text-[10px] font-semibold text-white">
                {{ $totalUnread > 9 ? '9+' : $totalUnread }}
            </span>
        @endif
    </button>

    <!-- Overlay + drawer: chatOpen comes from the shared x-data on <body> (admin-layout.blade.php) -->
    <div x-show="chatOpen" x-transition.opacity @keydown.escape.window="chatOpen = false"
        class="fixed inset-0 z-50 bg-black/40" style="display:none" @click.self="chatOpen = false">

        <aside class="absolute inset-y-0 right-0 flex w-80 max-w-full flex-col bg-white shadow-xl dark:bg-gray-900"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">

            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                <h6 class="font-semibold text-gray-700 dark:text-gray-200">{{ __('Colleagues') }}</h6>
                <button @click="chatOpen = false" class="text-gray-400 hover:text-gray-600"><i class="ik ik-x"></i></button>
            </div>

            <div class="border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                <div class="relative">
                    <i class="ik ik-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search colleagues...') }}"
                        class="w-full rounded-lg border border-gray-200 bg-gray-50 py-2 pl-9 pr-3 text-sm outline-none focus:border-primary-400 focus:bg-white focus:ring-2 focus:ring-primary-100 dark:border-gray-700 dark:bg-gray-800">
                </div>
            </div>

            <div class="flex-1 overflow-y-auto">
                @forelse ($contacts as $contact)
                    <button type="button" wire:click="openThread({{ $contact->id }})" @click="chatOpen = true"
                        wire:key="contact-{{ $contact->id }}"
                        class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition hover:bg-gray-50 dark:hover:bg-gray-800/60 {{ $activeContact?->id === $contact->id ? 'bg-primary-50 dark:bg-primary-900/20' : '' }}">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-100 text-sm font-semibold text-primary-600">
                            {{ $contact->initials }}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center justify-between gap-2">
                                <span class="truncate text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $contact->name }}</span>
                                @if ($contact->last_message)
                                    <span class="shrink-0 text-[11px] text-gray-400">{{ $contact->last_message->created_at->diffForHumans(null, true) }}</span>
                                @endif
                            </span>
                            <span class="flex items-center justify-between gap-2">
                                <span class="truncate text-xs text-gray-400">{{ $contact->last_message->message ?? __('No messages yet') }}</span>
                                @if ($contact->unread_count)
                                    <span class="flex h-4 min-w-4 shrink-0 items-center justify-center rounded-full bg-primary-500 px-1 text-[10px] font-semibold text-white">{{ $contact->unread_count }}</span>
                                @endif
                            </span>
                        </span>
                    </button>
                @empty
                    <p class="px-4 py-10 text-center text-sm text-gray-400">{{ __('No colleagues found') }}</p>
                @endforelse
            </div>
        </aside>

        @if ($activeContact)
            <div class="absolute inset-x-3 bottom-3 flex h-[26rem] max-h-[calc(100%-1.5rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-gray-900
                        sm:inset-x-auto sm:bottom-4 sm:right-[21rem] sm:w-72">

                <div class="flex items-center gap-2.5 border-b border-gray-100 bg-white px-3 py-2.5 dark:border-gray-800 dark:bg-gray-900">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-100 text-xs font-semibold text-primary-600">
                        {{ $activeContact->initials }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $activeContact->name }}</p>
                    </div>
                    <button wire:click="closeThread" class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600" title="{{ __('Close chat') }}">
                        <i class="ik ik-x text-sm"></i>
                    </button>
                </div>

                <div class="flex-1 space-y-2.5 overflow-y-auto bg-gray-50 px-3 py-3 dark:bg-gray-800/40" x-ref="thread" x-init="$nextTick(() => $refs.thread.scrollTop = $refs.thread.scrollHeight)" wire:key="thread-{{ $activeContact->id }}">
                    @forelse ($thread as $message)
                        <div class="flex {{ $message->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[80%]">
                                <div class="rounded-2xl px-3 py-1.5 text-[13px] leading-snug {{ $message->sender_id === auth()->id() ? 'rounded-br-sm bg-primary-500 text-white' : 'rounded-bl-sm bg-white text-gray-700 shadow-sm dark:bg-gray-900 dark:text-gray-200' }}">
                                    {{ $message->message }}
                                </div>
                                <p class="mt-0.5 text-[10px] text-gray-400 {{ $message->sender_id === auth()->id() ? 'text-right' : '' }}">
                                    {{ $message->created_at->format('H:i') }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="py-8 text-center text-xs text-gray-400">{{ __('Say hello 👋') }}</p>
                    @endforelse
                </div>

                <form wire:submit.prevent="send" class="flex items-center gap-2 border-t border-gray-100 px-2.5 py-2.5 dark:border-gray-800">
                    <input type="text" wire:model="draft" placeholder="{{ __('Type a message...') }}"
                        class="flex-1 rounded-full border border-gray-200 bg-gray-50 px-3.5 py-1.5 text-sm outline-none focus:border-primary-400 focus:bg-white focus:ring-2 focus:ring-primary-100 dark:border-gray-700 dark:bg-gray-800">
                    <button type="submit" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-500 text-white transition hover:bg-primary-600">
                        <i class="ik ik-send text-sm"></i>
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>
