@extends('accounting.layout')
@section('title', 'View Bill')
@section('content')
    <x-page-header title="Expense" subtitle="View Bill no #BL000045" icon="ik ik-user-plus"
                   :breadcrumbs="['Home' => url('accounting'), 'Expense' => null, 'Bill' => url('expense/bill')]" />

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-4">
        <div class="lg:col-span-1">
            <x-card>
                <div class="space-y-6">
                    <div class="flex gap-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-green-500 text-white"><i class="ik ik-file"></i></span>
                        <div>
                            <h6 class="font-semibold text-gray-700">Create Bill</h6>
                            <p class="mt-1 text-xs text-gray-400"><i class="ik ik-clock"></i> Created on Feb 12, 2023</p>
                            <x-button href="{{ url('expense/bill/edit/1') }}" variant="outline" size="sm" class="mt-2"><i class="ik ik-edit"></i> Edit</x-button>
                        </div>
                    </div>
                    <hr class="border-gray-100">
                    <div class="flex gap-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-500 text-white"><i class="ik ik-mail"></i></span>
                        <div>
                            <h6 class="font-semibold text-gray-700">Send Bill</h6>
                            <p class="mt-1 text-xs text-gray-400"><i class="ik ik-clock"></i> Sent on Feb 12, 2023</p>
                        </div>
                    </div>
                    <hr class="border-gray-100">
                    <div class="flex gap-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-500 text-white"><i class="ik ik-dollar-sign"></i></span>
                        <div>
                            <h6 class="font-semibold text-gray-700">Get Paid</h6>
                            <div class="mt-1">
                                <span class="text-2xl font-bold text-amber-500">28204</span>
                                <span class="ml-1 text-sm text-gray-600">Due Amount</span>
                            </div>
                            <p class="mt-1 text-xs text-gray-400">Status: Awaiting Payment</p>
                            <x-button href="" variant="outline" size="sm" class="mt-2"><i class="ik ik-plus"></i> Add Payment</x-button>
                        </div>
                    </div>
                </div>
            </x-card>
        </div>

        <div class="lg:col-span-3 space-y-5">
            <x-card>
                <x-slot:header>Product &amp; Services</x-slot:header>
                @include('accounting.expense.bill.bill_invoice')
            </x-card>

            <x-card no-padding>
                <x-slot:header>Payment History</x-slot:header>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                                <th class="px-5 py-3 font-medium">Date</th>
                                <th class="px-5 py-3 font-medium">Amount</th>
                                <th class="px-5 py-3 font-medium">Account</th>
                                <th class="px-5 py-3 font-medium">Reference</th>
                                <th class="px-5 py-3 font-medium">Description</th>
                                <th class="px-5 py-3 font-medium">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @php
                                $payments = [
                                    ['Feb 12, 2023', '800', 'Alex - Test State Bank', 'Lorem ipsum', 'Lorem ipsum dolor sit amet'],
                                    ['Feb 08, 2023', '1300', 'Alex - Test State Bank', 'Lorem ipsum', 'Lorem ipsum dolor sit amet'],
                                ];
                            @endphp
                            @foreach ($payments as [$date, $amount, $account, $ref, $desc])
                                <tr class="hover:bg-gray-50">
                                    <td class="px-5 py-3 text-gray-600">{{ $date }}</td>
                                    <td class="px-5 py-3 text-gray-700">{{ $amount }}</td>
                                    <td class="px-5 py-3 text-gray-600">{{ $account }}</td>
                                    <td class="px-5 py-3 text-gray-600">{{ $ref }}</td>
                                    <td class="px-5 py-3 text-gray-600">{{ $desc }}</td>
                                    <td class="px-5 py-3">
                                        <a href="#!" class="text-accent-500 hover:text-accent-600"><i class="ik ik-trash-2 f-16"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
    </div>
@endsection
