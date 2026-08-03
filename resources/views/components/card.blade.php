@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'noPadding' => false,
    'hover' => false,
])

<div {{ $attributes->class([
        'rounded-2xl border border-gray-100 bg-white shadow-sm shadow-gray-200/60 transition duration-200',
        'hover:-translate-y-0.5 hover:shadow-md hover:shadow-gray-200' => $hover,
    ]) }}>
    @if ($title || isset($header) || isset($actions) || $subtitle)
        <div class="flex items-center justify-between gap-4 border-b border-gray-100 px-5 py-4">
            <div class="flex items-center gap-3">
                @if ($icon)
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-500/10 text-primary-600">
                        <i class="{{ $icon }}"></i>
                    </span>
                @endif
                <div>
                    <h5 class="font-semibold text-gray-700">{{ $header ?? $title }}</h5>
                    @if ($subtitle)<p class="text-xs text-gray-400">{{ $subtitle }}</p>@endif
                </div>
            </div>
            @isset($actions)
                <div class="flex items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif
    <div class="{{ $noPadding ? '' : 'p-5' }}">{{ $slot }}</div>
</div>
