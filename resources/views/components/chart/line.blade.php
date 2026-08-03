@props([
    'data' => [],
    'labels' => [],
    'color' => 'primary',
    'height' => 'h-48',
    'area' => true,
    'prefix' => '',
    'suffix' => '',
    'smooth' => true,
])

@php
    $palette = [
        'primary' => '#5b9bf8', 'accent' => '#f0838b', 'green' => '#5ccf95', 'amber' => '#f6bf57',
        'purple' => '#9561e2', 'cyan' => '#4dc0b5', 'pink' => '#f66d9b', 'indigo' => '#6574cd', 'gray' => '#94a3b8',
    ];
    $hex = $palette[$color] ?? $palette['primary'];

    $data = array_values($data);
    $n = count($data);
    $max = $n ? max($data) : 1;
    $min = $n ? min($data) : 0;
    $range = ($max - $min) ?: 1;
    $W = 100; $H = 40; $pad = 3;

    $xy = [];
    foreach ($data as $i => $v) {
        $x = $n > 1 ? round(($i / ($n - 1)) * $W, 2) : $W / 2;
        $y = round($H - (($v - $min) / $range) * ($H - 2 * $pad) - $pad, 2);
        $xy[] = [$x, $y];
    }

    // Build a smooth (catmull-rom -> bezier) or straight path.
    $linePath = '';
    if ($n) {
        $linePath = 'M ' . $xy[0][0] . ' ' . $xy[0][1];
        if ($smooth && $n > 2) {
            for ($i = 0; $i < $n - 1; $i++) {
                $p0 = $xy[max($i - 1, 0)];
                $p1 = $xy[$i];
                $p2 = $xy[$i + 1];
                $p3 = $xy[min($i + 2, $n - 1)];
                $c1x = round($p1[0] + ($p2[0] - $p0[0]) / 6, 2);
                $c1y = round($p1[1] + ($p2[1] - $p0[1]) / 6, 2);
                $c2x = round($p2[0] - ($p3[0] - $p1[0]) / 6, 2);
                $c2y = round($p2[1] - ($p3[1] - $p1[1]) / 6, 2);
                $linePath .= " C $c1x $c1y $c2x $c2y {$p2[0]} {$p2[1]}";
            }
        } else {
            for ($i = 1; $i < $n; $i++) {
                $linePath .= " L {$xy[$i][0]} {$xy[$i][1]}";
            }
        }
    }
    $areaPath = $n ? $linePath . " L {$xy[$n-1][0]} $H L {$xy[0][0]} $H Z" : '';

    // Points for Alpine (percentages), with labels/values.
    $jsPoints = [];
    foreach ($xy as $i => $p) {
        $jsPoints[] = [
            'x' => $p[0],
            'y' => round(($p[1] / $H) * 100, 2),
            'value' => $data[$i],
            'label' => $labels[$i] ?? '',
        ];
    }
    $uid = 'ln' . substr(md5($hex . $n . implode(',', $data)), 0, 8);
@endphp

<div class="relative w-full {{ $height }}" x-data="{ hover: null, points: @js($jsPoints), pfx: @js($prefix), sfx: @js($suffix) }">
    <svg viewBox="0 0 100 40" preserveAspectRatio="none" class="h-full w-full overflow-visible">
        <defs>
            <linearGradient id="{{ $uid }}" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="{{ $hex }}" stop-opacity="0.28" />
                <stop offset="100%" stop-color="{{ $hex }}" stop-opacity="0" />
            </linearGradient>
        </defs>
        @if ($area && $areaPath)
            <path d="{{ $areaPath }}" fill="url(#{{ $uid }})" />
        @endif
        @if ($linePath)
            <path d="{{ $linePath }}" fill="none" stroke="{{ $hex }}" stroke-width="2"
                  stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />
        @endif
    </svg>

    {{-- vertical guide --}}
    <template x-if="hover !== null">
        <div class="pointer-events-none absolute top-0 bottom-0 w-px bg-gray-200" :style="`left:${points[hover].x}%`"></div>
    </template>

    {{-- active dot --}}
    <template x-if="hover !== null">
        <div class="pointer-events-none absolute h-3 w-3 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white shadow"
             :style="`left:${points[hover].x}%; top:${points[hover].y}%; background-color:{{ $hex }}`"></div>
    </template>

    {{-- tooltip --}}
    <template x-if="hover !== null">
        <div class="pointer-events-none absolute z-10 -translate-x-1/2 -translate-y-full whitespace-nowrap rounded-lg bg-slate-900 px-2.5 py-1.5 text-xs text-white shadow-lg"
             :style="`left:${Math.min(Math.max(points[hover].x,8),92)}%; top:calc(${points[hover].y}% - 10px)`">
            <span class="block text-slate-300" x-show="points[hover].label" x-text="points[hover].label"></span>
            <span class="font-semibold" x-text="pfx + points[hover].value + sfx"></span>
        </div>
    </template>

    {{-- hover zones --}}
    <div class="absolute inset-0 flex">
        <template x-for="(p, i) in points" :key="i">
            <div class="h-full flex-1" @mouseenter="hover = i" @mouseleave="hover = null"></div>
        </template>
    </div>

    @if (! empty($labels))
        <div class="mt-1 flex justify-between text-[10px] text-gray-400">
            @foreach ($labels as $l)<span>{{ $l }}</span>@endforeach
        </div>
    @endif
</div>
