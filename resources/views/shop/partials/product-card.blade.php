{{-- Batch 40: one product in a grid. Needs $product (tiers/images/category loaded) and $store. --}}
@php
    $state = $product->stockState();
    $from = $product->hasQuantityBreaks() ? $product->lowestPrice() : null;
    $url = route('shop.product', [$store->slug, $product->slug]);
    $saving = (! $product->has_variants && $product->compare_at_price && $product->compare_at_price > $product->price)
        ? (int) round((1 - $product->price / $product->compare_at_price) * 100) : 0;
    $variantHint = $product->variantSummary();
@endphp
<article class="s-card" wire:key="card-{{ $product->id }}">
    {{-- Corner badges (Medion-style NEW / HOT ribbons, mapped to real product state) --}}
    <div class="s-badges">
        @if ($saving > 0)<span class="s-badge s-badge-sale">{{ __('Save :n%', ['n' => $saving]) }}</span>@endif
        @if ($from)<span class="s-badge s-badge-deal">{{ __('Bulk deal') }}</span>@endif
        @if ($state === 'low_stock')<span class="s-badge s-badge-warn">{{ __('Low stock') }}</span>@endif
        @if ($state === 'out_of_stock')<span class="s-badge s-badge-out">{{ __('Sold out') }}</span>@endif
    </div>
    <a href="{{ $url }}" wire:navigate tabindex="-1" aria-hidden="true">
        @include('shop.partials.product-visual', ['product' => $product])
    </a>
    <div class="s-card-body">
        @if ($product->category)<span class="s-card-cat">{{ $product->category->name }}</span>@endif
        <a href="{{ $url }}" wire:navigate class="s-card-name">{{ $product->name }}</a>
        @if ($variantHint)<span class="s-card-variants"><i class="fas fa-th-list"></i>{{ $variantHint }}</span>@endif
        <div class="s-card-price">
            @if ($product->priceVaries())<span class="s-card-from-label">{{ __('From') }}</span>@endif
            <strong class="num">{{ $store->money($product->displayPrice()) }}</strong>
            @if (! $product->has_variants && $product->compare_at_price && $product->compare_at_price > $product->price)
                <s class="num">{{ $store->money($product->compare_at_price) }}</s>
            @endif
        </div>
        @if ($from)
            <span class="s-card-from">{{ __('From :price each in bulk', ['price' => $store->money($from)]) }}</span>
        @endif
        <div class="s-card-foot">
            <span class="s-stock s-stock-{{ $state }}">{{ match ($state) {
                'low_stock' => __('Low stock'),
                'backorder' => __('On back order'),
                'out_of_stock' => __('Out of stock'),
                default => __('In stock'),
            } }}</span>
            @if ($product->has_variants && $product->isPurchasable())
                <a href="{{ $url }}#choose" wire:navigate class="s-btn s-btn-primary s-card-add" aria-label="{{ __('Choose options for :name', ['name' => $product->name]) }}" title="{{ __('Choose options') }}">
                    <i class="fas fa-sliders-h"></i>
                </a>
            @elseif ($product->isPurchasable())
                <button type="button" class="s-btn s-btn-primary s-card-add"
                        wire:click="addToCart('{{ $product->id }}', {{ max(1, (int) $product->min_order_qty) }})"
                        wire:loading.attr="disabled" wire:target="addToCart('{{ $product->id }}', {{ max(1, (int) $product->min_order_qty) }})"
                        aria-label="{{ __('Add :name to basket', ['name' => $product->name]) }}" title="{{ __('Add to basket') }}">
                    <i class="fas fa-plus"></i>
                </button>
            @endif
        </div>
    </div>
</article>
