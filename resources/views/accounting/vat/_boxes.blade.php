{{-- Batch 44c: the nine VAT return boxes. Needs $calc (VatReturns::compute) and $cur. --}}
@php $b = $calc['boxes']; $whole = ['box6', 'box7', 'box8', 'box9']; @endphp
<table class="vat-boxes" style="width:100%;border-collapse:collapse;font-size:14px">
    <tbody>
        @foreach (\App\Services\Accounting\VatReturns::BOXES as $k => $label)
            @php $strong = in_array($k, ['box3', 'box5']); @endphp
            <tr style="border-bottom:1px solid #e5e7eb;{{ $k === 'box5' ? 'background:#f9fafb' : '' }}">
                <td style="padding:9px 8px;width:64px;color:#6b7280;font-weight:600;white-space:nowrap">{{ __('Box :n', ['n' => substr($k, 3)]) }}</td>
                <td style="padding:9px 8px;{{ $strong ? 'font-weight:600' : '' }}">{{ __($label) }}</td>
                <td style="padding:9px 8px;text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums;{{ $strong ? 'font-weight:700' : '' }}">
                    @if ($k === 'box5')
                        {{ $cur->money(abs($b[$k])) }} <span style="font-size:12px;font-weight:500;color:{{ $b[$k] < 0 ? '#15803d' : '#6b7280' }}">{{ $b[$k] < -0.004 ? __('to reclaim') : (abs($b[$k]) < 0.005 ? '' : __('to pay')) }}</span>
                    @elseif (in_array($k, $whole))
                        {{ ($b[$k] < 0 ? '−' : '').($cur->currency_symbol ?: $cur->currency_code).number_format(abs($b[$k]), 0) }}
                    @else
                        {{ $cur->money($b[$k]) }}
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
