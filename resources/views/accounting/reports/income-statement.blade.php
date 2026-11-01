@php
    $is = $rep['data'];
    $p = $rep['params'];
    $cmp = (bool) $is['compare'];
    $ledger = fn ($a) => route('accounts.reports.show', ['report' => 'ledger', 'account' => $a->id, 'from' => $is['from'], 'to' => $is['to']]);
    $colA = \Illuminate\Support\Carbon::parse($is['to'])->format('j M Y');
    $colB = $cmp ? \Illuminate\Support\Carbon::parse($is['compare'][1])->format('j M Y') : null;
    $section = function ($label, $s) use ($cur, $cmp, $ledger) {
        $out = '<tr class="h"><td colspan="'.($cmp ? 3 : 2).'">'.e($label).'</td></tr>';
        foreach ($s['rows'] as $r) {
            $out .= '<tr class="i"><td><a href="'.e($ledger($r['account'])).'">'.e($r['account']->name).'</a></td><td class="n">'.$cur->figure($r['current']).'</td>'.($cmp ? '<td class="n muted">'.$cur->figure($r['previous']).'</td>' : '').'</tr>';
        }
        return $out;
    };
    $total = fn ($label, $t, $class = 'sub') => '<tr class="'.$class.'"><td>'.e($label).'</td><td class="n">'.$cur->figure($t['current']).'</td>'.($cmp ? '<td class="n">'.$cur->figure($t['previous']).'</td>' : '').'</tr>';
@endphp
<div class="st">
    @include('accounting.reports._header')
    <table>
        <thead><tr><th></th><th class="n">{{ $colA }}</th>@if ($cmp)<th class="n">{{ $colB }}</th>@endif</tr></thead>
        <tbody>
            {!! $section($cur->term('income'), $is['revenue']) !!}
            {!! $total(__('Total').' '.mb_strtolower($cur->term('income')), $is['revenue']) !!}
            @if ($is['cos']['rows'])
                {!! $section(__('Cost of sales'), $is['cos']) !!}
                {!! $total(__('Total cost of sales'), $is['cos']) !!}
                {!! $total($cur->term('gross'), $is['gross'], 'sub big') !!}
            @endif
            @if ($is['other']['rows'])
                {!! $section(__('Other income'), $is['other']) !!}
                {!! $total(__('Total other income'), $is['other']) !!}
            @endif
            @if ($is['expenses'])
                <tr class="h"><td colspan="{{ $cmp ? 3 : 2 }}">{{ $cur->term('expenses') }}</td></tr>
                @foreach ($is['expenses'] as $e)
                    <tr><td style="padding-left:14px;font-weight:600">{{ $e['label'] }}</td><td></td>@if ($cmp)<td></td>@endif</tr>
                    @foreach ($e['rows'] as $r)
                        <tr class="i"><td><a href="{{ $ledger($r['account']) }}">{{ $r['account']->name }}</a></td><td class="n">{{ $cur->figure($r['current']) }}</td>@if ($cmp)<td class="n muted">{{ $cur->figure($r['previous']) }}</td>@endif</tr>
                    @endforeach
                @endforeach
                {!! $total(__('Total').' '.mb_strtolower($cur->term('expenses')), $is['totalExp']) !!}
            @endif
            @if ($is['tax']['rows'])
                {!! $total(($is['before']['current'] >= 0 ? $cur->term('result_short') : __('Deficit')).' '.__('before tax'), $is['before']) !!}
                {!! $section(__('Taxation'), $is['tax']) !!}
            @endif
            {!! $total($is['result']['current'] >= 0 ? $cur->term('result') : $cur->term('loss'), $is['result'], 'tot big') !!}
        </tbody>
    </table>
    <p class="foot">{{ __('Figures in brackets are negative. Comparative figures are for the same dates a year earlier.') }}</p>
</div>
