@extends('accounting.print._layout')
@section('title', $doc->kindLabel().' '.$doc->number)
@section('heading')
    <h1>{{ mb_strtoupper(match ($doc->kind) { 'invoice' => __('Invoice'), 'bill' => __('Supplier bill'), 'money_in' => __('Receipt'), default => __('Payment voucher') }) }}</h1>
    <div class="meta">
        <strong>{{ $doc->number }}</strong><br>
        {{ __('Date') }}: {{ $doc->issue_date->format('d M Y') }}<br>
        @if ($doc->due_date){{ __('Due') }}: {{ $doc->due_date->format('d M Y') }}<br>@endif
        @if ($doc->reference){{ __('Ref') }}: {{ $doc->reference }}<br>@endif
        @if ($doc->status === 'paid')<span class="stamp paid">{{ __('Paid') }}</span>@elseif ($doc->status === 'void')<span class="stamp void">{{ __('Void') }}</span>@elseif ($doc->isOverdue())<span class="stamp overdue">{{ __('Overdue') }}</span>@endif
    </div>
@endsection
@section('content')
    <div class="cols">
        <div>
            <h2>{{ match ($doc->kind) { 'invoice' => __('Bill to'), 'bill' => __('From'), 'money_in' => __('Received from'), default => __('Paid to') } }}</h2>
            @if ($doc->contact)
                <strong>{{ $doc->contact->name }}</strong>
                @if ($doc->contact->address)<div class="pre">{{ $doc->contact->address }}</div>@endif
                @if ($doc->contact->email)<div class="muted">{{ $doc->contact->email }}</div>@endif
            @else — @endif
        </div>
        @if ($doc->bankAccount)
            <div><h2>{{ $doc->kind === 'money_out' ? __('Paid from') : __('Paid into') }}</h2>{{ $doc->bankAccount->name }}</div>
        @endif
    </div>
    <table>
        <thead><tr><th>{{ __('Description') }}</th><th class="r">{{ __('Qty') }}</th><th class="r">{{ __('Price') }}</th><th class="r">{{ __('VAT') }}</th><th class="r">{{ __('Amount') }}</th></tr></thead>
        <tbody>
            @foreach ($doc->lines as $l)
                <tr>
                    <td class="pre">{{ $l->description }}</td>
                    <td class="r">{{ rtrim(rtrim(number_format((float) $l->qty, 3), '0'), '.') }}</td>
                    <td class="r">{{ $cur->money($l->unit_price) }}@if ((float) $l->discount > 0)<br><span class="muted">−{{ $cur->money($l->discount) }}</span>@endif</td>
                    <td class="r">{{ (float) $l->vat_rate ? rtrim(rtrim(number_format((float) $l->vat_rate, 2), '0'), '.').'%' : '—' }}</td>
                    <td class="r">{{ $cur->money($doc->prices_include_vat ? $l->total : $l->net) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <table class="totals">
        <tr><td>{{ __('Subtotal') }}</td><td class="r">{{ $cur->money($doc->subtotal) }}</td></tr>
        <tr><td>{{ __('VAT') }}</td><td class="r">{{ $cur->money($doc->vat_total) }}</td></tr>
        <tr class="grand"><td>{{ __('Total') }}</td><td class="r">{{ $cur->money($doc->total) }}</td></tr>
        @if ($doc->isOnAccount() && (float) $doc->amount_paid > 0)
            <tr><td>{{ __('Paid') }}</td><td class="r">−{{ $cur->money($doc->amount_paid) }}</td></tr>
            <tr class="grand"><td>{{ __('Balance due') }}</td><td class="r">{{ $cur->money($doc->balance()) }}</td></tr>
        @endif
    </table>
    @if ($doc->notes)<div class="note">{{ $doc->notes }}</div>@endif
@endsection
