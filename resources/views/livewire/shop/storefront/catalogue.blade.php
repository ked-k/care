<div class="s-wrap" x-data="{ filters: false }">
    <nav class="s-crumbs" aria-label="{{ __('Breadcrumb') }}">
        <a href="{{ route('shop.home', $store->slug) }}" wire:navigate>{{ __('Home') }}</a>
        <i class="fas fa-chevron-right" aria-hidden="true"></i>
        @if ($current)
            <a href="{{ route('shop.catalogue', $store->slug) }}" wire:navigate>{{ __('Products') }}</a>
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
            <span>{{ $current->name }}</span>
        @else
            <span>{{ __('Products') }}</span>
        @endif
    </nav>

    <div class="s-between">
        <div>
            <h1 class="s-page-title">{{ $title }}</h1>
            <p class="s-page-sub">{{ trans_choice(':count product|:count products', $products->total(), ['count' => number_format($products->total())]) }}</p>
        </div>
    </div>

    <div class="s-catalogue">
        <aside class="s-filters" :class="{ 'is-open': filters }">
            <div class="s-filter-box">
                <h3>{{ __('Search within results') }}</h3>
                <input type="search" class="s-input" wire:model.live.debounce.400ms="search" placeholder="{{ __('Name, size or SKU') }}" aria-label="{{ __('Search within results') }}">
            </div>

            <div class="s-filter-box">
                <h3>{{ __('Category') }}</h3>
                <ul class="s-cat-list">
                    @foreach ($categories as $cat)
                        <li>
                            <button type="button" wire:click="setCategory('{{ $cat->slug }}')" @class(['is-active' => $category === $cat->slug])>
                                <span>{{ $cat->name }}</span><small class="num">{{ number_format($cat->products_count) }}</small>
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="s-filter-box s-stack" style="gap:10px">
                <h3 style="margin:0">{{ __('Show') }}</h3>
                <label class="s-check"><input type="checkbox" wire:model.live="inStockOnly"> {{ __('In stock only') }}</label>
                <label class="s-check"><input type="checkbox" wire:model.live="bulkOnly"> {{ __('Has bulk-buy prices') }}</label>
            </div>
        </aside>

        <div>
            <div class="s-toolbar">
                <div class="s-row">
                    <button type="button" class="s-btn s-btn-ghost s-btn-sm s-filter-toggle" @click="filters = ! filters">
                        <i class="fas fa-sliders-h"></i> {{ __('Filters') }}
                    </button>
                    <div class="s-chips">
                        @if ($search !== '')
                            <button type="button" class="s-chip" wire:click="$set('search', '')">“{{ $search }}” <i class="fas fa-times" aria-label="{{ __('Remove') }}"></i></button>
                        @endif
                        @if ($current)
                            <button type="button" class="s-chip" wire:click="setCategory('{{ $current->slug }}')">{{ $current->name }} <i class="fas fa-times" aria-label="{{ __('Remove') }}"></i></button>
                        @endif
                        @if ($inStockOnly)
                            <button type="button" class="s-chip" wire:click="$set('inStockOnly', false)">{{ __('In stock') }} <i class="fas fa-times" aria-label="{{ __('Remove') }}"></i></button>
                        @endif
                        @if ($bulkOnly)
                            <button type="button" class="s-chip" wire:click="$set('bulkOnly', false)">{{ __('Bulk prices') }} <i class="fas fa-times" aria-label="{{ __('Remove') }}"></i></button>
                        @endif
                    </div>
                </div>
                <label class="s-row s-small">
                    <span class="s-muted">{{ __('Sort by') }}</span>
                    <select class="s-select" wire:model.live="sort">
                        <option value="relevance">{{ __('Most popular') }}</option>
                        <option value="price_asc">{{ __('Price: low to high') }}</option>
                        <option value="price_desc">{{ __('Price: high to low') }}</option>
                        <option value="name">{{ __('Name A–Z') }}</option>
                        <option value="newest">{{ __('Newest') }}</option>
                    </select>
                </label>
            </div>

            <div wire:loading.delay.class="s-muted" wire:target="search,category,sort,inStockOnly,bulkOnly,setCategory,gotoPage,nextPage,previousPage">
                @if ($products->isEmpty())
                    <div class="s-empty">
                        <i class="fas fa-search"></i>
                        <h3>{{ __('No products match') }}</h3>
                        <p>{{ __('Try a shorter search, a different size, or clear the filters.') }}</p>
                        <button type="button" class="s-btn s-btn-ink" wire:click="clearFilters">{{ __('Clear filters') }}</button>
                    </div>
                @else
                    <div class="s-products">
                        @foreach ($products as $product)
                            @include('shop.partials.product-card', ['product' => $product])
                        @endforeach
                    </div>
                    {{ $products->links('shop.partials.pager') }}
                @endif
            </div>
        </div>
    </div>
</div>
