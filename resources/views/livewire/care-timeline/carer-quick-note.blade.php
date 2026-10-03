{{--
    Batch 13: carer-only quick-notes screen — see
    App\Livewire\CareTimeline\CarerQuickNoteComponent for why this is its
    own component rather than reusing the staff TimelineIndexComponent.
--}}
<div>
    @if (! $serviceUser)
        {{-- ===================== PICKER ===================== --}}
        <div class="mb-5">
            <h1 class="text-lg font-semibold text-gray-800">{{ __('Add a note') }}</h1>
            <p class="text-sm text-gray-400">{{ __('Pick who the note is about.') }}</p>
        </div>

        @if ($serviceUsers->isEmpty())
            <x-empty-state icon="ik ik-users" title="{{ __('No assigned service users yet') }}"
                description="{{ __('Notes can be added once you have a shift assigned to someone.') }}" />
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
                            <span class="block text-xs text-gray-400">{{ __('Tap to add a note') }}</span>
                        </span>
                        <i class="ik ik-chevron-right text-gray-300"></i>
                    </button>
                @endforeach
            </div>
        @endif
    @else
        {{-- ===================== NOTE FORM ===================== --}}
        <div class="mb-5 flex items-center gap-3">
            <button type="button" wire:click="backToList" class="flex h-9 w-9 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100">
                <i class="ik ik-arrow-left text-lg"></i>
            </button>
            <div class="min-w-0">
                <h1 class="truncate text-lg font-semibold text-gray-800">{{ $serviceUser->name }}</h1>
                <p class="text-sm text-gray-400">{{ __('New note') }}</p>
            </div>
        </div>

        <form wire:submit.prevent="addNote" class="space-y-4">
            <div>
                <textarea wire:model="formContent" rows="6" placeholder="{{ __('What happened, or what should the next carer / family know?') }}"
                    class="w-full rounded-xl border border-gray-200 bg-white p-3 text-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                @error('formContent') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">{{ __('Photo (optional)') }}</label>
                <input type="file" wire:model="formPhoto" accept="image/*"
                    class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary-600">
                @error('formPhoto') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                <div wire:loading wire:target="formPhoto" class="mt-1 text-xs text-gray-400">{{ __('Uploading...') }}</div>
                @if ($formPhoto)
                    <img src="{{ $formPhoto->temporaryUrl() }}" class="mt-2 h-24 w-24 rounded-lg object-cover">
                @endif
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" wire:model="formVisibleToFamily" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                {{ __("Visible to this person's family") }}
            </label>

            <button type="submit"
                class="w-full rounded-xl bg-primary-600 py-3 text-sm font-semibold text-white hover:bg-primary-700" wire:loading.attr="disabled" wire:target="addNote">
                <span wire:loading.remove wire:target="addNote">{{ __('Save note') }}</span>
                <span wire:loading wire:target="addNote">{{ __('Saving...') }}</span>
            </button>
        </form>
    @endif
</div>
