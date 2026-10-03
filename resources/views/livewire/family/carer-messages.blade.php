{{--
    Batch 13: carer-only messaging screen — see
    App\Livewire\Family\CarerMessagesComponent for how this relates to the
    staff and family-portal chat components it shares data with.
--}}
<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)" wire:poll.20s>
    @if (! $serviceUser)
        {{-- ===================== PICKER ===================== --}}
        <div class="mb-5">
            <h1 class="text-lg font-semibold text-gray-800">{{ __('Messages') }}</h1>
            <p class="text-sm text-gray-400">{{ __("Pick who's family you'd like to message.") }}</p>
        </div>

        @if ($serviceUsers->isEmpty())
            <x-empty-state icon="ik ik-message-square" title="{{ __('No assigned service users yet') }}"
                description="{{ __('Messaging opens up once you have a shift assigned to someone.') }}" />
        @else
            <div class="space-y-2">
                @foreach ($serviceUsers as $su)
                    <button type="button" wire:click="selectServiceUser('{{ $su->id }}')" wire:key="su-{{ $su->id }}"
                        class="flex w-full items-center gap-3 rounded-xl border border-gray-100 bg-white p-3.5 text-left shadow-sm hover:border-primary-200">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-50 text-sm font-semibold text-primary-600">
                            {{ mb_strtoupper(mb_substr($su->name, 0, 1)) }}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-semibold text-gray-700">{{ $su->name }}</span>
                            <span class="block text-xs text-gray-400">{{ $su->next_of_kin_name ? __('Next of kin: :name', ['name' => $su->next_of_kin_name]) : __('Tap to message') }}</span>
                        </span>
                        @if ($su->unread_count)
                            <span class="flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-primary-500 px-1 text-[10px] font-semibold text-white">{{ $su->unread_count }}</span>
                        @else
                            <i class="ik ik-chevron-right text-gray-300"></i>
                        @endif
                    </button>
                @endforeach
            </div>
        @endif
    @elseif (! $activeSession)
        {{-- ===================== SESSION LIST ===================== --}}
        <div class="mb-4 flex items-center gap-3">
            <button type="button" wire:click="backToList" class="flex h-9 w-9 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100">
                <i class="ik ik-arrow-left text-lg"></i>
            </button>
            <div class="min-w-0 flex-1">
                <h1 class="truncate text-lg font-semibold text-gray-800">{{ $serviceUser->name }}</h1>
                <p class="text-sm text-gray-400">{{ __('Family conversations') }}</p>
            </div>
            <button type="button" wire:click="openNewForm"
                class="shrink-0 rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700">
                {{ __('New') }}
            </button>
        </div>

        @if ($showNewForm)
            <form wire:submit.prevent="startSession" class="mb-4 space-y-3 rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <div>
                    <input type="text" wire:model="newSubject" placeholder="{{ __('Subject, e.g. "Great afternoon today"') }}"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:ring-primary-500">
                    @error('newSubject') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <textarea wire:model="newMessage" rows="3" placeholder="{{ __('Your message...') }}"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                    @error('newMessage') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-center justify-end gap-2">
                    <button type="button" wire:click="cancelNewForm" class="rounded-lg px-3 py-1.5 text-sm font-medium text-gray-500 hover:bg-gray-50">{{ __('Cancel') }}</button>
                    <button type="submit" class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700">{{ __('Start conversation') }}</button>
                </div>
            </form>
        @endif

        @if ($sessions->isEmpty() && ! $showNewForm)
            <x-empty-state icon="ik ik-message-square" title="{{ __('No conversations yet') }}"
                description="{{ __('Start one above to reach their family.') }}" compact />
        @else
            <div class="space-y-2">
                @foreach ($sessions as $session)
                    <button type="button" wire:click="openSession('{{ $session->id }}')" wire:key="session-{{ $session->id }}"
                        class="flex w-full items-center gap-3 rounded-xl border border-gray-100 bg-white p-3.5 text-left shadow-sm hover:border-primary-200">
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center justify-between gap-2">
                                <span class="truncate text-sm font-semibold text-gray-700">{{ $session->subject }}</span>
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
                @endforeach
            </div>
        @endif
    @else
        {{-- ===================== THREAD ===================== --}}
        <div class="flex items-center gap-3 border-b border-gray-100 pb-3">
            <button type="button" wire:click="closeThread" class="flex h-9 w-9 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100">
                <i class="ik ik-arrow-left text-lg"></i>
            </button>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-gray-700">{{ $activeSession->subject }}</p>
                <p class="text-[11px] text-gray-400">{{ $serviceUser->name }} · {{ __('Opened') }} {{ $activeSession->created_at->diffForHumans() }}</p>
            </div>
            <button type="button" wire:click="toggleStatus('{{ $activeSession->id }}')"
                class="shrink-0 whitespace-nowrap text-xs font-medium text-primary-600 hover:underline">
                {{ $activeSession->status === 'open' ? __('Mark resolved') : __('Reopen') }}
            </button>
        </div>

        <div class="space-y-2.5 py-3" x-ref="thread" x-init="$nextTick(() => $refs.thread.scrollIntoView({ block: 'end' }))" wire:key="thread-{{ $activeSession->id }}">
            @forelse ($thread as $message)
                @php $mine = $message->sender_id === auth()->id(); @endphp
                <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[80%]">
                        @unless ($mine)
                            <p class="mb-0.5 text-[10px] font-medium text-gray-400">{{ $message->sender->name ?? __('Family') }}</p>
                        @endunless
                        <div class="rounded-2xl px-3 py-1.5 text-[13px] leading-snug {{ $mine ? 'rounded-br-sm bg-primary-500 text-white' : 'rounded-bl-sm bg-white text-gray-700 shadow-sm' }}">
                            {{ $message->message }}
                        </div>
                        <p class="mt-0.5 text-[10px] text-gray-400 {{ $mine ? 'text-right' : '' }}">{{ $message->created_at->format('d M, H:i') }}</p>
                    </div>
                </div>
            @empty
                <p class="py-8 text-center text-xs text-gray-400">{{ __('No messages yet') }}</p>
            @endforelse
        </div>

        <form wire:submit.prevent="send" class="sticky bottom-0 flex items-center gap-2 border-t border-gray-100 bg-[#f4f6f8] py-2.5">
            <input type="text" wire:model="draft" placeholder="{{ __('Type a reply...') }}"
                class="flex-1 rounded-full border border-gray-200 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
            <button type="submit" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-500 text-white transition hover:bg-primary-600">
                <i class="ik ik-send text-sm"></i>
            </button>
        </form>
    @endif
</div>
