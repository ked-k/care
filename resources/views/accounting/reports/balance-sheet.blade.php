@php
    $bs = $rep['data'];
    $cmp = (bool) $bs['compareAsAt'];
    $colA = \Illuminate\Support\Carbon::parse($bs['asAt'])->format('j M Y');
    $colB = $cmp ? \Illuminate\Support\Carbon::parse($bs['compareAsAt'])->format('j M Y') : null;
    $rows = function ($g) use ($cur, $cmp, $bs) {
        $out = '';
        foreach ($g['rows'] as $r) {
            $name = $r['account'] ? '<a href="'.e(route('accounts.reports.show', ['report' => 'ledger', 'account' => $r['account']->id, 'to' => $bs['asAt']])).'">'.e($r['account']->name).'</a>' : e($r['label']);
            $out .= '<tr class="i"><td>'.$name.'</td><td class="n">'.$cur->figure($r['current']).'</td>'.($cmp ? '<td class="n muted">'.$cur->figure($r['previous']).'</td>' : '').'</tr>';
        }
        return $out;
    };
    $line = fn ($label, $t, $class = 'sub') => '<tr class="'.$class.'"><td>'.e($label).'</td><td class="n">'.$cur->figure($t['current']).'</td>'.($cmp ? '<td class="n">'.$cur->figure($t['previous']).'</td>' : '').'</tr>';
    $head = fn ($label) => '<tr class="h"><td colspan="'.($cmp ? 3 : 2).'">'.e($label).'</td></tr>';
@endphp
<div class="st">
    @include('accounting.reports._header')
    <table>
        <thead><tr><th></th><th class="n">{{ $colA }}</th>@if ($cmp)<th class="n">{{ $colB }}</th>@endif</tr></thead>
        <tbody>
            <tr class="h"><td colspan="{{ $cmp ? 3 : 2 }}" style="font-size:13px">{{ __('Assets') }}</td></tr>
            @if ($bs['nonCurrent']['rows'])
                {!! $head(__('Non-current assets')) !!}{!! $rows($bs['nonCurrent']) !!}{!! $line(__('Total non-current assets'), $bs['nonCurrent']) !!}
            @endif
            {!! $head(__('Current assets')) !!}{!! $rows($bs['current']) !!}{!! $line(__('Total current assets'), $bs['current']) !!}
            {!! $line(__('Total assets'), $bs['assets'], 'tot') !!}

            <tr class="h"><td colspan="{{ $cmp ? 3 : 2 }}" style="font-size:13px;padding-top:22px">{{ __('Equity and liabilities') }}</td></tr>
            {!! $head(__('Equity')) !!}{!! $rows($bs['equity']) !!}{!! $line(__('Total equity'), $bs['equity']) !!}
            @if ($bs['longLiab']['rows'])
                {!! $head(__('Non-current liabilities')) !!}{!! $rows($bs['longLiab']) !!}{!! $line(__('Total non-current liabilities'), $bs['longLiab']) !!}
            @endif
            {!! $head(__('Current liabilities')) !!}{!! $rows($bs['currentLiab']) !!}{!! $line(__('Total current liabilities'), $bs['currentLiab']) !!}
            {!! $line(__('Total equity and liabilities'), ['current' => $bs['equity']['current'] + $bs['liabilities']['current'], 'previous' => $bs['equity']['previous'] + $bs['liabilities']['previous']], 'tot') !!}
        </tbody>
    </table>
    <p class="foot">
        @if (abs($bs['check']) < 0.01)<span class="ok">✓ {{ __('Balances') }}</span>@else<span class="warn">{{ __('Out of balance by :a — check the trial balance.', ['a' => $cur->money($bs['check'])]) }}</span>@endif
        · {{ __('“:r” is this year’s result to date; it moves into reserves when the year is closed.', ['r' => $cur->term('result')]) }}
    </p>
</div>
