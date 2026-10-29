<div class="s-wrap">
    @php
        $state = $product->stockState();
        $hasVariants = $product->has_variants && $variants->isNotEmpty();
        $topPrice = max(0.01, (float) $product->price);
        $vatNote = $store->prices_include_vat
            ? ((float) $product->vat_rate > 0 ? __('Prices include VAT at :rate%', ['rate' => rtrim(rtrim(number_format((float) $product->vat_rate, 2), '0'), '.')]) : __('No VAT'))
            : __('Prices exclude VAT');
        $perUnit = $product->unit_name ? __('per :unit', ['unit' => \Illuminate\Support\Str::lower($product->unit_name)])
            : ($product->pack_size && ! $hasVariants ? __('per :pack', ['pack' => \Illuminate\Support\Str::lower($product->pack_size)]) : __('each'));
    @endphp

    <nav class="s-crumbs" aria-label="{{ __('Breadcrumb') }}">
        <a href="{{ route('shop.home', $store->slug) }}" wire:navigate>{{ __('Home') }}</a>
        <i class="fas fa-chevron-right" aria-hidden="true"></i>
        @if ($product->category)
            <a href="{{ route('shop.category', [$store->slug, $product->category->slug]) }}" wire:navigate>{{ $product->category->name }}</a>
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
        @endif
        <span>{{ $product->labelTitle() }}</span>
    </nav>

    @if ($product->status !== 'active')
        <div class="s-notice s-notice-warn s-mt"><i class="fas fa-eye-slash"></i><span>{{ __('Staff preview: this product is a :status and customers can’t see it yet.', ['status' => $product->status]) }}</span></div>
    @endif

    <div class="s-product">
        <div class="s-gallery" x-data="{ active: 0 }">
            <div class="s-gallery-frame">
                @if ($product->is_featured)<span class="s-seller-badge">{{ __('Best seller') }}</span>@endif
                @if ($product->images->isEmpty())
                    @include('shop.partials.product-visual', ['product' => $product, 'large' => true])
                @else
                    @foreach ($product->images as $i => $image)
                        <div class="s-visual s-visual-lg" x-show="active === {{ $i }}" @if ($i > 0) x-cloak @endif>
                            <img src="{{ $image->url() }}" alt="{{ $image->alt ?: $product->name }}">
                        </div>
                    @endforeach
                @endif
            </div>
            @if ($product->images->count() > 1)
                <div class="s-thumbs">
                    @foreach ($product->images as $i => $image)
                        <button type="button" @click="active = {{ $i }}" :class="{ 'is-active': active === {{ $i }} }" aria-label="{{ __('Image :n', ['n' => $i + 1]) }}">
                            <img src="{{ $image->url() }}" alt="">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="s-buy">
            @if ($product->brand)<span class="s-tag">{{ $product->brand }}</span>@endif
            <h1>{{ $product->name }}</h1>

            @if ($hasVariants)
                {{-- Product with sizes/options: headline price here, the grid below does the buying. --}}
                <p class="s-as-low">{{ __('As low as') }} <strong class="num">{{ $store->money($lowest) }}</strong></p>
                <div class="s-meta">
                    <span><b>{{ trans_choice(':count option|:count options', $variants->count(), ['count' => $variants->count()]) }}</b> {{ __('to choose from') }}</span>
                    <span class="s-stock s-stock-{{ $state }}">{{ match ($state) {
                        'low_stock' => __('Limited stock'),
                        'backorder' => __('On back order'),
                        'out_of_stock' => __('Out of stock'),
                        default => __('In stock'),
                    } }}</span>
                </div>
                <p class="s-vat-note">{{ $priceVaries ? __('From :price :unit', ['price' => $store->money($fromPrice), 'unit' => $perUnit]) : __(':price :unit', ['price' => $store->money($fromPrice), 'unit' => $perUnit]) }} · {{ $vatNote }}</p>

                <div class="s-vpick">
                    @foreach ($product->options ?? [] as $option)
                        <div class="s-vpick-row">
                            <span class="s-vpick-name">{{ $option['name'] }}</span>
                            <span class="s-vpick-values">
                                @foreach (array_slice($option['values'], 0, 12) as $value)<span class="s-vchip">{{ $value }}</span>@endforeach
                                @if (count($option['values']) > 12)<span class="s-vchip s-vchip-more">{{ __('+:n more', ['n' => count($option['values']) - 12]) }}</span>@endif
                            </span>
                        </div>
                    @endforeach
                </div>

                @if ($product->isPurchasable())
                    <a href="#choose" class="s-btn s-btn-primary s-btn-lg s-btn-block" style="margin-top:20px">
                        {{ __('Choose size & quantity') }} <i class="fas fa-arrow-down"></i>
                    </a>
                    @if ($mixMatch)
                        <p class="s-hint s-mix-note"><i class="fas fa-layer-group"></i> {{ __('Mix sizes: every size counts towards the bulk price.') }}</p>
                    @endif
                @else
                    <div class="s-notice s-notice-warn" style="margin-top:22px">
                        <i class="fas fa-info-circle"></i>
                        <span>{{ __('Every option is out of stock right now.') }} @if ($store->contact_email){{ __('Email :email and we’ll tell you when it’s back.', ['email' => $store->contact_email]) }}@endif</span>
                    </div>
                @endif
            @else
                <div class="s-meta">
                    @if ($product->sku)<span>{{ __('SKU') }} <b>{{ $product->sku }}</b></span>@endif
                    @if ($product->pack_size)<span>{{ __('Pack') }} <b>{{ $product->pack_size }}</b></span>@endif
                    <span class="s-stock s-stock-{{ $state }}">{{ match ($state) {
                        'low_stock' => __('Only :n left', ['n' => $product->stock_qty]),
                        'backorder' => __('On back order — ships when restocked'),
                        'out_of_stock' => __('Out of stock'),
                        default => __('In stock'),
                    } }}</span>
                </div>

                <div class="s-price-now">
                    <strong class="num">{{ $store->money($unit) }}</strong>
                    <span>{{ $perUnit }}</span>
                    @if ($product->compare_at_price && $product->compare_at_price > $product->price && $unit >= (float) $product->price)
                        <s class="num">{{ $store->money($product->compare_at_price) }}</s>
                    @endif
                </div>
                <p class="s-vat-note">{{ $vatNote }}</p>

                @if (count($ladder) > 1)
                    {{-- The bulk-price ladder: each step is a quantity band, its height the price. --}}
                    <div class="s-ladder">
                        <div class="s-ladder-head">
                            <strong>{{ __('Bulk prices') }}</strong>
                            <span class="s-muted s-small">{{ __('Tap a step to set the quantity') }}</span>
                        </div>
                        <div class="s-ladder-steps">
                            @foreach ($ladder as $i => $step)
                                <button type="button" class="s-step {{ $i === $activeStep ? 'is-active' : '' }}" wire:click="setQty({{ $step['from'] }})"
                                        aria-label="{{ __('Buy :n or more at :price each', ['n' => $step['from'], 'price' => $store->money($step['price'])]) }}">
                                    <span class="s-step-price num">{{ $store->money($step['price']) }}</span>
                                    <span class="s-step-bar" style="height: {{ 36 + round(56 * ($step['price'] / $topPrice)) }}px">
                                        @if ($step['saving'] > 0)<span class="s-step-save">−{{ $step['saving'] }}%</span>@endif
                                    </span>
                                </button>
                            @endforeach
                        </div>
                        <div class="s-ladder-qty num">
                            @foreach ($ladder as $step)
                                <span>{{ $step['to'] ? $step['from'].'–'.$step['to'] : $step['from'].'+' }}</span>
                            @endforeach
                        </div>
                        @if ($nextStep && $product->isPurchasable())
                            <div class="s-ladder-next">
                                <i class="fas fa-arrow-down"></i>
                                {{ __('Add :more more to pay :price each.', ['more' => $nextStep['from'] - $qty, 'price' => $store->money($nextStep['price'])]) }}
                            </div>
                        @endif
                    </div>
                @endif

                @if ($product->isPurchasable())
                    <div class="s-buy-row" style="margin-top: 22px">
                        <div class="s-qty">
                            <button type="button" wire:click="decrement" aria-label="{{ __('One fewer') }}"><i class="fas fa-minus"></i></button>
                            <label for="qty" class="sr-only">{{ __('Quantity') }}</label>
                            <input id="qty" type="number" inputmode="numeric" min="{{ max(1, (int) $product->min_order_qty) }}" wire:model.live.blur="qty" class="num">
                            <button type="button" wire:click="increment" aria-label="{{ __('One more') }}"><i class="fas fa-plus"></i></button>
                        </div>
                        <button type="button" class="s-btn s-btn-primary s-btn-lg" wire:click="add" wire:loading.attr="disabled" wire:target="add">
                            <i class="fas fa-shopping-basket"></i>
                            <span wire:loading.remove wire:target="add">{{ __('Add to basket') }}</span>
                            <span wire:loading wire:target="add">{{ __('Adding…') }}</span>
                        </button>
                    </div>
                    <p class="s-buy-total num">{{ __(':qty × :unit = :total', ['qty' => $qty, 'unit' => $store->money($unit), 'total' => $store->money($lineTotal)]) }}</p>
                    @if ($product->min_order_qty > 1)
                        <p class="s-hint">{{ __('Minimum order: :n', ['n' => $product->min_order_qty]) }}</p>
                    @endif
                @else
                    <div class="s-notice s-notice-warn" style="margin-top:22px">
                        <i class="fas fa-info-circle"></i>
                        <span>
                            {{ __('This item is out of stock right now.') }}
                            @if ($store->contact_email){{ __('Email :email and we’ll tell you when it’s back.', ['email' => $store->contact_email]) }}@endif
                        </span>
                    </div>
                @endif
            @endif

            {{-- Description / Specifications tabs --}}
            <div class="s-info-tabs" x-data="{ tab: '{{ $product->description ? 'desc' : 'spec' }}' }">
                <div class="s-info-tablist" role="tablist">
                    @if ($product->description)
                        <button type="button" role="tab" :class="tab === 'desc' && 'is-active'" @click="tab = 'desc'">{{ __('Description') }}</button>
                    @endif
                    <button type="button" role="tab" :class="tab === 'spec' && 'is-active'" @click="tab = 'spec'">{{ __('Specifications') }}</button>
                </div>
                @if ($product->description)
                    <div class="s-prose" x-show="tab === 'desc'">{!! \Mews\Purifier\Facades\Purifier::clean($product->description) !!}</div>
                @endif
                <dl class="s-specs" x-show="tab === 'spec'" @if ($product->description) x-cloak @endif>
                    @if ($hasVariants)
                        @foreach ($product->options ?? [] as $option)<dt>{{ $option['name'] }}</dt><dd>{{ implode(', ', $option['values']) }}</dd>@endforeach
                    @else
                        @if ($product->sku)<dt>{{ __('Product code') }}</dt><dd>{{ $product->sku }}</dd>@endif
                        @if ($product->pack_size)<dt>{{ __('Pack size') }}</dt><dd>{{ $product->pack_size }}</dd>@endif
                        @if ($product->barcode)<dt>{{ __('Barcode') }}</dt><dd>{{ $product->barcode }}</dd>@endif
                    @endif
                    @if ($product->brand)<dt>{{ __('Brand') }}</dt><dd>{{ $product->brand }}</dd>@endif
                    @if ($product->category)<dt>{{ __('Category') }}</dt><dd>{{ $product->category->name }}</dd>@endif
                    @if ($product->summary)<dt>{{ __('Summary') }}</dt><dd>{{ $product->summary }}</dd>@endif
                </dl>
            </div>
        </div>
    </div>

    @if ($hasVariants)
        {{-- The order grid: one table per set of price bands, one row per size/option. --}}
        <section class="s-vgrid" id="choose" x-data="{ f: {}, shows(vals) { return Object.entries(this.f).every(([i, v]) => v === '' || vals[i] === v) } }">
            <div class="s-vgrid-head">
                <div>
                    <h2>{{ __('Choose your size and quantity') }}</h2>
                    <p class="s-muted">
                        {{ __('Type how many you want next to each option, then add them all at once.') }} {{ $vatNote }}.
                        @if ($mixMatch) <span class="s-mix-inline"><i class="fas fa-layer-group"></i> {{ __('Sizes are counted together for bulk prices.') }}</span>@endif
                    </p>
                </div>
                @if ($filters)
                    <div class="s-vfilters">
                        @foreach ($filters as $filter)
                            <div class="s-vfilter" role="group" aria-label="{{ $filter['name'] }}">
                                <span>{{ $filter['name'] }}</span>
                                <button type="button" class="s-vfilter-btn" :class="{ 'is-active': (f[{{ $filter['index'] }}] ?? '') === '' }" @click="f[{{ $filter['index'] }}] = ''">{{ __('All') }}</button>
                                @foreach ($filter['values'] as $value)
                                    <button type="button" class="s-vfilter-btn" :class="{ 'is-active': f[{{ $filter['index'] }}] === @js($value) }" @click="f[{{ $filter['index'] }}] = @js($value)">{{ $value }}</button>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            @foreach ($tables as $t => $table)
                <div class="s-vtable-wrap">
                    <table class="s-vtable">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('Product code') }}</th>
                                @foreach ($product->optionNames() as $name)<th scope="col">{{ $name }}</th>@endforeach
                                @foreach ($table['bands'] as $b => $band)
                                    <th scope="col" class="s-vprice-h num">
                                        @if ($b === 0)<span class="s-vprice-cap">{{ $unitLabel }}</span>@endif
                                        {{ $band['to'] ? ($band['from'] === $band['to'] ? $band['from'] : $band['from'].' – '.$band['to']) : $band['from'].'+' }}
                                    </th>
                                @endforeach
                                <th scope="col">{{ __('Stock') }}</th>
                                <th scope="col" class="s-vqty-h">{{ __('Quantity') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($table['rows'] as $row)
                                @php $v = $row['variant']; @endphp
                                <tr wire:key="vrow-{{ $v->id }}" x-show="shows(@js($row['values']))" @class(['is-chosen' => $row['qty'] > 0, 'is-off' => ! $row['purchasable']])>
                                    <td class="s-vcode" data-label="{{ __('Code') }}">
                                        @if ($v->image)<img src="{{ $v->image->url() }}" alt="" class="s-vthumb">@endif
                                        <span class="num">{{ $v->sku ?: '—' }}</span>
                                    </td>
                                    @foreach ($product->optionNames() as $i => $name)
                                        <td data-label="{{ $name }}" @class(['s-vopt', 's-vopt-main' => $i === 0])>{{ $row['values'][$i] ?? '—' }}</td>
                                    @endforeach
                                    @foreach ($table['bands'] as $b => $band)
                                        <td data-label="{{ $band['to'] ? ($band['from'] === $band['to'] ? $band['from'] : $band['from'].'–'.$band['to']) : $band['from'].'+' }}"
                                            @class(['s-vprice num', 's-band-first' => $b === 0, 'is-hit' => $row['hit'] === $b])>
                                            {{ $store->money($row['prices'][$b]) }}
                                            @if ($b === 0 && $row['was'])<s>{{ $store->money($row['was']) }}</s>@endif
                                        </td>
                                    @endforeach
                                    <td data-label="{{ __('Stock') }}">
                                        <span class="s-stock s-stock-{{ $row['state'] }}">{{ match ($row['state']) {
                                            'low_stock' => __('Only :n left', ['n' => $v->stock_qty]),
                                            'backorder' => __('Back order'),
                                            'out_of_stock' => __('Sold out'),
                                            default => __('In stock'),
                                        } }}</span>
                                    </td>
                                    <td class="s-vqty" data-label="{{ __('Quantity') }}">
                                        @if ($row['purchasable'])
                                            <div class="s-qty s-qty-sm">
                                                <button type="button" wire:click="bump('{{ $v->id }}', -1)" aria-label="{{ __('One fewer :name', ['name' => $v->title()]) }}"><i class="fas fa-minus"></i></button>
                                                <input type="number" inputmode="numeric" min="0" max="{{ $row['max'] }}" class="num" placeholder="0"
                                                       wire:model.live.debounce.400ms="quantities.{{ $v->id }}" aria-label="{{ __('Quantity of :name', ['name' => $v->title()]) }}">
                                                <button type="button" wire:click="bump('{{ $v->id }}', 1)" aria-label="{{ __('One more :name', ['name' => $v->title()]) }}"><i class="fas fa-plus"></i></button>
                                            </div>
                                        @else
                                            <span class="s-muted s-small">{{ __('Unavailable') }}</span>
                                        @endif
                                        @if ($row['in_basket'])<span class="s-vin-basket">{{ __(':n in basket', ['n' => $row['in_basket']]) }}</span>@endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach

            <div class="s-vbar">
                <div class="s-vbar-sum">
                    @if ($gridCount > 0)
                        <strong class="num">{{ trans_choice(':count item|:count items', $gridCount, ['count' => $gridCount]) }} · {{ $store->money($gridTotal) }}</strong>
                        @if ($nextShared)
                            <span class="s-vbar-next"><i class="fas fa-arrow-down"></i> {{ __('Add :more more (any size) to pay :price each.', ['more' => $nextShared['more'], 'price' => $store->money($nextShared['price'])]) }}</span>
                        @endif
                    @else
                        <span class="s-muted">{{ __('Type a quantity next to each size you need') }}</span>
                    @endif
                    @if ($product->min_order_qty > 1)<span class="s-hint">{{ __('Minimum order per option: :n', ['n' => $product->min_order_qty]) }}</span>@endif
                </div>
                <button type="button" class="s-btn s-btn-coral s-btn-lg" wire:click="addSelected" wire:loading.attr="disabled" wire:target="addSelected" @disabled($gridCount === 0)>
                    <i class="fas fa-shopping-basket"></i>
                    <span wire:loading.remove wire:target="addSelected">{{ __('Add to basket') }}</span>
                    <span wire:loading wire:target="addSelected">{{ __('Adding…') }}</span>
                </button>
            </div>
        </section>
    @endif

    {{-- Delivery / guarantee strip — only what this store actually offers --}}
    <section class="s-assure-strip">
        @if ($delivery->first()?->eta())
            <div><i class="fas fa-shipping-fast"></i><span><strong>{{ $delivery->first()->eta() }}</strong>{{ $delivery->first()->name }}</span></div>
        @endif
        @if ($freeOver)
            <div><i class="fas fa-truck"></i><span><strong>{{ __('Free delivery') }}</strong>{{ __('on orders over :amount', ['amount' => $store->money($freeOver)]) }}</span></div>
        @endif
        <div><i class="fas fa-layer-group"></i><span><strong>{{ __('Bulk prices') }}</strong>{{ __('applied automatically in your basket') }}</span></div>
        <div><i class="fas fa-lock"></i><span><strong>{{ __('Secure payment') }}</strong>{{ __('card, PayPal or bank transfer') }}</span></div>
        @if (filled($store->returns_policy))
            <a href="{{ route('shop.page', [$store->slug, 'returns']) }}" wire:navigate><i class="fas fa-undo"></i><span><strong>{{ __('Easy returns') }}</strong>{{ __('read our returns policy') }}</span></a>
        @endif
    </section>

    @if ($related->isNotEmpty())
        <section class="s-section" style="padding-top:0">
            <div class="s-section-head"><h2><img src="{{ asset('images/brand/caretrust-mark.png') }}" alt="" class="s-head-mark">{{ $hasVariants ? __('Alternative products') : __('Similar items') }}</h2></div>
            <div class="s-products">
                @foreach ($related as $relatedProduct)
                    @include('shop.partials.product-card', ['product' => $relatedProduct])
                @endforeach
            </div>
        </section>
    @endif
</div>
