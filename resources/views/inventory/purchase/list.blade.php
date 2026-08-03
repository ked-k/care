@extends('inventory.layout')
@section('title', 'Purchases')
@section('content')
    <x-page-header title="{{ __('Purchases') }}" subtitle="{{ __('View, delete and update Purchases') }}" icon="ik ik-truck"
                   :breadcrumbs="['Home' => url('dashboard'), 'Purchases' => null]" />

    <x-table title="{{ __('Purchases') }}" :total="2500" :from="1" :to="7" :static-pages="5" search
             add-url="/purchases/create" add-label="{{ __('Add Purchase') }}">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 font-medium" data-no-export><input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-200"></th>
                        <th class="px-5 py-3 font-medium">{{ __('SL.') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Supplier') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Warehouse') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Grand Total') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-5 py-3 font-medium" data-no-export>{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @php
                        $purchases = [
                            ['1.', 'John Doe', 'Warehouse 1', '$720', 'unpaid'],
                            ['2.', 'THhomas Mour', 'Warehouse 1', '$1000', 'paid'],
                            ['3.', 'John Smith', 'Warehouse 2', '$700', 'sent'],
                            ['4.', 'Alexander Cook', 'Warehouse 1', '$1700', 'paid'],
                            ['5.', 'John Doe', 'Warehouse 1', '$1020', 'overdue'],
                            ['6.', 'Stuart Brod', 'Warehouse 1', '$170', 'partial'],
                            ['7.', 'John Doe', 'Warehouse 1', '$700', 'draft'],
                        ];
                    @endphp
                    @foreach ($purchases as [$sl, $supplier, $warehouse, $total, $status])
                        <tr class="{{ $status === 'overdue' ? 'hover:bg-gray-50 border-l-2 border-accent-500' : 'hover:bg-gray-50' }}">
                            <td class="px-5 py-3" data-no-export><input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-200"></td>
                            <td class="px-5 py-3 text-gray-500">{{ $sl }}</td>
                            <td class="px-5 py-3 font-medium text-gray-700">{{ $supplier }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ $warehouse }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ $total }}</td>
                            <td class="px-5 py-3"><x-status-pill :status="$status" /></td>
                            <td class="px-5 py-3" data-no-export>
                                <div class="flex items-center gap-3">
                                    <a href="/purchases/1/edit" class="text-green-500 hover:text-green-600" title="{{ __('Edit') }}"><i class="ik ik-edit"></i></a>
                                    <a href="#" class="text-accent-500 hover:text-accent-600" title="{{ __('Delete') }}"><i class="ik ik-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
    </x-table>
@endsection
