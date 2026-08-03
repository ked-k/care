@props([
    'data' => [],
    'labels' => [],
    'color' => 'primary',
    'height' => 'h-48',
    'prefix' => '',
    'suffix' => '',
])

@php
    $palette = [
        'primary' => '#5b9bf8', 'accent' => '#f0838b', 'green' => '#5ccf95', 'amber' => '#f6bf57',
        'purple' => '#9561e2', 'cyan' => '#4dc0b5', 'pink' => '#f66d9b', 'indigo' => '#6574cd', 'gray' => '#94a3b8',
        'white' => '#ffffff',
    ];
    $hex = $palette[$color] ?? $palette['primary'];
    $data = array_values($data);
    $max = count($data) ? max($data) : 1;
    $max = $max ?: 1;
@endphp

<div class="w-full {{ $height }}">
    <div class="flex h-full items-end gap-1.5 sm:gap-2.5">
        @foreach ($data as $i => $v)
            <div class="group relative flex h-full flex-1 flex-col items-center justify-end">
                <div class="pointer-events-none absolute -top-8 z-10 hidden -translate-y-0 whitespace-nowrap rounded-lg bg-slate-900 px-2.5 py-1.5 text-xs font-semibold text-white shadow-lg group-hover:block">
                    {{ $prefix }}{{ $v }}{{ $suffix }}
                </div>
                <div class="w-full rounded-t-md transition-all duration-300 ease-out group-hover:brightness-110"
                     style="height: {{ round(($v / $max) * 100, 1) }}%; min-height: 4px; background: linear-gradient(to top, {{ $hex }}, {{ $hex }}cc);"></div>
                @if (! empty($labels))
                    <span class="mt-1.5 truncate text-[10px] text-gray-400">{{ $labels[$i] ?? '' }}</span>
                @endif
            </div>
        @endforeach
    </div>
</div>
