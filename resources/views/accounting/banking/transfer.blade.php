@extends('accounting.layout')
@section('title', 'Transfer')
@section('content')

@php
$transfers = [
    ['date' => '20 Nov, 2022', 'from' => '123456789012', 'to' => '234567890123', 'amount' => '$1000', 'reference' => 'TX12345', 'description' => 'Regarding Description'],
    ['date' => '17 Nov, 2022', 'from' => '234567890123', 'to' => '345678901234', 'amount' => '$2000', 'reference' => 'TX12346', 'description' => 'Investment payment'],
    ['date' => '12 Nov, 2022', 'from' => '345678901234', 'to' => '456789012345', 'amount' => '$3000', 'reference' => 'TX12347', 'description' => 'Consultant fee'],
    ['date' => '12 Nov, 2022', 'from' => '456789012345', 'to' => '567890123456', 'amount' => '$4000', 'reference' => 'TX12348', 'description' => 'Supplier payment'],
    ['date' => '11 Nov, 2022', 'from' => '567890123456', 'to' => '678901234567', 'amount' => '$5000', 'reference' => 'TX12349', 'description' => 'Office rent'],
    ['date' => '10 Nov, 2022', 'from' => '678901234567', 'to' => '789012345678', 'amount' => '$6000', 'reference' => 'TX12350', 'description' => 'Salary payment'],
    ['date' => '7 Nov, 2022',  'from' => '789012345678', 'to' => '890123456789', 'amount' => '$7000', 'reference' => 'TX12351', 'description' => 'Equipment purchase'],
    ['date' => '6 Nov, 2022',  'from' => '890123456789', 'to' => '123456789012', 'amount' => '$8000', 'reference' => 'TX12352', 'description' => 'Travel expenses'],
    ['date' => '5 Nov, 2022',  'from' => '123456789012', 'to' => '234567890123', 'amount' => '$9000', 'reference' => 'TX12353', 'description' => 'Marketing expenses'],
];
@endphp

<div x-data="{ add: false, edit: false }">
    <x-page-header title="Banking" subtitle="Transfer Bank to Bank" icon="ik ik-user-plus"
                   :breadcrumbs="['Home' => url('accounting'), 'Transfer' => null]" />

    <x-table title="Banking" :total="100" :from="1" :to="9" :static-pages="5" search>
        <x-slot:actions>
            <button x-on:click="add = true" class="inline-flex items-center gap-1.5 rounded-lg bg-primary-500 px-3 py-2 text-sm font-medium text-white hover:bg-primary-600"><i class="ik ik-plus"></i> Add Transfer</button>
        </x-slot:actions>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 font-medium" data-no-export><input type="checkbox" id="selectall" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-200"></th>
                        <th class="px-5 py-3 font-medium">Date</th>
                        <th class="px-5 py-3 font-medium">From Account</th>
                        <th class="px-5 py-3 font-medium">To Account</th>
                        <th class="px-5 py-3 font-medium">Amount</th>
                        <th class="px-5 py-3 font-medium">Reference</th>
                        <th class="px-5 py-3 font-medium">Description</th>
                        <th class="px-5 py-3 font-medium" data-no-export>Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($transfers as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3" data-no-export><input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-200"></td>
                        <td class="px-5 py-3 text-gray-500">{{$item['date']}}</td>
                        <td class="px-5 py-3 text-gray-500">{{$item['from']}}</td>
                        <td class="px-5 py-3 text-gray-500">{{$item['to']}}</td>
                        <td class="px-5 py-3 text-gray-700">{{$item['amount']}}</td>
                        <td class="px-5 py-3 text-gray-500">{{$item['reference']}}</td>
                        <td class="px-5 py-3 text-gray-500">{{$item['description']}}</td>
                        <td class="px-5 py-3" data-no-export>
                            <div class="flex items-center gap-3">
                                <a href="#" x-on:click.prevent="edit = true" class="text-green-500 hover:text-green-600"><i class="ik ik-edit"></i></a>
                                <a href="#!" class="text-accent-500 hover:text-accent-600"><i class="ik ik-trash-2"></i></a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
    </x-table>

    <!-- Add Transfer modal -->
    <div x-show="add" style="display:none" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" x-on:click.self="add = false">
        <div class="w-full max-w-md rounded-xl bg-white shadow-lg">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h5 class="font-semibold text-gray-700">Transfer</h5>
                <button type="button" x-on:click="add = false" class="text-gray-400 hover:text-gray-600"><i class="ik ik-x"></i></button>
            </div>
            <div class="p-5">
                <form class="space-y-4">
                    <x-form.input name="from_account" label="From Account" placeholder="Enter From Account" />
                    <x-form.input name="to_account" label="To Account" placeholder="Enter To Account" />
                    <x-form.input name="amount" label="Amount" placeholder="Enter Amount" />
                    <x-form.input name="reference" label="Reference" placeholder="Enter Reference" />
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" rows="3" placeholder="Enter Description" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100"></textarea>
                    </div>
                    <x-button type="submit">Save</x-button>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Transfer modal -->
    <div x-show="edit" style="display:none" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" x-on:click.self="edit = false">
        <div class="w-full max-w-md rounded-xl bg-white shadow-lg">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h5 class="font-semibold text-gray-700">Edit Transfer</h5>
                <button type="button" x-on:click="edit = false" class="text-gray-400 hover:text-gray-600"><i class="ik ik-x"></i></button>
            </div>
            <div class="p-5">
                <form class="space-y-4">
                    <x-form.input name="from_account" label="From Account" placeholder="Enter From Account" value="3425456435" />
                    <x-form.input name="to_account" label="To Account" placeholder="Enter To Account" value="3647817326218" />
                    <x-form.input name="amount" label="Amount" placeholder="Enter Amount" value="1200" />
                    <x-form.input name="reference" label="Reference" placeholder="Enter Reference" value="TX37829DG" />
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" rows="3" placeholder="Enter Description" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100"></textarea>
                    </div>
                    <x-button type="submit">Update</x-button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
