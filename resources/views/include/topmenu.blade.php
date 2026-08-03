@php
    $top = config('menu.top', []);

    $href = function ($i) {
        return ! empty($i['route']) ? route($i['route']) : url($i['url'] ?? '#');
    };

    // Resolve active state; if nothing matches, fall back to the first entry.
    $actives = array_map(function ($i) {
        if (empty($i['active'])) return false;
        $patterns = is_array($i['active']) ? $i['active'] : explode('|', $i['active']);
        return request()->is(...$patterns);
    }, $top);
    if (count($top) && ! in_array(true, $actives, true)) {
        $actives[0] = true;
    }
    $currentIndex = array_search(true, $actives, true) ?: 0;
    $current = $top[$currentIndex] ?? null;
@endphp

@if (! empty($top))
    {{-- Unified workspace switcher: one selector for every breakpoint --}}
    <x-dropdown align="left" width="w-64">
        <x-slot:trigger>
            <button class="flex items-center gap-2.5 rounded-lg border border-gray-500/15 bg-gray-500/5 py-1.5 pl-2.5 pr-2 text-sm font-medium text-topbar-text transition hover:bg-gray-500/10"
                    title="{{ __('Switch workspace') }}">
                <span class="flex h-7 w-7 items-center justify-center rounded-md bg-primary-500/10 text-primary-600">
                    <i class="{{ $current['icon'] ?? 'ik ik-grid' }}"></i>
                </span>
                <span class="hidden text-left leading-tight sm:block">
                    <span class="block text-[10px] font-normal uppercase tracking-wide text-topbar-text/40">{{ __('Workspace') }}</span>
                    <span class="block max-w-[140px] truncate">{{ __($current['label'] ?? 'Sections') }}</span>
                </span>
                <i class="ik ik-chevron-down text-xs opacity-60"></i>
            </button>
        </x-slot:trigger>

        <div class="px-4 py-2 text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ __('Switch workspace') }}</div>
        @foreach ($top as $idx => $i)
            <a href="{{ $href($i) }}" @class([
                    'flex items-center gap-3 px-4 py-2.5 text-sm transition',
                    'bg-primary-500/10' => $actives[$idx],
                    'hover:bg-gray-500/10' => ! $actives[$idx],
                ])>
                <span @class([
                        'flex h-9 w-9 shrink-0 items-center justify-center rounded-lg',
                        'bg-primary-500 text-white' => $actives[$idx],
                        'bg-gray-500/10 text-gray-500' => ! $actives[$idx],
                    ])>
                    <i class="{{ $i['icon'] }}"></i>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block font-medium leading-tight {{ $actives[$idx] ? 'text-primary-600' : 'text-gray-700' }}">{{ __($i['label']) }}</span>
                    @if (! empty($i['desc']))
                        <span class="block truncate text-xs text-gray-400">{{ __($i['desc']) }}</span>
                    @endif
                </span>
                @if ($actives[$idx])
                    <i class="ik ik-check text-primary-600"></i>
                @endif
            </a>
        @endforeach
    </x-dropdown>
@endif
