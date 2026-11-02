@php $fa = $rep['data']; $cols = array_merge($fa['rows'], ['__total' => $fa['total'] + ['label' => __('Total')]]); @endphp
<div class="st">
    @if (empty($noHeader))
        @include('accounting.reports._header')
    @elseif ($rep['title'])
        <h4 style="margin:6px 0 8px;font-size:13px">{{ $rep['title'] }}</h4>
    @endif
    <table>
        <thead><tr><th></th>@foreach ($fa['rows'] as $r)<th class="n">{{ $r['label'] }}</th>@endforeach<th class="n">{{ __('Total') }}</th></tr></thead>
        <tbody>
            <tr class="h"><td colspan="{{ count($cols) + 1 }}">{{ __('Cost') }}</td></tr>
            @foreach (['cost_bf' => __('At start of period'), 'additions' => __('Additions'), 'disposals' => __('Disposals')] as $k => $label)
                <tr class="i"><td>{{ $label }}</td>@foreach ($cols as $r)<td class="n">{{ $cur->figure($k === 'disposals' ? -$r[$k] : $r[$k]) }}</td>@endforeach</tr>
            @endforeach
            <tr class="sub"><td>{{ __('At end of period') }}</td>@foreach ($cols as $r)<td class="n">{{ $cur->figure($r['cost_cf']) }}</td>@endforeach</tr>
            <tr class="h"><td colspan="{{ count($cols) + 1 }}">{{ __('Depreciation') }}</td></tr>
            @foreach (['dep_bf' => __('At start of period'), 'charge' => __('Charge for the period'), 'dep_disposals' => __('On disposals')] as $k => $label)
                <tr class="i"><td>{{ $label }}</td>@foreach ($cols as $r)<td class="n">{{ $cur->figure($k === 'dep_disposals' ? -$r[$k] : $r[$k]) }}</td>@endforeach</tr>
            @endforeach
            <tr class="sub"><td>{{ __('At end of period') }}</td>@foreach ($cols as $r)<td class="n">{{ $cur->figure($r['dep_cf']) }}</td>@endforeach</tr>
            <tr class="h"><td colspan="{{ count($cols) + 1 }}">{{ __('Net book value') }}</td></tr>
            <tr class="tot"><td>{{ __('At end of period') }}</td>@foreach ($cols as $r)<td class="n">{{ $cur->figure($r['nbv_cf']) }}</td>@endforeach</tr>
            <tr class="i"><td>{{ __('At start of period') }}</td>@foreach ($cols as $r)<td class="n muted">{{ $cur->figure($r['nbv_bf']) }}</td>@endforeach</tr>
        </tbody>
    </table>
    <p class="foot">{{ __('From the fixed assets register. Rates: :r.', ['r' => collect(\App\Models\Accounting\FixedAsset::CATEGORIES)->filter(fn ($c, $k) => isset($fa['rows'][$k]))->map(fn ($c) => __($c[0]).' '.$c[2].'%')->implode(', ') ?: '—']) }}</p>
</div>
