<div class="s-wrap">
    @php
        $steps = $order->isCollection()
            ? ['placed' => __('Placed'), 'paid' => __('Paid'), 'packed' => __('Packed'), 'done' => __('Collected')]
            : ['placed' => __('Placed'), 'paid' => __('Paid'), 'shipped' => __('Dispatched'), 'done' => __('Delivered')];
        $done = [
            'placed' => true,
            'paid' => $order->payment_status === 'paid',
            'packed' => in_array($order->status, ['shipped', 'completed'], true),
            'shipped' => in_array($order->status, ['shipped', 'completed'], true),
            'done' => $order->status === 'completed',
        ];
        $closed = in_array($order->status, ['cancelled', 'refunded'], true);
    @endphp

    @if ($justPlaced && ! $closed)
        <div class="s-thanks">
            <span class="s-tick"><i class="fas fa-check"></i></span>
            <h1>{{ $order->payment_status === 'paid' ? __('Thank you — your order is confirmed') : __('Thank you — we’ve got your order') }}</h1>
            <p>{{ __('Order :n. We’ve sent the details to :email.', ['n' => $order->number, 'email' => $order->email]) }}</p>
        </div>
    @else
        <nav class="s-crumbs" aria-label="{{ __('Breadcrumb') }}">
            <a href="{{ route('shop.home', $store->slug) }}" wire:navigate>{{ __('Home') }}</a>
            @if (auth('shop')->check())
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
                <a href="{{ route('shop.account', $store->slug) }}" wire:navigate>{{ __('Your orders') }}</a>
            @endif
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
            <span>{{ $order->number }}</span>
        </nav>
        <div class="s-between">
            <h1 class="s-page-title">{{ __('Order :n', ['n' => $order->number]) }}</h1>
            <button type="button" class="s-btn s-btn-ghost s-btn-sm s-no-print" onclick="window.print()"><i class="fas fa-print"></i> {{ __('Print') }}</button>
        </div>
        <p class="s-page-sub">{{ __('Placed :date', ['date' => $order->created_at->format('j F Y, g:ia')]) }}</p>
    @endif

    <div class="s-two-col">
        <div class="s-stack">
            @if ($closed)
                <div class="s-notice s-notice-danger"><i class="fas fa-ban"></i><span>{{ __('This order was :status.', ['status' => \Illuminate\Support\Str::lower($order->statusLabel())]) }}</span></div>
            @else
                <div class="s-panel s-panel-pad">
                    <div class="s-between" style="margin-bottom:14px">
                        <h2>{{ $order->statusLabel() }}</h2>
                        <span class="s-tag {{ $order->payment_status === 'paid' ? 's-tag-ok' : 's-tag-warn' }}">{{ $order->paymentStatusLabel() }}</span>
                    </div>
                    <ol class="s-progress">
                        @foreach ($steps as $key => $label)
                            <li @class(['is-done' => $done[$key]])>{{ $label }}</li>
                        @endforeach
                    </ol>
                    @if ($order->tracking_number)
                        <p class="s-mt" style="margin-bottom:0">
                            {{ __('Tracking') }}: <strong>{{ $order->carrier ? $order->carrier.' — ' : '' }}{{ $order->tracking_number }}</strong>
                        </p>
                    @endif
                </div>
            @endif

            {{-- How to pay, when it's still owed --}}
            @if ($order->payment_status !== 'paid' && ! $closed)
                @if ($order->payment_method === 'bank_transfer' && ! empty($bankDetails))
                    <div class="s-panel s-panel-pad">
                        <h2>{{ __('Pay by bank transfer') }}</h2>
                        <p style="margin:8px 0 0">{{ __('Please transfer :amount using your order number as the reference. We’ll dispatch as soon as it arrives.', ['amount' => $store->money($order->total)]) }}</p>
                        <dl class="s-bank">
                            @foreach ($bankDetails as $label => $value)
                                <dt>{{ $label }}</dt><dd class="num">{{ $value }}</dd>
                            @endforeach
                            <dt>{{ __('Reference') }}</dt><dd class="num">{{ $order->number }}</dd>
                            <dt>{{ __('Amount') }}</dt><dd class="num">{{ $store->money($order->total) }}</dd>
                        </dl>
                        @if ($bankInstructions)<p class="s-hint s-mt" style="margin-bottom:0">{{ $bankInstructions }}</p>@endif
                    </div>
                @elseif ($order->payment_method === 'pay_on_delivery')
                    <div class="s-notice s-notice-info"><i class="fas fa-hand-holding-usd"></i><span>{{ __('You’ll pay :amount when your order is delivered or collected.', ['amount' => $store->money($order->total)]) }}</span></div>
                @endif

                @if ($canPayOnline)
                    <div class="s-panel s-panel-pad s-no-print">
                        <h2>{{ in_array($order->payment_method, ['stripe', 'paypal'], true) ? __('Finish paying') : __('Prefer to pay online?') }}</h2>
                        <p class="s-muted" style="margin:6px 0 14px">{{ __('Your order is saved. Pay now and we’ll start packing straight away.') }}</p>
                        <div class="s-row">
                            @foreach ($onlineMethods as $code => $gateway)
                                <button type="button" class="s-btn {{ $loop->first ? 's-btn-primary' : 's-btn-ghost' }}" wire:click="payNow('{{ $code }}')" wire:loading.attr="disabled" wire:target="payNow">
                                    <i class="{{ $gateway->icon() }}"></i> {{ __('Pay :amount with :method', ['amount' => $store->money($order->total), 'method' => $code === 'stripe' ? __('card') : $gateway->label($store)]) }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif

            <div class="s-panel">
                <div class="s-step-head"><h2>{{ __('Items') }}</h2></div>
                @foreach ($order->items as $item)
                    <div class="s-line">
                        @if ($item->product)
                            @include('shop.partials.product-visual', ['product' => $item->product])
                        @else
                            <div class="s-visual" style="background:var(--mist)"></div>
                        @endif
                        <div>
                            @if ($item->product && $item->product->status === 'active')
                                <a href="{{ route('shop.product', [$store->slug, $item->product->slug]) }}" wire:navigate class="s-line-name">{{ $item->name }}</a>
                            @else
                                <span class="s-line-name">{{ $item->name }}</span>
                            @endif
                            <div class="s-line-meta num">{{ $item->qty }} × {{ $store->money($item->unit_price) }}@if ($item->sku) — {{ $item->sku }}@endif</div>
                        </div>
                        <div class="s-line-total num">{{ $store->money($item->line_total) }}</div>
                    </div>
                @endforeach
            </div>

            @if ($order->events->isNotEmpty())
                <div class="s-panel s-panel-pad">
                    <h2 style="margin-bottom:14px">{{ __('Updates') }}</h2>
                    <ul class="s-timeline">
                        @foreach ($order->events as $event)
                            <li><time datetime="{{ $event->created_at->toIso8601String() }}">{{ $event->created_at->format('j M, g:ia') }}</time><span>{{ $event->message }}</span></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <aside class="s-sticky">
            <div class="s-panel s-panel-pad s-stack">
                <h2>{{ __('Summary') }}</h2>
                <div class="s-sum">
                    <div class="s-sum-row"><span>{{ __('Items') }}</span><span class="num">{{ $store->money($order->subtotal) }}</span></div>
                    @if ($order->discount_total > 0)
                        <div class="s-sum-row is-discount"><span>{{ __('Discount') }} @if ($order->discount_code)<span class="s-tag s-tag-ok">{{ $order->discount_code }}</span>@endif</span><span class="num">−{{ $store->money($order->discount_total) }}</span></div>
                    @endif
                    <div class="s-sum-row"><span>{{ $order->delivery_name ?: __('Delivery') }}</span><span class="num">{{ $order->delivery_price > 0 ? $store->money($order->delivery_price) : __('Free') }}</span></div>
                    @unless ($order->prices_include_vat)
                        <div class="s-sum-row"><span>{{ __('VAT') }}</span><span class="num">{{ $store->money($order->vat_total) }}</span></div>
                    @endunless
                    <div class="s-sum-total"><span class="s-strong">{{ __('Total') }}</span><strong class="num">{{ $store->money($order->total) }}</strong></div>
                    @if ($order->prices_include_vat && $order->vat_total > 0)
                        <div class="s-sum-vat num">{{ __('Includes :amount VAT', ['amount' => $store->money($order->vat_total)]) }}</div>
                    @endif
                </div>
                <hr class="s-hr">
                <div class="s-small"><span class="s-muted">{{ __('Payment') }}:</span> {{ $order->paymentMethodLabel() }}</div>
                @if ($order->shipping_address)
                    <div><div class="s-label" style="margin-bottom:4px">{{ __('Delivering to') }}</div>@include('shop.partials.address', ['address' => $order->shipping_address])</div>
                @else
                    <div><div class="s-label" style="margin-bottom:4px">{{ __('Collecting from') }}</div><address class="s-address" style="white-space:pre-line">{{ $store->address }}</address></div>
                @endif
                <div><div class="s-label" style="margin-bottom:4px">{{ __('Billing') }}</div>@include('shop.partials.address', ['address' => $order->billing_address])</div>
            </div>
            @if ($store->contact_email || $store->phone)
                <p class="s-small s-muted s-no-print" style="margin:0">
                    {{ __('Questions about this order?') }}
                    @if ($store->contact_email)<a class="s-btn-link s-btn" href="mailto:{{ $store->contact_email }}?subject={{ rawurlencode(__('Order :n', ['n' => $order->number])) }}">{{ $store->contact_email }}</a>@endif
                    @if ($store->phone) {{ __('or call :phone', ['phone' => $store->phone]) }}@endif
                </p>
            @endif
        </aside>
    </div>
</div>
