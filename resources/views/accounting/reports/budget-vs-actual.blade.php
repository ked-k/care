{{-- Batch 44c: budget vs actual, account by account, for whole months of a financial year. --}}
@php
    $bv = $rep['data'];
    $ledger = fn ($a) => route('accounts.reports.show', ['report' => 'ledger', 'account' => $a->id, 'from' => $rep['params']['from'], 'to' => $rep['params']['to']]);
    $var = fn ($v) => '<span class="'.($v < -0.004 ? 'warn' : ($v > 0.004 ? 'ok' : '')).'">'.$cur->figure($v).'</span>';
    $pct = fn ($p) => $p === null ? '–' : ($p > 0 ? '+' : '').number_format($p, 1).'%';
@endphp
<div class="st">
    @include('accounting.reports._header')
    @if (! $bv['budget'])
        <p style="text-align:center;padding:24px 0;color:#6b7280">
            {{ __('There is no budget for :y yet.', ['y' => $bv['year']->name]) }}
            @if (\Illuminate\Support\Facades\Route::has('accounts.budgets'))
                <a href="{{ route('accounts.budgets', ['year' => $bv['year']->id]) }}" style="color:#2563eb;text-decoration:underline">{{ __('Set one up') }}</a>
            @endif
        </p>
    @else
        @php
            // Cumulative result, budget vs actual, through the chosen months.
            $cumB = []; $cumA = []; $b = 0; $a = 0;
            foreach ($bv['monthly'] as $i => $m) {
                if ($i > $bv['toMonth']) break;
                $b += $m['budget']; $a += $m['actual']; $cumB[] = $b; $cumA[] = $a;
            }
            $all = array_merge($cumB, $cumA, [0]);
            $lo = min($all); $hi = max($all); $span = max(1, $hi - $lo);
            $w = 640; $h = 150; $pad = 24; $n = max(1, count($cumB) - 1);
            $x = fn ($i) => $pad + ($w - 2 * $pad) * ($n ? $i / $n : 0);
            $y = fn ($v) => $pad / 2 + ($h - $pad) * (1 - ($v - $lo) / $span);
            $pts = fn ($arr) => collect($arr)->map(fn ($v, $i) => round($x($i), 1).','.round($y($v), 1))->implode(' ');
        @endphp
        @if (count($cumB) > 1)
            <div style="margin:0 0 18px">
                <div style="display:flex;gap:16px;font-size:12px;color:#6b7280;margin-bottom:4px">
                    <span><span style="display:inline-block;width:14px;height:2px;background:#9ca3af;vertical-align:middle;margin-right:4px;border-top:2px dashed #9ca3af"></span>{{ __('Budget') }} — {{ __('cumulative :r', ['r' => mb_strtolower($cur->term('result_short'))]) }}</span>
                    <span><span style="display:inline-block;width:14px;height:3px;background:#2563eb;vertical-align:middle;margin-right:4px"></span>{{ __('Actual') }}</span>
                </div>
                <svg viewBox="0 0 {{ $w }} {{ $h }}" width="100%" role="img" aria-label="{{ __('Cumulative result: budget against actual') }}" style="max-height:170px">
                    <line x1="{{ $pad }}" x2="{{ $w - $pad }}" y1="{{ round($y(0), 1) }}" y2="{{ round($y(0), 1) }}" stroke="#e5e7eb" />
                    <polyline points="{{ $pts($cumB) }}" fill="none" stroke="#9ca3af" stroke-width="2" stroke-dasharray="5 4" />
                    <polyline points="{{ $pts($cumA) }}" fill="none" stroke="#2563eb" stroke-width="2.5" />
                    @foreach ($cumA as $i => $v)
                        <circle cx="{{ round($x($i), 1) }}" cy="{{ round($y($v), 1) }}" r="3" fill="#2563eb"><title>{{ $bv['months'][$i]->format('M Y') }}: {{ $cur->money($v) }} ({{ __('budget') }} {{ $cur->money($cumB[$i]) }})</title></circle>
                        <text x="{{ round($x($i), 1) }}" y="{{ $h - 2 }}" font-size="10" fill="#9ca3af" text-anchor="middle">{{ $bv['months'][$i]->format('M') }}</text>
                    @endforeach
                </svg>
            </div>
        @endif
        <table>
            <thead><tr><th class="code">{{ __('Code') }}</th><th></th><th class="n">{{ __('Budget') }}</th><th class="n">{{ __('Actual') }}</th><th class="n">{{ __('Variance') }}</th><th class="n">%</th><th class="n">{{ __('Full year') }}</th></tr></thead>
            <tbody>
                @foreach (['income' => $cur->term('income'), 'expense' => $cur->term('expenses')] as $t => $label)
                    <tr class="h"><td colspan="7">{{ $label }}</td></tr>
                    @forelse ($bv['sections'][$t] as $r)
                        <tr class="i">
                            <td class="code">{{ $r['account']->code }}</td>
                            <td><a href="{{ $ledger($r['account']) }}">{{ $r['account']->name }}</a></td>
                            <td class="n">{{ $cur->figure($r['budget']) }}</td>
                            <td class="n">{{ $cur->figure($r['actual']) }}</td>
                            <td class="n">{!! $var($r['variance']) !!}</td>
                            <td class="n muted">{{ $pct($r['pct']) }}</td>
                            <td class="n muted">{{ $cur->figure($r['year']) }}</td>
                        </tr>
                    @empty
                        <tr><td></td><td class="muted" colspan="6">{{ __('Nothing budgeted or recorded.') }}</td></tr>
                    @endforelse
                    @php $tt = $bv['totals'][$t]; @endphp
                    <tr class="sub"><td></td><td>{{ __('Total') }} {{ mb_strtolower($label) }}</td><td class="n">{{ $cur->figure($tt['budget']) }}</td><td class="n">{{ $cur->figure($tt['actual']) }}</td><td class="n">{!! $var($tt['variance']) !!}</td><td></td><td class="n">{{ $cur->figure($tt['year']) }}</td></tr>
                @endforeach
                @php $r = $bv['result']; @endphp
                <tr class="tot big"><td></td><td>{{ $r['actual'] >= 0 ? $cur->term('result_short') : __('Deficit') }}</td><td class="n">{{ $cur->figure($r['budget']) }}</td><td class="n">{{ $cur->figure($r['actual']) }}</td><td class="n">{!! $var($r['variance']) !!}</td><td></td><td class="n">{{ $cur->figure($r['year']) }}</td></tr>
            </tbody>
        </table>
        <p class="foot">{{ __('Variance is shown so that a positive figure is good news: income above budget, or spending below it. Red figures are behind budget. “Full year” is the whole year’s budget.') }}</p>
    @endif
</div>
