<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    {{-- Batch 40: public storefront shell. Deliberately NOT the admin layout
         and NOT Tailwind — see public/css/shop.css for why. Alpine comes from
         Livewire; resources/js/app.js adds the shared toast store. --}}
    @php
        $store = $store ?? app('shop.store');
        $title = ($pageTitle ?? null) ? $pageTitle.' | '.$store->name : $store->name.($store->tagline ? ' — '.$store->tagline : '');
        $categoriesNav = \App\Models\Shop\ShopCategory::where('store_id', $store->id)->active()->whereNull('parent_id')
            ->whereHas('products', fn ($q) => $q->active())
            ->orderByDesc('is_featured')->orderBy('sort_order')->orderBy('name')->get();
        $shopCustomer = auth('shop')->user();
        $shopCustomer = $shopCustomer && $shopCustomer->store_id === $store->id ? $shopCustomer : null;
        $isPreview = ! $store->is_live && \App\Http\Middleware\ResolveShopStore::isStaffPreview($store);
        $freeOver = $store->deliveryMethods()->active()->whereNotNull('free_over')->min('free_over');
        $currentCategory = request()->route('categorySlug') ?? request()->query('category');
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    {{-- Batch 43b: one address per page for search engines (the shop's own domain when it has one). --}}
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="description" content="{{ $store->tagline ?: __('Medical and care supplies from :name.', ['name' => $store->name]) }}">
    <meta name="theme-color" content="{{ $store->brand_color }}">
    <link rel="icon" type="image/png" href="{{ $store->logoUrl() ?: asset('images/brand/caretrust-favicon.png') }}">
    @if ($isPreview)<meta name="robots" content="noindex">@endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/shop.css') }}?v=45">
    <style>
        :root { --ink: {{ $store->brand_color }}; --action: {{ $store->accent_color }}; }
    </style>
    @vite(['resources/js/app.js'])
    @livewireStyles
</head>
<body class="shop">
    <a href="#main" class="sr-only">{{ __('Skip to content') }}</a>

    @if ($isPreview)
        <div class="s-preview">
            <div class="s-wrap">
                <span><i class="fas fa-eye"></i> {{ __('Preview — this shop isn’t live yet, so only your staff can see it.') }}</span>
                <a href="{{ route('shop.admin.settings') }}">{{ __('Shop settings') }}</a>
            </div>
        </div>
    @endif

    {{-- Announcement strip — brand gradient (pattern borrowed from Medimela's top banner) --}}
    <div class="s-strip">
        <div class="s-wrap">
            <span>
                @if ($freeOver)
                    <i class="fas fa-truck"></i>{{ __('Free delivery on orders over :amount', ['amount' => $store->money($freeOver)]) }}
                @else
                    <i class="fas fa-truck"></i>{{ __('UK delivery') }}
                @endif
            </span>
            <span class="s-strip-links s-hide-sm">
                @if ($store->contact_email)<a href="mailto:{{ $store->contact_email }}"><i class="far fa-envelope"></i>{{ $store->contact_email }}</a>@endif
                @if ($store->phone)<a href="tel:{{ preg_replace('/\s+/', '', $store->phone) }}"><i class="fas fa-phone"></i>{{ $store->phone }}</a>@endif
            </span>
        </div>
    </div>

    <header class="s-header">
        <div class="s-wrap">
            <a href="{{ route('shop.home', $store->slug) }}" class="s-brand" wire:navigate>
                <img src="{{ $store->logoUrl() ?: asset('images/brand/caretrust-mark.png') }}" alt="">
                <span>
                    <span class="s-brand-name">{{ $store->name }}</span>
                    <span class="s-brand-sub">{{ $store->tagline ?: __('Care & medical supplies') }}</span>
                </span>
            </a>

            {{-- Category-scoped search pill (Medimela) --}}
            <form class="s-search" action="{{ route('shop.catalogue', $store->slug) }}" method="get" role="search">
                @if ($categoriesNav->isNotEmpty())
                    <label for="s-qc" class="sr-only">{{ __('Category') }}</label>
                    <select id="s-qc" name="category" class="s-search-cat">
                        <option value="">{{ __('All categories') }}</option>
                        @foreach ($categoriesNav as $cat)
                            <option value="{{ $cat->slug }}" @selected($currentCategory === $cat->slug)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                @endif
                <label for="s-q" class="sr-only">{{ __('Search products') }}</label>
                <input id="s-q" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Search gloves, aprons, first aid…') }}" autocomplete="off">
                <button class="s-search-go" type="submit" aria-label="{{ __('Search') }}"><i class="fas fa-search"></i></button>
            </form>

            <div class="s-tools">
                <a href="{{ route($shopCustomer ? 'shop.account' : 'shop.login', $store->slug) }}" class="s-tool s-account" wire:navigate>
                    <i class="far fa-user"></i>
                    <span class="s-tool-label">
                        <small>{{ $shopCustomer ? __('Hello, :name', ['name' => $shopCustomer->first_name]) : __('Hello, sign in') }}</small>
                        <b>{{ __('My account') }}</b>
                    </span>
                </a>
                @livewire(\App\Livewire\Shop\Storefront\CartDrawerComponent::class, ['storeId' => $store->id], key('cart-drawer'))
            </div>
        </div>
    </header>

    @if ($categoriesNav->isNotEmpty())
        <nav class="s-rail" aria-label="{{ __('Categories') }}">
            <div class="s-wrap">
                {{-- "Browse categories" button + dropdown (Medimela) --}}
                <div class="s-browse" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                    <button type="button" class="s-browse-btn" @click="open = ! open" :aria-expanded="open">
                        <i class="fas fa-bars"></i><span>{{ __('Browse categories') }}</span><i class="fas fa-chevron-down s-browse-caret" :class="open && 'is-open'"></i>
                    </button>
                    <div class="s-browse-menu" x-show="open" x-transition.opacity.duration.150ms x-cloak>
                        @foreach ($categoriesNav as $cat)
                            <a href="{{ route('shop.category', [$store->slug, $cat->slug]) }}" wire:navigate @click="open = false">
                                <i class="{{ $cat->icon ?: \App\Models\Shop\ShopCategory::guessIcon($cat->name) }}"></i>{{ $cat->name }}
                            </a>
                        @endforeach
                        <a href="{{ route('shop.catalogue', $store->slug) }}" wire:navigate class="s-browse-all" @click="open = false">{{ __('View all products') }} <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>

                <div class="s-rail-links">
                    <a href="{{ route('shop.catalogue', $store->slug) }}" wire:navigate @class(['is-active' => request()->routeIs('shop.catalogue') && ! $currentCategory && ! request('q') && ! request('deals')])>{{ __('All products') }}</a>
                    @foreach ($categoriesNav->take(5) as $cat)
                        <a href="{{ route('shop.category', [$store->slug, $cat->slug]) }}" wire:navigate @class(['is-active' => $currentCategory === $cat->slug])>{{ $cat->name }}</a>
                    @endforeach
                </div>

                <a href="{{ route('shop.catalogue', [$store->slug, 'deals' => 1]) }}" wire:navigate class="s-rail-deal"><i class="fas fa-tags"></i>{{ __('Bulk-buy deals') }}</a>
            </div>
        </nav>
    @endif

    <main id="main">
        @foreach (['success' => 'ok', 'warning' => 'warn', 'error' => 'danger'] as $key => $tone)
            @if (session($key))
                <div class="s-wrap s-mt">
                    <div class="s-notice s-notice-{{ $tone }}" role="status">
                        <i class="fas {{ $tone === 'ok' ? 'fa-check-circle' : 'fa-exclamation-circle' }}"></i>
                        <span>{{ session($key) }}</span>
                    </div>
                </div>
            @endif
        @endforeach

        {{ $slot }}
    </main>

    <footer class="s-footer">
        <div class="s-wrap">
            <div>
                <div class="s-footer-brand">
                    <img src="{{ $store->logoUrl() ?: asset('images/brand/caretrust-mark.png') }}" alt="">
                    <h3>{{ $store->name }}</h3>
                </div>
                @if ($store->address)<p style="margin:0 0 8px;white-space:pre-line">{{ $store->address }}</p>@endif
                @if ($store->contact_email)<p style="margin:0"><a href="mailto:{{ $store->contact_email }}">{{ $store->contact_email }}</a></p>@endif
                @if ($store->phone)<p style="margin:0"><a href="tel:{{ preg_replace('/\s+/', '', $store->phone) }}">{{ $store->phone }}</a></p>@endif
                <div class="s-pay-icons" aria-label="{{ __('Ways to pay') }}">
                    @foreach (\App\Services\Shop\Payments\PaymentManager::available($store) as $gateway)
                        <i class="{{ $gateway->icon() }}" title="{{ $gateway->label($store) }}"></i>
                    @endforeach
                </div>
            </div>
            <div>
                <h3>{{ __('Shop') }}</h3>
                <ul>
                    <li><a href="{{ route('shop.catalogue', $store->slug) }}" wire:navigate>{{ __('All products') }}</a></li>
                    <li><a href="{{ route('shop.catalogue', [$store->slug, 'deals' => 1]) }}" wire:navigate>{{ __('Bulk-buy savings') }}</a></li>
                    <li><a href="{{ route('shop.cart', $store->slug) }}" wire:navigate>{{ __('Basket') }}</a></li>
                    <li><a href="{{ route($shopCustomer ? 'shop.account' : 'shop.login', $store->slug) }}" wire:navigate>{{ __('Your account') }}</a></li>
                </ul>
            </div>
            <div>
                <h3>{{ __('Help') }}</h3>
                <ul>
                    @foreach (\App\Livewire\Shop\Storefront\PageComponent::PAGES as $key => $meta)
                        @if (filled($store->{$meta['field']}))
                            <li><a href="{{ route('shop.page', [$store->slug, $key]) }}" wire:navigate>{{ __($meta['title']) }}</a></li>
                        @endif
                    @endforeach
                </ul>
            </div>
        </div>
        <div class="s-footer-legal">
            <div class="s-wrap">
                <span>© {{ now()->year }} {{ $store->name }}@if ($store->company_number) — {{ __('Company no. :n', ['n' => $store->company_number]) }}@endif @if ($store->vat_number) — {{ __('VAT no. :n', ['n' => $store->vat_number]) }}@endif</span>
                <span>{{ $store->prices_include_vat ? __('All prices include VAT where applicable.') : __('Prices exclude VAT.') }}</span>
            </div>
        </div>
    </footer>

    {{-- Toasts from $this->dispatch('toast', message: ..., type: ...) --}}
    <div class="s-toasts" x-data @toast.window="$store.toast.push($event.detail.message, $event.detail.type, $event.detail.timeout ?? 4500)" aria-live="polite">
        <template x-for="t in $store.toast.items" :key="t.id">
            <div class="s-toast" :class="'is-' + (t.type || 'success')">
                <i class="fas" :class="t.type === 'success' || !t.type ? 'fa-check-circle' : 'fa-exclamation-circle'"></i>
                <span x-text="t.message"></span>
                <button type="button" @click="$store.toast.dismiss(t.id)" aria-label="{{ __('Dismiss') }}"><i class="fas fa-times"></i></button>
            </div>
        </template>
    </div>

    @livewireScripts
</body>
</html>
