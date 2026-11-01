<div>
    <x-page-header title="{{ __('Shop settings') }}" subtitle="{{ __('Details, branding, payments and policies') }}" icon="ik ik-settings"
                   :breadcrumbs="['Shop' => route('shop.admin.dashboard'), 'Settings' => null]">
        <x-button variant="outline" href="{{ $storefrontUrl }}" target="_blank"><i class="ik ik-external-link"></i>{{ __('View shop') }}</x-button>
    </x-page-header>

    {{-- Open / closed --}}
    <div @class([
        'mb-6 flex flex-col gap-3 rounded-2xl border p-5 sm:flex-row sm:items-center sm:justify-between',
        'border-green-200 bg-green-50' => $form['is_live'],
        'border-amber-200 bg-amber-50' => ! $form['is_live'],
    ])>
        <div class="flex items-center gap-3">
            <span @class([
                'flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-white',
                'bg-green-500' => $form['is_live'],
                'bg-amber-500' => ! $form['is_live'],
            ])><i class="ik {{ $form['is_live'] ? 'ik-check' : 'ik-lock' }}"></i></span>
            <div>
                <p class="font-semibold text-gray-800">{{ $form['is_live'] ? __('Your shop is open') : __('Your shop is closed to the public') }}</p>
                <p class="text-sm text-gray-600">
                    {{ $form['is_live'] ? __('Anyone can browse and buy at :url', ['url' => $storefrontUrl]) : __('Only signed-in staff can preview it. Open it when products, delivery and payments are ready.') }}
                </p>
            </div>
        </div>
        @if ($form['is_live'])
            <x-button variant="outline" wire:click="setLive(false)" wire:confirm="{{ __('Close the shop? Customers won’t be able to see it or order.') }}">{{ __('Close shop') }}</x-button>
        @else
            <x-button variant="success" wire:click="setLive(true)"><i class="ik ik-unlock"></i>{{ __('Open shop') }}</x-button>
        @endif
    </div>

    <div class="mb-5 flex gap-1 overflow-x-auto border-b border-gray-200">
        @foreach (['store' => __('Shop details'), 'payments' => __('Payments'), 'policies' => __('Policy pages')] as $key => $label)
            <button type="button" wire:click="$set('tab', '{{ $key }}')" @class([
                '-mb-px shrink-0 border-b-2 px-4 py-2.5 text-sm font-medium transition',
                'border-primary-500 text-primary-600' => $tab === $key,
                'border-transparent text-gray-500 hover:text-gray-700' => $tab !== $key,
            ])>{{ $label }}</button>
        @endforeach
    </div>

    @if ($tab === 'store')
        <form wire:submit="saveStore" class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <div class="space-y-5 lg:col-span-2">
                <x-card title="{{ __('Shop details') }}" icon="ik ik-shopping-bag">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-form.input name="form.name" label="{{ __('Shop name') }}" wire:model="form.name" required />
                        <x-form.input name="form.slug" :label="__('Short name in links')" wire:model="form.slug" :hint="$form['domain'] ? __('Used on the care site: :u', ['u' => url('/store').'/'.$form['slug']]) : url('/store').'/'.$form['slug']" />
                        {{-- Batch 43b: the shop's own address --}}
                        <x-form.input name="form.domain" :label="__('Shop web address (optional)')" wire:model="form.domain" placeholder="shop.yourdomain.co.uk"
                                      :hint="__('Point this address at the same server as the care app (same public folder, with an SSL certificate). Customers then shop at it directly, and old /store links redirect there.')" />
                        <div class="sm:col-span-2"><x-form.input name="form.tagline" label="{{ __('Tagline') }}" wire:model="form.tagline" placeholder="{{ __('e.g. Gloves, PPE and first aid for care teams') }}" hint="{{ __('The headline on your shop’s home page.') }}" /></div>
                        <x-form.input type="email" name="form.contact_email" label="{{ __('Customer contact email') }}" wire:model="form.contact_email" />
                        <x-form.input type="email" name="form.orders_email" label="{{ __('Send new-order alerts to') }}" wire:model="form.orders_email" />
                        <x-form.input name="form.phone" label="{{ __('Phone') }}" wire:model="form.phone" />
                        <x-form.input name="form.order_prefix" label="{{ __('Order number prefix') }}" wire:model="form.order_prefix" hint="{{ __('Orders look like :example', ['example' => strtoupper($form['order_prefix'] ?: 'ORD').'-1001']) }}" />
                        <div class="sm:col-span-2"><x-form.textarea name="form.address" label="{{ __('Business / collection address') }}" wire:model="form.address" rows="3" /></div>
                        <x-form.input name="form.company_number" label="{{ __('Company number') }}" wire:model="form.company_number" />
                        <x-form.input name="form.vat_number" label="{{ __('VAT number') }}" wire:model="form.vat_number" />
                    </div>
                </x-card>

                <x-card title="{{ __('Prices and VAT') }}" icon="ik ik-percent">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-form.select name="form.prices_include_vat" label="{{ __('Product prices are entered') }}" wire:model="form.prices_include_vat">
                            <option value="1">{{ __('Including VAT (usual for the public)') }}</option>
                            <option value="0">{{ __('Excluding VAT (trade)') }}</option>
                        </x-form.select>
                        <x-form.select name="form.default_vat_rate" label="{{ __('Default VAT rate') }}" wire:model="form.default_vat_rate">
                            <option value="20">20%</option>
                            <option value="5">5%</option>
                            <option value="0">0%</option>
                        </x-form.select>
                    </div>
                    <p class="mt-3 text-xs text-gray-400">{{ __('The default applies to new products and to delivery charges. Each product can have its own rate.') }}</p>
                </x-card>
            </div>

            <div class="space-y-5">
                <x-card title="{{ __('Branding') }}" icon="ik ik-droplet">
                    <div class="space-y-4">
                        <div>
                            <p class="mb-1.5 text-sm font-medium text-gray-700">{{ __('Logo') }}</p>
                            @if ($store->logoUrl())<img src="{{ $store->logoUrl() }}" alt="" class="mb-2 h-14 w-auto rounded border border-gray-100 bg-white p-1">@endif
                            <input type="file" wire:model="logo" accept="image/*" class="text-sm text-gray-600">
                            @error('logo')<p class="mt-1 text-xs text-accent-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700" for="brand_color">{{ __('Main colour') }}</label>
                                <input id="brand_color" type="color" wire:model.live="form.brand_color" class="h-10 w-full cursor-pointer rounded-lg border border-gray-200">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700" for="accent_color">{{ __('Button colour') }}</label>
                                <input id="accent_color" type="color" wire:model.live="form.accent_color" class="h-10 w-full cursor-pointer rounded-lg border border-gray-200">
                            </div>
                        </div>
                        <div class="overflow-hidden rounded-xl border border-gray-100">
                            <div class="px-4 py-3 text-sm font-semibold text-white" style="background: {{ $form['brand_color'] }}">{{ $form['name'] }}</div>
                            <div class="bg-white p-4"><span class="inline-block rounded-lg px-4 py-2 text-sm font-semibold text-white" style="background: {{ $form['accent_color'] }}">{{ __('Add to basket') }}</span></div>
                        </div>
                    </div>
                </x-card>
                <x-button type="submit" class="w-full">{{ __('Save shop details') }}</x-button>
            </div>
        </form>
    @elseif ($tab === 'payments')
        <form wire:submit="savePayments" class="space-y-5">
            @foreach ($gateways as $code => $gateway)
                <x-card wire:key="gw-{{ $code }}">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary-500/10 text-xl text-primary-600"><i class="{{ $gateway->icon() }}"></i></span>
                            <div>
                                <h5 class="font-semibold text-gray-800">{{ $gateway->label($store) }}</h5>
                                <p class="text-sm text-gray-500">{{ $gateway->isOnline() ? __('Customers pay online at checkout.') : __('Customers pay later; you record the payment on the order.') }}</p>
                            </div>
                        </div>
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                            <input type="checkbox" wire:model.live="payments.{{ $code }}.enabled" class="h-4 w-4 rounded border-gray-300 text-primary-600">
                            {{ __('Offer at checkout') }}
                        </label>
                    </div>

                    @if (! empty($payments[$code]['enabled']) && count($gateway->settingsFields()))
                        <div class="mt-5 grid grid-cols-1 gap-4 border-t border-gray-100 pt-5 md:grid-cols-2">
                            @foreach ($gateway->settingsFields() as $field)
                                @php $fname = "payments.$code.{$field['key']}"; @endphp
                                <div @class(['md:col-span-2' => $field['type'] === 'textarea'])>
                                    @if ($field['type'] === 'select')
                                        <x-form.select name="{{ $fname }}" label="{{ __($field['label']) }}" wire:model="{{ $fname }}">
                                            @foreach ($field['options'] as $val => $lbl)<option value="{{ $val }}">{{ __($lbl) }}</option>@endforeach
                                        </x-form.select>
                                    @elseif ($field['type'] === 'textarea')
                                        <x-form.textarea name="{{ $fname }}" label="{{ __($field['label']) }}" wire:model="{{ $fname }}" rows="2" hint="{{ isset($field['hint']) ? __($field['hint']) : '' }}" />
                                    @else
                                        <x-form.input type="{{ $field['type'] === 'password' ? 'password' : 'text' }}" name="{{ $fname }}" label="{{ __($field['label']) }}" wire:model="{{ $fname }}"
                                                      autocomplete="off" hint="{{ isset($field['hint']) ? __($field['hint']) : '' }}" />
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @if ($code === 'stripe')
                            <div class="mt-4 rounded-xl bg-gray-50 p-4 text-sm text-gray-600">
                                <p class="font-medium text-gray-700">{{ __('Stripe webhook URL') }}</p>
                                <p class="mt-1 break-all font-mono text-xs">{{ $webhookUrl }}</p>
                                <p class="mt-2 text-xs text-gray-500">{{ __('In Stripe: Developers → Webhooks → Add endpoint, choose the event checkout.session.completed, then paste the signing secret above. Apple Pay and Google Pay are switched on in Stripe → Settings → Payment methods.') }}</p>
                            </div>
                        @endif
                    @endif
                </x-card>
            @endforeach
            <p class="text-xs text-gray-400">{{ __('Keys and secrets are stored encrypted. Saved secrets show as :mask — leave them as they are to keep the current value.', ['mask' => $mask]) }}</p>
            <x-button type="submit">{{ __('Save payment settings') }}</x-button>
        </form>
    @else
        <form wire:submit="savePolicies" class="space-y-5">
            <p class="text-sm text-gray-500">{{ __('Each page appears in your shop’s footer once it has text. UK distance-selling rules expect clear terms, delivery and returns information (including the 14-day cancellation right) before customers buy.') }}</p>
            @foreach (['about' => __('About us'), 'delivery_info' => __('Delivery'), 'returns_policy' => __('Returns and refunds'), 'terms' => __('Terms of sale'), 'privacy' => __('Privacy notice')] as $key => $label)
                <x-card title="{{ $label }}">
                    <x-form.textarea name="form.{{ $key }}" wire:model="form.{{ $key }}" rows="{{ in_array($key, ['terms', 'privacy']) ? 10 : 6 }}" />
                </x-card>
            @endforeach
            <x-button type="submit">{{ __('Save policy pages') }}</x-button>
        </form>
    @endif
</div>
