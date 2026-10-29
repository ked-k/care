{{--
    Batch 40: a product's picture, or — when it has none (true for the whole
    imported catalogue) — a printed carton-label tile built from the product's
    own data: category icon, short name, pack quantity, SKU.

    @include('shop.partials.product-visual', ['product' => $product, 'large' => false])
--}}
@php
    $img = $product->primaryImageUrl();
    preg_match('/(\d[\d,]*)\s*([A-Za-z][A-Za-z ]{0,18})$/', (string) ($product->pack_size ?: \Illuminate\Support\Str::afterLast($product->name, ' - ')), $packMatch);
@endphp
<div class="s-visual {{ ($large ?? false) ? 's-visual-lg' : '' }}">
    @if ($img)
        <img src="{{ $img }}" alt="{{ $product->name }}" loading="lazy">
    @else
        <div class="s-label-tile" role="img" aria-label="{{ $product->name }}">
            <div class="s-label-card">
                <div class="s-label-top">
                    <span>{{ $product->category?->name ?? __('Supplies') }}</span>
                    <i class="{{ $product->category?->icon ?: \App\Models\Shop\ShopCategory::guessIcon($product->category?->name ?? '') }}" aria-hidden="true"></i>
                </div>
                <div class="s-label-name">{{ $product->labelTitle() }}</div>
                @if (! empty($packMatch))
                    <div class="s-label-pack"><strong class="num">{{ $packMatch[1] }}</strong><span>{{ trim($packMatch[2]) }}</span></div>
                @endif
            </div>
            <div class="s-label-foot">
                <span>{{ $product->sku }}</span>
                <span class="s-label-bars" aria-hidden="true"></span>
            </div>
        </div>
    @endif
</div>
