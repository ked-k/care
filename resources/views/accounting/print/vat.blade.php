@extends('accounting.print._layout')
@section('title', __('VAT return :a – :b', ['a' => $calc['start'], 'b' => $calc['end']]))
@section('heading')
    <h1>{{ mb_strtoupper(__('VAT return')) }}</h1>
    <div class="meta">
        {{ \Illuminate\Support\Carbon::parse($calc['start'])->format('j M Y') }} – {{ \Illuminate\Support\Carbon::parse($calc['end'])->format('j M Y') }}<br>
        @if ($cur->tax_number){{ __('VAT no.') }} {{ $cur->tax_number }}<br>@endif
        @if ($ret){{ __('Filed') }}: {{ $ret->filed_at?->format('d M Y') }}{{ $ret->reference ? ' · '.$ret->reference : '' }}<br>
        @else<span class="stamp overdue">{{ __('Draft') }}</span>@endif
    </div>
@endsection
@section('content')
    @include('accounting.vat._boxes')
    <h2 style="margin-top:28px">{{ __('Entries included') }}</h2>
    @include('accounting.vat._rows', ['rows' => $calc['rows'], 'links' => false])
@endsection
