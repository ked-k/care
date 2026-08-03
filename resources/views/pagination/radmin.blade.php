@if ($paginator->hasPages())
    <nav class="flex items-center gap-1" role="navigation">
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-300"><i class="ik ik-chevron-left"></i></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-gray-50"><i class="ik ik-chevron-left"></i></a>
        @endif

        {{-- Page numbers --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="flex h-8 min-w-8 items-center justify-center px-1 text-sm text-gray-400">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" class="flex h-8 min-w-8 items-center justify-center rounded-lg bg-primary-500 px-2 text-sm font-medium text-white shadow-sm">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="flex h-8 min-w-8 items-center justify-center rounded-lg border border-gray-200 px-2 text-sm text-gray-600 transition hover:bg-gray-50">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-gray-50"><i class="ik ik-chevron-right"></i></a>
        @else
            <span class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-300"><i class="ik ik-chevron-right"></i></span>
        @endif
    </nav>
@endif
