{{-- Batch 40: storefront pagination for Livewire paginators. --}}
@if ($paginator->hasPages())
    <nav class="s-pager" aria-label="{{ __('Pages') }}">
        @if ($paginator->onFirstPage())
            <span class="is-disabled" aria-hidden="true"><i class="fas fa-chevron-left"></i></span>
        @else
            <button type="button" wire:click="previousPage" aria-label="{{ __('Previous page') }}"><i class="fas fa-chevron-left"></i></button>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="is-disabled">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="is-current" aria-current="page">{{ $page }}</span>
                    @else
                        <button type="button" wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <button type="button" wire:click="nextPage" aria-label="{{ __('Next page') }}"><i class="fas fa-chevron-right"></i></button>
        @else
            <span class="is-disabled" aria-hidden="true"><i class="fas fa-chevron-right"></i></span>
        @endif
    </nav>
@endif
