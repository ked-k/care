@props(['color' => 'gray'])

@php
    $colors = [
        'gray'    => 'bg-gray-100 text-gray-700',
        'dark'    => 'bg-slate-700 text-white',
        'primary' => 'bg-primary-500/15 text-primary-600',
        'success' => 'bg-green-500/15 text-green-600',
        'danger'  => 'bg-accent-500/15 text-accent-600',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded px-2 py-0.5 text-xs font-medium ' . ($colors[$color] ?? $colors['gray'])]) }}>{{ $slot }}</span>
