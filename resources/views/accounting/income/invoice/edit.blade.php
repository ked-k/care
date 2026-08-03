@extends('accounting.layout')
@section('title', 'Edit Invoice')
@section('content')
    <x-page-header title="Income" subtitle="Edit Invoice" icon="ik ik-user-plus"
                   :breadcrumbs="['Home' => url('accounting'), 'Income' => null, 'Invoice' => url('income/invoice')]" />

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-4">
        <div class="lg:col-span-1">
            <x-card>
                <div class="space-y-4">
                    <x-form.input name="invoice_no" label="Invoice No" value="#INV000045" readonly />

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Customer</label>
                        <div class="flex items-end gap-2">
                            <select class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                                <option value="">Select Customer</option>
                                <option value="1" selected>Alex Ferguson</option>
                                <option value="2">John Doe</option>
                            </select>
                            <x-button type="button" size="sm">+</x-button>
                        </div>
                    </div>

                    <x-form.input name="issue_date" label="Issue Date" type="date" value="2022-11-17" placeholder="Select Date" />
                    <x-form.input name="due_date" label="Due Date" type="date" placeholder="Select Date" />

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Category</label>
                        <select class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                            <option value="">Select Category</option>
                            <option value="1">Category 1</option>
                            <option value="2" selected>Category 2</option>
                            <option value="3">Category 3</option>
                            <option value="4">Category 4</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Note</label>
                        <textarea name="note" rows="4" placeholder="Enter Note"
                            class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam pulvinar tempor ex, in blandit risus bibendum vel.</textarea>
                    </div>
                </div>
            </x-card>
        </div>

        <div class="lg:col-span-3">
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
                            @php
                                $invoiceItems = config('mockdata.invoice_items');
                                $grandTotal = 0;
                                $grandDiscount = 0;
                            @endphp
                            @foreach($invoiceItems as $key => $product)
                                @php
                                    $subtotal = $product['qty'] * ($product['unit_price'] - $product['discount']);
                                    $grandTotal += $subtotal;
                                @endphp
                                <tr class="base-tr">
                                    <td class="py-2 pr-2"><input type="text" name="item" value="{{ $product['name'] }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100"></td>
                                    <td class="px-2 py-2"><input type="text" name="quantity" value="{{ $product['qty'] }}" class="w-24 rounded-lg border border-gray-200 bg-white px-3 py-2 text-center text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100"></td>
                                    <td class="px-2 py-2"><input type="text" name="unit_price" value="{{ $product['unit_price'] }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100"></td>
                                    <td class="px-2 py-2"><input type="text" name="discount" value="{{ $product['discount'] }}" class="w-24 rounded-lg border border-gray-200 bg-white px-3 py-2 text-center text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100"></td>
                                    <td class="px-3 py-2 text-right text-gray-700">{{ number_format($subtotal, 2, '.', '') }}</td>
                                    <td class="px-2 py-2"><i class="ik ik-trash-2 f-16 text-accent-500 remove-second-parent cursor-pointer"></i></td>
                                </tr>
                            @endforeach
                            @php
                                $taxAmount = $grandTotal * 0.1;
                                $grandTotalWithTax = $grandTotal + $taxAmount;
                            @endphp
                        </tbody>
                        <tfoot class="text-gray-700">
                            <tr>
                                <td colspan="3"></td>
                                <td class="px-3 py-2 font-medium">Total</td>
                                <td class="px-3 py-2 text-right font-medium">{{ number_format($grandTotal, 2, '.', '') }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="3"></td>
                                <td class="px-3 py-2">Tax (<span id="tax-per">10.00</span>%)</td>
                                <td class="px-3 py-2 text-right">{{ number_format($taxAmount, 2, '.', '') }}</td>
                                <td></td>
                            </tr>
                            <tr class="border-t border-gray-100 font-semibold">
                                <td colspan="3"></td>
                                <td class="px-3 py-2">Grand Total</td>
                                <td class="px-3 py-2 text-right">{{ number_format($grandTotalWithTax, 2, '.', '') }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="mt-4 text-right">
                        <x-button type="submit">Update</x-button>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
@endsection
