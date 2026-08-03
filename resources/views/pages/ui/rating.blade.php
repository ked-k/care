@extends('layouts.main')
@section('title', 'Rating')
@section('content')
    <x-page-header title="{{ __('Rating') }}" subtitle="{{ __('lorem ipsum dolor sit amet, consectetur adipisicing elit') }}" icon="ik ik-gitlab"
        :breadcrumbs="['Home' => route('dashboard'), 'UI' => '#', 'Advanced' => '#', 'Rating' => null]" />

    <x-card>
        <x-slot:header>{{ __('Rating') }}</x-slot:header>
        <div class="grid grid-cols-1 gap-8 md:grid-cols-2 xl:grid-cols-3">

            {{-- Star rating --}}
            <div x-data="{ rating: 3, hover: 0, max: 5 }">
                <h6 class="mb-1 font-semibold text-gray-700">{{ __('Star Rating') }}</h6>
                <p class="mb-3 text-sm text-gray-500">Click a star to set the rating.</p>
                <div class="flex items-center gap-1 text-2xl">
                    <template x-for="i in max" :key="i">
                        <button type="button"
                                x-on:click="rating = i"
                                x-on:mouseenter="hover = i"
                                x-on:mouseleave="hover = 0"
                                class="transition"
                                :class="(hover || rating) >= i ? 'text-amber-400' : 'text-gray-300'">
                            <i class="ik ik-star"></i>
                        </button>
                    </template>
                    <span class="ml-2 text-sm text-gray-500" x-text="rating + ' / ' + max"></span>
                </div>
            </div>

            {{-- 1 to 10 bars --}}
            <div x-data="{ rating: 7, max: 10 }">
                <h6 class="mb-1 font-semibold text-gray-700">{{ __('1/10 Rating') }}</h6>
                <p class="mb-3 text-sm text-gray-500">Bar-style rating.</p>
                <div class="flex items-center gap-1">
                    <template x-for="i in max" :key="i">
                        <button type="button" x-on:click="rating = i"
                                class="h-6 flex-1 rounded transition"
                                :class="rating >= i ? 'bg-primary-500' : 'bg-gray-200'"></button>
                    </template>
                </div>
                <p class="mt-2 text-sm text-gray-600">{{ __('Current') }}: <span class="font-semibold text-primary-600" x-text="rating"></span></p>
            </div>

            {{-- Movie rating --}}
            <div x-data="{ options: ['Bad', 'Mediocre', 'Good', 'Awesome'], rating: 'Good' }">
                <h6 class="mb-1 font-semibold text-gray-700">{{ __('Movie Rating') }}</h6>
                <p class="mb-3 text-sm text-gray-500">Pick a verdict.</p>
                <div class="flex flex-wrap gap-2">
                    <template x-for="opt in options" :key="opt">
                        <button type="button" x-on:click="rating = opt"
                                class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
                                :class="rating === opt ? 'bg-primary-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                x-text="opt"></button>
                    </template>
                </div>
            </div>

            {{-- Square rating --}}
            <div x-data="{ rating: 0, max: 5 }">
                <h6 class="mb-1 font-semibold text-gray-700">{{ __('Square Rating') }}</h6>
                <p class="mb-3 text-sm text-gray-500">Click to fill.</p>
                <div class="flex items-center gap-2">
                    <template x-for="i in max" :key="i">
                        <button type="button" x-on:click="rating = i"
                                class="h-8 w-8 rounded transition"
                                :class="rating >= i ? 'bg-amber-400' : 'bg-gray-200'"></button>
                    </template>
                </div>
            </div>

            {{-- Pill rating --}}
            <div x-data="{ options: ['A', 'B', 'C', 'D', 'E', 'F'], rating: 'C' }">
                <h6 class="mb-1 font-semibold text-gray-700">{{ __('Pill Rating') }}</h6>
                <p class="mb-3 text-sm text-gray-500">Grade selector.</p>
                <div class="inline-flex overflow-hidden rounded-full border border-gray-200">
                    <template x-for="opt in options" :key="opt">
                        <button type="button" x-on:click="rating = opt"
                                class="border-r border-gray-200 px-3 py-1.5 text-sm font-medium transition last:border-r-0"
                                :class="rating === opt ? 'bg-primary-500 text-white' : 'text-gray-700 hover:bg-gray-50'"
                                x-text="opt"></button>
                    </template>
                </div>
            </div>

            {{-- Fractional --}}
            <div x-data="{ rating: 5.6, max: 10 }">
                <h6 class="mb-1 font-semibold text-gray-700">{{ __('Fractional Star Rating') }}</h6>
                <p class="mb-3 text-sm text-gray-500">Read-only fractional value.</p>
                <div class="relative inline-block text-2xl">
                    <div class="text-gray-300">
                        <template x-for="i in 5" :key="i"><i class="ik ik-star"></i></template>
                    </div>
                    <div class="absolute inset-0 overflow-hidden text-amber-400" :style="`width: ${rating / max * 100}%`">
                        <div class="flex w-max">
                            <template x-for="i in 5" :key="i"><i class="ik ik-star"></i></template>
                        </div>
                    </div>
                </div>
                <p class="mt-2 text-sm text-gray-600">{{ __('Current rating') }}: <span class="font-semibold text-amber-500" x-text="rating"></span></p>
            </div>
        </div>
    </x-card>
@endsection
