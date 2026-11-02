@php
    $cf = $rep['data'];
    $cmp = (bool) $cf['compare'];
    $colA = \Illuminate\Support\Carbon::parse($cf['to'])->format('j M Y');
    $colB = $cmp ? \Illuminate\Support\Carbon::parse($cf['to'])->subYear()->format('j M Y') : null;
    $titles = ['operating' => __('Cash flows from operating activities'), 'investing' => __('Cash flows from investing activities'), 'financing' => __('Cash flows from financing activities')];
    $nets = ['operating' => __('Net cash from operating activities'), 'investing' => __('Net cash used in investing activities'), 'financing' => __('Net cash from financing activities')];
@endphp
<div class="st">
    @include('accounting.reports._header')
    <table>
        <thead><tr><th></th><th class="n">{{ $colA }}</th>@if ($cmp)<th class="n">{{ $colB }}</th>@endif</tr></thead>
        <tbody>
            @foreach ($titles as $k => $title)
                <tr class="h"><td colspan="{{ $cmp ? 3 : 2 }}">{{ $title }}</td></tr>
                @foreach ($cf['sections'][$k]['rows'] as $r)
                    <tr class="i"><td>{{ $r['label'] }}</td><td class="n">{{ $cur->figure($r['current']) }}</td>@if ($cmp)<td class="n muted">{{ $cur->figure($r['previous']) }}</td>@endif</tr>
                @endforeach
                <tr class="sub"><td>{{ $nets[$k] }}</td><td class="n">{{ $cur->figure($cf['sections'][$k]['current']) }}</td>@if ($cmp)<td class="n">{{ $cur->figure($cf['sections'][$k]['previous']) }}</td>@endif</tr>
            @endforeach
            <tr class="sub big"><td>{{ __('Net increase / (decrease) in cash') }}</td><td class="n">{{ $cur->figure($cf['net']['current']) }}</td>@if ($cmp)<td class="n">{{ $cur->figure($cf['net']['previous']) }}</td>@endif</tr>
            <tr class="i"><td>{{ __('Cash and cash equivalents at the start') }}</td><td class="n">{{ $cur->figure($cf['opening']['current']) }}</td>@if ($cmp)<td class="n muted">{{ $cur->figure($cf['opening']['previous']) }}</td>@endif</tr>
            @if (abs($cf['brought_in']['current']) >= 0.005 || abs($cf['brought_in']['previous']) >= 0.005)
                <tr class="i"><td>{{ __('Opening balances brought into the books') }}</td><td class="n">{{ $cur->figure($cf['brought_in']['current']) }}</td>@if ($cmp)<td class="n muted">{{ $cur->figure($cf['brought_in']['previous']) }}</td>@endif</tr>
            @endif
            <tr class="tot"><td>{{ __('Cash and cash equivalents at the end') }}</td><td class="n">{{ $cur->figure($cf['closing']['current']) }}</td>@if ($cmp)<td class="n">{{ $cur->figure($cf['closing']['previous']) }}</td>@endif</tr>
        </tbody>
    </table>
    <p class="foot">
        @if (abs($cf['check']) < 0.01)<span class="ok">✓ {{ __('Agrees with the cash and bank balances') }}</span>@else<span class="warn">{{ __('Differs from the bank balances by :a', ['a' => $cur->money($cf['check'])]) }}</span>@endif
        · {{ __('Indirect method. Cash and cash equivalents: :a.', ['a' => $cf['cashAccounts']->pluck('name')->implode(', ')]) }}
    </p>
</div>
