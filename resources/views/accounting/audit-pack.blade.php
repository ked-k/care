<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $cur->legal_name }} — {{ __('Financial statements') }} {{ $year->name }}</title>
    {{-- Batch 44b: the audit pack. Plain CSS, A4, one section per page. --}}
    @include('accounting.reports._style')
    <style>
        body { margin: 0; background: #e5e7eb; }
        .page { background: #fff; max-width: 820px; margin: 24px auto; padding: 48px 56px; box-shadow: 0 2px 10px rgba(0,0,0,.08); }
        .cover { min-height: 900px; display: flex; flex-direction: column; justify-content: center; text-align: center; font-family: "Segoe UI", Arial, sans-serif; color: #14283f; }
        .cover h1 { font-size: 30px; margin: 0 0 8px; text-transform: uppercase; letter-spacing: .03em; }
        .cover .what { font-size: 18px; margin-top: 28px; }
        .cover .meta { color: #64748b; margin-top: 6px; }
        .contents { text-align: left; margin: 48px auto 0; width: 420px; font-size: 14px; }
        .contents div { display: flex; justify-content: space-between; border-bottom: 1px dotted #cbd5e1; padding: 6px 0; }
        h2.sec { font-family: "Segoe UI", Arial, sans-serif; font-size: 15px; text-transform: uppercase; letter-spacing: .04em; color: #14283f; border-bottom: 2px solid #14283f; padding-bottom: 6px; margin: 0 0 18px; }
        .prose { font-family: "Segoe UI", Arial, sans-serif; font-size: 14px; color: #1f2937; line-height: 1.6; }
        .note h3 { font-size: 14px; margin: 18px 0 4px; color: #14283f; }
        .note p { margin: 0; white-space: pre-line; }
        .signed { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 56px; font: 13px "Segoe UI", Arial, sans-serif; color: #374151; }
        .signed div { border-top: 1px solid #9ca3af; padding-top: 6px; }
        .draft { position: fixed; top: 12px; right: 12px; background: #fef3c7; color: #92400e; padding: 6px 12px; border-radius: 6px; font: 600 12px "Segoe UI", Arial; }
        .actions { max-width: 820px; margin: 16px auto 0; display: flex; gap: 8px; font: 14px "Segoe UI", Arial, sans-serif; }
        .actions button, .actions a { padding: 8px 16px; border-radius: 6px; border: 0; background: #14283f; color: #fff; cursor: pointer; text-decoration: none; }
        .actions a { background: #fff; color: #14283f; border: 1px solid #cbd5e1; }
        @media print {
            body { background: #fff; }
            .actions, .draft { display: none; }
            .page { box-shadow: none; margin: 0; max-width: none; padding: 0; page-break-after: always; }
            .page:last-child { page-break-after: auto; }
            @page { size: A4; margin: 18mm 16mm; }
        }
    </style>
</head>
<body>
    @php
        $org = $cur->legal_name ?: $agency?->name;
        $ended = $year->ends_on->format('j F Y');
        $n = count($notes);
        $figureTitles = collect($figures)->pluck('title')->values();
        $contents = [$cur->term('income_statement'), $cur->term('balance_sheet'), __('Statement of changes in equity'), __('Statement of cash flows'), __('Notes to the financial statements'), __('Appendix: trial balance')];
        if ($withLedgers) { $contents[] = __('Appendix: general ledger'); }
    @endphp
    @unless ($closed)<div class="draft">{{ __('DRAFT — :y is not closed yet', ['y' => $year->name]) }}</div>@endunless
    <div class="actions">
        <button onclick="window.print()">{{ __('Print / save as PDF') }}</button>
        @if ($withLedgers)
            <a href="{{ route('accounts.audit-pack', $year->id) }}">{{ __('Without ledgers') }}</a>
        @else
            <a href="{{ route('accounts.audit-pack', [$year->id, 'ledgers' => 1]) }}">{{ __('Include every account’s ledger') }}</a>
        @endif
        <a href="{{ route('accounts.notes', ['year' => $year->id]) }}">{{ __('Edit notes') }}</a>
    </div>

    {{-- Cover --}}
    <section class="page cover">
        @if ($agency?->logo_path)<div><img src="{{ asset('storage/'.$agency->logo_path) }}" alt="" style="height:72px;margin-bottom:24px"></div>@endif
        <h1>{{ $org }}</h1>
        @if ($cur->registration_number)<div class="meta">{{ __('Registration number :n', ['n' => $cur->registration_number]) }}</div>@endif
        <div class="what">{{ __('Annual report and financial statements') }}<br>{{ __('for the year ended :d', ['d' => $ended]) }}</div>
        <div class="contents">
            @foreach ($contents as $i => $c)<div><span>{{ $i + 1 }}. {{ $c }}</span></div>@endforeach
        </div>
    </section>

    {{-- Approval --}}
    <section class="page prose">
        <h2 class="sec">{{ __('Approval of the financial statements') }}</h2>
        <p>{{ __('The :who are responsible for preparing financial statements for each financial year which give a true and fair view of the state of affairs of :org and of its :result for that year. In preparing them they have selected suitable accounting policies and applied them consistently, made judgements and estimates that are reasonable and prudent, and prepared the statements on a going concern basis.', [
            'who' => $cur->organisation_type === 'cooperative' ? __('committee members') : ($cur->organisation_type === 'charity' ? __('trustees') : __('directors')),
            'org' => $org, 'result' => mb_strtolower($cur->term('result_short')),
        ]) }}</p>
        <p>{{ __('The financial statements on the following pages were approved on ____________________ and signed on behalf of :org by:', ['org' => $org]) }}</p>
        <div class="signed"><div>{{ __('Name and signature') }}</div><div>{{ __('Name and signature') }}</div></div>
        <div class="signed"><div>{{ __('Position') }}</div><div>{{ __('Position') }}</div></div>
    </section>

    @foreach ($statements as $rep)
        <section class="page">@include($rep['view'])</section>
    @endforeach

    {{-- Notes --}}
    <section class="page prose">
        <h2 class="sec">{{ __('Notes to the financial statements') }}</h2>
        <p class="muted" style="color:#64748b">{{ __('for the year ended :d', ['d' => $ended]) }}</p>
        @foreach ($notes as $i => $note)
            <div class="note"><h3>{{ $i + 1 }}. {{ $note->title }}</h3><p>{{ $note->body }}</p></div>
        @endforeach

        @php $k = $n; $prevEnd = $year->ends_on->copy()->subYear()->format('j M Y'); $thisEnd = $year->ends_on->format('j M Y'); @endphp
        <div class="st" style="margin-top:18px">
            @foreach (['revenue', 'receivables', 'cash', 'payables', 'equity'] as $key)
                @php $f = $figures[$key]; $k++; @endphp
                <div class="note"><h3>{{ $k }}. {{ $f['title'] }}</h3></div>
                <table>
                    <thead><tr><th></th><th class="n">{{ $thisEnd }}</th><th class="n">{{ $prevEnd }}</th></tr></thead>
                    <tbody>
                        @forelse ($f['rows'] as $r)
                            <tr class="i"><td>{{ $r['account']?->name ?? $r['label'] }}</td><td class="n">{{ $cur->figure($r['current']) }}</td><td class="n muted">{{ $cur->figure($r['previous']) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="muted">{{ __('None.') }}</td></tr>
                        @endforelse
                        @php $tc = $f['total']['current'] ?? collect($f['rows'])->sum('current'); $tp = $f['total']['previous'] ?? collect($f['rows'])->sum('previous'); @endphp
                        <tr class="tot"><td>{{ __('Total') }}</td><td class="n">{{ $cur->figure($tc) }}</td><td class="n">{{ $cur->figure($tp) }}</td></tr>
                    </tbody>
                </table>
            @endforeach
            @php $k++; @endphp
            <div class="note"><h3>{{ $k }}. {{ $figures['ppe']['title'] }}</h3></div>
            @include('accounting.reports.fixed-assets', ['rep' => ['data' => $figures['ppe']['schedule'], 'title' => '', 'subtitle' => ''], 'noHeader' => true])
        </div>
    </section>

    <section class="page">@include($tb['view'], ['rep' => $tb])</section>

    @if ($withLedgers)
        <section class="page">
            <h2 class="sec">{{ __('Appendix: general ledger') }}</h2>
            @foreach ($ledgers as $lg)
                <div style="margin-bottom:22px">
                    @include('accounting.reports.ledger', ['rep' => ['data' => $lg, 'title' => $lg['account']->label(), 'subtitle' => $year->name, 'params' => ['from' => $year->starts_on->toDateString()]], 'noHeader' => true])
                </div>
            @endforeach
        </section>
    @endif
</body>
</html>
