@props([
    'data' => [],
    'color' => 'primary',
    'area' => true,
    'class' => 'h-10 w-24',
])

@php
    $palette = [
        'primary' => '#5b9bf8', 'accent' => '#f0838b', 'green' => '#5ccf95', 'amber' => '#f6bf57',
        'purple' => '#9561e2', 'cyan' => '#4dc0b5', 'pink' => '#f66d9b', 'indigo' => '#6574cd',
        'white' => '#ffffff', 'gray' => '#94a3b8',
    ];
    $hex = $palette[$color] ?? $palette['primary'];
    $data = array_values($data);
    $n = count($data);
    $max = $n ? max($data) : 1; $min = $n ? min($data) : 0;
    $range = ($max - $min) ?: 1;
    $W = 100; $H = 32; $pad = 3;
    $pts = [];
    foreach ($data as $i => $v) {
        $x = $n > 1 ? round(($i / ($n - 1)) * $W, 2) : $W / 2;
        $y = round($H - (($v - $min) / $range) * ($H - 2 * $pad) - $pad, 2);
        $pts[] = "$x,$y";
    }
    $line = implode(' ', $pts);
    $poly = $n ? "0,$H $line $W,$H" : '';
    $uid = 'sp' . substr(md5($hex . implode(',', $data)), 0, 8);
@endphp

<svg viewBox="0 0 100 32" preserveAspectRatio="none" class="{{ $class }}">
    <defs>
        <linearGradient id="{{ $uid }}" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="{{ $hex }}" stop-opacity="0.35" />
            <stop offset="100%" stop-color="{{ $hex }}" stop-opacity="0" />
        </linearGradient>
    </defs>
    @if ($area && $poly)<polygon points="{{ $poly }}" fill="url(#{{ $uid }})" />@endif
    @if ($n)<polyline points="{{ $line }}" fill="none" stroke="{{ $hex }}" stroke-width="2"
            stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />@endif
</svg>
