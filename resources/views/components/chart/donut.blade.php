@props([
    'segments' => [],   // [['value'=>40,'color'=>'primary','label'=>'A'], ...]
    'size' => 'h-44 w-44',
    'thickness' => 13,
    'label' => null,
    'sublabel' => null,
    'legend' => true,
])

@php
    $palette = [
        'primary' => '#5b9bf8', 'accent' => '#f0838b', 'green' => '#5ccf95', 'amber' => '#f6bf57',
        'purple' => '#9561e2', 'cyan' => '#4dc0b5', 'pink' => '#f66d9b', 'indigo' => '#6574cd', 'gray' => '#94a3b8',
    ];
    $segs = array_values($segments);
    $total = array_sum(array_map(fn ($s) => $s['value'], $segs)) ?: 1;
    $r = 42;
    $C = 2 * M_PI * $r;
    $cum = 0;
    foreach ($segs as $k => $s) {
        $frac = $s['value'] / $total;
        $segs[$k]['len'] = round($frac * $C, 3);
        $segs[$k]['off'] = round(-$cum * $C, 3);
        $segs[$k]['pct'] = round($frac * 100);
        $segs[$k]['hex'] = $palette[$s['color'] ?? 'primary'] ?? $palette['primary'];
        $cum += $frac;
    }
@endphp

<div class="flex flex-col items-center gap-4 sm:flex-row sm:justify-center sm:gap-6">
    <div class="relative {{ $size }} shrink-0">
        <svg viewBox="0 0 100 100" class="h-full w-full -rotate-90">
            <circle cx="50" cy="50" r="{{ $r }}" fill="none" stroke="#eef2f7" stroke-width="{{ $thickness }}" />
            @foreach ($segs as $s)
                <circle cx="50" cy="50" r="{{ $r }}" fill="none" stroke="{{ $s['hex'] }}" stroke-width="{{ $thickness }}"
                        stroke-dasharray="{{ $s['len'] }} {{ $C }}" stroke-dashoffset="{{ $s['off'] }}"
                        stroke-linecap="round" class="transition-all duration-500" />
            @endforeach
        </svg>
        @if ($label || $sublabel)
            <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                @if ($label)<span class="text-2xl font-bold text-gray-800">{{ $label }}</span>@endif
                @if ($sublabel)<span class="text-xs text-gray-400">{{ $sublabel }}</span>@endif
            </div>
        @endif
    </div>

    @if ($legend)
        <div class="space-y-2">
            @foreach ($segs as $s)
                <div class="flex items-center gap-2 text-sm">
                    <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $s['hex'] }}"></span>
                    <span class="text-gray-600">{{ $s['label'] ?? '—' }}</span>
                    <span class="ml-auto font-semibold text-gray-700">{{ $s['pct'] }}%</span>
                </div>
            @endforeach
        </div>
    @endif
</div>
