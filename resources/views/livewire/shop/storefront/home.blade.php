<div>
    {{-- ================= Hero (pattern from Medion: soft gradient, ribbon tag, floating badge, overlapping search) ================= --}}
    <section class="s-hero">
        <div class="s-hero-deco" aria-hidden="true"><span></span><span></span><span></span></div>
        <div class="s-wrap s-hero-grid">
            <div>
                <span class="s-ribbon">{{ __('Care & medical supplies') }}</span>
                <h1>
                    @if ($store->tagline)
                        {{ $store->tagline }}
                    @else
                        {{ __('Gloves, PPE and') }} <span class="s-grad-text">{{ __('first aid') }}</span> {{ __('for care teams') }}
                    @endif
                </h1>
                <p>{{ __(':count products in stock and ready to order, with bulk prices that drop as you buy more boxes.', ['count' => number_format($productCount)]) }}</p>
                <div class="s-hero-cta">
                    <a href="{{ route('shop.catalogue', $store->slug) }}" wire:navigate class="s-btn s-btn-primary s-btn-lg">{{ __('Shop now') }} <i class="fas fa-arrow-right"></i></a>
                    @if ($dealCount)
                        <a href="{{ route('shop.catalogue', [$store->slug, 'deals' => 1]) }}" wire:navigate class="s-btn s-btn-coral s-btn-lg">{{ __('Bulk deals') }} <i class="fas fa-tags"></i></a>
                    @endif
                </div>
                <div class="s-hero-facts">
                    @if ($freeOver)
                        <span><i class="fas fa-check-circle"></i>{{ __('Free delivery over :amount', ['amount' => $store->money($freeOver)]) }}</span>
                    @endif
                    @if ($delivery->firstWhere('is_collection', true))
                        <span><i class="fas fa-check-circle"></i>{{ __('Click & collect') }}</span>
                    @endif
                    <span><i class="fas fa-check-circle"></i>{{ __('Card, PayPal or bank transfer') }}</span>
                </div>
            </div>

            {{-- Visual: a stack of the shop's own best sellers, plus a floating delivery badge --}}
            <div class="s-hero-art" aria-hidden="true">
                <div class="s-hero-ring"></div>
                @foreach ($featured->take(3) as $i => $product)
                    <div class="s-hero-card s-hero-card-{{ $i + 1 }}">
                        @include('shop.partials.product-visual', ['product' => $product])
                        <div class="s-hero-card-meta">
                            <span>{{ \Illuminate\Support\Str::limit($product->labelTitle(), 28) }}</span>
                            <strong class="num">{{ $store->money($product->displayPrice()) }}</strong>
                        </div>
                    </div>
                @endforeach
                <div class="s-hero-badge">
                    <i class="fas fa-truck"></i>
                    <span>
                        @if ($freeOver)
                            <strong>{{ __('Free delivery') }}</strong>{{ __('over :amount', ['amount' => $store->money($freeOver)]) }}
                        @else
                            <strong>{{ $delivery->first()?->eta() ?? __('Fast UK delivery') }}</strong>{{ __('to your door') }}
                        @endif
                    </span>
                </div>
            </div>
        </div>

        {{-- Search card overlapping the hero's bottom edge --}}
        <div class="s-wrap">
            <form class="s-finder" action="{{ route('shop.catalogue', $store->slug) }}" method="get" role="search">
                <strong class="s-finder-title">{{ __('Find a product') }}</strong>
                <div class="s-finder-row">
                    @if ($categories->isNotEmpty())
                        <label for="hero-cat" class="sr-only">{{ __('Category') }}</label>
                        <select id="hero-cat" name="category" class="s-select">
                            <option value="">{{ __('All categories') }}</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->slug }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    @endif
                    <label for="hero-q" class="sr-only">{{ __('Search products') }}</label>
                    <input id="hero-q" type="search" name="q" class="s-input" placeholder="{{ __('What do you need? e.g. nitrile gloves medium') }}">
                    <button class="s-btn s-btn-primary" type="submit"><i class="fas fa-search"></i> {{ __('Search') }}</button>
                </div>
            </form>
        </div>
    </section>

    <div class="s-wrap">
        {{-- ================= Top categories — icons in dashed rings (Medion) ================= --}}
        @if ($categories->isNotEmpty())
            <section class="s-section">
                <div class="s-section-head">
                    <h2><img src="{{ asset('images/brand/caretrust-mark.png') }}" alt="" class="s-head-mark">{{ __('Top categories') }}</h2>
                    <a href="{{ route('shop.catalogue', $store->slug) }}" wire:navigate>{{ __('View all') }} <i class="fas fa-angle-double-right"></i></a>
                </div>
                <div class="s-circles">
                    @foreach ($categories->take(6) as $cat)
                        <a href="{{ route('shop.category', [$store->slug, $cat->slug]) }}" wire:navigate class="s-circle">
                            <span class="s-circle-icon"><i class="{{ $cat->icon ?: \App\Models\Shop\ShopCategory::guessIcon($cat->name) }}" aria-hidden="true"></i></span>
                            <strong>{{ $cat->name }}</strong>
                            <span>{{ trans_choice(':count item|:count items', $cat->products_count, ['count' => number_format($cat->products_count)]) }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ================= Pastel promo cards with pill tags (Medion) ================= --}}
        <section class="s-promos">
            <a href="{{ route('shop.catalogue', [$store->slug, 'deals' => 1]) }}" wire:navigate class="s-promo s-promo-violet">
                <span class="s-pill">{{ __('Bulk buy') }}</span>
                <strong>{{ __('Buy more, pay less per box') }}</strong>
                <span>{{ $dealCount ? trans_choice(':count product with bulk prices|:count products with bulk prices', $dealCount, ['count' => $dealCount]) : __('Bulk prices apply automatically') }}</span>
                <em>{{ __('Shop deals') }} <i class="fas fa-arrow-right"></i></em>
                <i class="fas fa-boxes s-promo-art" aria-hidden="true"></i>
            </a>
            @php $firstAid = $categories->first(fn ($c) => str_contains(strtolower($c->name), 'first aid')) ?? $categories->first(); @endphp
            @if ($firstAid)
                <a href="{{ route('shop.category', [$store->slug, $firstAid->slug]) }}" wire:navigate class="s-promo s-promo-sky">
                    <span class="s-pill">{{ __('Essentials') }}</span>
                    <strong>{{ $firstAid->name }}</strong>
                    <span>{{ trans_choice(':count product ready to ship|:count products ready to ship', $firstAid->products_count, ['count' => number_format($firstAid->products_count)]) }}</span>
                    <em>{{ __('Discover now') }} <i class="fas fa-arrow-right"></i></em>
                    <i class="{{ $firstAid->icon ?: \App\Models\Shop\ShopCategory::guessIcon($firstAid->name) }} s-promo-art" aria-hidden="true"></i>
                </a>
            @endif
            <a href="{{ route('shop.register', $store->slug) }}" wire:navigate class="s-promo s-promo-peach">
                <span class="s-pill">{{ __('Care teams') }}</span>
                <strong>{{ __('Faster checkout') }}</strong>
                <span>{{ __('Save delivery addresses and track every order with an account.') }}</span>
                <em>{{ __('Create account') }} <i class="fas fa-arrow-right"></i></em>
                <i class="fas fa-user-check s-promo-art" aria-hidden="true"></i>
            </a>
        </section>

        {{-- ================= Popular products ================= --}}
        @if ($featured->isNotEmpty())
            <section class="s-section">
                <div class="s-section-head">
                    <h2><img src="{{ asset('images/brand/caretrust-mark.png') }}" alt="" class="s-head-mark">{{ __('Popular with care teams') }}</h2>
                    <a href="{{ route('shop.catalogue', $store->slug) }}" wire:navigate>{{ __('See all products') }} <i class="fas fa-angle-double-right"></i></a>
                </div>
                <div class="s-products">
                    @foreach ($featured as $product)
                        @include('shop.partials.product-card', ['product' => $product])
                    @endforeach
                </div>
            </section>
        @else
            <section class="s-section">
                <div class="s-empty">
                    <i class="fas fa-box-open"></i>
                    <h3>{{ __('Products are on their way') }}</h3>
                    <p>{{ __('This shop is being stocked. Please check back soon.') }}</p>
                </div>
            </section>
        @endif

        {{-- ================= Trust strip — icons in circles (Medion) ================= --}}
        <section class="s-promises">
            <div class="s-promise">
                <i class="fas fa-layer-group" aria-hidden="true"></i>
                <div>
                    <strong>{{ __('Bulk prices') }}</strong>
                    <span>{{ __('Applied automatically in your basket') }}</span>
                </div>
            </div>
            <div class="s-promise">
                <i class="fas fa-truck" aria-hidden="true"></i>
                <div>
                    <strong>{{ $delivery->first()?->eta() ?? __('Fast UK delivery') }}</strong>
                    <span>{{ $freeOver ? __('Free on orders over :amount', ['amount' => $store->money($freeOver)]) : __('Choose an option at checkout') }}</span>
                </div>
            </div>
            <div class="s-promise">
                <i class="fas fa-lock" aria-hidden="true"></i>
                <div>
                    <strong>{{ __('Secure checkout') }}</strong>
                    <span>{{ __('Card payments handled by Stripe') }}</span>
                </div>
            </div>
            <div class="s-promise">
                <i class="fas fa-headset" aria-hidden="true"></i>
                <div>
                    <strong>{{ __('Talk to us') }}</strong>
                    <span>{{ $store->phone ?: ($store->contact_email ?: __('We’re here to help')) }}</span>
                </div>
            </div>
        </section>

        {{-- ================= Shop by category — pill tabs (Medion "Popular Items") ================= --}}
        @if ($tabs->count() > 1)
            <section class="s-section" x-data="{ tab: @js($tabs->keys()->first()) }">
                <div class="s-section-head s-section-head-tabs">
                    <h2><img src="{{ asset('images/brand/caretrust-mark.png') }}" alt="" class="s-head-mark">{{ __('Shop by category') }}</h2>
                    <div class="s-pills" role="tablist">
                        @foreach ($tabs as $slug => $t)
                            <button type="button" role="tab" class="s-pill-tab" :class="tab === @js($slug) && 'is-active'" :aria-selected="tab === @js($slug)" @click="tab = @js($slug)">{{ $t['category']->name }}</button>
                        @endforeach
                    </div>
                </div>
                @foreach ($tabs as $slug => $t)
                    <div role="tabpanel" x-show="tab === @js($slug)" @if (! $loop->first) x-cloak @endif>
                        <div class="s-products">
                            @foreach ($t['products'] as $product)
                                @include('shop.partials.product-card', ['product' => $product])
                            @endforeach
                        </div>
                        <div class="s-tab-more">
                            <a href="{{ route('shop.category', [$store->slug, $slug]) }}" wire:navigate class="s-btn s-btn-ghost">{{ __('All :name', ['name' => $t['category']->name]) }} <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                @endforeach
            </section>
        @endif

        {{-- ================= Wide brand banner (Medimela / Medion sale banner, repurposed) ================= --}}
        <section class="s-banner">
            <div>
                <span class="s-banner-eyebrow">{{ __('Ordering for a care team?') }}</span>
                <h2>{{ __('One account for the whole team') }}</h2>
                <p>{{ __('Keep delivery addresses on file, check out faster and follow every order from payment to delivery.') }}</p>
            </div>
            <a href="{{ route(auth('shop')->check() ? 'shop.account' : 'shop.register', $store->slug) }}" wire:navigate class="s-btn s-btn-white s-btn-lg">{{ __('Get started') }} <i class="fas fa-arrow-right"></i></a>
        </section>
    </div>
</div>
