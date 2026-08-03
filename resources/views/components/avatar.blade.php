@props(['name' => '', 'src' => null, 'size' => 'h-9 w-9', 'status' => null])

@php
    $initials = collect(explode(' ', trim($name)))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
    $statusColor = ['online' => 'bg-green-500', 'busy' => 'bg-amber-500', 'offline' => 'bg-gray-300'][$status] ?? null;
@endphp

<span class="relative inline-flex shrink-0">
    @if ($src)
        <img src="{{ $src }}" alt="{{ $name }}" {{ $attributes->class(['rounded-full object-cover', $size]) }}>
    @else
        <span {{ $attributes->class(['inline-flex items-center justify-center rounded-full bg-primary-100 text-xs font-semibold uppercase text-primary-700', $size]) }}>{{ $initials ?: '?' }}</span>
    @endif
    @if ($statusColor)
        <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full border-2 border-white {{ $statusColor }}"></span>
    @endif
</span>
