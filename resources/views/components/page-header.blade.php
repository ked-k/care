@props(['title' => '', 'subtitle' => null, 'icon' => null, 'breadcrumbs' => []])

<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex items-center gap-3">
        @if ($icon)
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-primary-500 to-primary-600 text-white">
                <i class="{{ $icon }} text-lg"></i>
            </span>
        @endif
        <div>
            <h4 class="text-xl font-semibold text-gray-800">{{ $title }}</h4>
            @if ($subtitle)<p class="text-sm text-gray-400">{{ $subtitle }}</p>@endif
        </div>
    </div>

    @if (! empty($breadcrumbs))
        <nav class="flex items-center text-sm text-gray-400">
            @foreach ($breadcrumbs as $label => $url)
                @if (! $loop->last && $url)
                    <a href="{{ $url }}" class="hover:text-primary-600">{{ $label }}</a>
                    <i class="ik ik-chevron-right mx-1 text-xs"></i>
                @else
                    <span class="text-gray-600">{{ is_string($label) ? $label : $url }}</span>
                @endif
            @endforeach
        </nav>
    @endif

    {{ $slot ?? '' }}
</div>
