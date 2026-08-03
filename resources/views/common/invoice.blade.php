<div class="text-gray-700">
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-400">From</p>
                <address class="text-sm not-italic leading-relaxed text-gray-600">
                    <strong class="text-gray-800">Themicly,</strong><br>Rajshahi<br>Bangladesh<br>Phone: (088) 016-1707 5540<br>Email: info@themicly.com
                </address>
            </div>
            <div>
                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-400">To</p>
                <address class="text-sm not-italic leading-relaxed text-gray-600">
                    <strong class="text-gray-800">John Doe</strong><br>795 Folsom Ave, Suite 600<br>San Francisco, CA 94107<br>Phone: (555) 123-7654<br>Email: john.doe@example.com
                </address>
            </div>
        </div>
        <div class="flex flex-col items-end justify-between gap-4">
            <h4 class="text-xl font-bold text-gray-800">Invoice #INV007612</h4>
            <div class="flex items-end gap-6">
                <div class="text-right text-sm text-gray-600">
                    <p><span class="font-semibold text-gray-700">Issue Date:</span> Feb 12, 2023</p>
                    <p><span class="font-semibold text-gray-700">Due Date:</span> Apr 12, 2023</p>
                    <p><span class="font-semibold text-gray-700">Account:</span> 968-34567-1234</p>
                </div>
                <img height="100" class="h-24 w-24" src="{{ asset('img/qr.png') }}" alt="">
            </div>
        </div>
    </div>

    <div class="mt-8 overflow-x-auto">
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
                @php
                    $invoiceItems = config('mockdata.invoice_items');
                    $grandTotal = 0;
                    $grandDiscount = 0;
                @endphp
                @foreach ($invoiceItems as $key => $product)
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

    <div class="mt-8 grid grid-cols-1 gap-8 md:grid-cols-2">
        <div>
            <p class="mb-3 text-base font-semibold text-gray-800">Payment Methods:</p>
            <div class="flex flex-wrap items-center gap-3">
                <img class="h-8" src="{{ asset('/img/credit/visa.png') }}" alt="Visa">
                <img class="h-8" src="{{ asset('/img/credit/mastercard.png') }}" alt="Mastercard">
                <img class="h-8" src="{{ asset('/img/credit/american-express.png') }}" alt="American Express">
                <img class="h-8" src="{{ asset('/img/credit/paypal2.png') }}" alt="Paypal">
            </div>
            <div class="mt-5 rounded-lg border border-gray-100 bg-gray-50 px-4 py-3 text-sm text-gray-600">
                Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
            </div>
        </div>
        <div class="md:flex md:justify-end">
            @php
                $taxAmount = $grandTotal * 0.1;
                $grandTotalWithTax = $grandTotal + $taxAmount;
            @endphp
            <table class="w-full text-sm md:max-w-xs">
                <tbody>
                    <tr class="border-b border-gray-100">
                        <th class="py-2 text-left font-medium text-gray-600">Subtotal:</th>
                        <td class="py-2 text-right text-gray-700">{{ number_format($grandTotal, 2, '.', '') }}</td>
                    </tr>
                    <tr class="border-b border-gray-100">
                        <th class="py-2 text-left font-medium text-gray-600">Tax (10%)</th>
                        <td class="py-2 text-right text-gray-700">{{ number_format($taxAmount, 2, '.', '') }}</td>
                    </tr>
                    <tr>
                        <th class="py-2 text-left font-semibold text-gray-800">Total:</th>
                        <td class="py-2 text-right font-semibold text-primary-600">{{ number_format($grandTotalWithTax, 2, '.', '') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
