@extends('accounting.layout')
@section('title', 'Create Budget Planner')
@section('content')
<x-page-header title="Budget Planner" subtitle="Create new budget plan" icon="ik ik-user-plus"
               :breadcrumbs="['Home' => url('accounting'), 'Budget Planner' => null]" />

<x-card>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
        <div class="lg:col-span-1">
            <form class="space-y-4">
                <x-form.input name="name" label="Name" />
                <div>
                    <label for="tax_type" class="mb-1 block text-sm font-medium text-gray-700">Period<span class="text-accent-500">*</span></label>
                    <select name="tax_type" id="tax_type" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                        <option>Select</option>
                        <option value="Monthly">Monthly</option>
                        <option value="Half Yearly">Half Yearly</option>
                        <option value="Yearly">Yearly</option>
                    </select>
                </div>
                <x-form.input name="year" label="Year" />
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
                        @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $month)
                        <tr>
                            <td class="border border-gray-100 px-3 py-2 text-gray-700">{{ $month }}</td>
                            <td class="border border-gray-100 px-1 py-1"><input type="text" name="sales[]" class="w-full rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-center text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100" /></td>
                            <td class="border border-gray-100 px-1 py-1"><input type="text" name="interests[]" class="w-full rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-center text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100" /></td>
                            <td class="border border-gray-100 px-1 py-1"><input type="text" name="others[]" class="w-full rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-center text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100" /></td>
                            <td class="border border-gray-100 px-1 py-1"><input type="text" name="rent[]" class="w-full rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-center text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100" /></td>
                            <td class="border border-gray-100 px-1 py-1"><input type="text" name="exp_others[]" class="w-full rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-center text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100" /></td>
                            <td class="border border-gray-100 px-3 py-2 text-center font-semibold text-gray-700">0</td>
                        </tr>
                        @endforeach
                        <tr class="bg-gray-50 font-semibold text-gray-700">
                            <td class="border border-gray-100 px-3 py-2 text-center">Total</td>
                            <td class="border border-gray-100 px-3 py-2 text-center">0</td>
                            <td class="border border-gray-100 px-3 py-2 text-center">0</td>
                            <td class="border border-gray-100 px-3 py-2 text-center">0</td>
                            <td class="border border-gray-100 px-3 py-2 text-center">0</td>
                            <td class="border border-gray-100 px-3 py-2 text-center">0</td>
                            <td class="border border-gray-100 px-3 py-2 text-center">0</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-card>
@endsection
