<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    <x-page-header title="{{ __('Medications') }}" subtitle="{{ $serviceUser->name }}" icon="ik ik-heart"
        :breadcrumbs="['Home' => url('dashboard'), 'Medications' => null]">
        <div class="flex items-center gap-3">
            <a href="{{ route('medications.mar-chart', $serviceUser) }}" wire:navigate
                class="text-sm font-medium text-primary-600 hover:underline">{{ __('Open MAR chart') }}</a>
            <label class="flex items-center gap-2 text-sm text-gray-500">
                <input type="checkbox" wire:model.live="showInactive"
                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                {{ __('Show discontinued') }}
            </label>
            <x-button variant="primary" size="sm" wire:click="openCreateForm"
                @click="$dispatch('open-drawer', 'medication-form')">
                <i class="ik ik-plus mr-1"></i>{{ __('Add medication') }}
            </x-button>
        </div>
    </x-page-header>

    <x-card no-padding hover>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:text-gray-500">
                        <th class="px-5 py-3 font-medium">{{ __('Medication') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Dosage') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Frequency') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Route') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Schedule') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Dates') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-5 py-3 font-medium text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse ($medications as $med)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40" wire:key="med-{{ $med->id }}">
                            <td class="px-5 py-3 font-semibold text-gray-700 dark:text-gray-200">
                                {{ $med->medication_name }}</td>
                            <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ $med->dosage }}</td>
                            <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ $med->frequency }}</td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400">
                                {{ ucfirst($med->administration_route) }}</td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400">
                                @if ($med->is_prn)
                                    <x-badge color="amber">{{ __('PRN') }}</x-badge>
                                @else
                                    {{ $med->scheduledTimeFormatted() }}
                                @endif
                            </td>
                            <td class="px-5 py-3 text-gray-400 text-xs whitespace-nowrap">
                                {{ $med->start_date->format('d M Y') }} –
                                {{ $med->end_date?->format('d M Y') ?? __('ongoing') }}
                            </td>
                            <td class="px-5 py-3">
                                <x-badge color="{{ $med->is_active ? 'success' : 'secondary' }}">
                                    {{ $med->is_active ? __('Active') : __('Discontinued') }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-3 text-right space-x-2 whitespace-nowrap">
                                <button type="button" wire:click="openEditForm('{{ $med->id }}')"
                                    @click="$dispatch('open-drawer', 'medication-form')"
                                    class="text-primary-600 hover:underline text-sm font-medium">{{ __('Edit') }}</button>
                                <button type="button" wire:click="toggleActive('{{ $med->id }}')"
                                    wire:confirm="{{ $med->is_active ? __('Discontinue this medication?') : __('Reactivate this medication?') }}"
                                    class="text-accent-500 hover:underline text-sm font-medium">
                                    {{ $med->is_active ? __('Discontinue') : __('Reactivate') }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-10">
                                <x-empty-state title="{{ __('No medications recorded') }}"
                                    description="{{ __('Add a medication to start building the MAR chart.') }}"
                                    icon="ik ik-heart" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <x-drawer name="medication-form" title="{{ $editingMedicationId ? __('Edit medication') : __('Add medication') }}"
        width="w-[30rem]">
        <div class="space-y-4">
            <x-form.input name="formMedicationName" label="{{ __('Medication name') }}" wire:model="formMedicationName"
                required />

            <div class="grid grid-cols-2 gap-3">
                <x-form.input name="formDosage" label="{{ __('Dosage') }}" wire:model="formDosage" required
                    placeholder="{{ __('e.g. 500mg') }}" />
                <x-form.select name="formAdministrationRoute" label="{{ __('Route') }}"
                    wire:model="formAdministrationRoute" required>
                    <option value="oral">{{ __('Oral') }}</option>
                    <option value="topical">{{ __('Topical') }}</option>
                    <option value="injection">{{ __('Injection') }}</option>
                    <option value="inhaled">{{ __('Inhaled') }}</option>
                    <option value="other">{{ __('Other') }}</option>
                </x-form.select>
            </div>

            <x-form.input name="formFrequency" label="{{ __('Frequency') }}" wire:model="formFrequency" required
                placeholder="{{ __('e.g. Once daily, Twice daily, PRN') }}" />

            <x-form.checkbox name="formIsPrn" label="{{ __('PRN (as needed) — no fixed schedule') }}"
                wire:model.live="formIsPrn" />

            @unless ($formIsPrn)
                <x-form.input type="time" name="formScheduledTimes" label="{{ __('Scheduled time') }}"
                    wire:model="formScheduledTimes" required />
                <p class="text-xs text-gray-400 -mt-2">
                    {{ __('One time slot per medication record — for a second daily dose, add this medication again with its own time.') }}
                </p>
            @endunless

            <div class="grid grid-cols-2 gap-3">
                <x-form.input type="date" name="formStartDate" label="{{ __('Start date') }}"
                    wire:model="formStartDate" required />
                <x-form.input type="date" name="formEndDate" label="{{ __('End date (optional)') }}"
                    wire:model="formEndDate" />
            </div>

            <x-form.textarea name="formInstructions" label="{{ __('Instructions (optional)') }}" rows="2"
                wire:model="formInstructions" />
            <x-form.textarea name="formSideEffects" label="{{ __('Side effects to watch for (optional)') }}"
                rows="2" wire:model="formSideEffects" />
        </div>

        <x-slot:footer>
            <x-button wire:click="saveMedication">{{ __('Save medication') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
