{{-- Batch 44c: the journals behind a VAT return. Needs $rows, $cur. --}}
<table style="width:100%;border-collapse:collapse;font-size:13px">
    <thead><tr style="border-bottom:1px solid #d1d5db;color:#6b7280;font-size:12px;text-align:left">
        <th style="padding:7px 8px">{{ __('Date') }}</th><th style="padding:7px 8px">{{ __('Journal') }}</th><th style="padding:7px 8px">{{ __('Details') }}</th>
        <th style="padding:7px 8px;text-align:right">{{ __('Net (6/7)') }}</th><th style="padding:7px 8px;text-align:right">{{ __('VAT (1/4)') }}</th></tr></thead>
    <tbody>
        @forelse ($rows as $r)
            @php $j = $r['journal']; $vatAmt = $r['side'] === 'purchases' || $r['box4'] ? -$r['box4'] + $r['box1'] : $r['box1']; $net = $r['box6'] ?: -$r['box7']; @endphp
            <tr style="border-bottom:1px solid #f3f4f6">
                <td style="padding:6px 8px;white-space:nowrap;color:#6b7280">{{ $j->date->format('d M Y') }}@if (! empty($r['late']))<span title="{{ __('Dated in an earlier period that has already been filed — included here instead') }}" style="margin-left:4px;color:#b45309;font-weight:600">{{ __('late') }}</span>@endif</td>
                <td style="padding:6px 8px;white-space:nowrap">
                    @if (! empty($links))<a href="{{ $j->sourceUrl() ?? route('accounts.journals.show', $j->id) }}" wire:navigate style="color:#2563eb">{{ $j->number }}</a>@else{{ $j->number }}@endif
                    <span style="display:block;font-size:11px;color:#9ca3af">{{ \App\Models\Accounting\Journal::TYPES[$r['type']] ?? $r['type'] }}{{ $j->type === 'reversal' ? ' · '.__('reversal') : '' }}</span></td>
                <td style="padding:6px 8px">{{ \Illuminate\Support\Str::limit($j->memo ?: $j->reference, 70) }}</td>
                <td style="padding:6px 8px;text-align:right;white-space:nowrap">{{ $r['side'] === 'adjustments' ? '–' : $cur->figure($r['box6'] ?: $r['box7']) }}</td>
                <td style="padding:6px 8px;text-align:right;white-space:nowrap">{{ $cur->figure($r['box1'] ?: $r['box4']) }}</td>
            </tr>
        @empty
            <tr><td colspan="5" style="padding:18px 8px;text-align:center;color:#9ca3af">{{ __('Nothing with VAT in this period.') }}</td></tr>
        @endforelse
    </tbody>
</table>
