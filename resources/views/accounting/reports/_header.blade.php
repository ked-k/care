<div class="st-head">
    <div class="org">{{ $cur->legal_name ?: auth()->user()?->agency?->name }}</div>
    @if ($cur->registration_number)<div class="sub">{{ __('Registration no.') }} {{ $cur->registration_number }}</div>@endif
    <div class="title">{{ $rep['title'] }}</div>
    <div class="sub">{{ $rep['subtitle'] }}</div>
    <div class="sub">{{ __('Amounts in :c', ['c' => $cur->currency_code]) }}</div>
</div>
