@php $tb = $rep['data']; $asAt = $rep['params']['as_at']; @endphp
<div class="st">
    @include('accounting.reports._header')
    <table>
        <thead><tr><th class="code">{{ __('Code') }}</th><th>{{ __('Account') }}</th><th class="n">{{ __('Debit') }}</th><th class="n">{{ __('Credit') }}</th></tr></thead>
        <tbody>
            @php $lastType = null; @endphp
            @foreach ($tb['rows'] as $r)
                @if ($r['account']->type !== $lastType)
                    @php $lastType = $r['account']->type; @endphp
                    <tr class="h"><td colspan="4">{{ __(\App\Models\Accounting\Account::TYPES[$lastType]) }}</td></tr>
                @endif
                <tr>
                    <td class="code">{{ $r['account']->code }}</td>
                    <td><a href="{{ route('accounts.reports.show', ['report' => 'ledger', 'account' => $r['account']->id, 'to' => $asAt]) }}">{{ $r['account']->name }}</a></td>
                    <td class="n">{{ $r['debit'] ? $cur->money($r['debit'], false) : '' }}</td>
                    <td class="n">{{ $r['credit'] ? $cur->money($r['credit'], false) : '' }}</td>
                </tr>
            @endforeach
            <tr class="tot"><td></td><td>{{ __('Total') }}</td><td class="n">{{ $cur->money($tb['debit'], false) }}</td><td class="n">{{ $cur->money($tb['credit'], false) }}</td></tr>
        </tbody>
    </table>
    <p class="foot">@if (abs($tb['debit'] - $tb['credit']) < 0.01)<span class="ok">✓ {{ __('Debits equal credits') }}</span>@else<span class="warn">{{ __('Out of balance') }}</span>@endif</p>
</div>
