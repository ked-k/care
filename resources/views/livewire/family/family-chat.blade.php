{{--
    Nested inside family-service-user.blade.php's Messages tab. Own root
    element, own wire:id — a proper Livewire child, not stack-pushed
    content, so it has no entangle scoping issue to worry about (it doesn't
    entangle anything itself; wire:poll here just refreshes this
    component's own render() on an interval).
--}}
<div wire:poll.20s x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    @if (! $activeSession)
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h6 class="text-sm font-semibold text-gray-600 dark:text-gray-300">{{ __('Messages') }}</h6>
                @unless ($showNewForm)
                    <x-button size="sm" variant="primary" wire:click="openNewForm">
                        <i class="ik ik-plus mr-1"></i>{{ __('New conversation') }}
                    </x-button>
                @endunless
            </div>

            @if ($showNewForm)
                <div class="space-y-3 rounded-xl border border-gray-100 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <x-form.input name="newSubject" label="{{ __('What\'s this about?') }}" wire:model="newSubject"
                        placeholder="{{ __('e.g. Medication query') }}" required />
                    <x-form.textarea name="newMessage" label="{{ __('Message') }}" rows="3" wire:model="newMessage" required />
                    <div class="flex justify-end gap-2">
                        <x-button size="sm" variant="ghost" wire:click="cancelNewForm">{{ __('Cancel') }}</x-button>
                        <x-button size="sm" variant="primary" wire:click="startSession">{{ __('Send') }}</x-button>
                    </div>
                </div>
            @endif

            @forelse ($sessions as $session)
                <button type="button" wire:click="openSession('{{ $session->id }}')" wire:key="session-{{ $session->id }}"
                    class="flex w-full items-center gap-3 rounded-xl border border-gray-100 bg-white p-3 text-left hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:hover:bg-gray-800/60">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-100 text-primary-600">
                        <i class="ik ik-message-square"></i>
                    </span>
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
                @unless ($showNewForm)
                    <x-empty-state icon="ik ik-message-square" title="{{ __('No conversations yet') }}"
                        description="{{ __('Start a conversation with the care team whenever you have a question.') }}" compact />
                @endunless
            @endforelse
        </div>
    @else
        <div class="flex h-[28rem] flex-col overflow-hidden rounded-xl border border-gray-100 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center gap-2.5 border-b border-gray-100 px-3 py-2.5 dark:border-gray-800">
                <button wire:click="closeSession" class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                    <i class="ik ik-arrow-left text-sm"></i>
                </button>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $activeSession->subject }}</p>
                    <p class="text-[11px] text-gray-400">{{ $activeSession->status === 'open' ? __('With your care team') : __('Marked resolved — sending a message reopens it') }}</p>
                </div>
            </div>

            <div class="flex-1 space-y-2.5 overflow-y-auto bg-gray-50 px-3 py-3 dark:bg-gray-800/40" x-ref="thread" x-init="$nextTick(() => $refs.thread.scrollTop = $refs.thread.scrollHeight)" wire:key="thread-{{ $activeSession->id }}">
                @forelse ($thread as $message)
                    @php $mine = $message->sender_id === auth()->id(); @endphp
                    <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[80%]">
                            @unless ($mine)
                                <p class="mb-0.5 text-[10px] font-medium text-gray-400">{{ $message->sender->name ?? __('Care team') }}</p>
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
                <input type="text" wire:model="draft" placeholder="{{ __('Type a message...') }}"
                    class="flex-1 rounded-full border border-gray-200 bg-gray-50 px-3.5 py-1.5 text-sm outline-none focus:border-primary-400 focus:bg-white focus:ring-2 focus:ring-primary-100 dark:border-gray-700 dark:bg-gray-800">
                <button type="submit" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-500 text-white transition hover:bg-primary-600">
                    <i class="ik ik-send text-sm"></i>
                </button>
            </form>
        </div>
    @endif
</div>
