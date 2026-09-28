<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)" wire:poll.15s>
    <x-page-header title="{{ __('Family Messages') }}" subtitle="{{ $serviceUser->name }}" icon="ik ik-message-square"
        :breadcrumbs="['Home' => url('dashboard'), 'Service Users' => route('service-users.index'), $serviceUser->name => null]">
        <div class="flex items-center gap-2">
            <select wire:model.live="statusFilter"
                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <option value="open">{{ __('Open') }}</option>
                <option value="closed">{{ __('Resolved') }}</option>
                <option value="all">{{ __('All') }}</option>
            </select>
        </div>
    </x-page-header>

    <div class="grid gap-4 lg:grid-cols-[20rem_1fr]">
        <x-card no-padding hover>
            <x-slot:header>{{ __('Conversations') }}</x-slot:header>
            <div class="max-h-[32rem] divide-y divide-gray-50 overflow-y-auto dark:divide-gray-800">
                @forelse ($sessions as $session)
                    <button type="button" wire:click="openSession('{{ $session->id }}')" wire:key="session-{{ $session->id }}"
                        class="flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-800/40 {{ $activeSession?->id === $session->id ? 'bg-primary-50 dark:bg-primary-900/20' : '' }}">
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center justify-between gap-2">
                                <span class="truncate text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $session->subject }}</span>
                                @if ($session->last_message_at)
                                    <span class="shrink-0 text-[11px] text-gray-400">{{ $session->last_message_at->diffForHumans(null, true) }}</span>
                                @endif
                            </span>
                            <span class="flex items-center justify-between gap-2">
                                <x-badge color="{{ $session->status === 'open' ? 'success' : 'gray' }}">
                                    {{ $session->status === 'open' ? __('Open') : __('Resolved') }}
                                </x-badge>
                                @if ($session->unread_count)
                                    <span class="flex h-4 min-w-4 shrink-0 items-center justify-center rounded-full bg-primary-500 px-1 text-[10px] font-semibold text-white">{{ $session->unread_count }}</span>
                                @endif
                            </span>
                        </span>
                    </button>
                @empty
                    <div class="px-4 py-10">
                        <x-empty-state icon="ik ik-message-square" title="{{ __('No conversations') }}"
                            description="{{ __('Messages family members send about this person will appear here.') }}" compact />
                    </div>
                @endforelse
            </div>
        </x-card>

        <x-card no-padding hover>
            @if (! $activeSession)
                <div class="flex h-[28rem] items-center justify-center">
                    <x-empty-state icon="ik ik-message-square" title="{{ __('Select a conversation') }}" compact />
                </div>
            @else
                <div class="flex h-[28rem] flex-col overflow-hidden">
                    <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $activeSession->subject }}</p>
                            <p class="text-[11px] text-gray-400">{{ __('Opened') }} {{ $activeSession->created_at->diffForHumans() }}</p>
                        </div>
                        <button type="button" wire:click="toggleStatus('{{ $activeSession->id }}')"
                            class="text-xs font-medium text-primary-600 hover:underline whitespace-nowrap">
                            {{ $activeSession->status === 'open' ? __('Mark resolved') : __('Reopen') }}
                        </button>
                    </div>

                    <div class="flex-1 space-y-2.5 overflow-y-auto bg-gray-50 px-3 py-3 dark:bg-gray-800/40" x-ref="thread" x-init="$nextTick(() => $refs.thread.scrollTop = $refs.thread.scrollHeight)" wire:key="thread-{{ $activeSession->id }}">
                        @forelse ($thread as $message)
                            @php $mine = $message->sender_id === auth()->id(); @endphp
                            <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[75%]">
                                    @unless ($mine)
                                        <p class="mb-0.5 text-[10px] font-medium text-gray-400">{{ $message->sender->name ?? __('Family') }}</p>
                                    @endunless
                                    <div class="rounded-2xl px-3 py-1.5 text-[13px] leading-snug {{ $mine ? 'rounded-br-sm bg-primary-500 text-white' : 'rounded-bl-sm bg-white text-gray-700 shadow-sm dark:bg-gray-900 dark:text-gray-200' }}">
                                        {{ $message->message }}
                                    </div>
                                    <p class="mt-0.5 text-[10px] text-gray-400 {{ $mine ? 'text-right' : '' }}">{{ $message->created_at->format('d M, H:i') }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="py-8 text-center text-xs text-gray-400">{{ __('No messages yet') }}</p>
                        @endforelse
                    </div>

                    <form wire:submit.prevent="send" class="flex items-center gap-2 border-t border-gray-100 px-2.5 py-2.5 dark:border-gray-800">
                        <input type="text" wire:model="draft" placeholder="{{ __('Type a reply...') }}"
                            class="flex-1 rounded-full border border-gray-200 bg-gray-50 px-3.5 py-1.5 text-sm outline-none focus:border-primary-400 focus:bg-white focus:ring-2 focus:ring-primary-100 dark:border-gray-700 dark:bg-gray-800">
                        <button type="submit" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-500 text-white transition hover:bg-primary-600">
                            <i class="ik ik-send text-sm"></i>
                        </button>
                    </form>
                </div>
            @endif
        </x-card>
    </div>
</div>
