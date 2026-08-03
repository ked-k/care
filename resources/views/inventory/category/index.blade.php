@extends('inventory.layout')
@section('title', 'Categories')
@section('content')

@php
$categories = [
    [ 'name' => 'Computer', 'parent_category' => 'Electronics', 'image' => '/img/portfolio-3.jpg', 'items' => 120, ],
    [ 'name' => 'Smartphone', 'parent_category' => 'Electronics', 'image' => '/img/portfolio-1.jpg', 'items' => 75, ],
    [ 'name' => 'Headphones', 'parent_category' => 'Electronics', 'image' => '/img/portfolio-2.jpg', 'items' => 40, ],
    [ 'name' => 'Television', 'parent_category' => 'Electronics', 'image' => '/img/portfolio-4.jpg', 'items' => 60, ],
    [ 'name' => 'Camera', 'parent_category' => 'Electronics', 'image' => '/img/portfolio-5.jpg', 'items' => 30, ],
    [ 'name' => 'Gaming', 'parent_category' => 'Electronics', 'image' => '/img/portfolio-6.jpg', 'items' => 50, ],
    [ 'name' => 'Furniture', 'parent_category' => null, 'image' => '/img/portfolio-7.jpg', 'items' => 200, ],
    [ 'name' => 'Home Decor', 'parent_category' => null, 'image' => '/img/portfolio-8.jpg', 'items' => 150, ],
    [ 'name' => 'Cookware', 'parent_category' => 'Kitchen', 'image' => '/img/portfolio-9.jpg', 'items' => 80, ],
    [ 'name' => 'Appliances', 'parent_category' => 'Kitchen', 'image' => '/img/portfolio-10.jpg', 'items' => 110, ],
    [ 'name' => 'Bedding', 'parent_category' => 'Bedroom', 'image' => '/img/portfolio-11.jpg', 'items' => 90, ],
    [ 'name' => 'Lighting', 'parent_category' => 'Home Decor', 'image' => '/img/portfolio-12.jpg', 'items' => 70, ],
];
@endphp

