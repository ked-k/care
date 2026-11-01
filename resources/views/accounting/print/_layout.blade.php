<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
    {{-- Batch 43: plain print styling, same family as the GRN / packing slip pages. --}}
    <style>
        * { box-sizing: border-box; }
        body { font-family: "Segoe UI", Roboto, Arial, sans-serif; color: #14283f; margin: 0; padding: 32px; font-size: 14px; background: #fff; }
        .page { max-width: 820px; margin: 0 auto; }
        .bar { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; border-bottom: 3px solid #14283f; padding-bottom: 16px; margin-bottom: 20px; }
        .org { font-size: 18px; font-weight: 700; }
        .muted { color: #64748b; }
        h1 { font-size: 26px; margin: 0; text-align: right; letter-spacing: .02em; }
        .meta { text-align: right; margin-top: 6px; line-height: 1.6; }
        .cols { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 22px; }
        h2 { font-size: 12px; margin: 0 0 6px; color: #64748b; text-transform: uppercase; letter-spacing: .05em; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 12px; color: #64748b; border-bottom: 1px solid #cbd5e1; padding: 8px 6px; }
        td { border-bottom: 1px solid #e2e8f0; padding: 9px 6px; vertical-align: top; }
        .r { text-align: right; white-space: nowrap; }
        .totals { margin-left: auto; width: 300px; margin-top: 12px; }
        .totals td { border: 0; padding: 4px 6px; }
        .totals tr.grand td { font-size: 16px; font-weight: 700; border-top: 2px solid #14283f; padding-top: 8px; }
        .stamp { display: inline-block; padding: 3px 10px; border: 2px solid; border-radius: 6px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
        .paid { color: #15803d; } .void { color: #9ca3af; } .overdue { color: #b91c1c; }
        .note { margin-top: 26px; padding-top: 12px; border-top: 1px solid #e2e8f0; white-space: pre-line; color: #475569; font-size: 13px; }
        .pre { white-space: pre-line; }
        .actions { max-width: 820px; margin: 0 auto 20px; }
        .actions button { font: inherit; padding: 8px 16px; border-radius: 6px; border: 0; background: #14283f; color: #fff; cursor: pointer; }
        @media print { .actions { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="actions"><button onclick="window.print()">{{ __('Print / save as PDF') }}</button></div>
    <div class="page">
        <div class="bar">
            <div>
                @if ($agency?->logo_path)<img src="{{ asset('storage/'.$agency->logo_path) }}" alt="" style="height:48px;margin-bottom:6px"><br>@endif
                <div class="org">{{ $cur->legal_name ?: $agency?->name }}</div>
                @if ($cur->address ?: $agency?->address)<div class="muted pre">{{ $cur->address ?: $agency?->address }}</div>@endif
                @if ($agency?->phone || $agency?->contact_email)<div class="muted">{{ $agency?->phone }}{{ $agency?->phone && $agency?->contact_email ? ' · ' : '' }}{{ $agency?->contact_email }}</div>@endif
                @if ($cur->tax_number)<div class="muted">{{ __('VAT / TIN') }}: {{ $cur->tax_number }}</div>@endif
                @if ($cur->registration_number)<div class="muted">{{ __('Reg. no.') }} {{ $cur->registration_number }}</div>@endif
            </div>
            <div>@yield('heading')</div>
        </div>
        @yield('content')
        @if ($cur->invoice_footer)<div class="note">{{ $cur->invoice_footer }}</div>@endif
    </div>
</body>
</html>
