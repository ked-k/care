<div>
    <button type="button" class="s-tool s-basket-btn" wire:click="toggle" aria-label="{{ __('Basket, :n items', ['n' => $count]) }}">
        <i class="fas fa-shopping-basket"></i>
        <span class="s-tool-label">{{ __('Basket') }}</span>
        <span class="s-count num">{{ $count }}</span>
    </button>

    @if ($open && $totals)
        <div class="s-drawer-bg" wire:click="close"></div>
        <aside class="s-drawer" role="dialog" aria-modal="true" aria-label="{{ __('Your basket') }}" x-data x-trap.noscroll="true" @keydown.escape.window="$wire.close()">
            <div class="s-drawer-head">
                <h2>{{ $justAdded ? __('Added to your basket') : __('Your basket') }}</h2>
                <button type="button" class="s-icon-btn" wire:click="close" aria-label="{{ __('Close') }}"><i class="fas fa-times"></i></button>
            </div>

            <div class="s-drawer-body">
                @forelse ($totals['lines'] as $line)
                    @php $p = $line['product']; @endphp
                    <div class="s-line {{ $justAdded === $p->id ? 'is-new' : '' }}" wire:key="drawer-{{ $line['item_id'] }}">
                        @include('shop.partials.product-visual', ['product' => $p])
                        <div>
                            <a href="{{ route('shop.product', [$store->slug, $p->slug]) }}" class="s-line-name" style="font-size:15px">{{ $p->name }}</a>
                            @if ($line['title'])<div class="s-line-variant">{{ $line['title'] }}</div>@endif
                            <div class="s-line-controls">
                                <div class="s-qty s-qty-sm">
                                    <button type="button" wire:click="updateQty('{{ $line['item_id'] }}', {{ $line['qty'] - 1 }})" aria-label="{{ __('One fewer') }}"><i class="fas fa-minus"></i></button>
                                    <input type="number" class="num" value="{{ $line['qty'] }}" readonly aria-label="{{ __('Quantity') }}">
                                    <button type="button" wire:click="updateQty('{{ $line['item_id'] }}', {{ $line['qty'] + 1 }})" aria-label="{{ __('One more') }}"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="s-line-total num">
                            {{ $store->money($line['line_total']) }}
                            @if ($line['saving'] > 0)<span class="s-line-saving"> — {{ __('saving :amount', ['amount' => $store->money($line['saving'])]) }}</span>@endif
                        </div>
                    </div>
                @empty
                    <div class="s-empty" style="border:0">
                        <i class="fas fa-shopping-basket"></i>
                        <h3>{{ __('Your basket is empty') }}</h3>
                    </div>
                @endforelse
            </div>

            @if (! empty($totals['lines']))
                <div class="s-drawer-foot">
                    @if ($totals['free_delivery_gap'] !== null && $totals['free_delivery_gap'] > 0)
                        <div class="s-free-gap">{{ __('Add :amount more for free delivery.', ['amount' => $store->money($totals['free_delivery_gap'])]) }}</div>
                    @endif
                    <div class="s-between">
                        <span class="s-strong">{{ __('Subtotal') }}</span>
                        <strong class="num" style="font-size:20px">{{ $store->money($totals['subtotal'] - $totals['discount']) }}</strong>
                    </div>
                    <a href="{{ route('shop.checkout', $store->slug) }}" class="s-btn s-btn-primary s-btn-lg s-btn-block"><i class="fas fa-lock"></i> {{ __('Checkout') }}</a>
                    <a href="{{ route('shop.cart', $store->slug) }}" class="s-btn s-btn-ghost s-btn-block">{{ __('View basket') }}</a>
                </div>
            @endif
        </aside>
    @endif
</div>
