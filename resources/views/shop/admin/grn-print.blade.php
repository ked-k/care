<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $grn->number }} — {{ __('Goods received note') }}</title>
    {{-- Batch 42: printable goods received note (same plain print style as the packing slip). --}}
    <style>
        * { box-sizing: border-box; }
        body { font-family: "Segoe UI", Roboto, Arial, sans-serif; color: #14283f; margin: 0; padding: 32px; font-size: 14px; }
        .bar { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #14283f; padding-bottom: 16px; margin-bottom: 20px; }
        h1 { font-size: 24px; margin: 0; }
        .muted { color: #64748b; }
        .cols { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 24px; margin-bottom: 24px; }
        h2 { font-size: 13px; margin: 0 0 6px; color: #64748b; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 12px; color: #64748b; border-bottom: 1px solid #cbd5e1; padding: 8px 6px; }
        td { border-bottom: 1px solid #e2e8f0; padding: 9px 6px; vertical-align: top; }
        .r { text-align: right; white-space: nowrap; }
        tfoot td { font-weight: 700; border-bottom: 0; }
        .stamp { display: inline-block; padding: 3px 10px; border: 2px solid; border-radius: 6px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
        .sign { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 40px; }
        .sign div { border-top: 1px solid #94a3b8; padding-top: 6px; }
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
            <h1>{{ __('Goods received note') }}</h1>
            <div style="font-size:18px; font-weight:700">{{ $grn->number }}</div>
            <span class="stamp" style="color: {{ ['draft' => '#b45309', 'received' => '#2f7d4f', 'reversed' => '#64748b'][$grn->status] ?? '#64748b' }}">{{ $grn->statusLabel() }}</span>
        </div>
    </div>

    <div class="cols">
        <div>
            <h2>{{ __('Supplier') }}</h2>
            <strong>{{ $grn->supplier?->name ?? '—' }}</strong>
            @if ($grn->supplier?->account_number)<br><span class="muted">{{ __('Account') }}: {{ $grn->supplier->account_number }}</span>@endif
        </div>
        <div>
            <h2>{{ __('References') }}</h2>
            {{ __('Delivery note / invoice') }}: {{ $grn->supplier_reference ?: '—' }}<br>
            {{ __('PO') }}: {{ $grn->purchase_order ?: '—' }}
        </div>
        <div>
            <h2>{{ __('Received') }}</h2>
            {{ $grn->received_on->format('j M Y') }}<br>
            <span class="muted">{{ $grn->receiver?->name ? __('by :name', ['name' => $grn->receiver->name]) : __('not yet received') }}</span>
        </div>
    </div>

    <table>
        <thead><tr><th>{{ __('Item') }}</th><th>{{ __('Code') }}</th><th>{{ __('Lot / batch') }}</th><th>{{ __('Expiry') }}</th><th class="r">{{ __('Qty') }}</th><th class="r">{{ __('Unit cost') }}</th><th class="r">{{ __('Total') }}</th></tr></thead>
        <tbody>
            @foreach ($grn->lines as $line)
                <tr>
                    <td>{{ $line->name }}</td>
                    <td>{{ $line->sku }}</td>
                    <td>{{ $line->lot_number ?: '—' }}</td>
                    <td>{{ $line->expiry_date?->format('d/m/Y') ?? '—' }}</td>
                    <td class="r"><strong>{{ $line->qty }}</strong></td>
                    <td class="r">{{ $line->unit_cost !== null ? $store->money($line->unit_cost) : '—' }}</td>
                    <td class="r">{{ $store->money($line->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot><tr><td colspan="4">{{ __('Total') }}</td><td class="r">{{ $grn->lines->sum('qty') }}</td><td></td><td class="r">{{ $store->money($grn->total_cost) }}</td></tr></tfoot>
    </table>

    @if ($grn->notes)<p><strong>{{ __('Notes') }}:</strong> {{ $grn->notes }}</p>@endif
    @if ($grn->status === 'reversed')<p><strong>{{ __('Reversed') }}:</strong> {{ $grn->reversed_at?->format('j M Y H:i') }} — {{ $grn->reversal_reason }}</p>@endif

    <div class="sign">
        <div>{{ __('Checked by (name and signature)') }}</div>
        <div>{{ __('Date') }}</div>
    </div>
</body>
</html>
