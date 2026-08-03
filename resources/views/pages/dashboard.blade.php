@extends('layouts.main')
@section('title', 'Dashboard')
@section('content')
    <x-page-header title="{{ __('Dashboard') }}" subtitle="{{ __('Welcome back, here is your store overview') }}" icon="ik ik-bar-chart-2"
                   :breadcrumbs="['Home' => url('dashboard'), 'Dashboard' => null]">
        <div x-data="{ period: 'Month' }" class="inline-flex rounded-lg border border-gray-200 bg-white p-0.5">
            <template x-for="p in ['Today', 'Week', 'Month', 'Year']" :key="p">
                <button @click="period = p" :class="period === p ? 'bg-primary-500 text-white' : 'text-gray-500 hover:bg-gray-100'"
                        class="rounded-md px-3 py-1.5 text-sm font-medium transition" x-text="p"></button>
            </template>
        </div>
    </x-page-header>

    <!-- Stat cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card value="2,563" label="{{ __('Products') }}" icon="fas fa-cube" color="accent"
                     trend="+4.6%" :spark="[8,12,9,14,11,16,13,18]" />
        <x-stat-card value="3,612" label="{{ __('Orders') }}" icon="ik ik-shopping-cart" color="primary"
                     trend="+8.1%" :spark="[5,8,6,9,12,10,14,17]" />
        <x-stat-card value="865" label="{{ __('Customers') }}" icon="ik ik-user" color="green"
                     trend="+2.3%" :spark="[10,9,11,10,13,12,15,16]" />
        <x-stat-card value="$35,500" label="{{ __('Sales') }}" icon="ik ik-dollar-sign" color="amber"
                     trend="-1.2%" :trendUp="false" :spark="[14,12,15,11,13,10,12,9]" />
    </div>

    <!-- Charts row -->
    <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-3">
        <x-card class="xl:col-span-2" hover>
            <x-slot:header>{{ __('Revenue Overview') }}</x-slot:header>
            <x-slot:actions>
                <span class="inline-flex items-center gap-1.5 text-xs text-gray-500"><span class="h-2.5 w-2.5 rounded-full bg-primary-500"></span>{{ __('This year') }}</span>
            </x-slot:actions>
            <x-chart.line height="h-64" color="primary"
                          :data="[12, 19, 14, 23, 18, 27, 22, 31, 26, 34, 29, 38]"
                          :labels="['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']"
                          prefix="$" suffix="k" />
        </x-card>

        <x-card hover>
            <x-slot:header>{{ __('Sales by Category') }}</x-slot:header>
            <x-chart.donut label="2,563" sublabel="{{ __('Total') }}"
                           :segments="[
                               ['value' => 42, 'color' => 'primary', 'label' => 'Electronics'],
                               ['value' => 28, 'color' => 'green', 'label' => 'Clothing'],
                               ['value' => 18, 'color' => 'amber', 'label' => 'Accessories'],
                               ['value' => 12, 'color' => 'accent', 'label' => 'Others'],
                           ]" />
        </x-card>
    </div>

    <!-- Secondary charts -->
    <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-3">
        <x-card hover>
            <x-slot:header>{{ __('Weekly Orders') }}</x-slot:header>
            <x-chart.bar height="h-48" color="primary" prefix="" suffix=" orders"
                         :data="[34, 52, 41, 63, 48, 72, 55]"
                         :labels="['Mon','Tue','Wed','Thu','Fri','Sat','Sun']" />
        </x-card>
        <x-card hover>
            <x-slot:header>{{ __('Sales Difference') }}</x-slot:header>
            <x-chart.line height="h-48" color="green" :area="true"
                          :data="[20, 14, 26, 18, 30, 24, 36]"
                          :labels="['W1','W2','W3','W4','W5','W6','W7']" prefix="$" suffix="k" />
        </x-card>
        <div class="rounded-2xl bg-gradient-to-br from-emerald-400 to-teal-500 p-5 text-white shadow-sm dark:bg-none dark:bg-green-500/10 dark:text-gray-800 dark:ring-1 dark:ring-green-500/20">
            <div class="mb-4 flex items-start justify-between">
                <div>
                    <h6 class="text-sm text-white/80">{{ __('Sales In July') }}</h6>
                    <h5 class="text-2xl font-bold">{{ __('$2,665.00') }}</h5>
                </div>
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15"><i class="ik ik-dollar-sign text-lg"></i></span>
            </div>
            <div class="mb-4 flex gap-6 text-sm">
                <div><p class="text-white/70">{{ __('Direct Sale') }}</p><p class="font-semibold">{{ __('$1,768') }}</p></div>
                <div><p class="text-white/70">{{ __('Referral') }}</p><p class="font-semibold">{{ __('$897') }}</p></div>
            </div>
            <x-chart.bar height="h-20" color="white"
                         :data="[40, 65, 50, 80, 60, 90, 70, 55, 75]" />
        </div>
    </div>

    <!-- New customers + products -->
    <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-3">
        <x-card hover>
            <x-slot:header>{{ __('New Customers') }}</x-slot:header>
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

        <x-card class="xl:col-span-2" no-padding hover>
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
                                <td class="px-5 py-3"><x-badge color="success">{{ __('Active') }}</x-badge></td>
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
        <x-card no-padding hover>
            <x-slot:header>{{ __('Application Sales') }}</x-slot:header>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                            <th class="px-5 py-3 font-medium">{{ __('Application') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Sales') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Trend') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Avg Price') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ([
                            ['Able Pro','Powerful Admin Theme','16,300','$53','$15,652','green',[8,10,9,12,14,13,16]],
                            ['Photoshop','Design Software','26,421','$35','$18,785','primary',[12,11,13,12,15,17,16]],
                            ['Guruable','Best Admin Template','8,265','$98','$9,652','amber',[14,12,11,10,9,11,8]],
                            ['Flatable','Admin App','10,652','$20','$7,856','accent',[9,11,10,13,11,14,15]],
                        ] as [$app, $desc, $sales, $avg, $total, $c, $spark])
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3">
                                    <h6 class="font-semibold text-gray-700">{{ __($app) }}</h6>
                                    <p class="text-xs text-gray-400">{{ __($desc) }}</p>
                                </td>
                                <td class="px-5 py-3 text-gray-700">{{ __($sales) }}</td>
                                <td class="px-5 py-3"><x-chart.sparkline :data="$spark" :color="$c" class="h-7 w-20" /></td>
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
