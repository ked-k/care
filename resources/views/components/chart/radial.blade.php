@props([
    'value' => 0,        // 0..100
    'color' => 'primary',
    'size' => 'h-32 w-32',
    'thickness' => 10,
    'label' => null,     // defaults to "{value}%"
    'sublabel' => null,
])

@php
    $palette = [
        'primary' => '#5b9bf8', 'accent' => '#f0838b', 'green' => '#5ccf95', 'amber' => '#f6bf57',
        'purple' => '#9561e2', 'cyan' => '#4dc0b5', 'pink' => '#f66d9b', 'indigo' => '#6574cd', 'gray' => '#94a3b8',
    ];
    $hex = $palette[$color] ?? $palette['primary'];
    $r = 42;
    $C = 2 * M_PI * $r;
    $pct = max(0, min(100, (float) $value));
    $len = round($C * $pct / 100, 3);
    $uid = 'rad' . substr(md5($hex . $value), 0, 8);
@endphp

<div class="relative {{ $size }}">
    <svg viewBox="0 0 100 100" class="h-full w-full -rotate-90">
        <defs>
            <linearGradient id="{{ $uid }}" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="{{ $hex }}" stop-opacity="0.65" />
                <stop offset="100%" stop-color="{{ $hex }}" />
            </linearGradient>
        </defs>
        <circle cx="50" cy="50" r="{{ $r }}" fill="none" stroke="#eef2f7" stroke-width="{{ $thickness }}" />
        <circle cx="50" cy="50" r="{{ $r }}" fill="none" stroke="url(#{{ $uid }})" stroke-width="{{ $thickness }}"
                stroke-dasharray="{{ $len }} {{ $C }}" stroke-linecap="round" class="transition-all duration-700" />
    </svg>
    <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
        <span class="text-xl font-bold text-gray-800">{{ $label ?? (round($pct) . '%') }}</span>
        @if ($sublabel)<span class="text-[11px] text-gray-400">{{ $sublabel }}</span>@endif
    </div>
</div>
