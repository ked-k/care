@extends('layouts.main')
@section('title', 'Profit & Loss')
@section('content')
    <x-report-shell title="{{ __('Profit & Loss') }}" subtitle="{{ __('Income vs expense and net margin') }}" icon="ik ik-bar-chart-2">

        <div class="mb-5 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card value="$486,200" label="{{ __('Revenue') }}" icon="ik ik-dollar-sign" color="primary" trend="+9.3%" :spark="[18,21,20,25,24,28,31]" />
            <x-stat-card value="$312,840" label="{{ __('Expenses') }}" icon="ik ik-credit-card" color="amber" trend="+5.1%" :trendUp="false" :spark="[14,15,16,15,18,17,19]" />
            <x-stat-card value="$173,360" label="{{ __('Gross Profit') }}" icon="ik ik-trending-up" color="green" trend="+12.7%" :spark="[8,10,9,12,13,15,17]" />
            <x-stat-card value="35.7%" label="{{ __('Net Margin') }}" icon="ik ik-percent" color="purple" trend="+1.8%" :spark="[6,7,7,8,8,9,10]" />
        </div>

        <div class="mb-5 grid grid-cols-1 gap-5 xl:grid-cols-2">
            <x-card hover>
                <x-slot:header>{{ __('Income') }}</x-slot:header>
                <x-chart.line height="h-64" color="green"
                              :data="[32, 38, 35, 44, 41, 49, 46, 55, 52, 61, 58, 67]"
                              :labels="['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']" prefix="$" suffix="k" />
            </x-card>
            <x-card hover>
                <x-slot:header>{{ __('Expense') }}</x-slot:header>
                <x-chart.bar height="h-64" color="amber"
                             :data="[22, 25, 23, 29, 27, 32, 30, 36, 34, 39, 37, 42]"
                             :labels="['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']" prefix="$" suffix="k" />
            </x-card>
        </div>

        <x-table title="{{ __('Breakdown by Account') }}" :total="8" :from="1" :to="6" :static-pages="2" search>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 font-medium">{{ __('Account') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Type') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Amount') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ([['Product Sales','Income','success','$412,500'],['Service Revenue','Income','success','$73,700'],['Cost of Goods Sold','Expense','danger','$198,400'],['Payroll','Expense','danger','$64,200'],['Marketing','Expense','danger','$28,900'],['Rent & Utilities','Expense','danger','$21,340']] as [$account,$type,$badge,$amount])
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-700">{{ $account }}</td>
                            <td class="px-5 py-3"><x-badge :color="$badge">{{ __($type) }}</x-badge></td>
                            <td class="px-5 py-3 font-medium text-gray-700">{{ $amount }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-table>
    </x-report-shell>
@endsection
