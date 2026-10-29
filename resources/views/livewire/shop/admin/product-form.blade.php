<div>
    @php
        $cellBase = 'rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-sm text-gray-700 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100';
        $cellErrBase = 'rounded-lg border border-accent-400 bg-white px-2.5 py-1.5 text-sm text-gray-700 outline-none';
        $cell = 'w-full '.$cellBase;
        $cellErr = 'w-full '.$cellErrBase;
        $priceLabel = $store->prices_include_vat ? __('inc. VAT') : __('ex. VAT');
        $imageOptions = $product?->images ?? collect();
    @endphp

    <x-page-header title="{{ $product ? __('Edit product') : __('New product') }}"
                   subtitle="{{ $product ? $product->name : __('Add something to sell in your shop') }}" icon="ik ik-package"
                   :breadcrumbs="['Shop' => route('shop.admin.dashboard'), 'Products' => route('shop.admin.products'), ($product ? __('Edit') : __('New')) => null]">
        <div class="flex flex-wrap items-center gap-2">
            @if ($product)
                <x-button variant="outline" href="{{ route('shop.product', [$store->slug, $product->slug]) }}" target="_blank"><i class="ik ik-external-link"></i>{{ __('View in shop') }}</x-button>
            @endif
            <x-button variant="ghost" href="{{ route('shop.admin.products') }}" wire:navigate>{{ __('Cancel') }}</x-button>
            <x-button type="submit" form="product-form" wire:loading.attr="disabled" wire:target="save,uploads"><i class="ik ik-check"></i>{{ __('Save product') }}</x-button>
        </div>
    </x-page-header>

    @if ($errors->any())
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-accent-400 bg-accent-500/5 px-4 py-3 text-sm text-accent-600" role="alert">
            <i class="ik ik-alert-circle mt-0.5"></i>
            <span>{{ trans_choice('Please fix :count thing before saving.|Please fix :count things before saving.', $errors->count(), ['count' => $errors->count()]) }} {{ $errors->first() }}</span>
        </div>
    @endif

    <form wire:submit="save" id="product-form" class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">

            {{-- ================= Details ================= --}}
            <x-card title="{{ __('Details') }}" icon="ik ik-file-text">
                <div class="space-y-4">
                    <x-form.input name="form.name" label="{{ __('Name') }}" wire:model="form.name" required placeholder="{{ __('e.g. Blue Nitrile Examination Gloves, Powder Free') }}" />
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <x-form.input name="form.sku" label="{{ $hasVariants ? __('Product code (SKU)') : __('Item code (SKU)') }}" wire:model="form.sku"
                                      hint="{{ $hasVariants ? __('Each size or option gets its own code below.') : null }}" />
                        <x-form.select name="form.category_id" label="{{ __('Category') }}" wire:model="form.category_id">
                            <option value="">{{ __('No category') }}</option>
                            @foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                        </x-form.select>
                        @unless ($hasVariants)
                            <x-form.input name="form.barcode" label="{{ __('Barcode') }}" wire:model="form.barcode" icon="ik ik-maximize" />
                        @endunless
                        <x-form.input name="form.brand" label="{{ __('Brand') }}" wire:model="form.brand" />
                    </div>
                    <x-form.input name="form.summary" label="{{ __('Short summary') }}" wire:model="form.summary" hint="{{ __('One line shown next to the price.') }}" />
                    <x-form.textarea name="form.description" label="{{ __('Description') }}" wire:model="form.description" rows="5" hint="{{ __('Basic HTML allowed (paragraphs, lists, bold).') }}" />
                </div>
            </x-card>

            {{-- ================= Price & VAT ================= --}}
            <x-card title="{{ __('Price and VAT') }}" icon="ik ik-tag">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <x-form.input type="number" step="0.01" min="0" name="form.price" wire:model="form.price" :required="! $hasVariants"
                                      label="{{ $hasVariants ? __('Default price (:vat)', ['vat' => $priceLabel]) : __('Selling price (:vat)', ['vat' => $priceLabel]) }}"
                                      hint="{{ $hasVariants ? __('Options with no price of their own sell at this.') : null }}" />
                        <x-form.input type="number" step="0.01" min="0" name="form.compare_at_price" label="{{ __('Was price') }}" wire:model="form.compare_at_price" hint="{{ __('Optional, shown crossed out') }}" />
                        <x-form.select name="form.vat_rate" label="{{ __('VAT rate') }}" wire:model="form.vat_rate">
                            <option value="20">20% {{ __('standard') }}</option>
                            <option value="5">5% {{ __('reduced') }}</option>
                            <option value="0">0% {{ __('zero-rated / exempt') }}</option>
                        </x-form.select>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <x-form.input name="form.unit_name" label="{{ __('Sold by the') }}" wire:model="form.unit_name" list="unit-names" placeholder="{{ __('box') }}"
                                      hint="{{ __('Shown as “£4.20 per box”.') }}" />
                        <x-form.input name="form.pack_size" label="{{ __('Pack size') }}" wire:model="form.pack_size" placeholder="{{ __('e.g. 100 gloves') }}" />
                        <x-form.input type="number" step="0.01" min="0" name="form.cost_price" label="{{ __('Your cost (staff only)') }}" wire:model="form.cost_price" />
                    </div>
                    <datalist id="unit-names">
                        @foreach (['box', 'case', 'pack', 'pair', 'each', 'roll', 'bottle', 'tub', 'bag', 'carton'] as $u)<option value="{{ $u }}"></option>@endforeach
                    </datalist>

                    <div class="rounded-xl border border-gray-100 p-4">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-gray-700">{{ __('Bulk prices') }}</p>
                                <p class="text-xs text-gray-400">
                                    {{ $hasVariants ? __('Lower price per item when someone buys more. Options on the default price share these, and their quantities count together.') : __('Lower price per item when someone buys more.') }}
                                </p>
                            </div>
                            <x-button size="sm" variant="outline" wire:click="addTier"><i class="ik ik-plus"></i>{{ __('Add break') }}</x-button>
                        </div>
                        @forelse ($tiers as $i => $tier)
                            <div class="mb-2 flex items-start gap-2" wire:key="tier-{{ $i }}">
                                <div class="flex-1"><x-form.input type="number" min="2" name="tiers.{{ $i }}.min_qty" wire:model="tiers.{{ $i }}.min_qty" placeholder="{{ __('Buy at least') }}" /></div>
                                <div class="flex-1"><x-form.input type="number" step="0.01" min="0" name="tiers.{{ $i }}.unit_price" wire:model="tiers.{{ $i }}.unit_price" placeholder="{{ __('Price each') }}" /></div>
                                <button type="button" wire:click="removeTier({{ $i }})" class="mt-2 text-gray-400 hover:text-accent-600" aria-label="{{ __('Remove') }}"><i class="ik ik-trash-2"></i></button>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400">{{ __('No bulk prices — every quantity pays the price above.') }}</p>
                        @endforelse
                    </div>
                </div>
            </x-card>

            {{-- ================= Sizes & options (variants) ================= --}}
            <x-card title="{{ __('Sizes and options') }}" subtitle="{{ __('For gloves in S/M/L/XL, aprons in colours, or a box and a case of the same thing') }}" icon="ik ik-layers">
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-100 p-4 transition hover:bg-gray-50">
                    <input type="checkbox" wire:model.live="hasVariants" class="mt-0.5 h-4 w-4 rounded border-gray-300 text-primary-600">
                    <span>
                        <span class="block text-sm font-medium text-gray-700">{{ __('This product comes in different sizes or options') }}</span>
                        <span class="block text-xs text-gray-400">{{ __('Customers pick from a grid on one product page; each option has its own code, barcode, stock and (if you want) price.') }}</span>
                    </span>
                </label>

                @if ($hasVariants)
                    @if ($product && ! $product->has_variants && $product->track_stock && $product->stock_qty > 0)
                        <div class="mt-4 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            <i class="ik ik-alert-triangle mt-0.5"></i>
                            <span>{{ __('This product has :n in stock as a single item. When you save, enter the opening stock for each option below — the old total is cleared and noted in the stock history.', ['n' => $product->stock_qty]) }}</span>
                        </div>
                    @endif

                    {{-- Options and their values --}}
                    <div class="mt-4 space-y-3">
                        @foreach ($options as $i => $option)
                            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4" wire:key="opt-{{ $i }}">
                                <div class="flex flex-wrap items-end gap-3">
                                    <div class="w-48">
                                        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-gray-500" for="opt-name-{{ $i }}">{{ __('Option :n', ['n' => $i + 1]) }}</label>
                                        <input id="opt-name-{{ $i }}" type="text" wire:model.live.blur="options.{{ $i }}.name" list="option-names" placeholder="{{ __('e.g. Size') }}"
                                               class="{{ $errors->has("options.$i.name") ? $cellErr : $cell }}">
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-gray-500" for="opt-add-{{ $i }}">{{ __('Add values') }}</label>
                                        <div class="flex gap-2">
                                            <input id="opt-add-{{ $i }}" type="text" wire:model="newValues.{{ $i }}" wire:keydown.enter.prevent="addValues({{ $i }})"
                                                   placeholder="{{ __('Type a value and press Enter — or several: S, M, L') }}" class="{{ $cell }}">
                                            <x-button size="sm" variant="outline" wire:click="addValues({{ $i }})">{{ __('Add') }}</x-button>
                                        </div>
                                    </div>
                                    <button type="button" wire:click="removeOption({{ $i }})" wire:confirm="{{ __('Remove the “:name” option? Rows that only differed by it are merged.', ['name' => $option['name'] ?: __('Option :n', ['n' => $i + 1])]) }}"
                                            class="mb-1.5 text-sm text-gray-400 hover:text-accent-600" title="{{ __('Remove option') }}"><i class="ik ik-trash-2"></i></button>
                                </div>

                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    @forelse ($option['values'] as $j => $value)
                                        <span class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white py-1 pl-2.5 pr-1 text-sm text-gray-700" wire:key="val-{{ $i }}-{{ $j }}-{{ md5($value) }}"
                                              x-data="{ editing: false, v: @js($value) }">
                                            <span x-show="! editing" @dblclick="editing = true; $nextTick(() => $refs.inp.select())" title="{{ __('Double-click to rename') }}">{{ $value }}</span>
                                            <input x-show="editing" x-cloak x-ref="inp" x-model="v" class="w-24 rounded border border-primary-300 px-1 text-sm outline-none"
                                                   @keydown.enter.prevent="editing = false; $wire.renameValue({{ $i }}, {{ $j }}, v)" @keydown.escape="editing = false; v = @js($value)"
                                                   @blur="if (editing) { editing = false; $wire.renameValue({{ $i }}, {{ $j }}, v) }">
                                            @if ($j > 0)<button type="button" wire:click="moveValue({{ $i }}, {{ $j }}, -1)" class="px-0.5 text-gray-300 hover:text-gray-600" aria-label="{{ __('Move left') }}"><i class="ik ik-chevron-left"></i></button>@endif
                                            <button type="button" wire:click="removeValue({{ $i }}, {{ $j }})" class="px-0.5 text-gray-300 hover:text-accent-600" aria-label="{{ __('Remove :v', ['v' => $value]) }}"><i class="ik ik-x"></i></button>
                                        </span>
                                    @empty
                                        <span class="text-xs {{ $errors->has("options.$i.values") ? 'text-accent-600' : 'text-gray-400' }}">{{ __('No values yet.') }}</span>
                                    @endforelse
                                </div>

                                <div class="mt-3 flex flex-wrap items-center gap-1.5 text-xs">
                                    <span class="text-gray-400">{{ __('Quick add:') }}</span>
                                    @php
                                        $n = strtolower($option['name']);
                                        $keys = match (true) {
                                            str_contains($n, 'size') => ['letters', 'words', 'gloves', 'paper'],
                                            str_contains($n, 'col') => ['colours'],
                                            str_contains($n, 'pack') => ['packs'],
                                            default => array_keys($presets),
                                        };
                                    @endphp
                                    @foreach ($keys as $key)
                                        <button type="button" wire:click="applyPreset({{ $i }}, '{{ $key }}')" class="rounded-md border border-gray-200 bg-white px-2 py-0.5 text-gray-600 hover:border-primary-300 hover:text-primary-600">{{ $presets[$key]['label'] }}</button>
                                    @endforeach
                                </div>
                                @error("options.$i.name")<p class="mt-2 text-xs text-accent-600">{{ $message }}</p>@enderror
                                @error("options.$i.values")<p class="mt-2 text-xs text-accent-600">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                        <datalist id="option-names">
                            @foreach (['Size', 'Colour', 'Pack', 'Material', 'Style', 'Length', 'Strength', 'Hinge'] as $o)<option value="{{ $o }}"></option>@endforeach
                        </datalist>

                        @if (count($options) < \App\Livewire\Shop\Admin\ProductFormComponent::MAX_OPTIONS)
                            <x-button size="sm" variant="outline" wire:click="addOption"><i class="ik ik-plus"></i>{{ __('Add another option') }}</x-button>
                        @endif
                        @error('options')<p class="text-xs text-accent-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- The grid: one row per combination --}}
                    @if ($variants)
                        <div class="mt-5 rounded-xl border border-gray-100">
                            <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 px-4 py-3">
                                <p class="mr-auto text-sm font-medium text-gray-700">{{ trans_choice(':count option to sell|:count options to sell', count($variants), ['count' => count($variants)]) }}</p>
                                <x-button size="sm" variant="ghost" wire:click="fillSkus" title="{{ __('Fill blank codes from the product code and the values') }}"><i class="ik ik-hash"></i>{{ __('Fill blank codes') }}</x-button>
                                <div class="flex items-center gap-1.5">
                                    <input type="number" step="0.01" min="0" wire:model="bulkPrice" placeholder="{{ __('Price for all') }}" class="{{ $cellBase }} w-28" aria-label="{{ __('Price for every option') }}">
                                    <x-button size="sm" variant="outline" wire:click="applyBulkPrice">{{ __('Apply') }}</x-button>
                                </div>
                            </div>
                            @error('bulkPrice')<p class="px-4 pt-2 text-xs text-accent-600">{{ $message }}</p>@enderror

                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                                            @foreach ($options as $option)<th class="px-3 py-2.5 font-medium">{{ $option['name'] ?: '—' }}</th>@endforeach
                                            <th class="px-3 py-2.5 font-medium">{{ __('Code') }}</th>
                                            <th class="px-3 py-2.5 font-medium">{{ __('Barcode') }}</th>
                                            <th class="px-3 py-2.5 font-medium">{{ __('Price') }}</th>
                                            <th class="px-3 py-2.5 font-medium">{{ $form['track_stock'] ? __('Stock') : '' }}</th>
                                            <th class="px-3 py-2.5 text-center font-medium">{{ __('On sale') }}</th>
                                            <th class="px-3 py-2.5"></th>
                                        </tr>
                                    </thead>
                                    @foreach ($variants as $r => $row)
                                        <tbody class="border-b border-gray-50" wire:key="vr-{{ md5($row['key']) }}" x-data="{ more: {{ collect(['compare_at_price', 'cost_price', 'low_stock_threshold', 'supplier_url', 'tiers.*'])->contains(fn ($f) => $errors->has("variants.$r.$f")) ? 'true' : 'false' }} }">
                                            <tr @class(['hover:bg-gray-50', 'opacity-50' => ! $row['is_active']])>
                                                @foreach ($options as $i => $option)
                                                    <td class="whitespace-nowrap px-3 py-2 {{ $i === 0 ? 'font-medium text-gray-700' : 'text-gray-600' }}">{{ $row['values'][$i] ?? '—' }}</td>
                                                @endforeach
                                                <td class="px-3 py-2"><input type="text" wire:model="variants.{{ $r }}.sku" class="{{ $errors->has("variants.$r.sku") ? $cellErrBase : $cellBase }} w-36 font-mono" aria-label="{{ __('Code') }}"></td>
                                                <td class="px-3 py-2"><input type="text" wire:model="variants.{{ $r }}.barcode" class="{{ $cellBase }} w-32" aria-label="{{ __('Barcode') }}"></td>
                                                <td class="px-3 py-2">
                                                    <input type="number" step="0.01" min="0" wire:model="variants.{{ $r }}.price" placeholder="{{ $form['price'] !== '' ? number_format((float) $form['price'], 2) : __('Price') }}"
                                                           class="{{ $errors->has("variants.$r.price") ? $cellErrBase : $cellBase }} w-24" aria-label="{{ __('Price') }}">
                                                </td>
                                                <td class="whitespace-nowrap px-3 py-2">
                                                    @if ($form['track_stock'])
                                                        @if ($row['id'])
                                                            <span class="font-semibold text-gray-700">{{ $row['stock'] }}</span>
                                                        @else
                                                            <input type="number" min="0" wire:model="variants.{{ $r }}.opening" placeholder="0" class="{{ $cellBase }} w-20" aria-label="{{ __('Opening stock') }}" title="{{ __('Opening stock') }}">
                                                        @endif
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2 text-center"><input type="checkbox" wire:model="variants.{{ $r }}.is_active" class="h-4 w-4 rounded border-gray-300 text-primary-600" aria-label="{{ __('On sale') }}"></td>
                                                <td class="whitespace-nowrap px-3 py-2 text-right">
                                                    <button type="button" @click="more = ! more" class="text-xs font-medium text-primary-600 hover:underline">
                                                        <span x-text="more ? @js(__('Less')) : @js(__('More'))"></span>
                                                        @if (count($row['tiers']))<span class="ml-1 rounded bg-green-50 px-1.5 text-green-600">{{ count($row['tiers']) }}</span>@endif
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr x-show="more" x-cloak class="bg-gray-50">
                                                <td colspan="{{ count($options) + 6 }}" class="px-3 py-3">
                                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                                                        <div>
                                                            <label class="mb-1 block text-xs text-gray-500">{{ __('Was price') }}</label>
                                                            <input type="number" step="0.01" min="0" wire:model="variants.{{ $r }}.compare_at_price" class="{{ $cell }}">
                                                        </div>
                                                        <div>
                                                            <label class="mb-1 block text-xs text-gray-500">{{ __('Your cost') }}</label>
                                                            <input type="number" step="0.01" min="0" wire:model="variants.{{ $r }}.cost_price" placeholder="{{ $form['cost_price'] }}" class="{{ $cell }}">
                                                        </div>
                                                        <div>
                                                            <label class="mb-1 block text-xs text-gray-500">{{ __('Warn at stock') }}</label>
                                                            <input type="number" min="0" wire:model="variants.{{ $r }}.low_stock_threshold" placeholder="{{ $form['low_stock_threshold'] }}" class="{{ $cell }}">
                                                        </div>
                                                        <div>
                                                            <label class="mb-1 block text-xs text-gray-500">{{ __('Photo') }}</label>
                                                            <select wire:model="variants.{{ $r }}.image_id" class="{{ $cell }}">
                                                                <option value="">{{ __('Product’s main photo') }}</option>
                                                                @foreach ($imageOptions as $n => $img)<option value="{{ $img->id }}">{{ __('Photo :n', ['n' => $n + 1]) }}</option>@endforeach
                                                            </select>
                                                        </div>
                                                        <div class="md:col-span-4">
                                                            <label class="mb-1 block text-xs text-gray-500">{{ __('Supplier link (staff only)') }}</label>
                                                            <input type="url" wire:model="variants.{{ $r }}.supplier_url" class="{{ $errors->has("variants.$r.supplier_url") ? $cellErr : $cell }}">
                                                        </div>
                                                    </div>
                                                    <div class="mt-3">
                                                        <div class="mb-2 flex items-center justify-between">
                                                            <p class="text-xs text-gray-500">
                                                                {{ __('Bulk prices for this option only') }}
                                                                <span class="text-gray-400">— {{ count($row['tiers']) ? __('it no longer shares the product’s bulk prices') : __('none: it uses the product’s bulk prices when it has no price of its own') }}</span>
                                                            </p>
                                                            <button type="button" wire:click="addVariantTier({{ $r }})" class="text-xs font-medium text-primary-600 hover:underline"><i class="ik ik-plus"></i> {{ __('Add break') }}</button>
                                                        </div>
                                                        @foreach ($row['tiers'] as $t => $tier)
                                                            <div class="mb-2 flex items-center gap-2" wire:key="vt-{{ md5($row['key']) }}-{{ $t }}">
                                                                <input type="number" min="2" wire:model="variants.{{ $r }}.tiers.{{ $t }}.min_qty" placeholder="{{ __('Buy at least') }}" class="{{ $errors->has("variants.$r.tiers.$t.min_qty") ? $cellErrBase : $cellBase }} w-32">
                                                                <input type="number" step="0.01" min="0" wire:model="variants.{{ $r }}.tiers.{{ $t }}.unit_price" placeholder="{{ __('Price each') }}" class="{{ $errors->has("variants.$r.tiers.$t.unit_price") ? $cellErrBase : $cellBase }} w-32">
                                                                <button type="button" wire:click="removeVariantTier({{ $r }}, {{ $t }})" class="text-gray-400 hover:text-accent-600" aria-label="{{ __('Remove') }}"><i class="ik ik-trash-2"></i></button>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </td>
                                            </tr>
                                            @if ($errors->has("variants.$r.*"))
                                                <tr><td colspan="{{ count($options) + 6 }}" class="px-3 pb-2 text-xs text-accent-600">{{ collect($errors->get("variants.$r.*"))->flatten()->first() }}</td></tr>
                                            @endif
                                        </tbody>
                                    @endforeach
                                </table>
                            </div>
                            <p class="border-t border-gray-100 px-4 py-2.5 text-xs text-gray-400">
                                {{ __('Leave a price blank to use the default price. Stock for saved options is changed in Inventory (receive or adjust), so every change is recorded.') }}
                            </p>
                        </div>
                    @elseif ($options && collect($options)->every(fn ($o) => empty($o['values'])))
                        <p class="mt-4 text-sm text-gray-400">{{ __('Add values above (e.g. Small, Medium, Large) and the options to sell appear here.') }}</p>
                    @endif
                @endif
            </x-card>

            {{-- ================= Stock ================= --}}
            <x-card title="{{ __('Stock') }}" icon="ik ik-layers">
                <div class="space-y-4">
                    <x-form.checkbox name="form.track_stock" label="{{ __('Track stock for this product') }}" wire:model.live="form.track_stock" />
                    @if ($form['track_stock'])
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            @if ($hasVariants)
                                <div>
                                    <p class="mb-1.5 text-sm font-medium text-gray-700">{{ __('In stock') }}</p>
                                    <p class="text-sm text-gray-600">{{ __('Counted per option — see the grid above.') }}</p>
                                </div>
                            @elseif (! $product)
                                <x-form.input type="number" min="0" name="openingStock" label="{{ __('Opening stock') }}" wire:model="openingStock" />
                            @else
                                <div>
                                    <p class="mb-1.5 text-sm font-medium text-gray-700">{{ __('In stock') }}</p>
                                    <p class="text-sm text-gray-600"><span class="text-lg font-semibold">{{ $product->stock_qty }}</span> —
                                        <a href="{{ route('shop.admin.inventory', ['search' => $form['sku'] ?: $form['name'], 'show' => 'all']) }}" wire:navigate class="text-primary-600 hover:underline">{{ __('receive or adjust in Inventory') }}</a></p>
                                </div>
                            @endif
                            <x-form.input type="number" min="0" name="form.low_stock_threshold" label="{{ __('Warn when stock falls to') }}" wire:model="form.low_stock_threshold" />
                        </div>
                        <x-form.checkbox name="form.allow_backorder" label="{{ __('Keep selling when out of stock (back orders)') }}" wire:model="form.allow_backorder" />

                        <div class="rounded-xl border border-gray-100 p-4">
                            <x-form.checkbox name="form.track_expiry" label="{{ __('Track batches and expiry dates') }}" wire:model.live="form.track_expiry" />
                            <p class="mt-1 text-xs text-gray-400">{{ __('Every delivery of this product must have an expiry date. Expired batches are taken off sale automatically overnight.') }}</p>
                            <div class="mt-3 max-w-sm">
                                <x-form.select name="form.stock_rotation" label="{{ __('Which stock goes out first') }}" wire:model="form.stock_rotation">
                                    <option value="fefo">{{ __('Soonest expiry first (FEFO)') }}</option>
                                    <option value="fifo">{{ __('Oldest delivery first (FIFO)') }}</option>
                                </x-form.select>
                            </div>
                        </div>
                    @endif
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <x-form.input type="number" min="1" name="form.min_order_qty" label="{{ $hasVariants ? __('Minimum order (per option)') : __('Minimum order') }}" wire:model="form.min_order_qty" />
                        <x-form.input type="number" min="1" name="form.max_order_qty" label="{{ $hasVariants ? __('Maximum per order (per option)') : __('Maximum per order') }}" wire:model="form.max_order_qty" hint="{{ __('Optional') }}" />
                    </div>
                </div>
            </x-card>
        </div>

        {{-- ================= Side column ================= --}}
        <div class="space-y-5">
            <x-card title="{{ __('Visibility') }}" icon="ik ik-eye">
                <div class="space-y-3">
                    <x-form.select name="form.status" label="{{ __('Status') }}" wire:model="form.status">
                        <option value="draft">{{ __('Draft — hidden from customers') }}</option>
                        <option value="active">{{ __('On sale') }}</option>
                        <option value="archived">{{ __('Archived') }}</option>
                    </x-form.select>
                    <x-form.checkbox name="form.is_featured" label="{{ __('Feature on the shop home page') }}" wire:model="form.is_featured" />
                </div>
            </x-card>

            <x-card title="{{ __('Photos') }}" icon="ik ik-image">
                <div class="space-y-3">
                    @if ($product && $product->images->isNotEmpty())
                        <div class="grid grid-cols-3 gap-2">
                            @foreach ($product->images as $n => $img)
                                <div class="relative rounded-lg border border-gray-100 p-1" wire:key="img-{{ $img->id }}">
                                    <img src="{{ $img->url() }}" alt="" class="h-20 w-full rounded object-contain">
                                    <span class="absolute left-2 top-2 rounded bg-gray-700 px-1.5 text-[10px] font-semibold text-white">{{ $loop->first ? __('Main') : $n + 1 }}</span>
                                    <div class="mt-1 flex justify-between text-xs">
                                        @unless ($loop->first)<button type="button" wire:click="makePrimaryImage('{{ $img->id }}')" class="text-primary-600 hover:underline">{{ __('Main') }}</button>@else<span></span>@endunless
                                        <button type="button" wire:click="deleteImage('{{ $img->id }}')" wire:confirm="{{ __('Remove this photo?') }}" class="text-accent-600 hover:underline">{{ __('Remove') }}</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <label class="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-gray-200 px-4 py-5 text-center text-sm text-gray-500 transition hover:bg-gray-50">
                        <i class="ik ik-upload text-xl"></i>
                        <span>{{ __('Add photos (JPG or PNG, up to 4 MB each)') }}</span>
                        <input type="file" wire:model="uploads" multiple accept="image/*" class="hidden">
                    </label>
                    <div wire:loading wire:target="uploads" class="text-xs text-gray-400">{{ __('Uploading…') }}</div>
                    @if ($uploads)
                        <p class="text-xs text-green-600">{{ trans_choice(':count photo ready — saved when you save the product|:count photos ready — saved when you save the product', count($uploads), ['count' => count($uploads)]) }}</p>
                    @endif
                    @if ($hasVariants)<p class="text-xs text-gray-400">{{ __('Once saved, each option can show its own photo (More → Photo).') }}</p>@endif
                    @error('uploads.*')<p class="text-xs text-accent-600">{{ $message }}</p>@enderror
                </div>
            </x-card>

            <x-card title="{{ __('Supplier (staff only)') }}" icon="ik ik-truck">
                <div class="space-y-3">
                    <x-form.input name="form.supplier_name" label="{{ __('Supplier') }}" wire:model="form.supplier_name" />
                    <x-form.input name="form.supplier_sku" label="{{ __('Supplier code') }}" wire:model="form.supplier_sku" />
                    <x-form.input name="form.supplier_url" label="{{ __('Supplier link') }}" wire:model="form.supplier_url" hint="{{ __('For reordering. Never shown in the shop.') }}" />
                </div>
            </x-card>

            @if ($product)
                <x-card>
                    <x-button variant="ghost" class="w-full justify-center text-accent-600" wire:click="delete" wire:confirm="{{ __('Delete this product? Past orders keep their details.') }}">
                        <i class="ik ik-trash-2"></i>{{ __('Delete product') }}
                    </x-button>
                </x-card>
            @endif
        </div>
    </form>
</div>
