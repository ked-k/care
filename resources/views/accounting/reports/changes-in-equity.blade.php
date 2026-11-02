@php
    $eq = $rep['data'];
    $total = fn ($vals) => $eq['cols']->sum(fn ($a) => $vals[$a->id] ?? 0);
    $rowLabels = ['opening' => __('Opening balances brought in'), 'result' => $cur->term('result'), 'approp' => __('Transfers to reserves (appropriations)'), 'other' => __('Shares issued, drawings and other movements')];
@endphp
<div class="st">
    @include('accounting.reports._header')
    <table>
        <thead><tr><th></th>@foreach ($eq['cols'] as $a)<th class="n">{{ $a->id === $eq['reId'] ? $cur->term('retained') : $a->name }}</th>@endforeach<th class="n">{{ __('Total') }}</th></tr></thead>
        <tbody>
            <tr class="sub"><td>{{ __('At :d', ['d' => \Illuminate\Support\Carbon::parse($eq['from'])->subDay()->format('j M Y')]) }}</td>@foreach ($eq['cols'] as $a)<td class="n">{{ $cur->figure($eq['start'][$a->id]) }}</td>@endforeach<td class="n">{{ $cur->figure($total($eq['start'])) }}</td></tr>
            @foreach ($rowLabels as $k => $label)
                @if (collect($eq['rows'][$k])->contains(fn ($v) => abs($v) >= 0.005))
                    <tr class="i"><td>{{ $label }}</td>@foreach ($eq['cols'] as $a)<td class="n">{{ $cur->figure($eq['rows'][$k][$a->id]) }}</td>@endforeach<td class="n">{{ $cur->figure($total($eq['rows'][$k])) }}</td></tr>
                @endif
            @endforeach
            <tr class="tot"><td>{{ __('At :d', ['d' => \Illuminate\Support\Carbon::parse($eq['to'])->format('j M Y')]) }}</td>@foreach ($eq['cols'] as $a)<td class="n">{{ $cur->figure($eq['end'][$a->id]) }}</td>@endforeach<td class="n">{{ $cur->figure($total($eq['end'])) }}</td></tr>
        </tbody>
    </table>
    <p class="foot">@if ($eq['balanced'])<span class="ok">✓ {{ __('Agrees with the balance sheet') }}</span>@else<span class="warn">{{ __('Does not agree with the balance sheet — check for entries posted directly to equity.') }}</span>@endif</p>
</div>
