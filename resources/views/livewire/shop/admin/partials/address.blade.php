@php $a = $address ?? []; @endphp
<address class="text-sm not-italic leading-relaxed text-gray-600">
    @if (! empty($a['name']))<span class="font-medium text-gray-700">{{ $a['name'] }}</span><br>@endif
    @if (! empty($a['company'])){{ $a['company'] }}<br>@endif
    {{ $a['line1'] ?? '' }}<br>
    @if (! empty($a['line2'])){{ $a['line2'] }}<br>@endif
    {{ $a['city'] ?? '' }}@if (! empty($a['county'])), {{ $a['county'] }}@endif<br>
    <span class="font-medium">{{ $a['postcode'] ?? '' }}</span>
    @if (! empty($a['phone']))<br>{{ $a['phone'] }}@endif
</address>
