@extends('accounting.print._layout')
@section('title', __('Statement').' — '.$c->name)
@section('heading')
    <h1>{{ mb_strtoupper(__('Statement')) }}</h1>
    <div class="meta">{{ \Illuminate\Support\Carbon::parse($from)->format('d M Y') }} – {{ \Illuminate\Support\Carbon::parse($to)->format('d M Y') }}</div>
@endsection
@section('content')
    @php $sign = $statement['sign']; @endphp
    <div class="cols"><div><h2>{{ __('Account') }}</h2><strong>{{ $c->name }}</strong>@if ($c->address)<div class="pre">{{ $c->address }}</div>@endif</div></div>
    <table>
        <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Details') }}</th><th class="r">{{ $sign > 0 ? __('Invoiced') : __('Paid') }}</th><th class="r">{{ $sign > 0 ? __('Paid') : __('Billed') }}</th><th class="r">{{ __('Balance') }}</th></tr></thead>
        <tbody>
            <tr><td>{{ \Illuminate\Support\Carbon::parse($from)->format('d M Y') }}</td><td class="muted">{{ __('Balance brought forward') }}</td><td></td><td></td><td class="r">{{ $cur->money($statement['opening'] * $sign) }}</td></tr>
            @foreach ($statement['lines'] as $l)
                <tr><td>{{ $l->date->format('d M Y') }}</td><td>{{ $l->journal->reference ?: $l->journal->number }}<br><span class="muted">{{ $l->journal->typeLabel() }}</span></td>
                    <td class="r">{{ (float) $l->debit ? $cur->money($l->debit) : '' }}</td><td class="r">{{ (float) $l->credit ? $cur->money($l->credit) : '' }}</td><td class="r">{{ $cur->money($l->running * $sign) }}</td></tr>
            @endforeach
        </tbody>
    </table>
    <table class="totals"><tr class="grand"><td>{{ $sign > 0 ? __('Amount due') : __('Balance owed') }}</td><td class="r">{{ $cur->money($statement['closing'] * $sign) }}</td></tr></table>
    @if ($open->isNotEmpty())
        <h2 style="margin-top:26px">{{ __('Open items') }}</h2>
        <table>
            <thead><tr><th>{{ __('Number') }}</th><th>{{ __('Due') }}</th><th class="r">{{ __('Outstanding') }}</th></tr></thead>
            <tbody>@foreach ($open as $d)<tr><td>{{ $d->number }}</td><td class="{{ $d->isOverdue() ? 'overdue' : '' }}">{{ $d->due_date?->format('d M Y') }}</td><td class="r">{{ $cur->money($d->balance()) }}</td></tr>@endforeach</tbody>
        </table>
    @endif
@endsection
