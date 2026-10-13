<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    <x-page-header title="{{ __('Recurring Visit Templates') }}"
        subtitle="{{ __('Set a repeating pattern once instead of re-entering the same visit every week') }}"
        icon="ik ik-repeat"
        :breadcrumbs="['Home' => url('dashboard'), 'Rota' => route('rota.index'), 'Recurring Templates' => null]">
        <div class="flex items-center gap-2">
            <x-button variant="outline" size="sm" wire:click="openGenerateForm">
                <i class="ik ik-play mr-1"></i>{{ __('Generate for a date range') }}
            </x-button>
            <x-button variant="primary" size="sm" wire:click="openCreateForm">
                <i class="ik ik-plus mr-1"></i>{{ __('New template') }}
            </x-button>
        </div>
    </x-page-header>

    <x-card no-padding hover>
        <x-table :paginator="$templates" title="{{ __('Recurring Visit Templates') }}">
            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:text-gray-500">
                        <th class="px-5 py-3 font-medium">{{ __('Service User') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Type') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Carer') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Pattern') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-5 py-3 font-medium text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse ($templates as $template)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40" wire:key="template-{{ $template->id }}">
                            <td class="px-5 py-3 font-semibold text-gray-700 dark:text-gray-200">
                                {{ $template->serviceUser->name ?? __('Unknown') }}
                            </td>
                            <td class="px-5 py-3">
                                @if ($template->isShiftTemplate())
                                    <x-badge color="primary">{{ ucfirst($template->shift_type) }} {{ __('shift') }}</x-badge>
                                @else
                                    <x-badge color="secondary">{{ __('Care call') }}</x-badge>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-gray-600 dark:text-gray-300">
                                {{ $template->assignee->name ?? __('Unassigned') }}
                            </td>
                            <td class="px-5 py-3 text-gray-600 dark:text-gray-300">
                                {{ collect($template->days_of_week)->map(fn ($d) => \App\Livewire\Rota\RecurringVisitTemplateComponent::DAY_LABELS[$d] ?? '?')->implode(', ') }}
                                · {{ substr($template->start_time, 0, 5) }}–{{ substr($template->end_time, 0, 5) }}
                            </td>
                            <td class="px-5 py-3">
                                <x-badge color="{{ $template->is_active ? 'success' : 'secondary' }}">
                                    {{ $template->is_active ? __('Active') : __('Inactive') }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-3 text-right space-x-2 whitespace-nowrap">
                                <button type="button" wire:click="openGenerateForm('{{ $template->id }}')"
                                    class="text-primary-600 hover:underline text-sm font-medium">{{ __('Generate') }}</button>
                                <button type="button" wire:click="openEditForm('{{ $template->id }}')"
                                    class="text-primary-600 hover:underline text-sm font-medium">{{ __('Edit') }}</button>
                                <button type="button" wire:click="toggleActive('{{ $template->id }}')"
                                    class="text-amber-600 hover:underline text-sm font-medium">
                                    {{ $template->is_active ? __('Deactivate') : __('Activate') }}
                                </button>
                                <button type="button" wire:click="delete('{{ $template->id }}')"
                                    wire:confirm="{{ __('Remove this template? Visits already generated from it are not affected.') }}"
                                    class="text-accent-500 hover:underline text-sm font-medium">{{ __('Delete') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10">
                                <x-empty-state title="{{ __('No recurring templates yet') }}"
                                    description="{{ __('Create one to stop re-entering the same weekly visit by hand.') }}"
                                    icon="ik ik-repeat" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-table>
    </x-card>

    <x-drawer name="template-form" title="{{ $editingId ? __('Edit template') : __('New recurring template') }}"
        width="w-[30rem]">
        <div class="space-y-4">
            <x-form.select name="formServiceUserId" label="{{ __('Service user') }}" wire:model="formServiceUserId"
                required>
                <option value="">{{ __('Select a service user') }}</option>
                @foreach ($serviceUsers as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </x-form.select>

            <x-form.select name="formCarerId" label="{{ __('Carer (optional)') }}" wire:model="formCarerId">
                <option value="">{{ __('Unassigned') }}</option>
                @foreach ($carers as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </x-form.select>

            <x-form.select name="formTemplateType" label="{{ __('Generates') }}" wire:model.live="formTemplateType"
                required>
                <option value="shift">{{ __('Recurring Shift (day/night, feeds payroll)') }}</option>
                <option value="care_call">{{ __('Recurring CareCall (welfare check, non-payroll)') }}</option>
            </x-form.select>

            @if ($formTemplateType === 'shift')
                <div class="grid grid-cols-2 gap-3">
                    <x-form.select name="formShiftType" label="{{ __('Shift type') }}" wire:model="formShiftType"
                        required>
                        <option value="day">{{ __('Day') }}</option>
                        <option value="night">{{ __('Night') }}</option>
                    </x-form.select>
                    <x-form.input type="number" min="0" name="formBreakMinutes" label="{{ __('Break (minutes)') }}"
                        wire:model="formBreakMinutes" />
                </div>
            @else
                <x-form.input name="formCallLabel" label="{{ __('Call label') }}" wire:model="formCallLabel"
                    placeholder="{{ __('e.g. Morning welfare check') }}" required />
            @endif

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Days of week') }}</label>
                <div class="flex flex-wrap gap-2">
                    @foreach (\App\Livewire\Rota\RecurringVisitTemplateComponent::DAY_LABELS as $value => $label)
                        <label
                            class="flex items-center gap-1.5 rounded-lg border border-gray-200 px-2.5 py-1 text-xs cursor-pointer dark:border-gray-700">
                            <input type="checkbox" wire:model="formDaysOfWeek" value="{{ $value }}"
                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            {{ __($label) }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <x-form.input type="time" name="formStartTime" label="{{ __('Start time') }}"
                    wire:model="formStartTime" required />
                <x-form.input type="time" name="formEndTime" label="{{ __('End time') }}" wire:model="formEndTime"
                    required />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <x-form.input type="date" name="formStartsOn" label="{{ __('Starts on') }}" wire:model="formStartsOn"
                    required />
                <x-form.input type="date" name="formEndsOn" label="{{ __('Ends on (optional)') }}"
                    wire:model="formEndsOn" />
            </div>

            <x-form.checkbox name="formIsActive" label="{{ __('Active') }}" wire:model="formIsActive" />

            <x-form.textarea name="formNotes" label="{{ __('Notes (optional)') }}" rows="2" wire:model="formNotes" />
        </div>

        <x-slot:footer>
            <x-button wire:click="save">{{ __('Save template') }}</x-button>
        </x-slot:footer>
    </x-drawer>

    <x-drawer name="generate-form" title="{{ __('Generate visits') }}" width="w-[24rem]">
        <div class="space-y-4">
            <p class="text-xs text-gray-500">
                {{ $generateTemplateId
                    ? __('Generates this template\'s visits for the chosen date range. Already-covered slots are skipped.')
                    : __('Generates every active template\'s visits for the chosen date range. Already-covered slots are skipped.') }}
            </p>
            <div class="grid grid-cols-2 gap-3">
                <x-form.input type="date" name="generateFrom" label="{{ __('From') }}" wire:model="generateFrom"
                    required />
                <x-form.input type="date" name="generateTo" label="{{ __('To') }}" wire:model="generateTo"
                    required />
            </div>
        </div>

        <x-slot:footer>
            <x-button wire:click="runGenerate">{{ __('Generate') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
