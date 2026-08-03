@extends('accounting.layout')
@section('title', 'Edit Budget Planner')
@section('content')

@php
$rows = [
    ['month' => 'January',   'sales' => '1000', 'interests' => '20',  'others' => '30', 'rent' => '40',  'exp_others' => '50',  'total' => '890'],
    ['month' => 'February',  'sales' => '600',  'interests' => '70',  'others' => '80', 'rent' => '90',  'exp_others' => '200', 'total' => '730'],
    ['month' => 'March',     'sales' => '600',  'interests' => '70',  'others' => '80', 'rent' => '90',  'exp_others' => '200', 'total' => '1000'],
    ['month' => 'April',     'sales' => '100',  'interests' => '20',  'others' => '30', 'rent' => '40',  'exp_others' => '50',  'total' => '900'],
    ['month' => 'May',       'sales' => '400',  'interests' => '60',  'others' => '70', 'rent' => '90',  'exp_others' => '100', 'total' => '1010'],
    ['month' => 'June',      'sales' => '400',  'interests' => '60',  'others' => '70', 'rent' => '90',  'exp_others' => '100', 'total' => '780'],
    ['month' => 'July',      'sales' => '100',  'interests' => '20',  'others' => '30', 'rent' => '40',  'exp_others' => '50',  'total' => '690'],
    ['month' => 'August',    'sales' => '600',  'interests' => '70',  'others' => '80', 'rent' => '90',  'exp_others' => '200', 'total' => '970'],
    ['month' => 'September', 'sales' => '600',  'interests' => '700', 'others' => '80', 'rent' => '90',  'exp_others' => '200', 'total' => '650'],
    ['month' => 'October',   'sales' => '100',  'interests' => '20',  'others' => '30', 'rent' => '40',  'exp_others' => '50',  'total' => '1230'],
    ['month' => 'November',  'sales' => '600',  'interests' => '170', 'others' => '80', 'rent' => '190', 'exp_others' => '200', 'total' => '700'],
    ['month' => 'December',  'sales' => '100',  'interests' => '20',  'others' => '30', 'rent' => '40',  'exp_others' => '50',  'total' => '1100'],
];
@endphp

<x-page-header title="Budget Planner" subtitle="Plan your budget" icon="ik ik-user-plus"
               :breadcrumbs="['Home' => url('accounting'), 'Budget Planner' => null]" />

<x-card>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
        <div class="lg:col-span-1">
            <form class="space-y-4">
                <x-form.input name="name" label="Name" value="Themicly Budget 2023" />
                <div>
                    <label for="tax_type" class="mb-1 block text-sm font-medium text-gray-700">Period<span class="text-accent-500">*</span></label>
                    <select name="tax_type" id="tax_type" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                        <option>Select</option>
                        <option value="Monthly">Monthly</option>
                        <option value="Half Yearly">Half Yearly</option>
                        <option value="Yearly" selected>Yearly</option>
                    </select>
                </div>
                <x-form.input name="year" label="Year" value="2023" />
                <x-button type="submit">Save</x-button>
            </form>
        </div>

        <div class="lg:col-span-3">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-sm">
                    <thead>
                        <tr>
                            <th rowspan="2" class="border border-gray-100 bg-gray-50 px-3 py-2 text-center font-medium text-gray-600">Month</th>
                            <th colspan="3" class="border border-gray-100 bg-green-50 px-3 py-2 text-center font-medium text-green-700">Income</th>
                            <th colspan="2" class="border border-gray-100 bg-accent-500/10 px-3 py-2 text-center font-medium text-accent-600">Expense</th>
                            <th rowspan="2" class="w-24 border border-gray-100 bg-gray-50 px-3 py-2 text-center font-medium text-gray-600">Total</th>
                        </tr>
                        <tr>
                            <th class="border border-gray-100 bg-green-50/60 px-3 py-2 text-center font-medium text-green-700">Sales</th>
                            <th class="border border-gray-100 bg-green-50/60 px-3 py-2 text-center font-medium text-green-700">Interests</th>
                            <th class="border border-gray-100 bg-green-50/60 px-3 py-2 text-center font-medium text-green-700">Others</th>
                            <th class="border border-gray-100 bg-accent-500/5 px-3 py-2 text-center font-medium text-accent-600">Rent</th>
                            <th class="border border-gray-100 bg-accent-500/5 px-3 py-2 text-center font-medium text-accent-600">Others</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                        <tr>
                            <td class="border border-gray-100 px-3 py-2 text-gray-700">{{ $row['month'] }}</td>
                            <td class="border border-gray-100 px-1 py-1"><input type="text" value="{{ $row['sales'] }}" name="sales[]" class="w-full rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-center text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100" /></td>
                            <td class="border border-gray-100 px-1 py-1"><input type="text" value="{{ $row['interests'] }}" name="interests[]" class="w-full rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-center text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100" /></td>
                            <td class="border border-gray-100 px-1 py-1"><input type="text" value="{{ $row['others'] }}" name="others[]" class="w-full rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-center text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100" /></td>
                            <td class="border border-gray-100 px-1 py-1"><input type="text" value="{{ $row['rent'] }}" name="rent[]" class="w-full rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-center text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100" /></td>
                            <td class="border border-gray-100 px-1 py-1"><input type="text" value="{{ $row['exp_others'] }}" name="exp_others[]" class="w-full rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-center text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100" /></td>
                            <td class="border border-gray-100 px-3 py-2 text-center font-semibold text-gray-700">{{ $row['total'] }}</td>
                        </tr>
                        @endforeach
                        <tr class="bg-gray-50 font-semibold text-gray-700">
                            <td class="border border-gray-100 px-3 py-2 text-center">Total</td>
                            <td class="border border-gray-100 px-3 py-2 text-center">1200</td>
                            <td class="border border-gray-100 px-3 py-2 text-center">700</td>
                            <td class="border border-gray-100 px-3 py-2 text-center">9300</td>
                            <td class="border border-gray-100 px-3 py-2 text-center">5700</td>
                            <td class="border border-gray-100 px-3 py-2 text-center">2390</td>
                            <td class="border border-gray-100 px-3 py-2 text-center">25000</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-card>
@endsection
