@props(['variant' => 'primary', 'type' => 'button', 'href' => null, 'size' => 'md'])

@php
    $variants = [
        'primary'   => 'bg-primary-500 text-white hover:bg-primary-600 focus:ring-primary-200',
        'secondary' => 'bg-gray-100 text-gray-700 hover:bg-gray-200 focus:ring-gray-200',
        'danger'    => 'bg-accent-500 text-white hover:bg-accent-600 focus:ring-accent-400/40',
        'success'   => 'bg-green-500 text-white hover:bg-green-600 focus:ring-green-200',
        'warning'   => 'bg-amber-500 text-white hover:bg-amber-600 focus:ring-amber-200',
        'outline'   => 'border border-gray-200 text-gray-700 hover:bg-gray-50 focus:ring-gray-200',
        'ghost'     => 'text-gray-600 hover:bg-gray-100 focus:ring-gray-200',
    ];
    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-5 py-2.5 text-base',
    ];
    $classes = 'inline-flex items-center justify-center gap-2 rounded-lg font-medium transition focus:outline-none focus:ring-2 disabled:cursor-not-allowed disabled:opacity-60 '
        . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
