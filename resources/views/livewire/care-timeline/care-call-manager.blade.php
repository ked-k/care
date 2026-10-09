<div>
    <x-page-header title="{{ __('Care Calls') }}" subtitle="{{ $serviceUser->name }}" icon="ik ik-calendar"
        :breadcrumbs="['Home' => url('dashboard'), 'Service Users' => route('service-users.index'), $serviceUser->name => null]">
        <div class="flex items-center gap-2">
            <x-button variant="outline" size="sm" wire:click="previousDay"><i class="ik ik-chevron-left"></i></x-button>
            <span class="text-sm font-medium text-gray-600 whitespace-nowrap">{{ \Carbon\Carbon::parse($date)->format('D, d M Y') }}</span>
            <x-button variant="outline" size="sm" wire:click="nextDay"><i class="ik ik-chevron-right"></i></x-button>
            <x-button variant="outline" size="sm" wire:click="goToday">{{ __('Today') }}</x-button>
            <x-button variant="primary" size="sm" wire:click="openCreateForm"><i class="ik ik-plus"></i> {{ __('Add call') }}</x-button>
        </div>
    </x-page-header>

    <x-card hover>
        <x-slot:header>{{ __('Preview — what carers and the timeline will show') }}</x-slot:header>
        <livewire:care-timeline.care-call-timeline
            :service-user-id="$serviceUser->id" :date="$date"
            :key="'timeline-'.$serviceUser->id.'-'.$date" />
    </x-card>

    <x-card no-padding hover class="mt-4">
        <x-slot:header>{{ __('Calls on this day') }}</x-slot:header>
        <div class="divide-y divide-gray-50">
            @forelse ($calls as $call)
                <div wire:key="row-{{ $call->id }}" class="flex items-center justify-between gap-3 px-5 py-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-gray-700">{{ $call->displayLabel() }}</span>
                            @if ($call->isCancelled())
                                <x-badge color="dark">{{ __('Cancelled') }}</x-badge>
                            @endif
                        </div>
                        <div class="text-xs text-gray-400">
                            {{ $call->scheduled_start->format('H:i') }} – {{ $call->scheduled_end->format('H:i') }}
                            @if ($call->carer)
                                · {{ $call->carer->name }}
                            @else
                                · {{ __('Unassigned') }}
                            @endif
                        </div>
                        @if ($call->notes)
                            <div class="mt-0.5 truncate text-xs text-gray-400">{{ $call->notes }}</div>
                        @endif
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <button type="button" wire:click="openEditForm('{{ $call->id }}')" class="text-sm font-medium text-primary-600 hover:underline">{{ __('Edit') }}</button>
                        <button type="button" wire:click="deleteCall('{{ $call->id }}')" wire:confirm="{{ __('Remove this call?') }}" class="text-sm font-medium text-accent-500 hover:underline">{{ __('Remove') }}</button>
                    </div>
                </div>
            @empty
                <div class="px-5 py-8">
                    <x-empty-state icon="ik ik-calendar" title="{{ __('No calls scheduled for this day') }}"
                        description="{{ __('Add one above — or use the Rota Builder for a staffed shift instead.') }}" compact />
                </div>
            @endforelse
        </div>
    </x-card>

    <x-drawer name="care-call-form" title="{{ $editingCallId ? __('Edit call') : __('Add call') }}">
        <form wire:submit.prevent="saveCall" class="space-y-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">{{ __('Label') }}</label>
                <input type="text" wire:model="formLabel" placeholder="{{ __('Morning / Lunch / Evening / Bedtime...') }}"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:ring-primary-500">
                @error('formLabel') <p class="mt-1 text-xs text-accent-500">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-gray-400">{{ __('Left blank, the time of day is used (e.g. a 13:00 start shows as "Lunch").') }}</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">{{ __('Start') }}</label>
                    <input type="time" wire:model="formStart" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:ring-primary-500">
                    @error('formStart') <p class="mt-1 text-xs text-accent-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">{{ __('End') }}</label>
                    <input type="time" wire:model="formEnd" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:ring-primary-500">
                    @error('formEnd') <p class="mt-1 text-xs text-accent-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">{{ __('Carer (optional)') }}</label>
                <select wire:model="formCarerId" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">{{ __('Unassigned') }}</option>
                    @foreach ($carers as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">{{ __('Intended care (optional)') }}</label>
                <textarea wire:model="formNotes" rows="4" placeholder="{{ __('e.g. prepare preferred meals/drinks, toileting/continence support, safe mobility as agreed') }}"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                @error('formNotes') <p class="mt-1 text-xs text-accent-500">{{ $message }}</p> @enderror
            </div>
        </form>

        <x-slot:footer>
            <x-button variant="outline" x-on:click="open = false">{{ __('Cancel') }}</x-button>
            <x-button variant="primary" wire:click="saveCall">{{ __('Save call') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
