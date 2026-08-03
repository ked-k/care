@php
    $invoiceItems = config('mockdata.invoice_items');
    $grandTotal = 0;
    $grandDiscount = 0;
@endphp

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-4 border-b border-gray-100 pb-6 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h4 class="text-lg font-semibold text-gray-800">Bill #BL007612</h4>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <p class="mb-1 text-xs uppercase tracking-wide text-gray-400">From</p>
            <address class="not-italic text-sm leading-6 text-gray-600">
                <strong class="text-gray-800">Themicly,</strong><br>Rajshahi<br>Bangladesh<br>Phone: (088) 016-1707 5540<br>Email: info@themicly.com
            </address>
        </div>
        <div>
            <p class="mb-1 text-xs uppercase tracking-wide text-gray-400">To</p>
            <address class="not-italic text-sm leading-6 text-gray-600">
                <strong class="text-gray-800">John Doe</strong><br>795 Folsom Ave, Suite 600<br>San Francisco, CA 94107<br>Phone: (555) 123-7654<br>Email: john.doe@example.com
            </address>
        </div>
        <div class="text-sm leading-6 text-gray-600 sm:text-right">
            <b class="text-gray-800">Issue Date:</b> Feb 12, 2023<br>
            <b class="text-gray-800">Due Date:</b> Apr 12, 2023<br>
            <b class="text-gray-800">Account:</b> 968-34567-1234
        </div>
        <div class="sm:text-right">
            <img height="100" class="ml-auto h-24 w-24" src="{{ asset('img/qr.png') }}" alt="">
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="px-3 py-3 font-medium">SL</th>
                    <th class="px-3 py-3 font-medium">Product</th>
                    <th class="px-3 py-3 font-medium">Unit Price</th>
                    <th class="px-3 py-3 font-medium">Qty</th>
                    <th class="px-3 py-3 font-medium">Discount</th>
                    <th class="px-3 py-3 text-right font-medium">Sub Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($invoiceItems as $key => $product)
                    @php
                        $subtotal = $product['qty'] * ($product['unit_price'] - $product['discount']);
                        $grandTotal += $subtotal;
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-3 text-gray-600">{{ $key + 1 }}</td>
                        <td class="px-3 py-3 text-gray-700">{{ $product['name'] }}</td>
                        <td class="px-3 py-3 text-gray-600">{{ $product['unit_price'] }}</td>
                        <td class="px-3 py-3 text-gray-600">{{ $product['qty'] }}</td>
                        <td class="px-3 py-3 text-gray-600">{{ number_format($product['discount'], 2, '.', '') }}</td>
                        <td class="px-3 py-3 text-right text-gray-700">{{ number_format($subtotal, 2, '.', '') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @php
        $taxAmount = $grandTotal * 0.1;
        $grandTotalWithTax = $grandTotal + $taxAmount;
    @endphp

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div>
            <p class="mb-2 font-medium text-gray-700">Payment Methods:</p>
            <div class="flex flex-wrap items-center gap-3">
                <img src="{{ asset('/img/credit/visa.png') }}" alt="Visa">
                <img src="{{ asset('/img/credit/mastercard.png') }}" alt="Mastercard">
                <img src="{{ asset('/img/credit/american-express.png') }}" alt="American Express">
                <img src="{{ asset('/img/credit/paypal2.png') }}" alt="Paypal">
            </div>
            <div class="mt-5 rounded-lg bg-gray-50 p-4 text-sm text-gray-600">
                Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
            </div>
        </div>
        <div class="lg:pl-10">
            <table class="w-full text-sm">
                <tbody class="divide-y divide-gray-50">
                    <tr>
                        <th class="py-2 text-left font-medium text-gray-700">Subtotal:</th>
                        <td class="py-2 text-right text-gray-700">{{ number_format($grandTotal, 2, '.', '') }}</td>
                    </tr>
                    <tr>
                        <th class="py-2 text-left font-medium text-gray-700">Tax (10%)</th>
                        <td class="py-2 text-right text-gray-700">{{ number_format($taxAmount, 2, '.', '') }}</td>
                    </tr>
                    <tr class="border-t border-gray-100">
                        <th class="py-2 text-left font-semibold text-gray-800">Total:</th>
                        <td class="py-2 text-right font-semibold text-gray-800">{{ number_format($grandTotalWithTax, 2, '.', '') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
