@extends('inventory.layout')
@section('title', 'Edit Product')
@section('content')

@php
    // Sample product being edited — wire to real data when available.
    $product = [
        'title'          => 'Wireless Headphones',
        'description'    => 'Premium over-ear wireless headphones with active noise cancellation and 30-hour battery life.',
        'category'       => 'Electronics',
        'brand'          => 'Sony',
        'price'          => '129.00',
        'purchase_price' => '78.00',
        'offer_price'    => '99.00',
        'tax'            => '5',
        'sku'            => 'EH1234',
        'barcode'        => '8901234567890',
        'stock'          => '142',
        'reorder'        => '20',
        'warehouse'      => 'Warehouse 1',
        'status'         => 'Published',
    ];
    $gallery = ['p1.jpg', 'p2.jpg', 'p3.jpg'];
@endphp

<div x-data="{
        images: @js(array_map(fn ($g) => '/img/products/'.$g, $gallery)),
        main: 0,
        add(e) { for (const f of e.target.files) this.images.push(URL.createObjectURL(f)); e.target.value = ''; },
        remove(i) { this.images.splice(i, 1); if (this.main >= this.images.length) this.main = Math.max(0, this.images.length - 1); },
        del() { if (confirm('{{ __('Delete this product? This cannot be undone.') }}')) window.toast('{{ __('Product deleted') }}', 'success'); },
     }">

    <x-page-header title="{{ __('Edit Product') }}" subtitle="{{ $product['title'] }}" icon="ik ik-package"
                   :breadcrumbs="['Home' => url('dashboard'), 'Products' => url('products'), 'Edit' => null]">
        <div class="flex items-center gap-2">
            <x-button variant="outline" href="{{ url('products') }}"><i class="ik ik-arrow-left"></i> {{ __('Back') }}</x-button>
            <x-button variant="danger" type="button" @click="del()"><i class="ik ik-trash-2"></i> {{ __('Delete') }}</x-button>
        </div>
    </x-page-header>

    <form @submit.prevent="window.toast('{{ __('Product updated') }}', 'success')" class="grid grid-cols-1 gap-5 lg:grid-cols-3">

        {{-- ===================== MAIN COLUMN ===================== --}}
        <div class="space-y-5 lg:col-span-2">
            <x-card>
                <x-slot:header>{{ __('Details') }}</x-slot:header>
                <div class="space-y-5">
                    <x-form.input name="title" label="{{ __('Product title') }}" :value="$product['title']" required />
                    <x-form.textarea name="description" label="{{ __('Description') }}" rows="5" :value="$product['description']" />
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <x-form.select name="category" label="{{ __('Category') }}" required>
                            @foreach (['Electronics','Fashion','Gaming','Clothing','Outdoor Gear'] as $c)
                                <option @selected($product['category'] === $c)>{{ $c }}</option>
                            @endforeach
                        </x-form.select>
                        <x-form.select name="brand" label="{{ __('Brand') }}">
                            @foreach (['Apple','Samsung','Sony','Generic'] as $b)
                                <option @selected($product['brand'] === $b)>{{ $b }}</option>
                            @endforeach
                        </x-form.select>
                    </div>
                </div>
            </x-card>

            <x-card>
                <x-slot:header>{{ __('Pricing') }}</x-slot:header>
                <div class="space-y-5">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <x-form.input name="price" label="{{ __('Selling price') }}" type="number" icon="ik ik-dollar-sign" :value="$product['price']" required />
                        <x-form.input name="purchase_price" label="{{ __('Purchase price') }}" type="number" icon="ik ik-dollar-sign" :value="$product['purchase_price']" />
                        <x-form.input name="offer_price" label="{{ __('Offer price') }}" type="number" icon="ik ik-tag" :value="$product['offer_price']" hint="{{ __('Leave blank if no discount') }}" />
                        <x-form.input name="tax" label="{{ __('Tax rate (%)') }}" type="number" icon="ik ik-percent" :value="$product['tax']" />
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3">
                        <div>
                            <p class="text-sm font-medium text-gray-700">{{ __('Track price changes') }}</p>
                            <p class="text-xs text-gray-400">{{ __('Keep a history of price updates for this product.') }}</p>
                        </div>
                        <x-form.toggle name="track_price" :checked="true" />
                    </div>
                </div>
            </x-card>

            <x-card>
                <x-slot:header>{{ __('Inventory') }}</x-slot:header>
                <div class="space-y-5">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <x-form.input name="sku" label="{{ __('SKU') }}" icon="ik ik-hash" :value="$product['sku']" />
                        <x-form.input name="barcode" label="{{ __('Barcode') }}" icon="ik ik-maximize" :value="$product['barcode']" />
                        <x-form.input name="stock" label="{{ __('Stock on hand') }}" type="number" icon="ik ik-layers" :value="$product['stock']" />
                        <x-form.input name="reorder" label="{{ __('Reorder level') }}" type="number" icon="ik ik-alert-triangle" :value="$product['reorder']" hint="{{ __('Get alerted below this quantity') }}" />
                    </div>
                    <x-form.select name="warehouse" label="{{ __('Warehouse') }}">
                        @foreach (['Warehouse 1','Warehouse 2'] as $w)
                            <option @selected($product['warehouse'] === $w)>{{ $w }}</option>
                        @endforeach
                    </x-form.select>
                </div>
            </x-card>
        </div>

        {{-- ===================== SIDE COLUMN ===================== --}}
        <div class="space-y-5">
            <x-card>
                <x-slot:header>{{ __('Media') }}</x-slot:header>
                {{-- Main preview --}}
                <div class="aspect-square overflow-hidden rounded-xl border border-gray-100 bg-gray-50">
                    <template x-if="images.length"><img :src="images[main]" class="h-full w-full object-cover" alt=""></template>
                    <div x-show="! images.length" class="flex h-full items-center justify-center text-gray-300"><i class="ik ik-image text-5xl"></i></div>
                </div>
                {{-- Thumbnails + add --}}
                <div class="mt-3 grid grid-cols-4 gap-2">
                    <template x-for="(img, i) in images" :key="i">
                        <div class="group relative">
                            <button type="button" @click="main = i"
                                    :class="main === i ? 'ring-2 ring-primary-500' : 'ring-1 ring-gray-200 hover:ring-gray-300'"
                                    class="block aspect-square w-full overflow-hidden rounded-lg">
                                <img :src="img" class="h-full w-full object-cover" alt="">
                            </button>
                            <button type="button" @click="remove(i)"
                                    class="absolute -right-1.5 -top-1.5 hidden h-5 w-5 items-center justify-center rounded-full bg-accent-500 text-white shadow group-hover:flex">
                                <i class="ik ik-x text-[11px]"></i>
                            </button>
                        </div>
                    </template>
                    <label class="flex aspect-square cursor-pointer items-center justify-center rounded-lg border-2 border-dashed border-gray-200 text-gray-400 transition hover:border-primary-300 hover:text-primary-500" title="{{ __('Add images') }}">
                        <i class="ik ik-plus"></i>
                        <input type="file" accept="image/*" multiple class="hidden" @change="add($event)">
                    </label>
                </div>
                <p class="mt-2 text-xs text-gray-400">{{ __('Click a thumbnail to set the main image. PNG/JPG up to 5MB.') }}</p>
            </x-card>

            <x-card>
                <x-slot:header>{{ __('Organization') }}</x-slot:header>
                <div class="space-y-5">
                    <x-form.select name="status" label="{{ __('Status') }}">
                        @foreach (['Published','Draft','Archived'] as $s)
                            <option @selected($product['status'] === $s)>{{ $s }}</option>
                        @endforeach
                    </x-form.select>
                    <x-form.input name="tags" label="{{ __('Tags') }}" icon="ik ik-tag" value="audio, wireless, anc" hint="{{ __('Comma separated') }}" />
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3">
                        <div>
                            <p class="text-sm font-medium text-gray-700">{{ __('Featured product') }}</p>
                            <p class="text-xs text-gray-400">{{ __('Show on the storefront homepage.') }}</p>
                        </div>
                        <x-form.toggle name="featured" />
                    </div>
                </div>
            </x-card>
        </div>

        {{-- ===================== STICKY SAVE BAR ===================== --}}
        <div class="sticky bottom-0 z-20 lg:col-span-3">
            <div class="flex items-center justify-end gap-3 rounded-xl border border-gray-100 bg-white/95 px-5 py-3 shadow-lg shadow-gray-200/60 backdrop-blur">
                <span class="mr-auto hidden text-sm text-gray-400 sm:inline">{{ __('Editing') }} <span class="font-medium text-gray-600">{{ $product['sku'] }}</span></span>
                <x-button variant="outline" type="button" href="{{ url('products') }}">{{ __('Cancel') }}</x-button>
                <x-button variant="primary" type="submit"><i class="ik ik-check"></i> {{ __('Save changes') }}</x-button>
            </div>
        </div>
    </form>
</div>
@endsection
