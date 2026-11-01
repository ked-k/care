<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $rep['title'] }} — {{ $cur->legal_name }}</title>
    @include('accounting.reports._style')
    <style>
        body { margin: 0; padding: 32px; background: #fff; }
        .sheet { max-width: 860px; margin: 0 auto; }
        .actions { max-width: 860px; margin: 0 auto 20px; display: flex; gap: 8px; }
        .actions button, .actions a { font: 14px "Segoe UI", Arial, sans-serif; padding: 8px 16px; border-radius: 6px; border: 0; background: #14283f; color: #fff; cursor: pointer; text-decoration: none; }
        .actions a { background: #e5e7eb; color: #1f2937; }
        .signed { display: grid; grid-template-columns: 1fr 1fr; gap: 48px; margin-top: 48px; font: 13px "Segoe UI", Arial, sans-serif; color: #374151; }
        .signed div { border-top: 1px solid #9ca3af; padding-top: 6px; }
        .printed { margin-top: 24px; font: 11px "Segoe UI", Arial, sans-serif; color: #9ca3af; text-align: right; }
        @media print { .actions { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="actions"><button onclick="window.print()">{{ __('Print / save as PDF') }}</button><a href="{{ route('accounts.reports.csv', ['report' => $rep['report']] + $rep['params']) }}">{{ __('Download CSV') }}</a></div>
    <div class="sheet">
        @include($rep['view'])
        @if (in_array($rep['report'], ['income-statement', 'balance-sheet']))
            <div class="signed"><div>{{ __('Approved by') }}</div><div>{{ __('Date') }}</div></div>
        @endif
        <div class="printed">{{ __('Printed :d by :u', ['d' => now()->format('d M Y H:i'), 'u' => auth()->user()?->name]) }}</div>
    </div>
</body>
</html>
