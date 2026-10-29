<div class="s-wrap">
    @php
        $method = $totals['delivery_method'];
        $isCollection = (bool) $method?->is_collection;
    @endphp

    <nav class="s-crumbs" aria-label="{{ __('Breadcrumb') }}">
        <a href="{{ route('shop.cart', $store->slug) }}" wire:navigate>{{ __('Basket') }}</a>
        <i class="fas fa-chevron-right" aria-hidden="true"></i>
        <span>{{ __('Checkout') }}</span>
    </nav>
    <h1 class="s-page-title">{{ __('Checkout') }}</h1>

    <form wire:submit="placeOrder" class="s-two-col" novalidate>
        <div class="s-stack">
            @error('checkout')
                <div class="s-notice s-notice-danger" role="alert"><i class="fas fa-exclamation-circle"></i><span>{{ $message }}</span></div>
            @enderror
            @foreach ($totals['problems'] as $problem)
                <div class="s-notice s-notice-warn"><i class="fas fa-exclamation-triangle"></i><span>{{ $problem }}</span></div>
            @endforeach
            @if ($errors->any() && ! $errors->has('checkout') && ! $errors->has('discountCode'))
                <div class="s-notice s-notice-danger" role="alert"><i class="fas fa-exclamation-circle"></i><span>{{ __('Please check the highlighted details below.') }}</span></div>
            @endif

            {{-- 1. Your details --}}
            <section class="s-panel">
                <div class="s-step-head">
                    <span class="s-step-num">1</span>
                    <h2>{{ __('Your details') }}</h2>
                    @unless ($customer)
                        <span style="margin-left:auto" class="s-small">{{ __('Have an account?') }} <a class="s-btn-link s-btn" href="{{ route('shop.login', $store->slug) }}" wire:navigate>{{ __('Sign in') }}</a></span>
                    @endunless
                </div>
                <div class="s-step-body">
                    <div class="s-grid-2">
                        <div class="s-field">
                            <label for="email">{{ __('Email') }}</label>
                            <input id="email" type="email" class="s-input @error('email') is-invalid @enderror" wire:model.live.blur="email" autocomplete="email" @if ($customer) readonly @endif>
                            @error('email')<span class="s-error">{{ $message }}</span>@else<span class="s-hint">{{ __('For your receipt and order updates.') }}</span>@enderror
                        </div>
                        <div class="s-field">
                            <label for="phone">{{ __('Phone') }}</label>
                            <input id="phone" type="tel" class="s-input @error('phone') is-invalid @enderror" wire:model.live.blur="phone" autocomplete="tel">
                            @error('phone')<span class="s-error">{{ $message }}</span>@else<span class="s-hint">{{ __('In case the courier needs you.') }}</span>@enderror
                        </div>
                    </div>

                    @if ($addresses->isNotEmpty())
                        <div class="s-field">
                            <span class="s-label">{{ __('Saved addresses') }}</span>
                            <div class="s-saved-addr">
                                @foreach ($addresses as $address)
                                    <button type="button" wire:click="useSavedAddress('{{ $address->id }}')" @class(['is-active' => $savedAddressId === $address->id])>
                                        <strong>{{ $address->label ?: $address->name }}</strong><br>
                                        <span class="s-muted">{{ $address->line1 }}, {{ $address->postcode }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="s-grid-2">
                        <div class="s-field">
                            <label for="first_name">{{ __('First name') }}</label>
                            <input id="first_name" class="s-input @error('billing.first_name') is-invalid @enderror" wire:model.live.blur="billing.first_name" autocomplete="given-name">
                            @error('billing.first_name')<span class="s-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="s-field">
                            <label for="last_name">{{ __('Last name') }}</label>
                            <input id="last_name" class="s-input @error('billing.last_name') is-invalid @enderror" wire:model.live.blur="billing.last_name" autocomplete="family-name">
                            @error('billing.last_name')<span class="s-error">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="s-field">
                        <label for="company">{{ __('Organisation') }} <span class="s-opt">{{ __('(optional)') }}</span></label>
                        <input id="company" class="s-input" wire:model.live.blur="billing.company" autocomplete="organization" placeholder="{{ __('Care home or company name') }}">
                    </div>
                    <div class="s-field">
                        <label for="line1">{{ $isCollection ? __('Billing address') : __('Address') }}</label>
                        <input id="line1" class="s-input @error('billing.line1') is-invalid @enderror" wire:model.live.blur="billing.line1" autocomplete="address-line1">
                        @error('billing.line1')<span class="s-error">{{ $message }}</span>@enderror
                        <input class="s-input" wire:model.live.blur="billing.line2" autocomplete="address-line2" aria-label="{{ __('Address line 2') }}" placeholder="{{ __('Flat, building or unit (optional)') }}">
                    </div>
                    <div class="s-grid-3">
                        <div class="s-field">
                            <label for="city">{{ __('Town or city') }}</label>
                            <input id="city" class="s-input @error('billing.city') is-invalid @enderror" wire:model.live.blur="billing.city" autocomplete="address-level2">
                            @error('billing.city')<span class="s-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="s-field">
                            <label for="county">{{ __('County') }} <span class="s-opt">{{ __('(optional)') }}</span></label>
                            <input id="county" class="s-input" wire:model.live.blur="billing.county" autocomplete="address-level1">
                        </div>
                        <div class="s-field">
                            <label for="postcode">{{ __('Postcode') }}</label>
                            <input id="postcode" class="s-input @error('billing.postcode') is-invalid @enderror" wire:model.live.blur="billing.postcode" autocomplete="postal-code" style="text-transform:uppercase">
                            @error('billing.postcode')<span class="s-error">{{ $message }}</span>@enderror
                        </div>
                    </div>
                </div>
            </section>

            {{-- 2. Delivery --}}
            <section class="s-panel">
                <div class="s-step-head"><span class="s-step-num">2</span><h2>{{ __('Delivery') }}</h2></div>
                <div class="s-step-body">
                    @forelse ($deliveryMethods as $dm)
                        @php $price = $dm->priceFor($totals['subtotal']); @endphp
                        <label @class(['s-choice', 'is-selected' => $deliveryMethodId === $dm->id])>
                            <input type="radio" name="delivery" value="{{ $dm->id }}" wire:model.live="deliveryMethodId">
                            <span class="s-choice-main">
                                <span class="s-choice-title">
                                    <span>{{ $dm->name }}</span>
                                    <span class="num">{{ $price > 0 ? $store->money($price) : __('Free') }}</span>
                                </span>
                                <span class="s-choice-desc">
                                    {{ $dm->description }}
                                    @if ($dm->eta()) {{ $dm->description ? '— ' : '' }}{{ $dm->eta() }}@endif
                                    @if ($dm->free_over !== null && $price > 0) — {{ __('free over :amount', ['amount' => $store->money($dm->free_over)]) }}@endif
                                </span>
                            </span>
                        </label>
                    @empty
                        <p class="s-muted">{{ __('We’ll contact you to arrange delivery.') }}</p>
                    @endforelse
                    @error('deliveryMethodId')<span class="s-error">{{ $message }}</span>@enderror

                    @unless ($isCollection)
                        <label class="s-check">
                            <input type="checkbox" wire:model.live="shipToDifferent">
                            {{ __('Deliver to a different address') }}
                        </label>
                        @if ($shipToDifferent)
                            <div class="s-stack" style="gap:14px">
                                <div class="s-grid-2">
                                    <div class="s-field">
                                        <label for="ship_name">{{ __('Recipient name') }}</label>
                                        <input id="ship_name" class="s-input @error('shipping.name') is-invalid @enderror" wire:model.live.blur="shipping.name">
                                        @error('shipping.name')<span class="s-error">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="s-field">
                                        <label for="ship_company">{{ __('Organisation') }} <span class="s-opt">{{ __('(optional)') }}</span></label>
                                        <input id="ship_company" class="s-input" wire:model.live.blur="shipping.company">
                                    </div>
                                </div>
                                <div class="s-field">
                                    <label for="ship_line1">{{ __('Delivery address') }}</label>
                                    <input id="ship_line1" class="s-input @error('shipping.line1') is-invalid @enderror" wire:model.live.blur="shipping.line1">
                                    @error('shipping.line1')<span class="s-error">{{ $message }}</span>@enderror
                                    <input class="s-input" wire:model.live.blur="shipping.line2" aria-label="{{ __('Delivery address line 2') }}" placeholder="{{ __('Flat, building or unit (optional)') }}">
                                </div>
                                <div class="s-grid-3">
                                    <div class="s-field">
                                        <label for="ship_city">{{ __('Town or city') }}</label>
                                        <input id="ship_city" class="s-input @error('shipping.city') is-invalid @enderror" wire:model.live.blur="shipping.city">
                                        @error('shipping.city')<span class="s-error">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="s-field">
                                        <label for="ship_county">{{ __('County') }} <span class="s-opt">{{ __('(optional)') }}</span></label>
                                        <input id="ship_county" class="s-input" wire:model.live.blur="shipping.county">
                                    </div>
                                    <div class="s-field">
                                        <label for="ship_postcode">{{ __('Postcode') }}</label>
                                        <input id="ship_postcode" class="s-input @error('shipping.postcode') is-invalid @enderror" wire:model.live.blur="shipping.postcode" style="text-transform:uppercase">
                                        @error('shipping.postcode')<span class="s-error">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="s-field">
                                    <label for="ship_phone">{{ __('Recipient phone') }} <span class="s-opt">{{ __('(optional)') }}</span></label>
                                    <input id="ship_phone" type="tel" class="s-input" wire:model.live.blur="shipping.phone">
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="s-notice s-notice-info"><i class="fas fa-store"></i><span>{{ __('We’ll email you when your order is ready to collect.') }} @if ($store->address){{ __('Collect from: :address', ['address' => str_replace("\n", ', ', $store->address)]) }}@endif</span></div>
                    @endunless

                    <div class="s-field">
                        <label for="notes">{{ __('Delivery notes') }} <span class="s-opt">{{ __('(optional)') }}</span></label>
                        <textarea id="notes" class="s-textarea" wire:model.live.blur="notes" placeholder="{{ __('e.g. Leave with reception, deliveries before 3pm') }}"></textarea>
                    </div>
                </div>
            </section>

            {{-- 3. Payment --}}
            <section class="s-panel">
                <div class="s-step-head"><span class="s-step-num">3</span><h2>{{ __('Payment') }}</h2></div>
                <div class="s-step-body">
                    @forelse ($gateways as $code => $gateway)
                        <label @class(['s-choice', 'is-selected' => $paymentMethod === $code])>
                            <input type="radio" name="payment" value="{{ $code }}" wire:model.live="paymentMethod">
                            <span class="s-choice-main">
                                <span class="s-choice-title"><span>{{ $gateway->label($store) }}</span></span>
                                <span class="s-choice-desc">{{ $gateway->description($store) }}</span>
                            </span>
                            <i class="{{ $gateway->icon() }}" aria-hidden="true"></i>
                        </label>
                    @empty
                        <div class="s-notice s-notice-warn"><i class="fas fa-exclamation-triangle"></i><span>{{ __('Online ordering isn’t set up yet. Please contact us to order.') }}</span></div>
                    @endforelse
                    @error('paymentMethod')<span class="s-error">{{ $message }}</span>@enderror

                    @unless ($customer)
                        <label class="s-check">
                            <input type="checkbox" wire:model.live="createAccount">
                            <span>{{ __('Save my details for next time') }} <span class="s-muted">{{ __('— creates an account so you can reorder and track orders') }}</span></span>
                        </label>
                        @if ($createAccount)
                            <div class="s-field">
                                <label for="password">{{ __('Choose a password') }}</label>
                                <input id="password" type="password" class="s-input @error('password') is-invalid @enderror" wire:model.live.blur="password" autocomplete="new-password">
                                @error('password')<span class="s-error">{{ $message }}</span>@else<span class="s-hint">{{ __('At least 8 characters.') }}</span>@enderror
                            </div>
                        @endif
                    @endunless

                    <label class="s-check">
                        <input type="checkbox" wire:model="marketingOptIn">
                        <span>{{ __('Email me about offers and new products. You can unsubscribe at any time.') }}</span>
                    </label>
                    <label class="s-check">
                        <input type="checkbox" wire:model="acceptTerms">
                        <span>
                            {{ __('I agree to the') }}
                            @if (filled($store->terms))<a href="{{ route('shop.page', [$store->slug, 'terms']) }}" target="_blank" class="s-btn-link s-btn">{{ __('terms of sale') }}</a>@else{{ __('terms of sale') }}@endif
                            @if (filled($store->returns_policy)) {{ __('and') }} <a href="{{ route('shop.page', [$store->slug, 'returns']) }}" target="_blank" class="s-btn-link s-btn">{{ __('returns policy') }}</a>@endif
                        </span>
                    </label>
                    @error('acceptTerms')<span class="s-error">{{ $message }}</span>@enderror
                </div>
            </section>
        </div>

        <aside class="s-sticky">
            <div class="s-panel s-panel-pad s-stack">
                <h2>{{ __('Your order') }}</h2>
                <div class="s-mini-lines">
                    @foreach ($totals['lines'] as $line)
                        <div class="s-mini-line">
                            <span>{{ $line['qty'] }} × {{ $line['name'] }}</span>
                            <span class="num">{{ $store->money($line['line_total']) }}</span>
                        </div>
                    @endforeach
                </div>
                <hr class="s-hr">
                @include('livewire.shop.storefront.partials.summary', ['totals' => $totals, 'showDiscountForm' => true])

                @php $selected = $gateways[$paymentMethod] ?? null; @endphp
                <button type="submit" class="s-btn s-btn-primary s-btn-lg s-btn-block" wire:loading.attr="disabled" wire:target="placeOrder" @disabled(empty($gateways))>
                    <i class="fas fa-lock"></i>
                    <span wire:loading.remove wire:target="placeOrder">
                        {{ $selected && $selected->isOnline() ? __('Continue to secure payment') : __('Place order') }}
                    </span>
                    <span wire:loading wire:target="placeOrder">{{ __('Placing your order…') }}</span>
                </button>
                <p class="s-small s-muted" style="margin:0;text-align:center">
                    {{ $selected && $selected->isOnline()
                        ? __('You’ll pay :amount on the next page.', ['amount' => $store->money($totals['total'])])
                        : __('You won’t be charged now.') }}
                </p>
            </div>
        </aside>
    </form>
</div>
