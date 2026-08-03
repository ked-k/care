@extends('inventory.layout')
@section('title', 'Add Product')
@section('content')
    <x-page-header title="{{ __('Add Product') }}" subtitle="{{ __('Create a new product in your catalogue') }}" icon="ik ik-package"
                   :breadcrumbs="['Home' => url('dashboard'), 'Products' => url('products'), 'Add Product' => null]" />

    @php
        // Per-step accent colors — literal Tailwind classes so they are generated at build time.
        $steps = [
            [
                'key' => 1, 'label' => 'Details', 'icon' => 'ik ik-edit',
                'done'    => 'border-primary-500 bg-primary-500 text-white',
                'current' => 'border-primary-500 text-primary-600 ring-4 ring-primary-100',
                'curLabel'=> 'text-primary-600',
                'line'    => 'bg-primary-500',
            ],
            [
                'key' => 2, 'label' => 'Pricing', 'icon' => 'ik ik-dollar-sign',
                'done'    => 'border-green-500 bg-green-500 text-white',
                'current' => 'border-green-500 text-green-600 ring-4 ring-green-100',
                'curLabel'=> 'text-green-600',
                'line'    => 'bg-green-500',
            ],
            [
                'key' => 3, 'label' => 'Stock', 'icon' => 'ik ik-layers',
                'done'    => 'border-amber-500 bg-amber-500 text-white',
                'current' => 'border-amber-500 text-amber-600 ring-4 ring-amber-100',
                'curLabel'=> 'text-amber-600',
                'line'    => 'bg-amber-500',
            ],
            [
                'key' => 4, 'label' => 'Media', 'icon' => 'ik ik-image',
                'done'    => 'border-violet-500 bg-violet-500 text-white',
                'current' => 'border-violet-500 text-violet-600 ring-4 ring-violet-100',
                'curLabel'=> 'text-violet-600',
                'line'    => 'bg-violet-500',
            ],
        ];
    @endphp

    <div x-data="{
            step: 1,
            total: 4,
            maxReached: 1,
            form: { title: '', price: '', sku: '' },
            get progress() { return Math.round((this.step / this.total) * 100); },
            next() { if (this.step < this.total) { this.step++; this.maxReached = Math.max(this.maxReached, this.step); } },
            prev() { if (this.step > 1) this.step--; },
            goTo(n) { if (n <= this.maxReached) this.step = n; },
         }"
         class="mx-auto max-w-4xl">

        {{-- Completion progress --}}
        <div class="mb-5">
            <div class="mb-1.5 flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500">{{ __('Your progress') }}</span>
                <span class="text-xs font-semibold text-gray-700"><span x-text="progress"></span>% {{ __('complete') }}</span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                <div class="h-full rounded-full bg-gradient-to-r from-primary-500 to-violet-600 transition-all duration-500 ease-out"
                     :style="`width: ${progress}%`"></div>
            </div>
        </div>

        {{-- Colorful stepper header --}}
        <div class="mb-6 flex items-center">
            @foreach ($steps as $s)
                <div class="flex items-center {{ ! $loop->last ? 'flex-1' : '' }}">
                    <div class="flex items-center gap-3">
                        <button type="button" @click="goTo({{ $s['key'] }})"
                                :disabled="{{ $s['key'] }} > maxReached"
                                :class="maxReached >= {{ $s['key'] }} ? 'cursor-pointer' : 'cursor-not-allowed'"
                                class="group flex items-center gap-3 text-left outline-none">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 text-sm font-semibold transition-all duration-300"
                                  :class="step > {{ $s['key'] }}
                                            ? '{{ $s['done'] }}'
                                            : (step === {{ $s['key'] }}
                                                ? '{{ $s['current'] }}'
                                                : 'border-gray-200 text-gray-400 group-hover:border-gray-300')">
                                <i class="{{ $s['icon'] }}" x-show="step <= {{ $s['key'] }}"></i>
                                <i class="ik ik-check" x-show="step > {{ $s['key'] }}" x-cloak></i>
                            </span>
                            <div class="hidden sm:block">
                                <p class="text-xs text-gray-400">{{ __('Step') }} {{ $s['key'] }}</p>
                                <p class="text-sm font-medium transition"
                                   :class="step === {{ $s['key'] }} ? '{{ $s['curLabel'] }}' : (step > {{ $s['key'] }} ? 'text-gray-700' : 'text-gray-400')">
                                    {{ __($s['label']) }}
                                </p>
                            </div>
                        </button>
                    </div>
                    @unless ($loop->last)
                        <div class="mx-3 h-1 flex-1 overflow-hidden rounded-full bg-gray-200">
                            <div class="h-full rounded-full transition-all duration-500 ease-out {{ $s['line'] }}"
                                 :style="`width: ${step > {{ $s['key'] }} ? 100 : 0}%`"></div>
                        </div>
                    @endunless
                </div>
            @endforeach
        </div>

        <x-card>
            <form @submit.prevent="step < total ? next() : window.toast('{{ __('Product created') }}', 'success')">
                {{-- Step 1: Details --}}
                <div x-show="step === 1" x-transition.opacity.duration.300ms class="space-y-5">
                    <x-form.input name="title" label="{{ __('Product title') }}" placeholder="{{ __('e.g. Wireless Headphones') }}" x-model="form.title" required />
                    <x-form.textarea name="description" label="{{ __('Description') }}" rows="5" placeholder="{{ __('Describe the product...') }}" />
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <x-form.select name="category" label="{{ __('Category') }}" required>
                            <option value="">{{ __('Select category') }}</option>
                            <option>Electronics</option><option>Fashion</option><option>Gaming</option><option>Clothing</option><option>Outdoor Gear</option>
                        </x-form.select>
                        <x-form.select name="brand" label="{{ __('Brand') }}">
                            <option value="">{{ __('Select brand') }}</option>
                            <option>Apple</option><option>Samsung</option><option>Sony</option><option>Generic</option>
                        </x-form.select>
                    </div>
                </div>

                {{-- Step 2: Pricing --}}
                <div x-show="step === 2" x-cloak x-transition.opacity.duration.300ms class="space-y-5">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <x-form.input name="price" label="{{ __('Selling price') }}" type="number" icon="ik ik-dollar-sign" placeholder="0.00" x-model="form.price" required />
                        <x-form.input name="purchase_price" label="{{ __('Purchase price') }}" type="number" icon="ik ik-dollar-sign" placeholder="0.00" />
                        <x-form.input name="offer_price" label="{{ __('Offer price') }}" type="number" icon="ik ik-tag" placeholder="0.00" hint="{{ __('Leave blank if no discount') }}" />
                        <x-form.input name="tax" label="{{ __('Tax rate (%)') }}" type="number" icon="ik ik-percent" placeholder="0" />
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3">
                        <div>
                            <p class="text-sm font-medium text-gray-700">{{ __('Track price changes') }}</p>
                            <p class="text-xs text-gray-400">{{ __('Keep a history of price updates for this product.') }}</p>
                        </div>
                        <x-form.toggle name="track_price" :checked="true" />
                    </div>
                </div>

                {{-- Step 3: Stock --}}
                <div x-show="step === 3" x-cloak x-transition.opacity.duration.300ms class="space-y-5">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <x-form.input name="sku" label="{{ __('SKU') }}" placeholder="EH1234" icon="ik ik-hash" x-model="form.sku" />
                        <x-form.input name="barcode" label="{{ __('Barcode') }}" placeholder="{{ __('Scan or enter') }}" icon="ik ik-maximize" />
                        <x-form.input name="stock" label="{{ __('Opening stock') }}" type="number" icon="ik ik-layers" placeholder="0" />
                        <x-form.input name="reorder" label="{{ __('Reorder level') }}" type="number" icon="ik ik-alert-triangle" placeholder="10" hint="{{ __('Get alerted below this quantity') }}" />
                    </div>
                    <x-form.select name="warehouse" label="{{ __('Warehouse') }}">
                        <option>{{ __('Warehouse 1') }}</option><option>{{ __('Warehouse 2') }}</option>
                    </x-form.select>
                </div>

                {{-- Step 4: Media + Review --}}
                <div x-show="step === 4" x-cloak x-transition.opacity.duration.300ms class="space-y-5">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Product images') }}</label>
                        <label class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 px-6 py-10 text-center transition hover:border-primary-300 hover:bg-primary-50/40">
                            <i class="ik ik-upload-cloud mb-2 text-3xl text-gray-300"></i>
                            <p class="text-sm font-medium text-gray-600">{{ __('Drop images here or click to browse') }}</p>
                            <p class="text-xs text-gray-400">{{ __('PNG, JPG up to 5MB each') }}</p>
                            <input type="file" multiple class="hidden">
                        </label>
                    </div>
                    <x-form.input name="tags" label="{{ __('Tags') }}" placeholder="{{ __('headphones, wireless, audio') }}" icon="ik ik-tag" hint="{{ __('Comma separated') }}" />

                    {{-- Live review summary --}}
                    <div class="rounded-2xl border border-gray-100 bg-gradient-to-br from-gray-50 to-white p-5 shadow-sm">
                        <div class="mb-3 flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-violet-100 text-violet-600">
                                <i class="ik ik-clipboard text-sm"></i>
                            </span>
                            <p class="text-sm font-semibold text-gray-700">{{ __('Review') }}</p>
                        </div>
                        <dl class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div class="rounded-xl bg-white p-3 shadow-xs ring-1 ring-gray-100">
                                <dt class="text-xs text-gray-400">{{ __('Title') }}</dt>
                                <dd class="mt-0.5 truncate text-sm font-medium text-gray-700" x-text="form.title || '{{ __('Not set') }}'"></dd>
                            </div>
                            <div class="rounded-xl bg-white p-3 shadow-xs ring-1 ring-gray-100">
                                <dt class="text-xs text-gray-400">{{ __('Price') }}</dt>
                                <dd class="mt-0.5 truncate text-sm font-medium text-green-600" x-text="form.price ? '$' + form.price : '{{ __('Not set') }}'"></dd>
                            </div>
                            <div class="rounded-xl bg-white p-3 shadow-xs ring-1 ring-gray-100">
                                <dt class="text-xs text-gray-400">{{ __('SKU') }}</dt>
                                <dd class="mt-0.5 truncate text-sm font-medium text-gray-700" x-text="form.sku || '{{ __('Not set') }}'"></dd>
                            </div>
                        </dl>
                    </div>
                </div>

                {{-- Footer nav --}}
                <div class="mt-6 flex items-center justify-between border-t border-gray-100 pt-5">
                    <button type="button" @click="prev()" :disabled="step === 1" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50 disabled:opacity-40">
                        <i class="ik ik-chevron-left"></i> {{ __('Back') }}
                    </button>
                    <span class="text-sm text-gray-400">{{ __('Step') }} <span x-text="step"></span> {{ __('of') }} <span x-text="total"></span></span>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-600">
                        <span x-show="step < total">{{ __('Continue') }} <i class="ik ik-chevron-right"></i></span>
                        <span x-show="step === total" x-cloak><i class="ik ik-check"></i> {{ __('Create Product') }}</span>
                    </button>
                </div>
            </form>
        </x-card>
    </div>
@endsection
