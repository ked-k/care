@extends('inventory.layout')
@section('title', 'Dashboard')
@section('content')
    <x-page-header title="{{ __('Dashboard') }}" subtitle="{{ __('Inventory overview') }}" icon="ik ik-grid"
                   :breadcrumbs="['Home' => url('dashboard'), 'Dashboard' => null]" />

    <!-- Stat cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $stats = [
                ['2,563', 'Products', 'fas fa-cube', 'accent', '+4.6%', true, [8,12,9,14,11,16]],
                ['3,612', 'Orders', 'ik ik-shopping-cart', 'primary', '+8.1%', true, [5,8,6,9,12,14]],
                ['865', 'Customers', 'ik ik-user', 'green', '+2.3%', true, [10,9,11,10,13,15]],
                ['35,500', 'Sales', 'fas fa-bangladeshi-taka-sign', 'amber', '-1.2%', false, [14,12,15,11,13,10]],
            ];
        @endphp
        @foreach ($stats as [$value, $label, $icon, $color, $trend, $up, $spark])
            <x-stat-card :value="__($value)" :label="__($label)" :icon="$icon" :color="$color"
                         :trend="$trend" :trendUp="$up" :spark="$spark" variant="gradient" />
        @endforeach
    </div>

    <!-- Mid row -->
    <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-3">
        <x-card title="{{ __('Realtime Profit') }}">
            <x-chart.line height="h-44" color="primary" :area="true"
                          :data="[18, 26, 21, 33, 28, 40, 35, 47]"
                          :labels="['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug']" prefix="$" suffix="k" />
        </x-card>
        <x-card title="{{ __('Sales Difference') }}">
            <x-chart.bar height="h-44" color="green"
                         :data="[34, 52, 41, 63, 48, 72, 55]"
                         :labels="['Mon','Tue','Wed','Thu','Fri','Sat','Sun']" />
        </x-card>
        <div class="rounded-xl bg-gradient-to-br from-emerald-400 to-teal-500 p-5 text-white shadow-sm dark:bg-none dark:bg-green-500/10 dark:text-gray-800 dark:ring-1 dark:ring-green-500/20">
            <div class="mb-6 flex items-start justify-between">
                <div>
                    <h6 class="text-sm text-white/80">{{ __('Sales In July') }}</h6>
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
    </div>

    <!-- New customers + products -->
    <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-3">
        <x-card title="{{ __('New Customers') }}">
            @php
                $customers = [
                    ['Alex Thompson', 'Cheers!', '1.jpg', true, null],
                    ['John Doue', 'stay hungry stay foolish!', '2.jpg', true, null],
                    ['Alex Thompson', 'Cheers!', '3.jpg', false, '30 min ago'],
                    ['John Doue', 'Cheers!', '4.jpg', false, '10 min ago'],
                ];
            @endphp
            <div class="space-y-4">
                @foreach ($customers as [$name, $msg, $img, $online, $ago])
                    <div class="flex items-center gap-3">
                        <span class="relative shrink-0">
                            <img src="{{ asset('img/users/'.$img) }}" alt="" class="h-10 w-10 rounded-full object-cover">
                            @if ($online)<span class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-white bg-green-500"></span>@endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <h6 class="truncate text-sm font-semibold text-gray-700">{{ __($name) }}</h6>
                            <p class="truncate text-xs text-gray-400">{{ __($msg) }}</p>
                        </div>
                        @if ($ago)<span class="whitespace-nowrap text-xs text-gray-400"><i class="far fa-clock mr-1"></i>{{ __($ago) }}</span>@endif
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card class="xl:col-span-2" no-padding>
            <x-slot:header>{{ __('New Products') }}</x-slot:header>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                            <th class="px-5 py-3 font-medium">{{ __('Product Name') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Image') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Price') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ([['HeadPhone','p1.jpg','$10'],['Iphone 6','p2.jpg','$20'],['Jacket','p3.jpg','$35'],['Sofa','p4.jpg','$85'],['Iphone 6','p2.jpg','$20']] as [$pname, $pimg, $price])
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 text-gray-700">{{ __($pname) }}</td>
                                <td class="px-5 py-3"><img src="{{ asset('img/widget/'.$pimg) }}" alt="" class="h-6 w-6 rounded object-cover"></td>
                                <td class="px-5 py-3"><span class="inline-block h-2.5 w-2.5 rounded-full bg-green-500"></span></td>
                                <td class="px-5 py-3 text-gray-700">{{ __($price) }}</td>
                                <td class="px-5 py-3">
                                    <a href="#!" class="mr-3 text-green-500 hover:text-green-600"><i class="ik ik-edit"></i></a>
                                    <a href="#!" class="text-accent-500 hover:text-accent-600"><i class="ik ik-trash-2"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    <!-- Application sales -->
    <div class="mt-5">
        <x-card no-padding>
            <x-slot:header>{{ __('Application Sales') }}</x-slot:header>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                            <th class="px-5 py-3 font-medium">{{ __('Application') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Sales') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Change') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Avg Price') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ([['Able Pro','Powerful Admin Theme','16,300',60,'$53','$15,652'],['Photoshop','Design Software','26,421',80,'$35','$18,785'],['Guruable','Best Admin Template','8,265',40,'$98','$9,652'],['Flatable','Admin App','10,652',55,'$20','$7,856']] as [$app, $desc, $sales, $change, $avg, $total])
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3">
                                    <h6 class="font-semibold text-gray-700">{{ __($app) }}</h6>
                                    <p class="text-xs text-gray-400">{{ __($desc) }}</p>
                                </td>
                                <td class="px-5 py-3 text-gray-700">{{ __($sales) }}</td>
                                <td class="px-5 py-3">
                                    <div class="h-1.5 w-24 rounded-full bg-gray-100">
                                        <div class="h-full rounded-full bg-primary-500" style="width: {{ $change }}%"></div>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-gray-700">{{ $avg }}</td>
                                <td class="px-5 py-3 font-medium text-primary-600">{{ __($total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-100 px-5 py-3 text-right">
                <a href="#!" class="text-sm font-medium text-primary-600 hover:underline">{{ __('View all Projects') }}</a>
            </div>
        </x-card>
    </div>
@endsection
