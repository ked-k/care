@extends('accounting.print._layout')
@section('title', $p->kindLabel().' '.$p->number)
@section('heading')
    <h1>{{ $p->kind === 'receipt' ? mb_strtoupper(__('Receipt')) : mb_strtoupper(__('Remittance advice')) }}</h1>
    <div class="meta"><strong>{{ $p->number }}</strong><br>{{ __('Date') }}: {{ $p->date->format('d M Y') }}<br>@if ($p->isVoid())<span class="stamp void">{{ __('Void') }}</span>@endif</div>
@endsection
@section('content')
    <div class="cols">
        <div><h2>{{ $p->kind === 'receipt' ? __('Received from') : __('Paid to') }}</h2><strong>{{ $p->contact?->name }}</strong>@if ($p->contact?->address)<div class="pre">{{ $p->contact->address }}</div>@endif</div>
        <div><h2>{{ __('Payment') }}</h2>{{ $p->methodLabel() }} · {{ $p->bankAccount?->name }}@if ($p->reference)<br>{{ __('Ref') }}: {{ $p->reference }}@endif</div>
    </div>
    <table>
        <thead><tr><th>{{ $p->kind === 'receipt' ? __('Invoice') : __('Bill') }}</th><th>{{ __('Date') }}</th><th class="r">{{ __('Total') }}</th><th class="r">{{ __('Paid now') }}</th></tr></thead>
        <tbody>
            @forelse ($p->allocations as $a)
                <tr><td>{{ $a->document->number }}@if ($a->document->reference)<span class="muted"> · {{ $a->document->reference }}</span>@endif</td><td>{{ $a->document->issue_date->format('d M Y') }}</td><td class="r">{{ $cur->money($a->document->total) }}</td><td class="r">{{ $cur->money($a->amount) }}</td></tr>
            @empty
                <tr><td colspan="4" class="muted">{{ __('Payment on account') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    <table class="totals">
        @if ($p->unallocated() > 0)<tr><td>{{ __('On account') }}</td><td class="r">{{ $cur->money($p->unallocated()) }}</td></tr>@endif
        <tr class="grand"><td>{{ __('Amount') }}</td><td class="r">{{ $cur->money($p->amount) }}</td></tr>
    </table>
    @if ($p->kind === 'receipt')<p class="note">{{ __('Thank you for your payment.') }}</p>@endif
@endsection
