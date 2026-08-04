<div>
    <x-page-header title="{{ __('Analytics') }}" subtitle="{{ __('Agency-wide performance across every module') }}"
        icon="ik ik-bar-chart-2" :breadcrumbs="['Home' => url('dashboard'), 'Analytics' => null]">
        <select wire:model.live="range"
            class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm text-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            <option value="7">{{ __('Last 7 days') }}</option>
            <option value="30">{{ __('Last 30 days') }}</option>
            <option value="90">{{ __('Last 90 days') }}</option>
        </select>
    </x-page-header>

    {{-- Top-line KPIs --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card value="{{ $serviceUsers['active'] }}" label="{{ __('Active Service Users') }}" icon="ik ik-users"
            color="primary" />

        <x-stat-card
            value="{{ $shifts['scheduled'] > 0 ? round(($shifts['completed'] / $shifts['scheduled']) * 100) : 0 }}%"
            label="{{ __('Shift Completion Rate') }}" icon="ik ik-calendar" color="green" :spark="$shifts['weekly']" />

        <x-stat-card
            value="{{ $medications['adherenceRate'] !== null ? $medications['adherenceRate'] . '%' : __('No data') }}"
            label="{{ __('Medication Adherence') }}" icon="ik ik-heart" color="accent" />

        <x-stat-card value="{{ $safeguarding['total_open_cases'] }}" label="{{ __('Open Safeguarding Cases') }}"
            icon="ik ik-shield" color="{{ $safeguarding['total_open_cases'] > 0 ? 'amber' : 'green' }}" />
    </div>

    {{-- Charts row --}}
    <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-3">
        <x-card class="xl:col-span-2" hover>
            <x-slot:header>{{ __('Shift Completion — Last 8 Weeks') }}</x-slot:header>
            <x-chart.line height="h-64" color="green" :area="true" :data="$shifts['weekly']" :labels="['-7w', '-6w', '-5w', '-4w', '-3w', '-2w', '-1w', 'This week']"
                prefix="" suffix="%" />
        </x-card>

        <x-card hover>
            <x-slot:header>{{ __('Medication Outcomes') }}</x-slot:header>
            @if ($medications['total'] > 0)
                <x-chart.donut label="{{ $medications['total'] }}" sublabel="{{ __('Doses') }}" :segments="[
                    ['value' => $medications['given'], 'color' => 'green', 'label' => 'Given'],
                    ['value' => $medications['prompted'], 'color' => 'primary', 'label' => 'Prompted'],
                    ['value' => $medications['refused'], 'color' => 'accent', 'label' => 'Refused'],
                    ['value' => $medications['missed'], 'color' => 'amber', 'label' => 'Missed'],
                ]" />
            @else
                <x-empty-state title="{{ __('No medication data') }}"
                    description="{{ __('Nothing recorded in this period.') }}" icon="ik ik-heart" />
            @endif
        </x-card>
    </div>

    {{-- Second row --}}
    <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-3">
        <x-card hover>
            <x-slot:header>{{ __('Safeguarding by Status') }}</x-slot:header>
            @if ($safeguarding['total'] > 0)
                <x-chart.donut label="{{ $safeguarding['total'] }}" sublabel="{{ __('Reports') }}"
                    :segments="[
                        ['value' => $safeguarding['open'], 'color' => 'amber', 'label' => 'Open'],
                        ['value' => $safeguarding['escalated'], 'color' => 'accent', 'label' => 'Escalated'],
                        ['value' => $safeguarding['investigating'], 'color' => 'primary', 'label' => 'Investigating'],
                        ['value' => $safeguarding['resolved'], 'color' => 'green', 'label' => 'Resolved'],
                        ['value' => $safeguarding['closed'], 'color' => 'gray', 'label' => 'Closed'],
                    ]" />
            @else
                <x-empty-state title="{{ __('No reports') }}"
                    description="{{ __('Nothing reported — that\'s a good thing.') }}" icon="ik ik-shield" />
            @endif
        </x-card>

        <x-card hover>
            <x-slot:header>{{ __('Timesheets This Period') }}</x-slot:header>
            <x-chart.bar height="h-48" color="primary" prefix="" suffix="" :data="[
                $timesheets['draft'],
                $timesheets['submitted'],
                $timesheets['approved'],
                $timesheets['rejected'],
                $timesheets['paid'],
            ]"
                :labels="['Draft', 'Submitted', 'Approved', 'Rejected', 'Paid']" />
        </x-card>

        <x-card hover>
            <x-slot:header>{{ __('Care Plan Reviews') }}</x-slot:header>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">{{ __('On track') }}</span>
                    <x-badge color="success">{{ $carePlans['on_track'] }}</x-badge>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">{{ __('Due within 14 days') }}</span>
                    <x-badge color="amber">{{ $carePlans['due_soon'] }}</x-badge>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">{{ __('Overdue') }}</span>
                    <x-badge color="danger">{{ $carePlans['overdue'] }}</x-badge>
                </div>
                <div class="border-t border-gray-100 pt-3 flex items-center justify-between dark:border-gray-800">
                    <span
                        class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Total active plans') }}</span>
                    <span
                        class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $carePlans['total_active'] }}</span>
                </div>
            </div>
        </x-card>
    </div>

    {{-- Third row: consent + payroll snapshot --}}
    <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2">
        <x-card hover>
            <x-slot:header>{{ __('Consent Status') }}</x-slot:header>
            <x-chart.donut label="{{ $serviceUsers['active'] }}" sublabel="{{ __('Service Users') }}"
                :segments="[
                    ['value' => $serviceUsers['consented'], 'color' => 'green', 'label' => 'Recorded'],
                    ['value' => $serviceUsers['consent_pending'], 'color' => 'amber', 'label' => 'Pending'],
                ]" />
        </x-card>

        <x-card hover>
            <x-slot:header>{{ __('Latest Completed Payroll Run') }}</x-slot:header>
            @if ($payroll['latest_run'])
                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">{{ __('Reference') }}</span>
                        <span
                            class="font-semibold text-gray-700 dark:text-gray-200">{{ $payroll['latest_run']->reference }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">{{ __('Period') }}</span>
                        <span class="text-gray-700 dark:text-gray-200">
                            {{ $payroll['latest_run']->pay_period_start->format('d M') }} –
                            {{ $payroll['latest_run']->pay_period_end->format('d M Y') }}
                        </span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">{{ __('Gross') }}</span>
                        <span
                            class="text-gray-700 dark:text-gray-200">{{ number_format($payroll['latest_run']->total_gross, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">{{ __('Deductions') }}</span>
                        <span
                            class="text-amber-600">{{ number_format($payroll['latest_run']->total_deductions, 2) }}</span>
                    </div>
                    <div
                        class="border-t border-gray-100 pt-3 flex justify-between text-sm font-semibold dark:border-gray-800">
                        <span class="text-gray-700 dark:text-gray-200">{{ __('Net') }}</span>
                        <span class="text-primary-600">{{ number_format($payroll['latest_run']->total_net, 2) }}</span>
                    </div>
                </div>
            @else
                <x-empty-state title="{{ __('No completed runs yet') }}"
                    description="{{ __('Once a payroll run is marked paid, it appears here.') }}"
                    icon="ik ik-dollar-sign" />
            @endif
        </x-card>
    </div>
</div>
