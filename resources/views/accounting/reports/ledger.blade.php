@php $lg = $rep['data']; $a = $lg['account']; $sign = $a->isDebitNature() ? 1 : -1; @endphp
<div class="st">
    @include('accounting.reports._header')
    <table>
        <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Entry') }}</th><th>{{ __('Details') }}</th><th class="n">{{ __('Debit') }}</th><th class="n">{{ __('Credit') }}</th><th class="n">{{ __('Balance') }}</th></tr></thead>
        <tbody>
            <tr><td>{{ \Illuminate\Support\Carbon::parse($rep['params']['from'])->format('d M Y') }}</td><td></td><td class="muted">{{ __('Opening balance') }}</td><td></td><td></td><td class="n">{{ $cur->figure($lg['opening'] * $sign) }}</td></tr>
            @forelse ($lg['lines'] as $l)
                <tr>
                    <td style="white-space:nowrap">{{ $l->date->format('d M Y') }}</td>
                    <td style="white-space:nowrap"><a href="{{ route('accounts.journals.show', $l->journal_id) }}">{{ $l->journal->number }}</a><div class="muted" style="font-size:12px">{{ $l->journal->typeLabel() }}</div></td>
                    <td>{{ $l->description ?: $l->journal->memo }}@if ($l->contact)<div class="muted" style="font-size:12px">{{ $l->contact->name }}</div>@endif</td>
                    <td class="n">{{ (float) $l->debit ? $cur->money($l->debit, false) : '' }}</td>
                    <td class="n">{{ (float) $l->credit ? $cur->money($l->credit, false) : '' }}</td>
                    <td class="n">{{ $cur->figure($l->running * $sign) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted" style="text-align:center;padding:18px">{{ __('No entries in this period.') }}</td></tr>
            @endforelse
            <tr class="tot"><td colspan="3">{{ __('Closing balance') }}</td><td class="n">{{ $cur->money($lg['lines']->sum('debit'), false) }}</td><td class="n">{{ $cur->money($lg['lines']->sum('credit'), false) }}</td><td class="n">{{ $cur->figure($lg['closing'] * $sign) }}</td></tr>
        </tbody>
    </table>
    <p class="foot">{{ $a->isDebitNature() ? __('Balance shown as a debit balance (what the account holds).') : __('Balance shown as a credit balance (what is owed or earned).') }}</p>
</div>
