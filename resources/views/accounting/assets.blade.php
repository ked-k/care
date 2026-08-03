@extends('accounting.layout')
@section('title', 'Assets')
@section('content')

@php
$assets = [ [ 'name' => 'Test Asset 1', 'purchased_date' => 'Nov 8, 2023', 'supported_date' => 'Jan 04 2024', 'amount' => 13000, 'description' => 'Lorem ipsum dolor sit amet' ],
[ 'name' => 'Test Asset 2', 'purchased_date' => 'Dec 10, 2023', 'supported_date' => 'Feb 06 2024', 'amount' => 12000, 'description' => 'Lorem ipsum dolor sit amet' ],
[ 'name' => 'Test Asset 3', 'purchased_date' => 'Jan 15, 2024', 'supported_date' => 'Mar 12 2024', 'amount' => 15000, 'description' => 'Lorem ipsum dolor sit amet' ],
[ 'name' => 'Test Asset 4', 'purchased_date' => 'Feb 22, 2024', 'supported_date' => 'Apr 18 2024', 'amount' => 20000, 'description' => 'Lorem ipsum dolor sit amet' ],
[ 'name' => 'Test Asset 5', 'purchased_date' => 'Mar 12, 2024', 'supported_date' => 'May 08 2024', 'amount' => 18000, 'description' => 'Lorem ipsum dolor sit amet' ],
[ 'name' => 'Test Asset 6', 'purchased_date' => 'Apr 25, 2024', 'supported_date' => 'Jun 21 2024', 'amount' => 25000, 'description' => 'Lorem ipsum dolor sit amet' ],
[ 'name' => 'Test Asset 7', 'purchased_date' => 'May 19, 2024', 'supported_date' => 'Jul 16 2024', 'amount' => 18000, 'description' => 'Lorem ipsum dolor sit amet' ],
[ 'name' => 'Test Asset 8', 'purchased_date' => 'Jun 30, 2024', 'supported_date' => 'Aug 26 2024', 'amount' => 30000, 'description' => 'Lorem ipsum dolor sit amet' ],
[ 'name' => 'Test Asset 9', 'purchased_date' => 'Jul 11, 2024', 'supported_date' => 'Sep 06 2024', 'amount' => 17000, 'description' => 'Lorem ipsum dolor sit amet' ],
[ 'name' => 'Test Asset 10', 'purchased_date' => 'Aug 19, 2024', 'supported_date' => 'Oct 16 2024', 'amount' => 21000, 'description' => 'Lorem ipsum dolor sit amet' ]
];
@endphp

<div x-data="{ add: false, edit: false }">
    <x-page-header title="Assets" subtitle="Manage Assets" icon="ik ik-user-plus"
                   :breadcrumbs="['Home' => url('accounting'), 'Assets' => null]" />

    <x-table title="Assets" :total="100" :from="1" :to="10" :static-pages="5" search>
        <x-slot:actions>
            <button x-on:click="add = true" class="inline-flex items-center gap-1.5 rounded-lg bg-primary-500 px-3 py-2 text-sm font-medium text-white hover:bg-primary-600"><i class="ik ik-plus"></i> Add Assets</button>
        </x-slot:actions>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 font-medium" data-no-export><input type="checkbox" id="selectall" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-200"></th>
                        <th class="px-5 py-3 font-medium">Name</th>
                        <th class="px-5 py-3 font-medium">Purchased Date</th>
                        <th class="px-5 py-3 font-medium">Supported Date</th>
                        <th class="px-5 py-3 font-medium">Amount</th>
                        <th class="px-5 py-3 font-medium">Description</th>
                        <th class="px-5 py-3 font-medium" data-no-export>Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($assets as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3" data-no-export><input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-200"></td>
                        <td class="px-5 py-3 font-medium text-gray-700">{{$item['name']}}</td>
                        <td class="px-5 py-3 text-gray-500">{{$item['purchased_date']}}</td>
                        <td class="px-5 py-3 text-gray-500">{{$item['supported_date']}}</td>
                        <td class="px-5 py-3 text-gray-700">{{$item['amount']}}</td>
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

    <!-- Add Assets modal -->
    <div x-show="add" style="display:none" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" x-on:click.self="add = false">
        <div class="w-full max-w-md rounded-xl bg-white shadow-lg">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h5 class="font-semibold text-gray-700">Add Assets</h5>
                <button type="button" x-on:click="add = false" class="text-gray-400 hover:text-gray-600"><i class="ik ik-x"></i></button>
            </div>
            <div class="p-5">
                <form class="space-y-4">
                    <x-form.input name="name" label="Name" placeholder="Enter Name" />
                    <x-form.input name="purchased_date" type="date" label="Purchased Date" placeholder="Enter purchased date" value="{{date('Y-m-d')}}" />
                    <x-form.input name="supporte_date" type="date" label="Supported Date" placeholder="Enter Supported Date" />
                    <x-form.input name="amount" label="Amount" placeholder="Enter Amount" />
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" rows="4" placeholder="Enter Description" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100"></textarea>
                    </div>
                    <x-button type="submit">Save</x-button>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Assets modal -->
    <div x-show="edit" style="display:none" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" x-on:click.self="edit = false">
        <div class="w-full max-w-md rounded-xl bg-white shadow-lg">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h5 class="font-semibold text-gray-700">Edit Assets</h5>
                <button type="button" x-on:click="edit = false" class="text-gray-400 hover:text-gray-600"><i class="ik ik-x"></i></button>
            </div>
            <div class="p-5">
                <form class="space-y-4">
                    <x-form.input name="name" label="Name" placeholder="Enter Name" value="Testing Asset 1" />
                    <x-form.input name="purchased_date" type="date" label="Purchased Date" placeholder="Enter purchased date" value="{{date('Y-m-d')}}" />
                    <x-form.input name="supporte_date" type="date" label="Supported Date" placeholder="Enter Supported Date" value="2024-11-07" />
                    <x-form.input name="amount" label="Amount" placeholder="Enter Amount" value="13000" />
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" rows="4" placeholder="Enter Description" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">Lorem ipsum dolor sit amet</textarea>
                    </div>
                    <x-button type="submit">Update</x-button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
