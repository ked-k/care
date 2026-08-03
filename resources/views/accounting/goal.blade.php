@extends('accounting.layout')
@section('title', 'Goal')
@section('content')

@php
$goals = [ [ 'name' => 'Alex', 'type' => 'Invoice', 'from' => '2022-07', 'to' => '2022-08', 'amount' => 13000 ],
[ 'name' => 'John', 'type' => 'Payment', 'from' => '2021-09', 'to' => '2021-10', 'amount' => 5000 ],
[ 'name' => 'Mary', 'type' => 'Bill', 'from' => '2023-01', 'to' => '2023-02', 'amount' => 8000 ],
[ 'name' => 'David', 'type' => 'Invoice', 'from' => '2022-12', 'to' => '2023-01', 'amount' => 10000 ],
[ 'name' => 'Sarah', 'type' => 'Payment', 'from' => '2022-03', 'to' => '2022-04', 'amount' => 2000 ],
[ 'name' => 'Chris', 'type' => 'Bill', 'from' => '2022-06', 'to' => '2022-07', 'amount' => 6000 ],
[ 'name' => 'Oliver', 'type' => 'Invoice', 'from' => '2023-02', 'to' => '2023-03', 'amount' => 15000 ],
[ 'name' => 'Sophie', 'type' => 'Payment', 'from' => '2022-01', 'to' => '2022-02', 'amount' => 3000 ],
[ 'name' => 'Emma', 'type' => 'Bill', 'from' => '2022-09', 'to' => '2022-10', 'amount' => 7000 ],
[ 'name' => 'Tom', 'type' => 'Invoice', 'from' => '2022-11', 'to' => '2022-12', 'amount' => 12000 ]
];
@endphp

<div x-data="{ add: false, edit: false }">
    <x-page-header title="Goal" subtitle="Setup monthly goal" icon="ik ik-user-plus"
                   :breadcrumbs="['Home' => url('accounting'), 'Goal' => null]" />

    <x-table title="{{ __('Goals') }}" :total="count($goals)" :from="1" :to="count($goals)" :static-pages="5" search
             search-placeholder="{{ __('Search goals...') }}">
        <x-slot:actions>
            <x-button size="sm" x-on:click="add = true"><i class="ik ik-plus"></i> {{ __('Add Goal') }}</x-button>
        </x-slot:actions>

        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="px-5 py-3 font-medium" data-no-export><input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-200"></th>
                    <th class="px-5 py-3 font-medium">{{ __('Name') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Type') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('From') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('To') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Amount') }}</th>
                    <th class="px-5 py-3 font-medium" data-no-export>{{ __('Action') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($goals as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3" data-no-export><input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-200"></td>
                    <td class="px-5 py-3 font-medium text-gray-700">{{ $item['name'] }}</td>
                    <td class="px-5 py-3"><x-badge color="primary">{{ $item['type'] }}</x-badge></td>
                    <td class="px-5 py-3 text-gray-500">{{ $item['from'] }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $item['to'] }}</td>
                    <td class="px-5 py-3 font-semibold text-gray-700">${{ number_format($item['amount']) }}</td>
                    <td class="px-5 py-3" data-no-export>
                        <div class="flex items-center gap-3">
                            <a href="#" x-on:click.prevent="edit = true" class="text-green-500 hover:text-green-600" title="{{ __('Edit') }}"><i class="ik ik-edit"></i></a>
                            <a href="#!" class="text-accent-500 hover:text-accent-600" title="{{ __('Delete') }}"><i class="ik ik-trash-2"></i></a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </x-table>

    <!-- Add Goal modal -->
    <div x-show="add" style="display:none" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" x-on:click.self="add = false">
        <div class="w-full max-w-md rounded-xl bg-white shadow-lg">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h5 class="font-semibold text-gray-700">Add Goal</h5>
                <button type="button" x-on:click="add = false" class="text-gray-400 hover:text-gray-600"><i class="ik ik-x"></i></button>
            </div>
            <div class="p-5">
                <form class="space-y-4">
                    <x-form.input name="name" label="Name" placeholder="Enter Name" />
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Type</label>
                        <select name="type" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                            <option selected="selected" value=""> Select Type</option>
                            <option value="1">Invoice</option>
                            <option value="2">Bill</option>
                            <option value="3">Payment</option>
                            <option value="4">Expense</option>
                        </select>
                    </div>
                    <x-form.input name="from" label="From" placeholder="Enter From month" />
                    <x-form.input name="to" label="To" placeholder="Enter To month" />
                    <x-form.input name="amount" label="Amount" placeholder="Enter Amount" />
                    <x-button type="submit">Save</x-button>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Goal modal -->
    <div x-show="edit" style="display:none" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" x-on:click.self="edit = false">
        <div class="w-full max-w-md rounded-xl bg-white shadow-lg">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h5 class="font-semibold text-gray-700">Edit Goal</h5>
                <button type="button" x-on:click="edit = false" class="text-gray-400 hover:text-gray-600"><i class="ik ik-x"></i></button>
            </div>
            <div class="p-5">
                <form class="space-y-4">
                    <x-form.input name="name" label="Name" placeholder="Enter Name" value="Alex" />
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Type</label>
                        <select name="type" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                            <option value="2"> Select Type</option>
                            <option value="1">Invoice</option>
                            <option value="2" selected="selected"> Bill</option>
                            <option value="3">Payment</option>
                            <option value="4">Expense</option>
                        </select>
                    </div>
                    <x-form.input name="from" label="From" placeholder="Enter From month" value="2023-03" />
                    <x-form.input name="to" label="To" placeholder="Enter To month" value="2023-04" />
                    <x-form.input name="amount" label="Amount" placeholder="Enter Amount" value="2800" />
                    <x-button type="submit">Update</x-button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
