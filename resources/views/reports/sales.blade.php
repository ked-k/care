@extends('layouts.main')
@section('title', 'Sales Report')
@section('content')
    <x-report-shell title="{{ __('Sales Report') }}" subtitle="{{ __('Revenue, orders & top products') }}" icon="ik ik-shopping-cart">

        <div class="mb-5 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card value="$128,450" label="{{ __('Total Revenue') }}" icon="ik ik-dollar-sign" color="primary" trend="+12.4%" :spark="[12,18,15,22,19,26,30]" />
            <x-stat-card value="3,612" label="{{ __('Orders') }}" icon="ik ik-shopping-cart" color="green" trend="+6.1%" :spark="[8,11,9,13,12,15,17]" />
            <x-stat-card value="$35.56" label="{{ __('Avg. Order Value') }}" icon="ik ik-trending-up" color="amber" trend="+2.0%" :spark="[10,12,11,13,12,14,15]" />
            <x-stat-card value="2.8%" label="{{ __('Refund Rate') }}" icon="ik ik-corner-up-left" color="accent" trend="-0.4%" :trendUp="false" :spark="[6,5,6,4,5,4,3]" />
        </div>

        <div class="mb-5 grid grid-cols-1 gap-5 xl:grid-cols-3">
            <x-card class="xl:col-span-2" hover>
                <x-slot:header>{{ __('Revenue Trend') }}</x-slot:header>
                <x-chart.line height="h-64" color="primary"
                              :data="[18, 24, 21, 30, 27, 36, 33, 42, 38, 47, 44, 52]"
                              :labels="['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']" prefix="$" suffix="k" />
            </x-card>
            <x-card hover>
                <x-slot:header>{{ __('Sales by Channel') }}</x-slot:header>
                <x-chart.donut label="3,612" sublabel="{{ __('Orders') }}"
                               :segments="[
                                   ['value' => 48, 'color' => 'primary', 'label' => 'Online'],
                                   ['value' => 32, 'color' => 'green', 'label' => 'POS'],
                                   ['value' => 20, 'color' => 'amber', 'label' => 'Wholesale'],
                               ]" />
            </x-card>
        </div>

        <x-table title="{{ __('Top Products') }}" :total="42" :from="1" :to="5" :static-pages="5" search>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 font-medium">{{ __('Product') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Units Sold') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Revenue') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Trend') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ([['HeadPhone','842','$8,420','green',[6,8,7,10,12,11,14]],['Smart Watch','651','$32,550','primary',[10,11,13,12,15,16,18]],['Camera','318','$38,160','amber',[14,12,11,13,12,10,9]],['Jacket','512','$17,920','green',[8,9,11,10,13,12,14]],['Joystick','489','$19,560','primary',[7,9,8,11,10,12,13]]] as [$prod,$units,$rev,$c,$spark])
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-700">{{ $prod }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $units }}</td>
                            <td class="px-5 py-3 font-medium text-primary-600">{{ $rev }}</td>
                            <td class="px-5 py-3"><x-chart.sparkline :data="$spark" :color="$c" class="h-7 w-20" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-table>
    </x-report-shell>
@endsection
