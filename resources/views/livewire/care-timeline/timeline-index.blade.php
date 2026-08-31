<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    <x-page-header title="{{ __('Care Timeline') }}" subtitle="{{ __('for :name', ['name' => $serviceUser->name]) }}"
        icon="ik ik-activity" :breadcrumbs="['Home' => url('dashboard'), 'Service Users' => route('service-users.index'), 'Timeline' => null]">
        <x-button variant="primary" size="sm" wire:click="openCreateForm" @click="$dispatch('open-drawer', 'timeline-entry-form')">
            <i class="ik ik-plus mr-1"></i>{{ __('Add update') }}
        </x-button>
    </x-page-header>

    <div class="space-y-3">
        @forelse ($entries as $entry)
            <x-card no-padding hover>
                <div class="flex items-start gap-3 p-4">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-900/30">
                        <i class="ik {{ $entry->entry_type === 'task' ? 'ik-check-square' : 'ik-message-circle' }}"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                                {{ ucfirst(str_replace('_', ' ', $entry->entry_type)) }}
                            </span>
                            <span class="flex items-center gap-2 text-xs text-gray-400">
                                {{ $entry->created_at->format('d M Y, H:i') }}
                                @if (! $entry->visible_to_family)
                                    <x-badge color="secondary">{{ __('Not shared with family') }}</x-badge>
                                @endif
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $entry->content }}</p>
                        @if ($entry->media)
                            <img src="{{ $entry->media->url() }}" class="mt-2 h-32 w-32 rounded-lg object-cover" alt="">
                        @endif
                        <p class="mt-1 text-xs text-gray-400">{{ __('by') }} {{ $entry->creator->name ?? __('System') }}</p>
                    </div>
                </div>
            </x-card>
        @empty
            <x-card>
                <x-empty-state title="{{ __('No timeline entries yet') }}"
                    description="{{ __('Entries appear here automatically as carers complete tasks, or you can add one manually.') }}"
                    icon="ik ik-activity" />
            </x-card>
        @endforelse
    </div>

    <x-drawer name="timeline-entry-form" title="{{ __('Add a timeline update') }}">
        <div class="space-y-4">
            <x-form.select name="formEntryType" label="{{ __('Type') }}" wire:model="formEntryType">
                <option value="note">{{ __('General note') }}</option>
                <option value="visit">{{ __('Visit / appointment') }}</option>
                <option value="family_contact">{{ __('Family contact') }}</option>
                <option value="wellbeing">{{ __('Wellbeing update') }}</option>
                <option value="other">{{ __('Other') }}</option>
            </x-form.select>
            <x-form.textarea name="formContent" label="{{ __('Update') }}" rows="4" wire:model="formContent" required />
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('Photo (optional)') }}</label>
                <input type="file" wire:model="formPhoto" accept="image/*"
                    class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-primary-600 hover:file:bg-primary-100">
                @error('formPhoto') <p class="mt-1 text-xs text-accent-500">{{ $message }}</p> @enderror
            </div>
            <x-form.checkbox name="formVisibleToFamily" label="{{ __('Visible to family in the family portal') }}"
                wire:model="formVisibleToFamily" />
        </div>
        <x-slot:footer>
            <x-button wire:click="addEntry">{{ __('Add to timeline') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
