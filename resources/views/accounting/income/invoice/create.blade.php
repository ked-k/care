@extends('accounting.layout')
@section('title', 'Create Invoice')
@section('content')
    <x-page-header title="Income" subtitle="Invoice" icon="ik ik-user-plus"
                   :breadcrumbs="['Home' => url('accounting'), 'Income' => null, 'Invoice' => url('income/invoice')]" />

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
        {{-- LEFT: form + line items --}}
        <div class="space-y-5">
            <x-card>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-form.input name="invoice_no" label="Invoice No" value="#PRO000045" readonly />

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Customer</label>
                        <div class="flex items-end gap-2">
                            <select class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                                <option selected value="">Select Customer</option>
                                <option value="1">Alex Ferguson</option>
                                <option value="2">John Doe</option>
                            </select>
                            <x-button type="button" size="sm">+</x-button>
                        </div>
                    </div>

                    <x-form.input name="issue_date" label="Issue Date" type="date" placeholder="Select Date" />
                    <x-form.input name="due_date" label="Due Date" type="date" placeholder="Select Date" />

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Category</label>
                        <select class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                            <option selected value="">Select Category</option>
                            <option value="1">Category 1</option>
                            <option value="2">Category 2</option>
                            <option value="3">Category 3</option>
                            <option value="4">Category 4</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Note</label>
                        <textarea name="note" rows="3" placeholder="Enter Note"
                            class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100"></textarea>
                    </div>
                </div>
            </x-card>

            <x-card no-padding>
                <x-slot:header>Product &amp; Services</x-slot:header>
                <x-slot:actions>
                    <x-button variant="success" size="sm" class="add-product-item"><i class="ik ik-plus"></i> Add Item</x-button>
                </x-slot:actions>
                <div class="overflow-x-auto p-5">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                                <th class="px-3 py-3 font-medium">Item</th>
                                <th class="px-3 py-3 font-medium">Quantity</th>
                                <th class="px-3 py-3 font-medium">Unit Price</th>
                                <th class="px-3 py-3 font-medium">Discount</th>
                                <th class="px-3 py-3 text-right font-medium">Sub Total</th>
                                <th class="px-3 py-3 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr id="product-base-content" class="base-tr">
                                <td class="py-2 pr-2"><input type="text" name="item" placeholder="Enter product/service name" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100"></td>
                                <td class="px-2 py-2"><input type="text" name="quantity" placeholder="Quantity" class="w-24 rounded-lg border border-gray-200 bg-white px-3 py-2 text-center text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100"></td>
                                <td class="px-2 py-2"><input type="text" name="unit_price" placeholder="price" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100"></td>
                                <td class="px-2 py-2"><input type="text" name="discount" placeholder="discount" class="w-24 rounded-lg border border-gray-200 bg-white px-3 py-2 text-center text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100"></td>
                                <td class="px-3 py-2 text-right text-gray-700">0.00</td>
                                <td class="px-2 py-2"><i class="ik ik-trash-2 f-16 text-accent-500 remove-second-parent cursor-pointer"></i></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        {{-- RIGHT: sticky summary + status --}}
        <div class="xl:sticky xl:top-20 xl:self-start space-y-5">
            <x-card>
                <x-slot:header>Summary</x-slot:header>
                <div class="space-y-2.5 text-sm">
                    <div class="flex justify-between text-gray-500"><span>Subtotal</span><span class="font-medium text-gray-700">$1,250.00</span></div>
                    <div class="flex justify-between text-gray-500"><span>Discount</span><span class="font-medium text-gray-700">-$50.00</span></div>
                    <div class="flex justify-between text-gray-500"><span>Tax (10%)</span><span class="font-medium text-gray-700">$120.00</span></div>
                    <div class="flex justify-between border-t border-gray-100 pt-2.5 text-lg font-bold text-gray-800"><span>Total</span><span>$1,320.00</span></div>
                </div>
                <div class="mt-4 grid gap-2">
                    <x-button type="submit"><i class="ik ik-check"></i> Save Invoice</x-button>
                    <x-button variant="outline" type="button"><i class="ik ik-send"></i> Save &amp; Send</x-button>
                </div>
            </x-card>
            <x-card>
                <x-slot:header>Status</x-slot:header>
                {{-- status stepper: Draft -> Sent -> Paid --}}
                <ol class="space-y-4">
                    <li class="flex items-center gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-500 text-xs font-semibold text-white"><i class="ik ik-check"></i></span>
                        <span class="text-sm font-medium text-gray-800">Draft</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-400">2</span>
                        <span class="text-sm font-medium text-gray-400">Sent</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-400">3</span>
                        <span class="text-sm font-medium text-gray-400">Paid</span>
                    </li>
                </ol>
            </x-card>
        </div>
    </div>
@endsection
