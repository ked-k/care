@extends('accounting.layout')
@section('title', 'Payment')
@section('content')

@php
$paymentList = [
    ['date' => 'Feb 6, 2023',        'amount' => 11300,        'account' => 'Lorem Bank',        'vendor' => 'Alex Corporation',        'reference' => null,        'description' => 'Lorem ipsum dolor sit amet',],
    ['date' => 'Feb 3, 2023',        'amount' => 17306,        'account' => 'State Bank of Lorem',        'vendor' => 'David',        'reference' => 'Test Reference',        'description' => null,],
    ['date' => 'Feb 5, 2023',        'amount' => 9000,        'account' => 'Lorem Bank',        'vendor' => 'John Inc.',        'reference' => 'Test Reference 1',        'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit',],
    ['date' => 'Feb 1, 2023',        'amount' => 12000,        'account' => 'State Bank of Lorem',        'vendor' => 'Michael',        'reference' => null,        'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit',],
    ['date' => 'Feb 2, 2023',        'amount' => 8200,        'account' => 'Lorem Bank',        'vendor' => 'Jane',        'reference' => 'Test Reference 2',        'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit',],
    ['date' => 'Feb 4, 2023',        'amount' => 6500,        'account' => 'State Bank of Lorem',        'vendor' => 'Jessica',        'reference' => 'Test Reference 3',        'description' => null,],
    ['date' => 'Feb 7, 2023',        'amount' => 14000,        'account' => 'Lorem Bank',        'vendor' => 'Mark',        'reference' => 'Test Reference 4',        'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit',],
    ['date' => 'Feb 8, 2023',        'amount' => 11000,        'account' => 'State Bank of Lorem',        'vendor' => 'William',        'reference' => null,        'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit',],
    ['date' => 'Feb 9, 2023',        'amount' => 10500,        'account' => 'Lorem Bank',        'vendor' => 'Robert',        'reference' => 'Test Reference 5',        'description' => null,],
];
@endphp

<div x-data="{ add: false, edit: false }">
    <x-page-header title="Expense" subtitle="Payment" icon="ik ik-user-plus"
                   :breadcrumbs="['Home' => url('accounting'), 'Expense' => null, 'Payment' => null]" />

    <x-table title="Payment" :total="100" :from="1" :to="9" :static-pages="5" search>
        <x-slot:actions>
            <button x-on:click="add = true" class="inline-flex items-center gap-1.5 rounded-lg bg-primary-500 px-3 py-2 text-sm font-medium text-white hover:bg-primary-600"><i class="ik ik-plus"></i> Add Payment</button>
        </x-slot:actions>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 font-medium" data-no-export><input type="checkbox" id="selectall" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-200"></th>
                        <th class="px-5 py-3 font-medium">Date</th>
                        <th class="px-5 py-3 font-medium">Amount</th>
                        <th class="px-5 py-3 font-medium">Account No</th>
                        <th class="px-5 py-3 font-medium">Vendor</th>
                        <th class="px-5 py-3 font-medium">Reference</th>
                        <th class="px-5 py-3 font-medium">Description</th>
                        <th class="px-5 py-3 font-medium">Documents</th>
                        <th class="px-5 py-3 font-medium" data-no-export>Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($paymentList as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3" data-no-export><input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-200"></td>
                        <td class="px-5 py-3 text-gray-500">{{$item['date']}}</td>
                        <td class="px-5 py-3 text-gray-700">{{$item['amount']}}</td>
                        <td class="px-5 py-3 text-gray-500">{{$item['account']}}</td>
                        <td class="px-5 py-3 font-medium text-gray-700">{{$item['vendor']}}</td>
                        <td class="px-5 py-3 text-gray-500">{{$item['reference']}}</td>
                        <td class="px-5 py-3 text-gray-500">{{$item['description']}}</td>
                        <td class="px-5 py-3"></td>
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

    <!-- Add Payment modal -->
    <div x-show="add" style="display:none" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" x-on:click.self="add = false">
        <div class="w-full max-w-md rounded-xl bg-white shadow-lg">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h5 class="font-semibold text-gray-700">Add Payment</h5>
                <button type="button" x-on:click="add = false" class="text-gray-400 hover:text-gray-600"><i class="ik ik-x"></i></button>
            </div>
            <div class="p-5">
                <form class="space-y-4">
                    <x-form.input name="date" type="date" label="Date" placeholder="Enter Date" />
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Vendor</label>
                        <select name="vendor" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                            <option selected="selected" value="">Select Vendor</option>
                            <option value="1">Alex Corporation</option>
                            <option value="2">John Inc</option>
                        </select>
                    </div>
                    <x-form.input name="bank_name" label="Account" placeholder="Enter Account" />
                    <x-form.input name="amount" label="Amount" placeholder="Enter Amount" />
                    <x-form.input name="contact_no" label="Reference" placeholder="Enter Reference" />
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" rows="4" placeholder="Enter Description" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100"></textarea>
                    </div>
                    <x-button type="submit">Save</x-button>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Payment modal -->
    <div x-show="edit" style="display:none" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" x-on:click.self="edit = false">
        <div class="w-full max-w-md rounded-xl bg-white shadow-lg">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h5 class="font-semibold text-gray-700">Edit Payment Details</h5>
                <button type="button" x-on:click="edit = false" class="text-gray-400 hover:text-gray-600"><i class="ik ik-x"></i></button>
            </div>
            <div class="p-5">
                <form class="space-y-4">
                    <x-form.input name="date" type="date" label="Date" placeholder="Enter Date" value="22/02/2023" />
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Vendor</label>
                        <select name="vendor" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                            <option selected="selected" value="">Select Vendor</option>
                            <option value="1" selected>Alex Corporation</option>
                            <option value="2">John Inc</option>
                        </select>
                    </div>
                    <x-form.input name="bank_name" label="Account" placeholder="Enter Account" value="Alex - State Bank" />
                    <x-form.input name="amount" label="Amount" placeholder="Enter Amount" value="500" />
                    <x-form.input name="reference" label="Reference" placeholder="Enter Reference" />
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" rows="4" placeholder="Enter Description" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">Lorem ipsum dolor</textarea>
                    </div>
                    <x-button type="submit">Update</x-button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
