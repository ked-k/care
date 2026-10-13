<div>
    <!-- Floating "New conversation" button — bottom-24 clears layouts.carer's
         bottom tab bar; harmless extra headroom on layouts.admin-layout,
         which has no bottom bar to collide with. -->
    <button type="button" @click="$dispatch('open-modal', 'new-conversation')"
        class="fixed bottom-24 right-5 z-40 flex h-12 w-12 items-center justify-center rounded-full bg-primary-500 text-white shadow-lg transition hover:bg-primary-600"
        title="{{ __('Start a conversation') }}">
        <i class="ik ik-message-square text-xl"></i>
    </button>

    <x-modal name="new-conversation" title="{{ __('Start a conversation') }}" maxWidth="max-w-xl">
        <form wire:submit.prevent="startConversation" class="space-y-4">
            <div class="relative">
                <i class="ik ik-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search people...') }}"
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 py-2 pl-9 pr-3 text-sm outline-none focus:border-primary-400 focus:bg-white focus:ring-2 focus:ring-primary-100">
            </div>

            @error('participants')
                <p class="text-sm text-danger-600">{{ $message }}</p>
            @enderror

            <div class="max-h-60 space-y-4 overflow-y-auto pr-1">
                <div>
                    <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ __('Staff colleagues') }}</p>
                    <div class="space-y-0.5">
                        @forelse ($staff as $person)
                            <label class="flex items-center gap-2.5 rounded-lg px-2 py-1.5 text-sm hover:bg-gray-50">
                                <input type="checkbox" wire:model="selectedStaffIds" value="{{ $person->id }}"
                                    class="rounded border-gray-300 text-primary-500 focus:ring-primary-400">
                                {{ $person->name }}
                            </label>
                        @empty
                            <p class="px-2 py-2 text-xs text-gray-400">{{ __('No colleagues found') }}</p>
                        @endforelse
                    </div>
                </div>

                <div>
                    <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ __('Family members / clients') }}</p>
                    <div class="space-y-0.5">
                        @forelse ($family as $person)
                            <label class="flex items-center gap-2.5 rounded-lg px-2 py-1.5 text-sm hover:bg-gray-50">
                                <input type="checkbox" wire:model="selectedFamilyUserIds" value="{{ $person->id }}"
                                    class="rounded border-gray-300 text-primary-500 focus:ring-primary-400">
                                {{ $person->name }}
                            </label>
                        @empty
                            <p class="px-2 py-2 text-xs text-gray-400">{{ __('No family members found') }}</p>
                        @endforelse
                    </div>
                </div>

                <div>
                    <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ __('Tag service user(s) — for reference, not a recipient') }}</p>
                    <div class="space-y-0.5">
                        @forelse ($serviceUsers as $su)
                            <label class="flex items-center gap-2.5 rounded-lg px-2 py-1.5 text-sm hover:bg-gray-50">
                                <input type="checkbox" wire:model="selectedServiceUserTagIds" value="{{ $su->id }}"
                                    class="rounded border-gray-300 text-primary-500 focus:ring-primary-400">
                                {{ $su->name }}
                            </label>
                        @empty
                            <p class="px-2 py-2 text-xs text-gray-400">{{ __('No service users found') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div>
                <input type="text" wire:model="subject" placeholder="{{ __('Subject (optional)') }}"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
            </div>

            <div>
                <textarea wire:model="firstMessage" rows="3" placeholder="{{ __('Write your first message...') }}"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100"></textarea>
                @error('firstMessage')
                    <p class="mt-1 text-xs text-danger-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" @click="$dispatch('close-modal', 'new-conversation')"
                    class="rounded-lg px-4 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50">{{ __('Cancel') }}</button>
                <button type="submit"
                    class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">{{ __('Start conversation') }}</button>
            </div>
        </form>
    </x-modal>
</div>
