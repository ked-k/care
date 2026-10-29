<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('Packing slip') }} {{ $order->number }}</title>
    {{-- Batch 40: plain print view, own styles — prints one A4 page per order. --}}
    <style>
        * { box-sizing: border-box; }
        body { font-family: "Segoe UI", Roboto, Arial, sans-serif; color: #14283f; margin: 0; padding: 32px; font-size: 14px; }
        .bar { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #14283f; padding-bottom: 16px; margin-bottom: 20px; }
        h1 { font-size: 26px; margin: 0; }
        .muted { color: #64748b; }
        .cols { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 24px; margin-bottom: 24px; }
        h2 { font-size: 13px; margin: 0 0 6px; color: #64748b; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 12px; color: #64748b; border-bottom: 1px solid #cbd5e1; padding: 8px 6px; }
        td { border-bottom: 1px solid #e2e8f0; padding: 10px 6px; vertical-align: top; }
        .qty { font-size: 18px; font-weight: 700; width: 60px; }
        .tick { width: 28px; height: 28px; border: 2px solid #14283f; border-radius: 4px; }
        .note { margin-top: 20px; padding: 12px; border: 1px dashed #94a3b8; border-radius: 6px; }
        .actions { margin-bottom: 20px; }
        .actions button { font: inherit; padding: 8px 16px; border-radius: 6px; border: 0; background: #14283f; color: #fff; cursor: pointer; }
        @media print { .actions { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="actions"><button onclick="window.print()">{{ __('Print') }}</button></div>
    <div class="bar">
        <div>
            <h1>{{ $store->name }}</h1>
            <div class="muted" style="white-space:pre-line">{{ $store->address }}</div>
        </div>
        <div style="text-align:right">
            <h1>{{ $order->number }}</h1>
            <div class="muted">{{ $order->created_at->format('j M Y') }}</div>
            <div><strong>{{ $order->delivery_name }}</strong></div>
        </div>
    </div>

    <div class="cols">
        <div>
            <h2>{{ $order->shipping_address ? __('Deliver to') : __('Customer collecting') }}</h2>
            @php $a = $order->shipping_address ?? $order->billing_address; @endphp
            <strong>{{ $a['name'] ?? '' }}</strong><br>
            @foreach (['company', 'line1', 'line2', 'city', 'county', 'postcode'] as $f)
                @if (! empty($a[$f])){{ $a[$f] }}<br>@endif
            @endforeach
            {{ $a['phone'] ?? $order->phone }}
        </div>
        <div>
            <h2>{{ __('Contact') }}</h2>
            {{ $order->email }}<br>{{ $order->phone }}
        </div>
        <div>
            <h2>{{ __('Payment') }}</h2>
            {{ $order->paymentMethodLabel() }} — <strong>{{ $order->paymentStatusLabel() }}</strong>
            @if ($order->payment_status !== 'paid')<br><strong>{{ __('Collect :amount', ['amount' => $store->money($order->total)]) }}</strong>@endif
        </div>
    </div>

    <table>
        <thead><tr><th>{{ __('Qty') }}</th><th>{{ __('Item') }}</th><th>{{ __('SKU') }}</th><th>{{ __('Pick from batch') }}</th><th>{{ __('Packed') }}</th></tr></thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td class="qty">{{ $item->qty }}</td>
                    <td>{{ $item->name }}@if ($item->pack_size)<br><span class="muted">{{ $item->pack_size }}</span>@endif</td>
                    <td>{{ $item->sku }}</td>
                    <td>
                        @forelse ($item->lotsUsed() as $used)
                            <div>{{ $used['qty'] }} × {{ $used['lot']->lot_number ?: __('no lot no.') }}@if ($used['lot']->expiry_date) <span class="muted">({{ __('exp.') }} {{ $used['lot']->expiry_date->format('d/m/Y') }})</span>@endif</div>
                        @empty
                            <span class="muted">—</span>
                        @endforelse
                    </td>
                    <td><div class="tick"></div></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="muted">{{ trans_choice(':count item|:count items', $order->items->sum('qty'), ['count' => $order->items->sum('qty')]) }}</p>

    @if ($order->customer_notes)
        <div class="note"><strong>{{ __('Customer note') }}:</strong> {{ $order->customer_notes }}</div>
    @endif
</body>
</html>
