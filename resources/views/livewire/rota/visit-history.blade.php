<div>
    <x-page-header title="{{ __('Visit History') }}"
        subtitle="{{ __('Every scheduled visit across the agency, by date range') }}" icon="ik ik-list"
        :breadcrumbs="['Home' => url('dashboard'), 'Rota' => route('rota.index'), 'Visit History' => null]">
        <x-button variant="outline" size="sm" wire:click="exportCsv">
            <i class="ik ik-download mr-1"></i>{{ __('Export CSV') }}
        </x-button>
    </x-page-header>

    <x-card no-padding hover class="mb-4">
        <div class="flex flex-wrap items-center gap-3 p-4">
            <x-form.input type="date" name="fromDate" label="{{ __('From') }}" wire:model.live="fromDate" />
            <x-form.input type="date" name="toDate" label="{{ __('To') }}" wire:model.live="toDate" />

            <x-form.select name="serviceUserId" label="{{ __('Service user') }}" wire:model.live="serviceUserId">
                <option value="">{{ __('All service users') }}</option>
                @foreach ($serviceUsers as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </x-form.select>

            <x-form.select name="carerId" label="{{ __('Carer') }}" wire:model.live="carerId">
                <option value="">{{ __('All carers') }}</option>
                @foreach ($carers as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </x-form.select>

            <x-form.select name="shiftTypeFilter" label="{{ __('Type') }}" wire:model.live="shiftTypeFilter">
                <option value="">{{ __('All types') }}</option>
                <option value="day">{{ __('Day') }}</option>
                <option value="night">{{ __('Night') }}</option>
                <option value="live_in">{{ __('Live-in') }}</option>
            </x-form.select>

            <x-form.select name="statusFilter" label="{{ __('Status') }}" wire:model.live="statusFilter">
                <option value="">{{ __('All statuses') }}</option>
                <option value="scheduled">{{ __('Scheduled') }}</option>
                <option value="completed">{{ __('Completed') }}</option>
                <option value="missed">{{ __('Missed') }}</option>
                <option value="cancelled">{{ __('Cancelled') }}</option>
            </x-form.select>
        </div>
    </x-card>

    <x-card no-padding hover>
        <x-table :paginator="$visits" title="{{ __('Visits') }}">
            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:text-gray-500">
                        <th class="px-5 py-3 font-medium">{{ __('Date') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Service User') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Carer') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Type') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Time') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse ($visits as $visit)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40" wire:key="visit-{{ $visit->id }}">
                            <td class="px-5 py-3 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                {{ $visit->scheduled_start->format('d M Y') }}
                            </td>
                            <td class="px-5 py-3 font-semibold text-gray-700 dark:text-gray-200">
                                {{ $visit->serviceUser->name ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-gray-600 dark:text-gray-300">
                                {{ $visit->carer->name ?? __('Unassigned') }}
                                @if ($visit->isDoubleHanded())
                                    <i class="ik ik-users ml-1 text-primary-500" title="{{ __('Double-handed') }}"></i>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <x-badge color="{{ $visit->shift_type === 'live_in' ? 'primary' : 'secondary' }}">
                                    {{ ucfirst(str_replace('_', '-', $visit->shift_type ?? 'day')) }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                {{ $visit->scheduled_start->format('H:i') }}–{{ $visit->scheduled_end->format('H:i') }}
                            </td>
                            <td class="px-5 py-3">
                                <x-badge color="{{ match ($visit->status) {
                                    'completed' => 'success',
                                    'missed' => 'danger',
                                    'cancelled' => 'secondary',
                                    default => 'primary',
                                } }}">
                                    {{ ucfirst($visit->status) }}
                                </x-badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10">
                                <x-empty-state title="{{ __('No visits in this range') }}"
                                    description="{{ __('Widen the date range or clear a filter.') }}" icon="ik ik-list" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-table>
    </x-card>
</div>
