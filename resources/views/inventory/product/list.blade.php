@extends('inventory.layout')
@section('title', 'Products')
@section('content')
<div x-data="{ view: localStorage.getItem('inv_view') || 'list', qeOpen: false, qe: {}, qePreview: '',
               setView(v) { this.view = v; localStorage.setItem('inv_view', v); },
               qePick(e) { const f = e.target.files[0]; if (f) this.qePreview = URL.createObjectURL(f); } }">

    <x-page-header title="{{ __('Products') }}" subtitle="{{ __('Manage your catalogue & stock') }}" icon="ik ik-package"
                   :breadcrumbs="['Home' => url('dashboard'), 'Products' => null]">
        <div class="inline-flex rounded-lg border border-gray-200 p-0.5">
            <button @click="setView('list')" :class="view === 'list' ? 'bg-primary-500 text-white' : 'text-gray-500 hover:bg-gray-100'" class="flex h-8 w-8 items-center justify-center rounded-md transition" title="{{ __('List view') }}"><i class="ik ik-list"></i></button>
            <button @click="setView('grid')" :class="view === 'grid' ? 'bg-primary-500 text-white' : 'text-gray-500 hover:bg-gray-100'" class="flex h-8 w-8 items-center justify-center rounded-md transition" title="{{ __('Grid view') }}"><i class="ik ik-grid"></i></button>
        </div>
    </x-page-header>

    {{-- KPI stat strip --}}
    <div class="mb-5 grid grid-cols-2 gap-4 xl:grid-cols-4">
        <x-stat-card variant="soft" value="2,563" label="{{ __('Total SKUs') }}" icon="ik ik-package" color="primary" />
        <x-stat-card variant="soft" value="$184,250" label="{{ __('Stock Value') }}" icon="ik ik-dollar-sign" color="green" />
        <x-stat-card variant="soft" value="48" label="{{ __('Low Stock') }}" icon="ik ik-alert-triangle" color="amber" />
        <x-stat-card variant="soft" value="12" label="{{ __('Out of Stock') }}" icon="ik ik-x-circle" color="accent" />
    </div>

    @php
        $products = [
            ['headphone.webp', 'HeadPhone', 'EH1234', 'Electronics', '100', '90', 50],
            ['ipone-6.jpg', 'Iphone 6', 'EH1235', 'Electronics', '5000', '4850', 1],
            ['bag.webp', 'Leather Bag', 'EH1236', 'Fashion', '500', '450', 100],
            ['camera.webp', 'Camera', 'EH1237', 'Electronics', '100', '90', 50],
            ['joystick.webp', 'Joystick', 'EH1238', 'Gaming', '5000', '4850', 10],
            ['jacket.webp', 'Jacket', 'EH1239', 'Fashion', '500', '450', 100],
            ['watch.webp', 'Smart Watch', 'EH1240', 'Electronics', '100', '90', 50],
            ['tshirt.jpg', 'T-shirt', 'EH1241', 'Clothing', '5500', '4850', 0],
            ['helmet.jpg', 'Helmet', 'EH1242', 'Outdoor Gear', '500', '450', 8],
        ];
        $statusOf = fn ($s) => $s == 0 ? 'out' : ($s <= 10 ? 'low' : 'in-stock');
    @endphp

    {{-- LIST view --}}
    <div x-show="view === 'list'">
        <x-table title="{{ __('Products') }}" :total="2500" :from="1" :to="9" :static-pages="5" search selectable
                 :add-url="url('products/create')" add-label="{{ __('Add Product') }}">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="w-10 px-5 py-3" data-no-export><input type="checkbox" class="row-select-all h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-200" @change="toggleAll($event)" aria-label="{{ __('Select all') }}"></th>
                        <th class="px-5 py-3 font-medium">{{ __('Product') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('SKU') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Category') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Price') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Stock') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-5 py-3 font-medium" data-no-export>{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($products as [$img, $title, $sku, $cat, $price, $purchase, $stock])
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3" data-no-export><input type="checkbox" value="{{ $sku }}" class="row-select h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-200" @change="sync()"></td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <img src="/img/products/{{ $img }}" class="h-10 w-10 rounded-lg object-cover" alt="">
                                    <span class="font-medium text-gray-700">{{ $title }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-gray-500">{{ $sku }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ $cat }}</td>
                            <td class="px-5 py-3 font-medium text-gray-700">${{ $price }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ $stock }}</td>
                            <td class="px-5 py-3"><x-status-pill :status="$statusOf($stock)" /></td>
                            <td class="px-5 py-3" data-no-export>
                                <div class="flex items-center gap-3">
                                    <button type="button" @click="qe = @js(['title' => $title, 'sku' => $sku, 'price' => $price, 'stock' => $stock, 'img' => $img, 'cat' => $cat]); qePreview = ''; qeOpen = true" class="text-gray-500 hover:text-primary-600" title="{{ __('Quick edit') }}"><i class="ik ik-edit-2"></i></button>
                                    <a href="{{ url('products/1/edit') }}" class="text-gray-500 hover:text-primary-600" title="{{ __('Full edit') }}"><i class="ik ik-edit"></i></a>
                                    <a href="#!" class="text-accent-500 hover:text-accent-600" title="{{ __('Delete') }}"><i class="ik ik-trash-2"></i></a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-table>
    </div>

    {{-- GRID view --}}
    <div x-show="view === 'grid'" x-cloak class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
        @foreach ($products as [$img, $title, $sku, $cat, $price, $purchase, $stock])
            <div class="group overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="relative aspect-square overflow-hidden bg-gray-50">
                    <img src="/img/products/{{ $img }}" class="h-full w-full object-cover transition group-hover:scale-105" alt="">
                    <span class="absolute left-2.5 top-2.5"><x-status-pill :status="$statusOf($stock)" /></span>
                </div>
                <div class="p-3.5">
                    <p class="truncate font-medium text-gray-700">{{ $title }}</p>
                    <p class="text-xs text-gray-400">{{ $cat }} · {{ $sku }}</p>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="font-semibold text-primary-600">${{ $price }}</span>
                        <span class="text-xs text-gray-400">{{ $stock }} {{ __('in stock') }}</span>
                    </div>
                    <button type="button" @click="qe = @js(['title' => $title, 'sku' => $sku, 'price' => $price, 'stock' => $stock, 'img' => $img, 'cat' => $cat]); qePreview = ''; qeOpen = true"
                            class="mt-3 w-full rounded-lg border border-gray-200 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50"><i class="ik ik-edit-2"></i> {{ __('Quick edit') }}</button>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Quick-edit slide-over --}}
    <div x-show="qeOpen" x-cloak x-transition.opacity @keydown.escape.window="qeOpen = false" class="fixed inset-0 z-[90] bg-black/40">
        <aside x-show="qeOpen" x-trap.noscroll="qeOpen" @click.outside="qeOpen = false"
               x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
               class="absolute inset-y-0 right-0 flex w-96 max-w-full flex-col bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h3 class="font-semibold text-gray-800">{{ __('Quick Edit') }}</h3>
                <button @click="qeOpen = false" class="text-gray-400 hover:text-gray-600"><i class="ik ik-x"></i></button>
            </div>
            <div class="flex-1 space-y-4 overflow-y-auto p-5">
                <div class="flex items-center gap-4">
                    <label class="group relative h-16 w-16 shrink-0 cursor-pointer overflow-hidden rounded-xl" title="{{ __('Change image') }}">
                        <img :src="qePreview || ('/img/products/' + qe.img)" class="h-16 w-16 rounded-xl object-cover" alt="">
                        <span class="absolute inset-0 flex items-center justify-center bg-black/45 text-white opacity-0 transition group-hover:opacity-100">
                            <i class="ik ik-camera"></i>
                        </span>
                        <input type="file" accept="image/*" class="hidden" @change="qePick($event)">
                    </label>
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-gray-800" x-text="qe.title"></p>
                        <p class="truncate text-sm text-gray-400" x-text="qe.cat + ' · ' + qe.sku"></p>
                        <label class="mt-1 inline-flex cursor-pointer items-center gap-1 text-xs font-medium text-primary-600 hover:text-primary-700">
                            <i class="ik ik-upload"></i> {{ __('Change image') }}
                            <input type="file" accept="image/*" class="hidden" @change="qePick($event)">
                        </label>
                    </div>
                </div>
                <p x-show="qePreview" x-cloak class="text-xs text-green-600"><i class="ik ik-check"></i> {{ __('New image selected — save to apply') }}</p>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Product name') }}</label>
                    <input x-model="qe.title" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Price') }}</label>
                        <input x-model="qe.price" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Stock') }}</label>
                        <input x-model="qe.stock" type="number" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Category') }}</label>
                    <select x-model="qe.cat" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                        <option>Electronics</option><option>Fashion</option><option>Gaming</option><option>Clothing</option><option>Outdoor Gear</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-3 border-t border-gray-100 p-4">
                <a href="{{ url('products/1/edit') }}" class="flex items-center justify-center rounded-lg border border-gray-200 px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50" title="{{ __('Open full editor') }}"><i class="ik ik-external-link"></i></a>
                <button @click="qeOpen = false" class="flex-1 rounded-lg border border-gray-200 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50">{{ __('Cancel') }}</button>
                <button @click="qeOpen = false; window.toast('{{ __('Product updated') }}', 'success')" class="flex-1 rounded-lg bg-primary-500 py-2.5 text-sm font-semibold text-white hover:bg-primary-600"><i class="ik ik-check"></i> {{ __('Save') }}</button>
            </div>
        </aside>
    </div>
</div>
@endsection
