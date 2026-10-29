{{-- Batch 40: an address array as snapshotted on an order. --}}
@php $a = $address ?? []; @endphp
<address class="s-address">
    @if (! empty($a['name']))<strong>{{ $a['name'] }}</strong><br>@endif
    @if (! empty($a['company'])){{ $a['company'] }}<br>@endif
    {{ $a['line1'] ?? '' }}<br>
    @if (! empty($a['line2'])){{ $a['line2'] }}<br>@endif
    {{ $a['city'] ?? '' }}@if (! empty($a['county'])), {{ $a['county'] }}@endif<br>
    {{ $a['postcode'] ?? '' }}
    @if (! empty($a['phone']))<br><span class="s-muted">{{ $a['phone'] }}</span>@endif
</address>