<div x-data="{ addOpen: false, editOpen: false, view: 'grid' }">
    <x-page-header title="{{ __('Categories') }}" subtitle="{{ __('Add, remove or edit product categories') }}" icon="ik ik-list"
                   :breadcrumbs="['Home' => url('dashboard'), 'Categories' => null]" />

    @include('include.message')

    <x-card>
        <x-slot:header>{{ __('Categories') }}</x-slot:header>
        <x-slot:actions>
            <x-button x-on:click="addOpen = true"><i class="ik ik-plus"></i> {{ __('Add Category') }}</x-button>
        </x-slot:actions>

        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-1 rounded-lg border border-gray-200 p-1">
                <button type="button" @click="view = 'list'"
                        :class="view === 'list' ? 'bg-primary-50 text-primary-600' : 'text-gray-400 hover:bg-gray-100'"
                        class="flex h-8 items-center gap-1.5 rounded-md px-3 text-sm font-medium transition" title="{{ __('List view') }}">
                    <i class="ik ik-list"></i><span class="hidden sm:inline">{{ __('List') }}</span>
                </button>
                <button type="button" @click="view = 'grid'"
                        :class="view === 'grid' ? 'bg-primary-50 text-primary-600' : 'text-gray-400 hover:bg-gray-100'"
                        class="flex h-8 items-center gap-1.5 rounded-md px-3 text-sm font-medium transition" title="{{ __('Grid view') }}">
                    <i class="ik ik-grid"></i><span class="hidden sm:inline">{{ __('Grid') }}</span>
                </button>
            </div>
            <div class="flex items-center gap-2">
                <input type="text" placeholder="{{ __('Search..') }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100 sm:w-56">
                <x-button variant="outline" type="submit"><i class="ik ik-search"></i></x-button>
                <span class="hidden whitespace-nowrap text-sm text-gray-400 lg:inline">{{ __('Displaying 1-10 of 210 items') }}</span>
            </div>
        </div>

        {{-- ===================== LIST VIEW ===================== --}}
        <div x-show="view === 'list'" class="space-y-3">
            @foreach($categories as $category)
            <div class="flex items-center gap-3 rounded-xl border border-gray-100 p-3 hover:bg-gray-50">
                <button type="button" x-on:click="editOpen = true" class="shrink-0">
                    <img src="{{asset($category['image'])}}" alt="{{$category['name']}}" class="h-16 w-16 rounded-lg object-cover">
                </button>
                <div class="min-w-0 flex-1">
                    <button type="button" x-on:click="editOpen = true" class="block truncate text-left">
                        <b class="text-gray-700">{{$category['name']}}</b>
                        @if($category['parent_category'])
                        <span class="text-gray-400">{{$category['parent_category']}}</span>
                        @endif
                    </button>
                    <p class="mt-1 text-xs text-gray-400">{{ __('Total') }} {{$category['items']}} {{ __('items') }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" x-on:click="editOpen = true" class="text-green-500 hover:text-green-600"><i class="ik ik-edit-2"></i></button>
                    <a href="#" class="text-accent-500 hover:text-accent-600"><i class="ik ik-trash-2"></i></a>
                    <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-200">
                </div>
            </div>
            @endforeach
        </div>

        {{-- ===================== GRID VIEW ===================== --}}
        <div x-show="view === 'grid'" x-cloak class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($categories as $category)
            <div class="flex flex-col overflow-hidden rounded-xl border border-gray-100 transition hover:-translate-y-0.5 hover:shadow-md">
                <button type="button" x-on:click="editOpen = true" class="relative block">
                    <img src="{{asset($category['image'])}}" alt="{{$category['name']}}" class="h-32 w-full object-cover">
                    <span class="absolute right-2 top-2 rounded-full bg-black/55 px-2 py-0.5 text-[11px] font-medium text-white">{{$category['items']}} {{ __('items') }}</span>
                </button>
                <div class="flex flex-1 flex-col p-3">
                    <button type="button" x-on:click="editOpen = true" class="block text-left">
                        <b class="block truncate text-gray-700">{{$category['name']}}</b>
                        <span class="text-xs text-gray-400">{{ $category['parent_category'] ?? __('Top level') }}</span>
                    </button>
                    <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-2">
                        <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-200">
                        <div class="flex items-center gap-3">
                            <button type="button" x-on:click="editOpen = true" class="text-green-500 hover:text-green-600"><i class="ik ik-edit-2"></i></button>
                            <a href="#" class="text-accent-500 hover:text-accent-600"><i class="ik ik-trash-2"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </x-card>

    <!-- category add modal -->
    <div x-show="addOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" x-on:click="addOpen = false"></div>
        <div class="relative w-full max-w-sm rounded-xl border border-gray-100 bg-white shadow-lg">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h5 class="font-semibold text-gray-700">{{ __('Add Category') }}</h5>
                <button type="button" x-on:click="addOpen = false" class="text-gray-400 hover:text-gray-600"><i class="ik ik-x"></i></button>
            </div>
            <div class="space-y-4 p-5">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Category Image') }}</label>
                    <input type="file" name="category_image" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                </div>
                <x-form.input name="category_title" label="{{ __('Category Title') }}" placeholder="{{ __('Enter Category Title') }}" />
                <x-form.input name="category_code" label="{{ __('Category Code') }}" placeholder="{{ __('Enter Category Code') }}" />
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Parent Category') }}</label>
                    <select class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                        <option selected="selected" value="">Select Category</option>
                        <option value="1">Electronics</option>
                        <option value="3">Smart Home</option>
                        <option value="4">Arts &amp; Crafts</option>
                        <option value="5">Fashion</option>
                        <option value="6">Baby</option>
                        <option value="7">Health &amp; Care</option>
                        <option value="8">Others</option>
                    </select>
                </div>
                <x-button type="submit">{{ __('Save') }}</x-button>
            </div>
        </div>
    </div>

    <!-- category edit modal -->
    <div x-show="editOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" x-on:click="editOpen = false"></div>
        <div class="relative w-full max-w-sm rounded-xl border border-gray-100 bg-white shadow-lg">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h5 class="font-semibold text-gray-700">{{ __('Edit Category') }}</h5>
                <button type="button" x-on:click="editOpen = false" class="text-gray-400 hover:text-gray-600"><i class="ik ik-x"></i></button>
            </div>
            <div class="space-y-4 p-5">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Category Image') }}</label>
                    <input type="file" name="category_image" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                </div>
                <x-form.input name="category_title" label="{{ __('Category Title') }}" placeholder="{{ __('Enter Category Title') }}" value="Computer" />
                <x-form.input name="category_code" label="{{ __('Category Code') }}" placeholder="{{ __('Enter Category Code') }}" value="CAT12" />
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Parent Category') }}</label>
                    <select class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                        <option selected="selected" value="">Select Category</option>
                        <option value="1">Electronics</option>
                        <option value="3">Smart Home</option>
                        <option value="4">Arts &amp; Crafts</option>
                        <option value="5">Fashion</option>
                        <option value="6">Baby</option>
                        <option value="7">Health &amp; Care</option>
                        <option value="8">Others</option>
                    </select>
                </div>
                <x-button type="submit">{{ __('Update') }}</x-button>
            </div>
        </div>
    </div>
</div>
@endsection
