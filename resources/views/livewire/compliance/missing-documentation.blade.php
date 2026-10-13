<div>
    <x-page-header title="{{ __('Missing Documentation') }}"
        subtitle="{{ __('Care plan, assessment, safeguarding and staff-compliance gaps, by service user') }}"
        icon="ik ik-alert-triangle"
        :breadcrumbs="['Home' => url('dashboard'), 'Compliance Dashboard' => route('compliance.dashboard'), 'Missing Documentation' => null]">
        <div class="flex items-center gap-3">
            <label class="flex items-center gap-2 text-sm text-gray-500">
                <input type="checkbox" wire:model.live="showCompliantToo"
                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                {{ __('Show fully compliant too') }}
            </label>
            <x-button variant="outline" size="sm" wire:click="exportCsv">
                <i class="ik ik-download mr-1"></i>{{ __('Export CSV') }}
            </x-button>
        </div>
    </x-page-header>

    <x-card no-padding hover>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:text-gray-500">
                        <th class="px-5 py-3 font-medium">{{ __('Service User') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Care Plan') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Assessment') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Safeguarding') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Staff Compliance') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse ($rows as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40" wire:key="md-{{ $row['serviceUser']->id }}">
                            <td class="px-5 py-3 font-semibold text-gray-700 dark:text-gray-200">
                                {{ $row['serviceUser']->name }}
                            </td>
                            <td class="px-5 py-3">
                                @if ($row['carePlanGap'])
                                    <x-badge color="danger">{{ $row['carePlanGap'] }}</x-badge>
                                @else
                                    <x-badge color="success">{{ __('OK') }}</x-badge>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if ($row['assessmentGap'])
                                    <x-badge color="danger">{{ $row['assessmentGap'] }}</x-badge>
                                @else
                                    <x-badge color="success">{{ __('OK') }}</x-badge>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if ($row['safeguardingCount'] > 0)
                                    <x-badge color="danger">
                                        {{ trans_choice(':count open case|:count open cases', $row['safeguardingCount'], ['count' => $row['safeguardingCount']]) }}
                                    </x-badge>
                                @else
                                    <x-badge color="success">{{ __('OK') }}</x-badge>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if ($row['staffGaps']->isNotEmpty())
                                    <div class="flex flex-col gap-1">
                                        @foreach ($row['staffGaps'] as $gap)
                                            <x-badge color="warning">{{ $gap }}</x-badge>
                                        @endforeach
                                    </div>
                                @else
                                    <x-badge color="success">{{ __('OK') }}</x-badge>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10">
                                <x-empty-state title="{{ __('Nothing missing') }}"
                                    description="{{ __('Every service user has an active care plan, a current assessment, no open safeguarding cases, and fully compliant assigned carers.') }}"
                                    icon="ik ik-check-square" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
