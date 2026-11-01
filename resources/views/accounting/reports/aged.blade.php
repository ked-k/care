@php $ag = $rep['data']; $buckets = \App\Services\Accounting\Reports::AGE_BUCKETS; $hasCredit = ! empty($ag['totals']['credit']); @endphp
<div class="st">
    @include('accounting.reports._header')
    <table>
        <thead><tr><th>{{ $ag['kind'] === 'invoice' ? __('Customer') : __('Supplier') }}</th>@foreach ($buckets as $b)<th class="n">{{ __($b) }}</th>@endforeach @if ($hasCredit)<th class="n">{{ __('Unmatched credit') }}</th>@endif<th class="n">{{ __('Total') }}</th></tr></thead>
        <tbody>
            @forelse ($ag['contacts'] as $cid => $c)
                <tr>
                    <td>@if ($c['contact'])<a href="{{ route('accounts.contacts.show', $c['contact']->id) }}">{{ $c['contact']->name }}</a>@else — @endif
                        <div class="muted" style="font-size:12px">{{ collect($c['docs'])->map(fn ($d) => $d['doc']->number)->take(6)->implode(', ') }}{{ count($c['docs']) > 6 ? '…' : '' }}</div></td>
                    @foreach (array_keys($buckets) as $b)<td class="n {{ $b !== 'current' && $c[$b] > 0 ? 'warn' : '' }}">{{ $c[$b] > 0 ? $cur->money($c[$b], false) : '' }}</td>@endforeach
                    @if ($hasCredit)<td class="n">{{ ! empty($c['credit']) ? '('.$cur->money($c['credit'], false).')' : '' }}</td>@endif
                    <td class="n" style="font-weight:600">{{ $cur->figure($c['total']) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted" style="text-align:center;padding:18px">{{ __('Nothing outstanding.') }}</td></tr>
            @endforelse
            <tr class="tot"><td>{{ __('Total') }}</td>@foreach (array_keys($buckets) as $b)<td class="n">{{ $cur->money($ag['totals'][$b], false) }}</td>@endforeach @if ($hasCredit)<td class="n">({{ $cur->money($ag['totals']['credit'], false) }})</td>@endif<td class="n">{{ $cur->money($ag['totals']['total'], false) }}</td></tr>
        </tbody>
    </table>
    <p class="foot">{{ __('Ages are counted from each invoice’s due date.') }}</p>
</div>
