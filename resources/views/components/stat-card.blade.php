@props([
    'value' => '',
    'label' => '',
    'icon' => null,
    'color' => 'primary',     // primary | accent | green | amber | purple | cyan
    'trend' => null,          // e.g. '+12.5%'
    'trendUp' => true,
    'spark' => [],            // optional sparkline data
    'variant' => 'gradient',  // gradient | soft
])

@php
    // Softer, lighter gradients (toned down from the original 500→600).
    $gradients = [
        'primary' => 'from-primary-400 to-primary-500',
        'accent'  => 'from-accent-400 to-accent-500',
        'green'   => 'from-emerald-400 to-teal-500',
        'amber'   => 'from-amber-300 to-amber-400',
        'purple'  => 'from-purple-400 to-purple-500',
        'cyan'    => 'from-cyan-400 to-cyan-500',
    ];
    // In dark mode, drop the bright gradient for a subtle colour-tinted dark card.
    $darkGrad = [
        'primary' => 'dark:bg-none dark:bg-primary-500/10 dark:ring-1 dark:ring-primary-500/20',
        'accent'  => 'dark:bg-none dark:bg-accent-500/10 dark:ring-1 dark:ring-accent-500/20',
        'green'   => 'dark:bg-none dark:bg-green-500/10 dark:ring-1 dark:ring-green-500/20',
        'amber'   => 'dark:bg-none dark:bg-amber-500/10 dark:ring-1 dark:ring-amber-500/20',
        'purple'  => 'dark:bg-none dark:bg-purple-500/10 dark:ring-1 dark:ring-purple-500/20',
        'cyan'    => 'dark:bg-none dark:bg-cyan-500/10 dark:ring-1 dark:ring-cyan-500/20',
    ];
    $softs = [
        'primary' => 'bg-primary-500/10 text-primary-600',
        'accent'  => 'bg-accent-500/10 text-accent-600',
        'green'   => 'bg-green-500/10 text-green-600',
        'amber'   => 'bg-amber-500/10 text-amber-600',
        'purple'  => 'bg-purple-500/10 text-purple-600',
        'cyan'    => 'bg-cyan-500/10 text-cyan-600',
    ];
    $isGradient = $variant === 'gradient';
@endphp

@if ($isGradient)
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br {{ $gradients[$color] ?? $gradients['primary'] }} {{ $darkGrad[$color] ?? $darkGrad['primary'] }} p-5 text-white dark:text-gray-800 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-lg">
        <div class="flex items-start justify-between">
            <div>
                <h4 class="text-2xl font-bold tracking-tight">{{ $value }}</h4>
                <p class="text-sm text-white/80">{{ $label }}</p>
            </div>
            @if ($icon)
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15"><i class="{{ $icon }} text-xl"></i></span>
            @endif
        </div>
        <div class="mt-4 flex items-end justify-between gap-3">
            @if ($trend)
                <span class="inline-flex items-center gap-1 rounded-full bg-white/15 px-2 py-0.5 text-xs font-medium">
                    <i class="ik {{ $trendUp ? 'ik-trending-up' : 'ik-trending-down' }}"></i>{{ $trend }}
                </span>
            @endif
            @if (! empty($spark))
                <div class="ml-auto w-24"><x-chart.sparkline :data="$spark" color="white" class="h-8 w-full" /></div>
            @endif
        </div>
    </div>
@else
    <x-card hover>
        <div class="flex items-start justify-between">
            <div>
                <h4 class="text-2xl font-bold text-gray-800">{{ $value }}</h4>
                <p class="text-sm text-gray-400">{{ $label }}</p>
            </div>
            @if ($icon)
                <span class="flex h-11 w-11 items-center justify-center rounded-xl {{ $softs[$color] ?? $softs['primary'] }}"><i class="{{ $icon }} text-xl"></i></span>
            @endif
        </div>
        @if ($trend || ! empty($spark))
            <div class="mt-4 flex items-end justify-between gap-3">
                @if ($trend)
                    <span class="inline-flex items-center gap-1 text-xs font-medium {{ $trendUp ? 'text-green-600' : 'text-accent-600' }}">
                        <i class="ik {{ $trendUp ? 'ik-trending-up' : 'ik-trending-down' }}"></i>{{ $trend }}
                    </span>
                @endif
                @if (! empty($spark))
                    <div class="ml-auto w-24"><x-chart.sparkline :data="$spark" :color="$color" class="h-8 w-full" /></div>
                @endif
            </div>
        @endif
    </x-card>
@endif
