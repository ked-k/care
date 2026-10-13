<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    <x-page-header title="{{ __('Carer Availability') }}"
        subtitle="{{ __('Date ranges a carer should not be scheduled — leave, sickness, or any other reason') }}"
        icon="ik ik-calendar"
        :breadcrumbs="['Home' => url('dashboard'), 'Rota' => route('rota.index'), 'Carer Availability' => null]">
        <x-button variant="primary" size="sm" wire:click="openCreateForm">
            <i class="ik ik-plus mr-1"></i>{{ __('New unavailability') }}
        </x-button>
    </x-page-header>

    <x-card no-padding hover>
        <x-table :paginator="$records" title="{{ __('Carer Availability') }}">
            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:text-gray-500">
                        <th class="px-5 py-3 font-medium">{{ __('Carer') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Start') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('End') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Reason') }}</th>
                        <th class="px-5 py-3 font-medium text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse ($records as $record)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40" wire:key="unavail-{{ $record->id }}">
                            <td class="px-5 py-3 font-semibold text-gray-700 dark:text-gray-200">
                                {{ $record->user->name ?? __('Unknown') }}
                            </td>
                            <td class="px-5 py-3 text-gray-600 dark:text-gray-300">
                                {{ $record->start_date->format('d M Y') }}
                            </td>
                            <td class="px-5 py-3 text-gray-600 dark:text-gray-300">
                                {{ $record->end_date->format('d M Y') }}
                            </td>
                            <td class="px-5 py-3 text-gray-600 dark:text-gray-300">
                                {{ $record->reason ?: '—' }}
                            </td>
                            <td class="px-5 py-3 text-right space-x-2 whitespace-nowrap">
                                <button type="button" wire:click="openEditForm('{{ $record->id }}')"
                                    class="text-primary-600 hover:underline text-sm font-medium">{{ __('Edit') }}</button>
                                <button type="button" wire:click="delete('{{ $record->id }}')"
                                    wire:confirm="{{ __('Remove this unavailability record?') }}"
                                    class="text-accent-500 hover:underline text-sm font-medium">{{ __('Delete') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10">
                                <x-empty-state title="{{ __('No unavailability recorded') }}"
                                    description="{{ __('Record a date range here when a carer should not be scheduled, so the Rota Builder can warn before they are.') }}"
                                    icon="ik ik-calendar" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-table>
    </x-card>

    <x-drawer name="availability-form" title="{{ $editingId ? __('Edit unavailability') : __('New unavailability') }}"
        width="w-[26rem]">
        <div class="space-y-4">
            <x-form.select name="formCarerId" label="{{ __('Carer') }}" wire:model="formCarerId" required>
                <option value="">{{ __('Select a carer') }}</option>
                @foreach ($carers as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </x-form.select>

            <div class="grid grid-cols-2 gap-3">
                <x-form.input type="date" name="formStartDate" label="{{ __('Start date') }}"
                    wire:model="formStartDate" required />
                <x-form.input type="date" name="formEndDate" label="{{ __('End date') }}" wire:model="formEndDate"
                    required />
            </div>

            <x-form.input name="formReason" label="{{ __('Reason (optional)') }}" wire:model="formReason" />
        </div>

        <x-slot:footer>
            <x-button wire:click="save">{{ __('Save') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
