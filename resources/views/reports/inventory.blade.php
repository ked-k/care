@extends('layouts.main')
@section('title', 'Inventory Report')
@section('content')
    <x-report-shell title="{{ __('Inventory Report') }}" subtitle="{{ __('Stock levels, valuation & movement') }}" icon="ik ik-package">

        <div class="mb-5 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card value="1,284" label="{{ __('Total SKUs') }}" icon="ik ik-package" color="primary" trend="+24" :spark="[10,12,11,14,13,16,18]" />
            <x-stat-card value="$642,180" label="{{ __('Stock Value') }}" icon="ik ik-dollar-sign" color="green" trend="+4.8%" :spark="[20,22,21,25,24,28,30]" />
            <x-stat-card value="37" label="{{ __('Low Stock') }}" icon="ik ik-alert-triangle" color="amber" trend="+6" :trendUp="false" :spark="[4,5,6,5,7,8,9]" />
            <x-stat-card value="12" label="{{ __('Out of Stock') }}" icon="ik ik-x-octagon" color="accent" trend="-3" :spark="[6,5,5,4,4,3,2]" />
        </div>

        <div class="mb-5 grid grid-cols-1 gap-5 xl:grid-cols-3">
            <x-card class="xl:col-span-2" hover>
                <x-slot:header>{{ __('Stock by Category') }}</x-slot:header>
                <x-chart.bar height="h-64" color="primary"
                             :data="[420, 380, 260, 510, 190, 340, 290]"
                             :labels="['Electronics','Apparel','Home','Grocery','Toys','Beauty','Sports']" suffix=" units" />
            </x-card>
            <x-card hover>
                <x-slot:header>{{ __('Valuation by Warehouse') }}</x-slot:header>
                <x-chart.donut label="$642k" sublabel="{{ __('Total value') }}"
                               :segments="[
                                   ['value' => 44, 'color' => 'primary', 'label' => 'Central'],
                                   ['value' => 31, 'color' => 'green', 'label' => 'East'],
                                   ['value' => 16, 'color' => 'amber', 'label' => 'West'],
                                   ['value' => 9, 'color' => 'purple', 'label' => 'Overflow'],
                               ]" />
            </x-card>
        </div>

        <x-table title="{{ __('Low Stock Items') }}" :total="37" :from="1" :to="5" :static-pages="5" search>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 font-medium">{{ __('Product') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('SKU') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('In Stock') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Reorder Level') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ([['Wireless Headphones','SKU-1042',6,20,'danger','Critical'],['Smart Watch Pro','SKU-2087',14,25,'danger','Low'],['Action Camera','SKU-3310',0,15,'danger','Out of stock'],['Leather Jacket','SKU-4521',18,30,'primary','Low'],['Gaming Joystick','SKU-5198',22,40,'primary','Low']] as [$prod,$sku,$stock,$reorder,$badge,$status])
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-700">{{ $prod }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ $sku }}</td>
                            <td class="px-5 py-3 font-medium text-gray-700">{{ $stock }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $reorder }}</td>
                            <td class="px-5 py-3"><x-badge :color="$badge">{{ __($status) }}</x-badge></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-table>
    </x-report-shell>
@endsection
