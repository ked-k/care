<div class="s-wrap">
    <nav class="s-crumbs" aria-label="{{ __('Breadcrumb') }}">
        <a href="{{ route('shop.home', $store->slug) }}" wire:navigate>{{ __('Home') }}</a>
        <i class="fas fa-chevron-right" aria-hidden="true"></i>
        <span>{{ __('Basket') }}</span>
    </nav>
    <h1 class="s-page-title">{{ __('Your basket') }}</h1>

    @if (empty($totals['lines']))
        <div class="s-empty s-mt-lg">
            <i class="fas fa-shopping-basket"></i>
            <h3>{{ __('Your basket is empty') }}</h3>
            <p>{{ __('Search for what you need, or browse by category.') }}</p>
            <a href="{{ route('shop.catalogue', $store->slug) }}" wire:navigate class="s-btn s-btn-primary">{{ __('Browse products') }}</a>
        </div>
    @else
        <div class="s-two-col">
            <div class="s-stack">
                @foreach ($totals['problems'] as $problem)
                    <div class="s-notice s-notice-warn"><i class="fas fa-exclamation-triangle"></i><span>{{ $problem }}</span></div>
                @endforeach

                <div class="s-panel">
                    @foreach ($totals['lines'] as $line)
                        @php $p = $line['product']; @endphp
                        <div class="s-line" wire:key="line-{{ $line['item_id'] }}">
                            <a href="{{ route('shop.product', [$store->slug, $p->slug]) }}" wire:navigate>@include('shop.partials.product-visual', ['product' => $p])</a>
                            <div>
                                <a href="{{ route('shop.product', [$store->slug, $p->slug]) }}" wire:navigate class="s-line-name">{{ $p->name }}</a>
                                @if ($line['title'])<div class="s-line-variant">{{ $line['title'] }}</div>@endif
                                <div class="s-line-meta num">
                                    {{ $store->money($line['unit_price']) }} {{ __('each') }}
                                    @if ($line['sku']) — {{ $line['sku'] }}@endif
                                </div>
                                @if ($line['saving'] > 0)
                                    <div class="s-line-saving">
                                        {{ $line['mixed'] ? __('Bulk price (sizes counted together) saves you :amount', ['amount' => $store->money($line['saving'])]) : __('Bulk price saves you :amount', ['amount' => $store->money($line['saving'])]) }}
                                    </div>
                                @endif
                                <div class="s-line-controls">
                                    <div class="s-qty s-qty-sm">
                                        <button type="button" wire:click="updateQty('{{ $line['item_id'] }}', {{ $line['qty'] - 1 }})" aria-label="{{ __('One fewer') }}"><i class="fas fa-minus"></i></button>
                                        <input type="number" class="num" value="{{ $line['qty'] }}" wire:key="qty-{{ $line['item_id'] }}-{{ $line['qty'] }}"
                                               @change="$wire.updateQty('{{ $line['item_id'] }}', parseInt($event.target.value || 0))" aria-label="{{ __('Quantity of :name', ['name' => $line['name']]) }}">
                                        <button type="button" wire:click="updateQty('{{ $line['item_id'] }}', {{ $line['qty'] + 1 }})" aria-label="{{ __('One more') }}"><i class="fas fa-plus"></i></button>
                                    </div>
                                    <button type="button" class="s-btn-link s-btn s-small" wire:click="remove('{{ $line['item_id'] }}')">{{ __('Remove') }}</button>
                                </div>
                            </div>
                            <div class="s-line-total num">{{ $store->money($line['line_total']) }}</div>
                        </div>
                    @endforeach
                </div>

                <a href="{{ route('shop.catalogue', $store->slug) }}" wire:navigate class="s-btn-link s-btn"><i class="fas fa-chevron-left"></i> {{ __('Continue shopping') }}</a>
            </div>

            <aside class="s-sticky">
                <div class="s-panel s-panel-pad s-stack">
                    <h2>{{ __('Order summary') }}</h2>
                    @include('livewire.shop.storefront.partials.summary', ['totals' => $totals, 'showDiscountForm' => true])
                    <a href="{{ route('shop.checkout', $store->slug) }}" wire:navigate class="s-btn s-btn-primary s-btn-lg s-btn-block">
                        <i class="fas fa-lock"></i> {{ __('Go to checkout') }}
                    </a>
                </div>
            </aside>
        </div>
    @endif
</div>
