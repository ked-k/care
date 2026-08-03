@extends('layouts.main')
@section('title', 'Widget Chart')
@section('content')
    <x-page-header title="{{ __('Widget Chart') }}" subtitle="{{ __('lorem ipsum dolor sit amet, consectetur adipisicing elit') }}" icon="ik ik-menu" :breadcrumbs="['Home' => url('dashboard'), 'Widgets' => '#', 'Chart' => null]" />

    <!-- Round chart statistic cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $roundStats = [
                ['42%', '144', 'Leads', 'accent', true, [6,9,7,11,9,13]],
                ['56%', '102', 'Goals', 'primary', true, [8,7,10,9,12,14]],
                ['83%', '124', 'Contacts', 'green', true, [10,12,11,14,13,16]],
                ['42%', '84', 'Accounts', 'amber', false, [14,12,13,11,12,10]],
            ];
        @endphp
        @foreach ($roundStats as [$pct, $value, $label, $color, $up, $spark])
            <x-stat-card :value="$value" :label="__($label)" :color="$color"
                         :trend="$pct" :trendUp="$up" :spark="$spark" variant="gradient" />
        @endforeach
    </div>

    <!-- product bar chart cards -->
    <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $prodBars = [['Order Received', 'ik ik-bar-chart-2'], ['Total Sales', 'ik ik-bar-chart-2'], ['Total Profit', 'ik ik-bar-chart-2'], ['Total Profit', 'ik ik-bar-chart-2']];
        @endphp
        @foreach ($prodBars as [$title, $icon])
            <x-card>
                <x-slot:header>{{ __($title) }}</x-slot:header>
                <p class="mb-4 text-sm text-gray-400">June - July</p>
                <div class="grid grid-cols-2 gap-4 text-center">
                    <div>
                        <div class="mb-2"><x-chart.bar height="h-20" color="green" :data="[30, 52, 41, 63, 48, 72]" /></div>
                        <h6 class="text-sm font-semibold text-gray-700">$ 56,480<i class="fas fa-caret-up ml-2 text-green-500"></i></h6>
                    </div>
                    <div>
                        <div class="mb-2"><x-chart.bar height="h-20" color="accent" :data="[60, 44, 55, 38, 50, 32]" /></div>
                        <h6 class="text-sm font-semibold text-gray-700">$ 32,432<i class="fas fa-caret-down ml-2 text-accent-500"></i></h6>
                    </div>
                </div>
            </x-card>
        @endforeach
    </div>

    <!-- sale cards -->
    <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <x-card>
            <x-slot:header>{{ __('Order Overview') }}</x-slot:header>
            <div class="text-center">
                <div class="mx-auto inline-block"><x-chart.radial :value="35" color="primary" sublabel="{{ __('Orders') }}" size="h-28 w-28" /></div>
                <h6 class="mt-4 font-semibold text-gray-700">Congratulation!</h6>
                <p class="text-sm text-gray-500">You have +75 orders compared to last month</p>
            </div>
        </x-card>
        <x-card>
            <x-slot:header>{{ __('Unique Visitors') }}</x-slot:header>
            <div class="text-center">
                <div class="mx-auto mb-4 flex h-28 w-28 items-center justify-center rounded-full border-8 border-accent-100 border-t-accent-500">
                    <img src="{{ asset('img/users/2.jpg') }}" alt="User-Image" class="h-20 w-20 rounded-full object-cover">
                </div>
                <div class="grid grid-cols-2 divide-x divide-gray-100">
                    <div>
                        <span class="mx-auto mb-1 block h-1 w-6 rounded bg-gray-300"></span>
                        <h5 class="font-bold text-gray-700">1,507</h5>
                        <p class="text-sm text-gray-500">Female</p>
                    </div>
                    <div>
                        <span class="mx-auto mb-1 block h-1 w-6 rounded bg-accent-500"></span>
                        <h5 class="font-bold text-gray-700">1,264</h5>
                        <p class="text-sm text-gray-500">male</p>
                    </div>
                </div>
            </div>
        </x-card>
        <x-card>
            <x-slot:header>{{ __('Sales Statistics') }}</x-slot:header>
            <div class="text-center">
                <div class="mx-auto mb-4 flex h-28 w-28 items-center justify-center rounded-full border-8 border-accent-100 border-t-accent-500">
                    <img src="{{ asset('img/users/3.jpg') }}" alt="User-Image" class="h-20 w-20 rounded-full object-cover">
                </div>
                <div class="grid grid-cols-2 divide-x divide-gray-100">
                    <div>
                        <h5 class="font-bold text-gray-700">5,632</h5>
                        <p class="text-sm text-gray-500">Weekly</p>
                    </div>
                    <div>
                        <h5 class="font-bold text-gray-700">23,131</h5>
                        <p class="text-sm text-gray-500">Monthly</p>
                    </div>
                </div>
            </div>
        </x-card>
        <x-card>
            <x-slot:header>{{ __('Summary') }}</x-slot:header>
            <div class="text-center">
                <div class="mx-auto mb-4 inline-block"><x-chart.radial :value="35" color="primary" size="h-16 w-16" :thickness="8" /></div>
            </div>
            <p class="mb-2 flex justify-between text-sm text-gray-600">House <span>90%</span></p>
            <div class="mb-5 h-2 rounded-full bg-gray-100"><div class="h-full rounded-full bg-primary-500" style="width:90%"></div></div>
            <p class="mb-2 flex justify-between text-sm text-gray-600">Food <span>30%</span></p>
            <div class="mb-5 h-2 rounded-full bg-gray-100"><div class="h-full rounded-full bg-primary-500" style="width:30%"></div></div>
            <div class="text-center">
                <a href="#!" class="text-sm text-gray-500 hover:text-primary-600"><i class="fas fa-bars mr-2"></i>View More</a>
            </div>
        </x-card>
    </div>

    <!-- sale 2 cards -->
    <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-3">
        <x-card>
            <x-slot:header>{{ __('Realtime Profit') }}</x-slot:header>
            <x-chart.line height="h-44" color="primary" :area="true"
                          :data="[18, 26, 21, 33, 28, 40, 35, 47]"
                          :labels="['1','2','3','4','5','6','7','8']" prefix="$" suffix="k" />
        </x-card>
        <x-card>
            <x-slot:header>{{ __('Sales Difference') }}</x-slot:header>
            <x-chart.bar height="h-44" color="accent"
                         :data="[34, 52, 41, 63, 48, 72, 55]"
                         :labels="['Mon','Tue','Wed','Thu','Fri','Sat','Sun']" />
        </x-card>
        <div class="rounded-xl bg-gradient-to-br from-emerald-400 to-teal-500 p-5 text-white shadow-sm dark:bg-none dark:bg-green-500/10 dark:text-gray-800 dark:ring-1 dark:ring-green-500/20">
            <div class="mb-6 flex items-start justify-between">
                <div>
                    <h6 class="text-sm text-white/80">Sales In July</h6>
                    <h5 class="text-xl font-bold">$2665.00</h5>
                </div>
                <div class="text-center">
                    <p class="text-xs text-white/80">Direct Sale</p>
                    <h6 class="font-semibold">$1768</h6>
                </div>
                <div class="text-center">
                    <p class="text-xs text-white/80">Referal</p>
                    <h6 class="font-semibold">$897</h6>
                </div>
            </div>
            <x-chart.bar height="h-20" color="white" :data="[40, 65, 50, 80, 60, 90, 70, 55, 75]" />
        </div>
    </div>

    <!-- sale revenue cards -->
    <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-3">
        <x-card class="xl:col-span-2">
            <x-slot:header>{{ __('Deals Analytics') }}</x-slot:header>
            <x-chart.bar height="h-72" color="primary" suffix=" deals"
                         :data="[34, 52, 41, 63, 48, 72, 55, 80, 66, 90, 74, 95]"
                         :labels="['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']" />
        </x-card>
        <x-card>
            <x-slot:header>{{ __('Total Revenue') }}</x-slot:header>
            <div class="text-center">
                <div class="mx-auto inline-block"><x-chart.radial :value="80" color="primary" label="120" sublabel="{{ __('Revenue') }}" size="h-28 w-28" /></div>
                <h6 class="mt-6 text-sm text-gray-500">Today’s Total Sales</h6>
                <h3 class="mb-6 text-2xl font-bold text-gray-700">100</h3>
                <div class="grid grid-cols-3">
                    <div>
                        <p class="text-sm text-gray-500">Target</p>
                        <h3 class="text-lg font-bold text-amber-500">$1253</h3>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Last Week</p>
                        <h3 class="text-lg font-bold text-amber-500">$795</h3>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Last Month</p>
                        <h3 class="text-lg font-bold text-amber-500">$978</h3>
                    </div>
                </div>
            </div>
        </x-card>
    </div>

    <!-- map card -->
    <div class="mt-5">
        <x-card>
            <x-slot:header>{{ __('Deals Analytics') }}</x-slot:header>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <x-chart.line height="h-72" color="cyan" :area="true"
                                  :data="[120, 98, 140, 115, 165, 138, 188, 160, 210]"
                                  :labels="['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep']" prefix="$" />
                </div>
                <div class="flex items-center justify-center">
                    <x-chart.donut label="3,210" sublabel="{{ __('Deals') }}" :legend="false"
                                   :segments="[
                                       ['value' => 44, 'color' => 'primary', 'label' => 'Won'],
                                       ['value' => 32, 'color' => 'amber', 'label' => 'Pending'],
                                       ['value' => 24, 'color' => 'accent', 'label' => 'Lost'],
                                   ]" />
                </div>
            </div>
        </x-card>
    </div>

    <!-- income cards -->
    <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @php
            $incomeCards = [
                ['Sale Income', 'line', 'primary', [14, 22, 18, 30, 25, 38, 32, 44]],
                ['Rent Income', 'line', 'green', [10, 16, 13, 22, 18, 28, 24, 34]],
                ['Income Analysis', 'bar', 'amber', [34, 52, 41, 63, 48, 72, 55]],
            ];
        @endphp
        @foreach ($incomeCards as [$title, $type, $color, $data])
            <x-card no-padding>
                <x-slot:header>{{ __($title) }}</x-slot:header>
                <div class="p-5">
                    <div class="grid grid-cols-3 text-center">
                        <div>
                            <p class="mb-1 text-sm text-gray-400">Overall</p>
                            <h6 class="font-semibold text-gray-700">68.52%</h6>
                        </div>
                        <div>
                            <p class="mb-1 text-sm text-gray-400">Monthly</p>
                            <h6 class="font-semibold text-gray-700">28.90%</h6>
                        </div>
                        <div>
                            <p class="mb-1 text-sm text-gray-400">Day</p>
                            <h6 class="font-semibold text-gray-700">13.50%</h6>
                        </div>
                    </div>
                </div>
                <div class="border-t border-gray-100 px-5 pb-4">
                    @if ($type === 'bar')
                        <x-chart.bar height="h-32" :color="$color" :data="$data" />
                    @else
                        <x-chart.line height="h-32" :color="$color" :area="true" :data="$data" />
                    @endif
                </div>
            </x-card>
        @endforeach
    </div>
@endsection
