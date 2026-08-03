@extends('accounting.layout')
@section('title', 'Accounting Dashboard')
@section('content')
    <x-page-header title="{{ __('Accounting Dashboard') }}" subtitle="{{ __('Overview of your finances') }}"
                   icon="ik ik-bar-chart-2" :breadcrumbs="['Home' => url('accounting'), 'Dashboard' => null]" />

    <!-- Stat cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $stats = [
                ['144', __('Bills'), '42%', 'accent', true, [6,9,7,11,9,13]],
                ['10', __('Goals'), '56%', 'primary', true, [8,7,10,9,12,14]],
                ['124', __('Contacts'), '83%', 'green', true, [10,12,11,14,13,16]],
                ['84', __('Invoices'), '42%', 'amber', false, [14,12,13,11,12,10]],
            ];
        @endphp
        @foreach ($stats as [$value, $label, $percent, $color, $up, $spark])
            <x-stat-card :value="$value" :label="$label" :color="$color"
                         :trend="$percent" :trendUp="$up" :spark="$spark" variant="gradient" />
        @endforeach
    </div>

    <!-- Mid row -->
    <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-3">
        <div class="space-y-5 xl:col-span-2">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <x-card title="{{ __('Realtime Profit') }}">
                    <x-chart.line height="h-48" color="green" :area="true"
                                  :data="[18, 26, 21, 33, 28, 40, 35, 47]"
                                  :labels="['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug']" prefix="$" suffix="k" />
                </x-card>
                <x-card title="{{ __('Income & Expense') }}">
                    <x-chart.bar height="h-48" color="primary" prefix="$"
                                 :data="[34, 52, 41, 63, 48, 72, 55]"
                                 :labels="['Mon','Tue','Wed','Thu','Fri','Sat','Sun']" />
                </x-card>
            </div>

            <x-card no-padding>
                <x-slot:header>{{ __('Recent Invoices') }}</x-slot:header>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                                <th class="px-5 py-3 font-medium">{{ __('#') }}</th>
                                <th class="px-5 py-3 font-medium">{{ __('Customer') }}</th>
                                <th class="px-5 py-3 font-medium">{{ __('Issue Date') }}</th>
                                <th class="px-5 py-3 font-medium">{{ __('Due Date') }}</th>
                                <th class="px-5 py-3 font-medium">{{ __('Amount') }}</th>
                                <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 text-gray-700">#INV00001</td>
                                <td class="px-5 py-3 text-gray-700">Apple Company</td>
                                <td class="px-5 py-3 text-gray-500">23/05/2017</td>
                                <td class="px-5 py-3 text-gray-500">04/08/2018</td>
                                <td class="px-5 py-3 text-gray-700">$55.00</td>
                                <td class="px-5 py-3"><x-badge color="primary">Draft</x-badge></td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 text-gray-700">#INV00002</td>
                                <td class="px-5 py-3 text-gray-700">Envato Pvt Ltd.</td>
                                <td class="px-5 py-3 text-gray-500">20/03/2017</td>
                                <td class="px-5 py-3 text-gray-500">04/08/2019</td>
                                <td class="px-5 py-3 text-gray-700">$551.00</td>
                                <td class="px-5 py-3"><x-badge color="danger">Unpaid</x-badge></td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 text-gray-700">#INV00003</td>
                                <td class="px-5 py-3 text-gray-700">Dribble Company</td>
                                <td class="px-5 py-3 text-gray-500">13/05/2017</td>
                                <td class="px-5 py-3 text-gray-500">03/01/2018</td>
                                <td class="px-5 py-3 text-gray-700">$655.00</td>
                                <td class="px-5 py-3"><x-badge color="gray">Sent</x-badge></td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 text-gray-700">#INV00004</td>
                                <td class="px-5 py-3 text-gray-700">Adobe Family</td>
                                <td class="px-5 py-3 text-gray-500">11/01/2016</td>
                                <td class="px-5 py-3 text-gray-500">02/03/2017</td>
                                <td class="px-5 py-3 text-gray-700">$535.00</td>
                                <td class="px-5 py-3"><x-badge color="success">Paid</x-badge></td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 text-gray-700">#INV00005</td>
                                <td class="px-5 py-3 text-gray-700">Apple Company</td>
                                <td class="px-5 py-3 text-gray-500">23/05/2017</td>
                                <td class="px-5 py-3 text-gray-500">04/08/2018</td>
                                <td class="px-5 py-3 text-gray-700">$25.00</td>
                                <td class="px-5 py-3"><x-badge color="danger">Unpaid</x-badge></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </x-card>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <x-card title="{{ __('Income') }}">
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs text-gray-400">{{ __('Overall') }}</p>
                            <h6 class="font-semibold text-gray-700">68.52%</h6>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">{{ __('Monthly') }}</p>
                            <h6 class="font-semibold text-gray-700">28.90%</h6>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">{{ __('Day') }}</p>
                            <h6 class="font-semibold text-gray-700">13.50%</h6>
                        </div>
                    </div>
                    <div class="mt-4">
                        <x-chart.line height="h-24" color="green" :area="true" :data="[12, 18, 14, 22, 19, 28, 24, 32]" />
                    </div>
                </x-card>
                <x-card title="{{ __('Expense') }}">
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs text-gray-400">{{ __('Overall') }}</p>
                            <h6 class="font-semibold text-gray-700">68.52%</h6>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">{{ __('Monthly') }}</p>
                            <h6 class="font-semibold text-gray-700">28.90%</h6>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">{{ __('Day') }}</p>
                            <h6 class="font-semibold text-gray-700">13.50%</h6>
                        </div>
                    </div>
                    <div class="mt-4">
                        <x-chart.bar height="h-24" color="accent" :data="[22, 14, 26, 18, 30, 24, 16]" />
                    </div>
                </x-card>
            </div>
        </div>

        <div class="space-y-5">
            <div class="rounded-xl bg-gradient-to-br from-emerald-400 to-teal-500 p-5 text-white shadow-sm dark:bg-none dark:bg-green-500/10 dark:text-gray-800 dark:ring-1 dark:ring-green-500/20">
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <h6 class="text-sm text-white/80">{{ __('Cashflow') }}</h6>
                        <h5 class="text-xl font-bold">{{ __('$2665.00') }}</h5>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-white/80">{{ __('Direct Sale') }}</p>
                        <h6 class="font-semibold">{{ __('$1768') }}</h6>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-white/80">{{ __('Referal') }}</p>
                        <h6 class="font-semibold">{{ __('$897') }}</h6>
                    </div>
                </div>
                <x-chart.bar height="h-20" color="white" :data="[40, 65, 50, 80, 60, 90, 70, 55, 75]" />
            </div>

            <x-card title="{{ __('Todos') }}">
                <ul class="space-y-4">
                    <li class="border-l-2 border-primary-400 pl-3">
                        <p class="text-xs text-gray-400">
                            <span class="font-medium text-primary-600">Meeting</span> - Upcoming in 5 days
                        </p>
                        <p class="text-sm text-gray-700">Meeting with Sara in the Caffee Caldo for Brunch</p>
                        <small class="text-xs text-gray-400">Scheduled for 16th Mar, 2017</small>
                    </li>
                    <li class="border-l-2 border-primary-400 pl-3">
                        <p class="text-xs text-gray-400">
                            <span class="font-medium text-primary-600">Meeting</span> - Delay 7 days
                        </p>
                        <p class="text-sm text-gray-700">Technical management meeting</p>
                        <small class="text-xs text-gray-400">Completed 15 days ago</small>
                    </li>
                    <li class="border-l-2 border-gray-200 pl-3 opacity-60">
                        <p class="text-xs text-gray-400">
                            <span class="font-medium text-accent-600">Transfer</span> - Completed
                        </p>
                        <p class="text-sm text-gray-700 line-through">Transfer all domain names as soon as possible!</p>
                        <small class="text-xs text-gray-400">Completed 2 days ago</small>
                    </li>
                </ul>
            </x-card>
        </div>
    </div>
@endsection
