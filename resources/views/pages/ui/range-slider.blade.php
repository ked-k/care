@extends('layouts.main')
@section('title', 'Range Slider')
@section('content')
    <x-page-header title="{{ __('Range Slider') }}" subtitle="{{ __('Self-contained range slider components built with Tailwind and Alpine.js — no jQuery, no external libraries.') }}"
        icon="ik ik-sliders" :breadcrumbs="['Home' => url('dashboard'), 'UI' => '#', 'Range Slider' => null]" />

    @php
        // Shared base classes for a styled native range input.
        // Track styling + Tailwind arbitrary variants for the WebKit/Moz thumb.
        $thumb = 'appearance-none cursor-pointer h-2 w-full rounded-full bg-gray-200 ' .
            '[&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:h-4 [&::-webkit-slider-thumb]:w-4 ' .
            '[&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-white ' .
            '[&::-webkit-slider-thumb]:shadow [&::-webkit-slider-thumb]:transition ' .
            '[&::-moz-range-thumb]:h-4 [&::-moz-range-thumb]:w-4 [&::-moz-range-thumb]:rounded-full ' .
            '[&::-moz-range-thumb]:border-2 [&::-moz-range-thumb]:border-white [&::-moz-range-thumb]:shadow';
    @endphp

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">

        {{-- 1. Basic single-value slider with live value bubble --}}
        <x-card>
            <x-slot:header>{{ __('Basic Slider') }}</x-slot:header>
            <div x-data="{ val: 40, min: 0, max: 100 }">
                <div class="mb-3 flex items-center justify-between">
                    <p class="text-sm text-gray-500">{{ __('Drag to change the value.') }}</p>
                    <span class="inline-flex min-w-[3rem] items-center justify-center rounded-lg bg-primary-50 px-2.5 py-1 text-sm font-semibold text-primary-600"
                        x-text="val"></span>
                </div>
                <input type="range" x-model.number="val" :min="min" :max="max" step="1"
                    class="accent-primary-500 {{ $thumb }}">
            </div>
        </x-card>

        {{-- 2. Slider with min/max labels and a filled track --}}
        <x-card>
            <x-slot:header>{{ __('Filled Track') }}</x-slot:header>
            <div x-data="{ val: 65, min: 0, max: 100 }">
                <p class="mb-3 text-sm text-gray-500">{{ __('Colored fill follows the thumb.') }}</p>
                <div class="relative flex h-4 items-center">
                    {{-- background track --}}
                    <div class="absolute h-2 w-full rounded-full bg-gray-200"></div>
                    {{-- filled portion --}}
                    <div class="absolute h-2 rounded-full bg-primary-500"
                        :style="`width:${(val-min)/(max-min)*100}%`"></div>
                    <input type="range" x-model.number="val" :min="min" :max="max" step="1"
                        class="relative bg-transparent accent-primary-500 {{ $thumb }}">
                </div>
                <div class="mt-2 flex justify-between text-xs text-gray-400">
                    <span x-text="min"></span>
                    <span class="font-semibold text-primary-600" x-text="val"></span>
                    <span x-text="max"></span>
                </div>
            </div>
        </x-card>

        {{-- 3. Stepped slider with tick marks / labels --}}
        <x-card>
            <x-slot:header>{{ __('Stepped Slider') }}</x-slot:header>
            <div x-data="{ val: 30 }">
                <div class="mb-3 flex items-center justify-between">
                    <p class="text-sm text-gray-500">{{ __('Snaps in steps of 10.') }}</p>
                    <x-badge color="primary"><span x-text="val"></span>%</x-badge>
                </div>
                <input type="range" x-model.number="val" min="0" max="100" step="10"
                    class="accent-primary-500 {{ $thumb }}">
                <div class="mt-2 flex justify-between px-0.5 text-xs text-gray-400">
                    @foreach (range(0, 100, 20) as $tick)
                        <span class="flex flex-col items-center gap-1">
                            <span class="h-1.5 w-px bg-gray-300"></span>{{ $tick }}
                        </span>
                    @endforeach
                </div>
            </div>
        </x-card>

        {{-- 4. Range (dual-thumb) slider --}}
        <x-card>
            <x-slot:header>{{ __('Range Slider (Dual Thumb)') }}</x-slot:header>
            <div x-data="{
                min: 0,
                max: 1000,
                lo: 200,
                hi: 750,
                clamp() {
                    // Prevent the thumbs from crossing each other.
                    if (this.lo > this.hi) {
                        if (this.$event && this.$event.target.dataset.thumb === 'lo') { this.lo = this.hi; }
                        else { this.hi = this.lo; }
                    }
                }
            }">
                <div class="mb-3 flex items-center justify-between">
                    <p class="text-sm text-gray-500">{{ __('Select a min and max value.') }}</p>
                    <span class="text-sm font-semibold text-primary-600">
                        $<span x-text="lo"></span> &ndash; $<span x-text="hi"></span>
                    </span>
                </div>
                <div class="relative flex h-4 items-center">
                    {{-- background track --}}
                    <div class="absolute h-2 w-full rounded-full bg-gray-200"></div>
                    {{-- selected segment between the two thumbs --}}
                    <div class="absolute h-2 rounded-full bg-primary-500"
                        :style="`left:${(lo-min)/(max-min)*100}%;right:${100-(hi-min)/(max-min)*100}%`"></div>
                    {{-- both inputs overlaid; only the thumbs are interactive --}}
                    <input type="range" data-thumb="lo" x-model.number="lo" @input="clamp()" :min="min" :max="max" step="10"
                        class="pointer-events-none absolute w-full appearance-none bg-transparent accent-primary-500
                            [&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:appearance-none
                            [&::-webkit-slider-thumb]:h-4 [&::-webkit-slider-thumb]:w-4 [&::-webkit-slider-thumb]:rounded-full
                            [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-white
                            [&::-webkit-slider-thumb]:bg-primary-500 [&::-webkit-slider-thumb]:shadow
                            [&::-moz-range-thumb]:pointer-events-auto [&::-moz-range-thumb]:h-4 [&::-moz-range-thumb]:w-4
                            [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:border-2 [&::-moz-range-thumb]:border-white
                            [&::-moz-range-thumb]:bg-primary-500 [&::-moz-range-thumb]:shadow">
                    <input type="range" data-thumb="hi" x-model.number="hi" @input="clamp()" :min="min" :max="max" step="10"
                        class="pointer-events-none absolute w-full appearance-none bg-transparent accent-primary-500
                            [&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:appearance-none
                            [&::-webkit-slider-thumb]:h-4 [&::-webkit-slider-thumb]:w-4 [&::-webkit-slider-thumb]:rounded-full
                            [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-white
                            [&::-webkit-slider-thumb]:bg-primary-500 [&::-webkit-slider-thumb]:shadow
                            [&::-moz-range-thumb]:pointer-events-auto [&::-moz-range-thumb]:h-4 [&::-moz-range-thumb]:w-4
                            [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:border-2 [&::-moz-range-thumb]:border-white
                            [&::-moz-range-thumb]:bg-primary-500 [&::-moz-range-thumb]:shadow">
                </div>
                <div class="mt-2 flex justify-between text-xs text-gray-400">
                    <span>${{ 0 }}</span><span>${{ 1000 }}</span>
                </div>
            </div>
        </x-card>

        {{-- 5. Colored variants --}}
        <x-card>
            <x-slot:header>{{ __('Colored Variants') }}</x-slot:header>
            <div x-data="{ a: 60, b: 45, c: 80, d: 35 }" class="space-y-5">
                <div>
                    <div class="mb-1 flex justify-between text-sm">
                        <span class="text-gray-600">{{ __('Primary') }}</span>
                        <span class="font-semibold text-primary-600" x-text="a"></span>
                    </div>
                    <input type="range" x-model.number="a" min="0" max="100" class="accent-primary-500 {{ $thumb }}">
                </div>
                <div>
                    <div class="mb-1 flex justify-between text-sm">
                        <span class="text-gray-600">{{ __('Green') }}</span>
                        <span class="font-semibold text-green-600" x-text="b"></span>
                    </div>
                    <input type="range" x-model.number="b" min="0" max="100" class="accent-green-500 {{ $thumb }}">
                </div>
                <div>
                    <div class="mb-1 flex justify-between text-sm">
                        <span class="text-gray-600">{{ __('Amber') }}</span>
                        <span class="font-semibold text-amber-600" x-text="c"></span>
                    </div>
                    <input type="range" x-model.number="c" min="0" max="100" class="accent-amber-500 {{ $thumb }}">
                </div>
                <div>
                    <div class="mb-1 flex justify-between text-sm">
                        <span class="text-gray-600">{{ __('Accent') }}</span>
                        <span class="font-semibold text-accent-600" x-text="d"></span>
                    </div>
                    <input type="range" x-model.number="d" min="0" max="100" class="accent-accent-500 {{ $thumb }}">
                </div>
            </div>
        </x-card>

        {{-- 6. Vertical slider --}}
        <x-card>
            <x-slot:header>{{ __('Vertical Slider') }}</x-slot:header>
            <div x-data="{ vol: 55 }" class="flex flex-col items-center gap-4 py-2">
                <span class="inline-flex min-w-[3rem] items-center justify-center rounded-lg bg-primary-50 px-2.5 py-1 text-sm font-semibold text-primary-600">
                    <span x-text="vol"></span>%
                </span>
                <div class="flex h-44 items-center justify-center">
                    {{-- rotate a normal range input to make it vertical --}}
                    <input type="range" x-model.number="vol" min="0" max="100"
                        class="h-2 w-40 -rotate-90 accent-primary-500 {{ $thumb }}">
                </div>
                <span class="text-xs text-gray-400">{{ __('Volume') }}</span>
            </div>
        </x-card>

        {{-- 7. Price filter example --}}
        <x-card>
            <x-slot:header>{{ __('Price Filter') }}</x-slot:header>
            <div x-data="{
                floor: 0,
                ceiling: 2000,
                lo: 250,
                hi: 1200,
                norm() {
                    this.lo = Math.min(Math.max(this.floor, Number(this.lo) || this.floor), this.ceiling);
                    this.hi = Math.min(Math.max(this.floor, Number(this.hi) || this.floor), this.ceiling);
                    if (this.lo > this.hi) { [this.lo, this.hi] = [this.hi, this.lo]; }
                }
            }">
                <p class="mb-4 text-sm text-gray-500">{{ __('Number inputs and the slider stay in sync.') }}</p>

                <div class="relative mb-4 flex h-4 items-center">
                    <div class="absolute h-2 w-full rounded-full bg-gray-200"></div>
                    <div class="absolute h-2 rounded-full bg-primary-500"
                        :style="`left:${(lo-floor)/(ceiling-floor)*100}%;right:${100-(hi-floor)/(ceiling-floor)*100}%`"></div>
                    <input type="range" x-model.number="lo" @input="if(lo>hi) lo=hi" :min="floor" :max="ceiling" step="50"
                        class="pointer-events-none absolute w-full appearance-none bg-transparent accent-primary-500
                            [&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:appearance-none
                            [&::-webkit-slider-thumb]:h-4 [&::-webkit-slider-thumb]:w-4 [&::-webkit-slider-thumb]:rounded-full
                            [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-white
                            [&::-webkit-slider-thumb]:bg-primary-500 [&::-webkit-slider-thumb]:shadow
                            [&::-moz-range-thumb]:pointer-events-auto [&::-moz-range-thumb]:h-4 [&::-moz-range-thumb]:w-4
                            [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:border-2 [&::-moz-range-thumb]:border-white
                            [&::-moz-range-thumb]:bg-primary-500 [&::-moz-range-thumb]:shadow">
                    <input type="range" x-model.number="hi" @input="if(hi<lo) hi=lo" :min="floor" :max="ceiling" step="50"
                        class="pointer-events-none absolute w-full appearance-none bg-transparent accent-primary-500
                            [&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:appearance-none
                            [&::-webkit-slider-thumb]:h-4 [&::-webkit-slider-thumb]:w-4 [&::-webkit-slider-thumb]:rounded-full
                            [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-white
                            [&::-webkit-slider-thumb]:bg-primary-500 [&::-webkit-slider-thumb]:shadow
                            [&::-moz-range-thumb]:pointer-events-auto [&::-moz-range-thumb]:h-4 [&::-moz-range-thumb]:w-4
                            [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:border-2 [&::-moz-range-thumb]:border-white
                            [&::-moz-range-thumb]:bg-primary-500 [&::-moz-range-thumb]:shadow">
                </div>

                <div class="flex items-end gap-3">
                    <div class="flex-1">
                        <label class="mb-1 block text-xs font-medium text-gray-500">{{ __('Min ($)') }}</label>
                        <input type="number" x-model.number="lo" @change="norm()" :min="floor" :max="ceiling" step="50"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100 focus:outline-none">
                    </div>
                    <span class="pb-2.5 text-gray-400">&ndash;</span>
                    <div class="flex-1">
                        <label class="mb-1 block text-xs font-medium text-gray-500">{{ __('Max ($)') }}</label>
                        <input type="number" x-model.number="hi" @change="norm()" :min="floor" :max="ceiling" step="50"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100 focus:outline-none">
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between">
                    <span class="text-sm text-gray-500">
                        {{ __('Showing') }}
                        <span class="font-semibold text-gray-700">$<span x-text="lo"></span></span>
                        &ndash;
                        <span class="font-semibold text-gray-700">$<span x-text="hi"></span></span>
                    </span>
                    <x-button type="button" size="sm">{{ __('Apply') }}</x-button>
                </div>
            </div>
        </x-card>

        {{-- 8. Slider with icons at each end --}}
        <x-card>
            <x-slot:header>{{ __('Slider With Icons') }}</x-slot:header>
            <div x-data="{ brightness: 70, vol: 40 }" class="space-y-6">
                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <span class="text-sm text-gray-600">{{ __('Brightness') }}</span>
                        <span class="text-sm font-semibold text-amber-600"><span x-text="brightness"></span>%</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <i class="ik ik-moon text-gray-400"></i>
                        <input type="range" x-model.number="brightness" min="0" max="100"
                            class="accent-amber-500 {{ $thumb }}">
                        <i class="ik ik-sun text-amber-500"></i>
                    </div>
                </div>
                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <span class="text-sm text-gray-600">{{ __('Volume') }}</span>
                        <span class="text-sm font-semibold text-primary-600"><span x-text="vol"></span>%</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <i class="ik ik-volume-1 text-gray-400"></i>
                        <input type="range" x-model.number="vol" min="0" max="100"
                            class="accent-primary-500 {{ $thumb }}">
                        <i class="ik ik-volume-2 text-primary-500"></i>
                    </div>
                </div>
            </div>
        </x-card>

    </div>
@endsection
