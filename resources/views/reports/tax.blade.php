@extends('layouts.main')
@section('title', 'Tax Summary')
@section('content')
    <x-report-shell title="{{ __('Tax Summary') }}" subtitle="{{ __('Collected & payable tax by period') }}" icon="ik ik-percent">

        <div class="mb-5 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card value="$48,920" label="{{ __('Tax Collected') }}" icon="ik ik-dollar-sign" color="primary" trend="+7.2%" :spark="[10,12,11,14,15,17,19]" />
            <x-stat-card value="$21,460" label="{{ __('Tax Paid') }}" icon="ik ik-credit-card" color="cyan" trend="+3.4%" :spark="[6,7,7,8,9,9,10]" />
            <x-stat-card value="$27,460" label="{{ __('Net Payable') }}" icon="ik ik-trending-up" color="amber" trend="+9.8%" :trendUp="false" :spark="[8,9,10,11,12,13,15]" />
            <x-stat-card value="2" label="{{ __('Filings Due') }}" icon="ik ik-calendar" color="accent" trend="{{ __('This quarter') }}" :spark="[1,2,1,2,2,1,2]" />
        </div>

        <div class="mb-5 grid grid-cols-1 gap-5">
            <x-card hover>
                <x-slot:header>{{ __('Tax by Month') }}</x-slot:header>
                <x-chart.bar height="h-64" color="primary"
                             :data="[3.2, 3.6, 3.4, 4.1, 3.9, 4.5, 4.2, 4.8, 4.6, 5.1, 4.9, 5.4]"
                             :labels="['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']" prefix="$" suffix="k" />
            </x-card>
        </div>

        <x-table title="{{ __('Tax by Rate') }}" :total="5" :from="1" :to="5" :static-pages="1" search>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 font-medium">{{ __('Rate') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Taxable Amount') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Tax Collected') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ([['Standard (20%)','$182,400','$36,480'],['Reduced (10%)','$84,600','$8,460'],['Low (5%)','$52,300','$2,615'],['Zero (0%)','$41,200','$0'],['Digital (8%)','$17,075','$1,365']] as [$rate,$taxable,$collected])
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-700">{{ $rate }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $taxable }}</td>
                            <td class="px-5 py-3 font-medium text-primary-600">{{ $collected }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-table>
    </x-report-shell>
@endsection
