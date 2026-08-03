@extends('accounting.layout')
@section('title', 'Account')
@section('content')

@php
$accounts = [
    ['name' => 'Tovino Thomas',  'bank' => 'State Bank',      'account' => '2187420213632', 'balance' => '$32102', 'contact' => '219-122-1234', 'branch' => 'New York, USA'],
    ['name' => 'Isabella Taylor','bank' => 'State Bank',      'account' => '890123456789',  'balance' => '$28000', 'contact' => '888-888-8888', 'branch' => 'Boston, USA'],
    ['name' => 'William Johnson','bank' => 'Bank of America', 'account' => '789012345678',  'balance' => '$35000', 'contact' => '777-777-7777', 'branch' => 'San Francisco, USA '],
    ['name' => 'Sophia Lee',     'bank' => 'Wells Fargo',     'account' => '678901234567',  'balance' => '$30000', 'contact' => '666-666-6666', 'branch' => 'New York, USA'],
    ['name' => 'David Brown',    'bank' => 'Chase Bank',      'account' => '567890123456',  'balance' => '$25000', 'contact' => '555-555-5555', 'branch' => 'Seattle, USA'],
    ['name' => 'Emily Davis',    'bank' => 'State Bank',      'account' => '456789012345',  'balance' => '$12000', 'contact' => '444-444-4444', 'branch' => 'Miami, USA'],
    ['name' => 'Michael Smith',  'bank' => 'Bank of America', 'account' => '345678901234',  'balance' => '$15000', 'contact' => '333-333-3333', 'branch' => 'Houston, USA'],
    ['name' => 'Jane Doe',       'bank' => 'Wells Fargo',     'account' => '234567890123',  'balance' => '$20000', 'contact' => '222-222-2222', 'branch' => 'Chicago, USA'],
    ['name' => 'Jane Doe',       'bank' => 'Wells Fargo',     'account' => '234567890123',  'balance' => '$20000', 'contact' => '222-222-2222', 'branch' => 'Chicago, USA'],
];
@endphp

<div x-data="{ add: false, edit: false }">
    <x-page-header title="Banking" subtitle="Manage Bank Account" icon="ik ik-user-plus"
                   :breadcrumbs="['Home' => url('accounting'), 'Banking' => null]" />

    <x-table title="Banking" :total="100" :from="1" :to="9" :static-pages="5" search>
        <x-slot:actions>
            <button x-on:click="add = true" class="inline-flex items-center gap-1.5 rounded-lg bg-primary-500 px-3 py-2 text-sm font-medium text-white hover:bg-primary-600"><i class="ik ik-plus"></i> Add Bank</button>
        </x-slot:actions>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 font-medium" data-no-export><input type="checkbox" id="selectall" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-200"></th>
                        <th class="px-5 py-3 font-medium">Name</th>
                        <th class="px-5 py-3 font-medium">Bank</th>
                        <th class="px-5 py-3 font-medium">Account No</th>
                        <th class="px-5 py-3 font-medium">Current Balance</th>
                        <th class="px-5 py-3 font-medium">Contact No</th>
                        <th class="px-5 py-3 font-medium">Branch</th>
                        <th class="px-5 py-3 font-medium" data-no-export>Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($accounts as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3" data-no-export><input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-200"></td>
                        <td class="px-5 py-3 font-medium text-gray-700">{{$item['name']}}</td>
                        <td class="px-5 py-3 text-gray-500">{{$item['bank']}}</td>
                        <td class="px-5 py-3 text-gray-500">{{$item['account']}}</td>
                        <td class="px-5 py-3 text-gray-700">{{$item['balance']}}</td>
                        <td class="px-5 py-3 text-gray-500">{{$item['contact']}}</td>
                        <td class="px-5 py-3 text-gray-500">{{$item['branch']}}</td>
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

    <!-- Add Bank modal -->
    <div x-show="add" style="display:none" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" x-on:click.self="add = false">
        <div class="w-full max-w-md rounded-xl bg-white shadow-lg">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h5 class="font-semibold text-gray-700">Add Bank Account</h5>
                <button type="button" x-on:click="add = false" class="text-gray-400 hover:text-gray-600"><i class="ik ik-x"></i></button>
            </div>
            <div class="p-5">
                <form class="space-y-4">
                    <x-form.input name="name" label="Account Holder Name" placeholder="Enter Account Holder Name" />
                    <x-form.input name="account_no" label="Account Number" placeholder="Enter Account Number" />
                    <x-form.input name="bank_name" label="Bank Name" placeholder="Enter Bank Name" />
                    <x-form.input name="branch" label="Branch" placeholder="Enter Branch" />
                    <x-form.input name="balance" label="Opening Balance" placeholder="Enter Opening Balance" />
                    <x-form.input name="contact_no" label="Contact No" placeholder="Enter Contact No" />
                    <x-button type="submit">Save</x-button>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Bank modal -->
    <div x-show="edit" style="display:none" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" x-on:click.self="edit = false">
        <div class="w-full max-w-md rounded-xl bg-white shadow-lg">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h5 class="font-semibold text-gray-700">Edit Bank Details</h5>
                <button type="button" x-on:click="edit = false" class="text-gray-400 hover:text-gray-600"><i class="ik ik-x"></i></button>
            </div>
            <div class="p-5">
                <form class="space-y-4">
                    <x-form.input name="name" label="Account Holder Name" placeholder="Enter Account Holder Name" value="John Doe" />
                    <x-form.input name="account_no" label="Account Number" placeholder="Enter Account Number" value="3647817326218" />
                    <x-form.input name="bank_name" label="Bank Name" placeholder="Enter Bank Name" value="State Bank of America" />
                    <x-form.input name="branch" label="Branch" placeholder="Enter Branch" value="Boston, Shicago" />
                    <x-form.input name="balance" label="Opening Balance" placeholder="Enter Opening Balance" value="100" />
                    <x-form.input name="contact_no" label="Contact No" placeholder="Enter Contact No" value="222-222-222" />
                    <x-button type="submit">Update</x-button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
