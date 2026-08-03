@extends('layouts.main')
@section('title', 'Carousel')
@section('content')
    <x-page-header title="{{ __('Slider') }}" subtitle="{{ __('Self-contained carousel & slider variants built with Tailwind and Alpine.js') }}" icon="ik ik-film"
        :breadcrumbs="['Home' => route('dashboard'), 'UI' => '#', 'Advanced' => '#', 'Slider' => null]" />

    @php
        $slides = [
            ['portfolio-1.jpg', 'Homemade Cheesecake with Fresh Berries and Mint'],
            ['portfolio-2.jpg', 'Wedding Cake with Flowers Macarons and Blueberries'],
            ['portfolio-3.jpg', 'Cheesecake with Chocolate Cookies and Cream Biscuits'],
            ['portfolio-4.jpg', 'Cheesecake with Chocolate Cookies and Cream Biscuits'],
            ['portfolio-5.jpg', 'Homemade Cheesecake with Fresh Berries and Mint'],
            ['portfolio-6.jpg', 'Cheesecake with Chocolate Cookies and Cream Biscuits'],
        ];

        // Colored gradient panels — safe against missing assets.
        $panels = [
            ['from-primary-500 to-primary-600', 'Discover', 'Build admin panels faster'],
            ['from-accent-500 to-accent-600', 'Create', 'Beautiful interfaces out of the box'],
            ['from-green-500 to-green-600', 'Ship', 'Production-ready components'],
            ['from-amber-500 to-amber-600', 'Scale', 'Everything is responsive'],
            ['from-cyan-500 to-cyan-600', 'Enjoy', 'A delightful developer experience'],
        ];

        $captionSlides = [
            ['portfolio-7.jpg', 'Mountain Retreat', 'Escape to the peaks for a weekend of fresh air and quiet trails.'],
            ['portfolio-8.jpg', 'Coastal Escape', 'Sun, sand and endless horizons await at our seaside resorts.'],
            ['portfolio-9.jpg', 'City Lights', 'Explore vibrant nightlife and culture in the heart of the city.'],
        ];

        $products = [
            ['portfolio-1.jpg', 'Classic Cheesecake', '$24.00', 'primary'],
            ['portfolio-2.jpg', 'Wedding Macarons', '$32.00', 'success'],
            ['portfolio-3.jpg', 'Cream Biscuits', '$18.00', 'danger'],
            ['portfolio-4.jpg', 'Berry Tart', '$21.00', 'primary'],
            ['portfolio-5.jpg', 'Chocolate Fudge', '$27.00', 'success'],
            ['portfolio-6.jpg', 'Lemon Drizzle', '$19.00', 'danger'],
        ];

        $thumbs = ['portfolio-10.jpg', 'portfolio-11.jpg', 'portfolio-12.jpg', 'portfolio-1.jpg', 'portfolio-2.jpg'];

        $testimonials = [
            ['users/1.jpg', 'Sarah Mitchell', 'Product Designer', 'This admin starter saved us weeks of work. The components are clean, consistent and a joy to extend.'],
            ['users/2.jpg', 'James Carter', 'CTO, Nimbus', 'Switching to the Tailwind + Alpine stack made our dashboard feel modern and lightning fast.'],
            ['users/3.jpg', 'Aisha Khan', 'Frontend Lead', 'No heavy carousel libraries — everything is self-contained and easy to customize. Highly recommend.'],
        ];
    @endphp

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">

        {{-- 1. Basic image slider --}}
        <x-card>
            <x-slot:header>{{ __('Basic Slider') }}</x-slot:header>
            <div x-data="{ active: 0, count: {{ count($slides) }} }" class="relative">
                <div class="relative overflow-hidden rounded-lg">
                    <div class="flex transition-transform duration-500 ease-out" :style="`transform: translateX(-${active * 100}%)`">
                        @foreach ($slides as [$img, $title])
                            <div class="w-full shrink-0">
                                <img class="h-64 w-full object-cover" src="{{ asset('img/'.$img) }}" alt="{{ __($title) }}">
                            </div>
                        @endforeach
                    </div>

                    <button type="button" @click="active = (active - 1 + count) % count"
                        class="absolute left-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/80 text-gray-700 shadow transition hover:bg-white">
                        <i class="ik ik-chevron-left"></i>
                    </button>
                    <button type="button" @click="active = (active + 1) % count"
                        class="absolute right-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/80 text-gray-700 shadow transition hover:bg-white">
                        <i class="ik ik-chevron-right"></i>
                    </button>
                </div>

                <div class="mt-4 flex items-center justify-center gap-2">
                    <template x-for="i in count" :key="i">
                        <button type="button" @click="active = i - 1" class="h-2.5 rounded-full transition-all"
                            :class="active === i - 1 ? 'w-5 bg-primary-500' : 'w-2.5 bg-gray-300 hover:bg-gray-400'"></button>
                    </template>
                </div>
            </div>
        </x-card>

        {{-- 2. Auto-playing carousel --}}
        <x-card>
            <x-slot:header>{{ __('Auto-play (pause on hover)') }}</x-slot:header>
            <div x-data="{
                    active: 0,
                    count: {{ count($panels) }},
                    timer: null,
                    start() { this.timer = setInterval(() => { this.active = (this.active + 1) % this.count }, 4000) },
                    stop() { clearInterval(this.timer) }
                 }"
                 x-init="start()" @mouseenter="stop()" @mouseleave="start()" class="relative">
                <div class="overflow-hidden rounded-lg">
                    <div class="flex transition-transform duration-700 ease-out" :style="`transform: translateX(-${active * 100}%)`">
                        @foreach ($panels as [$grad, $kicker, $text])
                            <div class="w-full shrink-0">
                                <div class="flex h-64 flex-col items-center justify-center bg-gradient-to-br {{ $grad }} px-6 text-center text-white">
                                    <span class="text-sm font-medium uppercase tracking-widest opacity-80">{{ __($kicker) }}</span>
                                    <h4 class="mt-2 text-2xl font-semibold">{{ __($text) }}</h4>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-center gap-2">
                    <template x-for="i in count" :key="i">
                        <button type="button" @click="active = i - 1" class="h-1.5 overflow-hidden rounded-full bg-gray-200 transition-all"
                            :class="active === i - 1 ? 'w-8' : 'w-4'">
                            <span class="block h-full bg-primary-500 transition-all" :class="active === i - 1 ? 'w-full' : 'w-0'"></span>
                        </button>
                    </template>
                </div>
            </div>
        </x-card>

        {{-- 3. Image slider with captions --}}
        <x-card>
            <x-slot:header>{{ __('Slider with Captions') }}</x-slot:header>
            <div x-data="{ active: 0, count: {{ count($captionSlides) }} }" class="relative">
                <div class="relative overflow-hidden rounded-lg">
                    <div class="flex transition-transform duration-500 ease-out" :style="`transform: translateX(-${active * 100}%)`">
                        @foreach ($captionSlides as [$img, $title, $text])
                            <div class="relative w-full shrink-0">
                                <img class="h-72 w-full object-cover" src="{{ asset('img/'.$img) }}" alt="{{ __($title) }}">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                                <div class="absolute inset-x-0 bottom-0 p-6 text-white">
                                    <h4 class="text-xl font-semibold">{{ __($title) }}</h4>
                                    <p class="mt-1 max-w-md text-sm text-white/80">{{ __($text) }}</p>
                                    <div class="mt-3">
                                        <x-button variant="primary" size="sm">{{ __('Learn More') }}</x-button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" @click="active = (active - 1 + count) % count"
                        class="absolute left-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/80 text-gray-700 shadow transition hover:bg-white">
                        <i class="ik ik-chevron-left"></i>
                    </button>
                    <button type="button" @click="active = (active + 1) % count"
                        class="absolute right-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/80 text-gray-700 shadow transition hover:bg-white">
                        <i class="ik ik-chevron-right"></i>
                    </button>

                    <div class="absolute left-1/2 top-4 flex -translate-x-1/2 items-center gap-2">
                        <template x-for="i in count" :key="i">
                            <button type="button" @click="active = i - 1" class="h-2.5 rounded-full transition-all"
                                :class="active === i - 1 ? 'w-5 bg-white' : 'w-2.5 bg-white/50 hover:bg-white/80'"></button>
                        </template>
                    </div>
                </div>
            </div>
        </x-card>

        {{-- 4. Fade transition carousel --}}
        <x-card>
            <x-slot:header>{{ __('Fade Transition') }}</x-slot:header>
            <div x-data="{ active: 0, count: {{ count($panels) }} }" class="relative">
                <div class="relative h-64 overflow-hidden rounded-lg">
                    @foreach ($panels as $i => [$grad, $kicker, $text])
                        <div x-show="active === {{ $i }}"
                             x-transition:enter="transition ease-out duration-700"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition ease-in duration-700"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="absolute inset-0 flex flex-col items-center justify-center bg-gradient-to-br {{ $grad }} px-6 text-center text-white">
                            <span class="text-sm font-medium uppercase tracking-widest opacity-80">{{ __($kicker) }}</span>
                            <h4 class="mt-2 text-2xl font-semibold">{{ __($text) }}</h4>
                        </div>
                    @endforeach

                    <button type="button" @click="active = (active - 1 + count) % count"
                        class="absolute left-3 top-1/2 z-10 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/80 text-gray-700 shadow transition hover:bg-white">
                        <i class="ik ik-chevron-left"></i>
                    </button>
                    <button type="button" @click="active = (active + 1) % count"
                        class="absolute right-3 top-1/2 z-10 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/80 text-gray-700 shadow transition hover:bg-white">
                        <i class="ik ik-chevron-right"></i>
                    </button>
                </div>

                <div class="mt-4 flex items-center justify-center gap-2">
                    <template x-for="i in count" :key="i">
                        <button type="button" @click="active = i - 1" class="h-2.5 rounded-full transition-all"
                            :class="active === i - 1 ? 'w-5 bg-primary-500' : 'w-2.5 bg-gray-300 hover:bg-gray-400'"></button>
                    </template>
                </div>
            </div>
        </x-card>

        {{-- 5. Card / multi-item carousel --}}
        <x-card class="xl:col-span-2">
            <x-slot:header>{{ __('Multi-item Card Carousel') }}</x-slot:header>
            <div x-data="{
                    active: 0,
                    total: {{ count($products) }},
                    perView: 1,
                    get pages() { return Math.max(1, Math.ceil(this.total / this.perView)) },
                    setPerView() {
                        this.perView = window.innerWidth >= 1024 ? 3 : (window.innerWidth >= 640 ? 2 : 1);
                        if (this.active > this.pages - 1) this.active = this.pages - 1;
                    },
                    next() { this.active = (this.active + 1) % this.pages },
                    prev() { this.active = (this.active - 1 + this.pages) % this.pages }
                 }"
                 x-init="setPerView(); window.addEventListener('resize', () => setPerView())"
                 class="relative">
                <div class="overflow-hidden">
                    <div class="flex transition-transform duration-500 ease-out" :style="`transform: translateX(-${active * 100}%)`">
                        @foreach ($products as [$img, $name, $price, $color])
                            <div class="shrink-0 px-2" :style="`width: ${100 / perView}%`">
                                <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
                                    <img class="h-40 w-full object-cover" src="{{ asset('img/'.$img) }}" alt="{{ __($name) }}">
                                    <div class="p-4">
                                        <div class="mb-2"><x-badge color="{{ $color }}">{{ __('In Stock') }}</x-badge></div>
                                        <h6 class="font-semibold text-gray-700">{{ __($name) }}</h6>
                                        <div class="mt-2 flex items-center justify-between">
                                            <span class="text-lg font-bold text-primary-600">{{ $price }}</span>
                                            <x-button variant="outline" size="sm"><i class="ik ik-shopping-cart"></i></x-button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between">
                    <button type="button" @click="prev()"
                        class="flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 text-gray-600 transition hover:bg-gray-50">
                        <i class="ik ik-chevron-left"></i>
                    </button>
                    <div class="flex items-center gap-2">
                        <template x-for="i in pages" :key="i">
                            <button type="button" @click="active = i - 1" class="h-2.5 rounded-full transition-all"
                                :class="active === i - 1 ? 'w-5 bg-primary-500' : 'w-2.5 bg-gray-300 hover:bg-gray-400'"></button>
                        </template>
                    </div>
                    <button type="button" @click="next()"
                        class="flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 text-gray-600 transition hover:bg-gray-50">
                        <i class="ik ik-chevron-right"></i>
                    </button>
                </div>
            </div>
        </x-card>

        {{-- 6. Thumbnail navigation slider --}}
        <x-card>
            <x-slot:header>{{ __('Thumbnail Navigation') }}</x-slot:header>
            <div x-data="{ active: 0, count: {{ count($thumbs) }} }">
                <div class="relative overflow-hidden rounded-lg">
                    <div class="flex transition-transform duration-500 ease-out" :style="`transform: translateX(-${active * 100}%)`">
                        @foreach ($thumbs as $img)
                            <div class="w-full shrink-0">
                                <img class="h-64 w-full object-cover" src="{{ asset('img/'.$img) }}" alt="Slide">
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-3 grid grid-cols-5 gap-2">
                    @foreach ($thumbs as $i => $img)
                        <button type="button" @click="active = {{ $i }}"
                            class="overflow-hidden rounded-lg transition focus:outline-none"
                            :class="active === {{ $i }} ? 'ring-2 ring-primary-500 ring-offset-1' : 'opacity-60 hover:opacity-100'">
                            <img class="h-14 w-full object-cover" src="{{ asset('img/'.$img) }}" alt="Thumbnail">
                        </button>
                    @endforeach
                </div>
            </div>
        </x-card>

        {{-- 7. Testimonial / quote slider --}}
        <x-card>
            <x-slot:header>{{ __('Testimonial Slider') }}</x-slot:header>
            <div x-data="{ active: 0, count: {{ count($testimonials) }} }" class="relative">
                <div class="overflow-hidden">
                    <div class="flex transition-transform duration-500 ease-out" :style="`transform: translateX(-${active * 100}%)`">
                        @foreach ($testimonials as [$avatar, $name, $role, $quote])
                            <div class="w-full shrink-0">
                                <div class="flex flex-col items-center px-6 py-4 text-center">
                                    <i class="ik ik-message-circle mb-4 text-3xl text-primary-300"></i>
                                    <p class="max-w-xl text-base italic leading-relaxed text-gray-600">&ldquo;{{ __($quote) }}&rdquo;</p>
                                    <img class="mt-6 h-14 w-14 rounded-full object-cover ring-2 ring-primary-100" src="{{ asset('img/'.$avatar) }}" alt="{{ $name }}">
                                    <h6 class="mt-3 font-semibold text-gray-700">{{ $name }}</h6>
                                    <span class="text-xs text-gray-400">{{ __($role) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-center gap-2">
                    <template x-for="i in count" :key="i">
                        <button type="button" @click="active = i - 1" class="h-2.5 rounded-full transition-all"
                            :class="active === i - 1 ? 'w-5 bg-primary-500' : 'w-2.5 bg-gray-300 hover:bg-gray-400'"></button>
                    </template>
                </div>
            </div>
        </x-card>

    </div>
@endsection
