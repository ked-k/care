<div>
    <x-page-header title="{{ __('Rota') }}" subtitle="{{ __('Weekly schedules for carers and service users') }}"
        icon="ik ik-calendar" :breadcrumbs="['Home' => url('dashboard'), 'Rota' => null]">
        <x-button variant="primary" @click="$dispatch('open-drawer', 'new-rota-period')">
            <i class="ik ik-plus mr-1"></i>{{ __('New rota period') }}
        </x-button>
    </x-page-header>

    <x-card no-padding hover>
        <x-table :paginator="$periods" title="{{ __('Rota Periods') }}">
            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:text-gray-500">
                        <th class="px-5 py-3 font-medium">{{ __('Week Commencing') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Shifts') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-5 py-3 font-medium text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse ($periods as $period)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40" wire:key="period-{{ $period->id }}">
                            <td class="px-5 py-3 font-semibold text-gray-700 dark:text-gray-200">
                                {{ $period->week_commencing->format('d M Y') }}
                            </td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400">
                                {{ $period->shifts_count }} {{ __('shifts') }}
                            </td>
                            <td class="px-5 py-3">
                                <x-badge
                                    color="{{ match ($period->status) {
                                        'draft' => 'secondary',
                                        'published' => 'primary',
                                        'archived' => 'gray',
                                        default => 'secondary',
                                    } }}">
                                    {{ ucfirst($period->status) }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-3 text-right space-x-2 whitespace-nowrap">
                                <a href="{{ route('rota.builder', $period) }}" wire:navigate
                                    class="text-primary-600 hover:underline text-sm font-medium">{{ __('Open builder') }}</a>

                                @if ($period->status === 'draft')
                                    <button type="button" wire:click="publish('{{ $period->id }}')"
                                        class="text-green-600 hover:underline text-sm font-medium">{{ __('Publish') }}</button>
                                @endif

                                @if ($period->status === 'published')
                                    <button type="button" wire:click="generateTimesheets('{{ $period->id }}')"
                                        class="text-accent-600 hover:underline text-sm font-medium">{{ __('Generate timesheets') }}</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10">
                                <x-empty-state title="{{ __('No rota periods yet') }}"
                                    description="{{ __('Create your first weekly rota to start scheduling carers.') }}"
                                    icon="ik ik-calendar" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-table>
    </x-card>

    <x-drawer name="new-rota-period" title="{{ __('New rota period') }}" width="w-[28rem]">
        <div class="space-y-4">
            <x-form.input type="date" name="newWeekCommencing" label="{{ __('Week commencing') }}"
                wire:model="newWeekCommencing" required />
            <x-form.textarea name="newNotes" label="{{ __('Notes (optional)') }}" rows="3"
                wire:model="newNotes" />
        </div>

        <x-slot:footer>
            <x-button wire:click="createPeriod">{{ __('Create & open builder') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
