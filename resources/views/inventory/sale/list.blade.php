@extends('inventory.layout')
@section('title', 'Sales')
@section('content')
<div x-data="{ invoiceOpen: false }">
    <x-page-header title="{{ __('Sales') }}" subtitle="{{ __('View, delete and update Sales') }}" icon="ik ik-shopping-cart"
                   :breadcrumbs="['Home' => url('dashboard'), 'Sales' => null]" />

    <x-table title="{{ __('Sales') }}" :total="2500" :from="1" :to="7" :static-pages="5" search
             add-url="/sales/create" add-label="{{ __('Add Sale') }}">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 font-medium" data-no-export><input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-200"></th>
                        <th class="px-5 py-3 font-medium">{{ __('Refeence No.') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Customer') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Warehouse') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Grand Total') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Paid') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Due') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Payment Status') }}</th>
                        <th class="px-5 py-3 font-medium" data-no-export>{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @php
                        $sales = [
                            ['REF_0011', 'John Doe', 'Warehouse 1', 'Completed', 'success', '$720', '$700', '$20', 'partial'],
                            ['REF_0012', 'THhomas Mour', 'Warehouse 1', 'Completed', 'success', '$1000', '$1000', '$0', 'paid'],
                            ['REF_0013', 'John Smith', 'Warehouse 2', 'Pending', 'gray', '$700', '$0', '$700', 'unpaid'],
                            ['REF_0014', 'Alexander Cook', 'Warehouse 1', 'Completed', 'success', '$1700', '$1700', '$0', 'paid'],
                            ['REF_0015', 'John Doe', 'Warehouse 1', 'Completed', 'success', '$1720', '$700', '$1020', 'overdue'],
                            ['REF_0016', 'Stuart Brod', 'Warehouse 1', 'Completed', 'success', '$170', '$170', '$0', 'paid'],
                            ['REF_0011', 'John Doe', 'Warehouse 1', 'Completed', 'success', '$720', '$700', '$20', 'overdue'],
                        ];
                    @endphp
                    @foreach ($sales as [$ref, $customer, $warehouse, $status, $statusColor, $total, $paid, $due, $payStatus])
                        <tr class="{{ $payStatus === 'overdue' ? 'hover:bg-gray-50 border-l-2 border-accent-500' : 'hover:bg-gray-50' }}">
                            <td class="px-5 py-3" data-no-export><input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-200"></td>
                            <td class="px-5 py-3"><button type="button" x-on:click="invoiceOpen = true" class="font-semibold text-primary-600 hover:underline">{{ $ref }}</button></td>
                            <td class="px-5 py-3 font-medium text-gray-700">{{ $customer }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ $warehouse }}</td>
                            <td class="px-5 py-3"><x-badge color="{{ $statusColor }}">{{ $status }}</x-badge></td>
                            <td class="px-5 py-3 text-gray-700">{{ $total }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ $paid }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ $due }}</td>
                            <td class="px-5 py-3"><x-status-pill :status="$payStatus" /></td>
                            <td class="px-5 py-3" data-no-export>
                                <div class="flex items-center gap-3">
                                    <a href="/sales/1/edit" class="text-green-500 hover:text-green-600" title="{{ __('Edit') }}"><i class="ik ik-edit"></i></a>
                                    <button type="button" x-on:click="invoiceOpen = true" class="text-gray-500 hover:text-gray-700" title="{{ __('Preveiw Invoice') }}"><i class="ik ik-file-text"></i></button>
                                    <a href="#" class="text-gray-500 hover:text-gray-700" title="{{ __('Invoice POS') }}"><i class="ik ik-printer"></i></a>
                                    <a href="#" class="text-gray-500 hover:text-gray-700" title="{{ __('Send on Email') }}"><i class="ik ik-mail"></i></a>
                                    <a href="#" class="text-accent-500 hover:text-accent-600" title="{{ __('Delete') }}"><i class="ik ik-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
    </x-table>

    <!-- Invoice preview modal -->
    <div x-show="invoiceOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" x-on:click="invoiceOpen = false"></div>
        <div class="relative max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-xl border border-gray-100 bg-white shadow-lg">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h5 class="font-semibold text-gray-700">{{ __('Preview Invoice') }}</h5>
                <button type="button" x-on:click="invoiceOpen = false" class="text-gray-400 hover:text-gray-600"><i class="ik ik-x"></i></button>
            </div>
            <div class="p-5 text-gray-600">
                @includeIf('common.invoice')
            </div>
        </div>
    </div>
</div>
@endsection
