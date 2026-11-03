@php
    // Inside a Livewire component using WithPagination, page via wire:click (keeps
    // filters/per-page state and avoids Livewire's relative paginator path, which
    // otherwise resolves to e.g. /shop-admin/shop-admin/products). Elsewhere, use
    // absolute hrefs.
    $lw = \Livewire\Livewire::current();
    $lwPaged = $lw && method_exists($lw, 'gotoPage');
    $pageName = $paginator->getPageName();
    $btn = 'flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-gray-50';
    $num = 'flex h-8 min-w-8 items-center justify-center rounded-lg border border-gray-200 px-2 text-sm text-gray-600 transition hover:bg-gray-50';
@endphp
@if ($paginator->hasPages())
    <nav class="flex items-center gap-1" role="navigation">
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-300"><i class="ik ik-chevron-left"></i></span>
        @elseif ($lwPaged)
            <button type="button" wire:click="previousPage('{{ $pageName }}')" wire:loading.attr="disabled" rel="prev" class="{{ $btn }}"><i class="ik ik-chevron-left"></i></button>
        @else
            <a href="{{ url($paginator->previousPageUrl()) }}" rel="prev" class="{{ $btn }}"><i class="ik ik-chevron-left"></i></a>
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
                    @elseif ($lwPaged)
                        <button type="button" wire:key="paginator-{{ $pageName }}-page-{{ $page }}" wire:click="gotoPage({{ $page }}, '{{ $pageName }}')" class="{{ $num }}">{{ $page }}</button>
                    @else
                        <a href="{{ url($url) }}" class="{{ $num }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if (! $paginator->hasMorePages())
            <span class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-300"><i class="ik ik-chevron-right"></i></span>
        @elseif ($lwPaged)
            <button type="button" wire:click="nextPage('{{ $pageName }}')" wire:loading.attr="disabled" rel="next" class="{{ $btn }}"><i class="ik ik-chevron-right"></i></button>
        @else
            <a href="{{ url($paginator->nextPageUrl()) }}" rel="next" class="{{ $btn }}"><i class="ik ik-chevron-right"></i></a>
        @endif
    </nav>
@endif
