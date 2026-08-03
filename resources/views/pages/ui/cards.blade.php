@extends('layouts.main')
@section('title', 'Cards')
@section('content')
    <x-page-header title="{{ __('Cards') }}" icon="ik ik-credit-card"
        :breadcrumbs="['Home' => route('dashboard'), 'UI' => '#', 'Cards' => null]" />

    <h5 class="mb-4 font-semibold text-gray-700">{{ __('Icon Card') }}</h5>
    <div class="grid grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-6">
        <a href="#" class="rounded-xl border border-gray-100 bg-white p-5 text-center shadow-sm transition hover:shadow-md">
            <i class="ik ik-clock text-3xl text-primary-500"></i>
            <p class="mt-3 text-sm font-semibold text-gray-700">{{ __('Pending Orders') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-800">16</p>
        </a>
    </div>

    <h5 class="mb-4 mt-8 font-semibold text-gray-700">{{ __('Image Card') }}</h5>
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-4">
        <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
            <div class="relative">
                <img class="h-44 w-full object-cover" src="{{ asset('img/portfolio-1.jpg') }}" alt="Card image cap">
                <span class="absolute left-3 top-3"><x-badge color="primary">New</x-badge></span>
                <span class="absolute left-3 top-10"><x-badge color="gray">Trending</x-badge></span>
            </div>
            <div class="p-5">
                <p class="mb-4 font-medium text-gray-700">Eff that place, you might as well stay here with us instead</p>
                <p class="text-xs text-gray-400">09.04.2018</p>
            </div>
        </div>
        <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
            <div class="p-5">
                <p class="mb-4 font-medium text-gray-700">Yes ok, great! I'm not stuck in Stockholm anymore, we're making progress.</p>
                <p class="text-xs text-gray-400">09.04.2018</p>
            </div>
            <div class="relative">
                <img class="h-44 w-full object-cover" src="{{ asset('img/portfolio-2.jpg') }}" alt="Card image cap">
                <span class="absolute left-3 top-3"><x-badge color="primary">New</x-badge></span>
                <span class="absolute left-3 top-10"><x-badge color="gray">Trending</x-badge></span>
            </div>
        </div>
    </div>

    <h5 class="mb-4 mt-8 font-semibold text-gray-700">{{ __('Image Overlay Card') }}</h5>
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-4">
        <div class="relative overflow-hidden rounded-xl text-white shadow-sm">
            <img class="h-64 w-full object-cover" src="{{ asset('img/portfolio-4.jpg') }}" alt="Card image">
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-black/10 p-5">
                <p class="mb-3 font-semibold">Fruitcake</p>
                <p class="text-sm text-white/80">This is a wider card with supporting text below as a natural lead-in to additional content.</p>
            </div>
        </div>
    </div>

    <h5 class="mb-4 mt-8 font-semibold text-gray-700">{{ __('Image Card List') }}</h5>
    <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
        <div class="flex items-center gap-4">
            <a href="#" class="shrink-0">
                <img alt="Thumbnail" src="{{ asset('img/portfolio-5.jpg') }}" class="h-24 w-24 object-cover">
            </a>
            <div class="flex flex-1 flex-col items-start gap-2 py-4 pr-4 lg:flex-row lg:items-center lg:justify-between">
                <a href="#" class="font-medium text-gray-700 hover:text-primary-600">Trex Outdoor Furniture Cape</a>
                <p class="text-sm text-gray-400">Project</p>
                <p class="text-sm text-gray-400">09.04.2018</p>
                <x-badge color="gray">On Hold</x-badge>
                <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-200">
            </div>
        </div>
    </div>

    <h5 class="mb-4 mt-8 font-semibold text-gray-700">{{ __('User Card') }}</h5>
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <x-card>
            <div class="text-center">
                <img alt="Profile" src="{{ asset('img/user.jpg') }}" class="mx-auto mb-4 h-20 w-20 rounded-full object-cover">
                <p class="font-medium text-gray-700">John Doe</p>
                <p class="mb-4 text-sm text-gray-400">Front End Developer</p>
                <x-button variant="primary">Edit</x-button>
            </div>
        </x-card>

        <div class="space-y-5">
            <div class="flex items-center gap-4 rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <img alt="Profile" src="{{ asset('img/user.jpg') }}" class="h-12 w-12 rounded-full object-cover">
                <div>
                    <a href="#" class="font-medium text-gray-700 hover:text-primary-600">John Doe</a>
                    <p class="text-sm text-gray-400">Front End Developer</p>
                </div>
            </div>
            <div class="flex items-center gap-4 rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-100 font-semibold text-primary-700">JD</div>
                <div>
                    <a href="#" class="font-medium text-gray-700 hover:text-primary-600">John Doe</a>
                    <p class="text-sm text-gray-400">Front End Developer</p>
                </div>
            </div>
        </div>
    </div>
@endsection
