@extends('layouts.main')
@section('title', 'Pricing')
@section('content')
    <x-page-header title="{{ __('Pricing') }}" subtitle="{{ __('Four ready-made pricing layouts — pick the one that fits your product') }}" icon="ik ik-dollar-sign"
                   :breadcrumbs="['Home' => route('dashboard'), 'Pricing' => null]" />

    <div x-data="{ tab: 1 }">
        {{-- Segmented tab switcher --}}
        <div class="mb-6 flex justify-center">
            <div class="inline-flex flex-wrap items-center gap-1 rounded-xl border border-gray-100 bg-white p-1 shadow-sm shadow-black/5">
                <button @click="tab = 1" :class="tab === 1 ? 'bg-primary-500 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50'"
                        class="rounded-lg px-4 py-2 text-sm font-medium transition">
                    <i class="ik ik-grid mr-1"></i>{{ __('Classic') }}
                </button>
                <button @click="tab = 2" :class="tab === 2 ? 'bg-primary-500 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50'"
                        class="rounded-lg px-4 py-2 text-sm font-medium transition">
                    <i class="ik ik-zap mr-1"></i>{{ __('Gradient') }}
                </button>
                <button @click="tab = 3" :class="tab === 3 ? 'bg-primary-500 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50'"
                        class="rounded-lg px-4 py-2 text-sm font-medium transition">
                    <i class="ik ik-list mr-1"></i>{{ __('Comparison') }}
                </button>
                <button @click="tab = 4" :class="tab === 4 ? 'bg-primary-500 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50'"
                        class="rounded-lg px-4 py-2 text-sm font-medium transition">
                    <i class="ik ik-layers mr-1"></i>{{ __('Compact') }}
                </button>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- VARIATION 1 — Classic tiers with billing toggle             --}}
        {{-- ============================================================ --}}
        @php
            $classicPlans = [
                [
                    'name' => 'Free', 'price' => 0, 'tag' => 'For individuals getting started',
                    'featured' => false,
                    'features' => [
                        ['Single user', true], ['5 GB storage', true], ['Unlimited public projects', true],
                        ['Community support', true], ['Private projects', false], ['Phone support', false],
                    ],
                ],
                [
                    'name' => 'Plus', 'price' => 9, 'tag' => 'For small growing teams',
                    'featured' => true,
                    'features' => [
                        ['5 users', true], ['50 GB storage', true], ['Unlimited public projects', true],
                        ['Community support', true], ['Private projects', true], ['Phone support', false],
                    ],
                ],
                [
                    'name' => 'Pro', 'price' => 29, 'tag' => 'For scaling businesses',
                    'featured' => false,
                    'features' => [
                        ['Unlimited users', true], ['150 GB storage', true], ['Unlimited public projects', true],
                        ['Community support', true], ['Private projects', true], ['Dedicated phone support', true],
                    ],
                ],
            ];
        @endphp
        <div x-show="tab === 1" x-transition x-cloak x-data="{ yearly: false }">
            {{-- Billing toggle --}}
            <div class="mb-8 flex items-center justify-center gap-3">
                <span :class="!yearly ? 'text-gray-800 font-semibold' : 'text-gray-400'" class="text-sm">{{ __('Monthly') }}</span>
                <button type="button" @click="yearly = !yearly" role="switch" :aria-checked="yearly"
                        :class="yearly ? 'bg-primary-500' : 'bg-gray-200'"
                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full transition focus:outline-none focus:ring-2 focus:ring-primary-200">
                    <span :class="yearly ? 'translate-x-5' : 'translate-x-0.5'"
                          class="mt-0.5 inline-block h-5 w-5 transform rounded-full bg-white shadow transition"></span>
                </button>
                <span :class="yearly ? 'text-gray-800 font-semibold' : 'text-gray-400'" class="text-sm">{{ __('Yearly') }}</span>
                <span x-show="yearly" x-transition><x-badge color="success">{{ __('Save 20%') }}</x-badge></span>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                @foreach ($classicPlans as $plan)
                    <div @class([
                        'relative flex flex-col rounded-2xl border bg-white p-6 shadow-sm shadow-black/5 transition',
                        'border-primary-500 ring-1 ring-primary-500 lg:scale-105 z-10' => $plan['featured'],
                        'border-gray-100' => ! $plan['featured'],
                    ])>
                        @if ($plan['featured'])
                            <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-primary-500 px-3 py-1 text-xs font-semibold text-white shadow">
                                <i class="ik ik-star text-[10px]"></i> {{ __('Most Popular') }}
                            </span>
                        @endif
                        <h5 class="text-sm font-semibold uppercase tracking-wide text-gray-400">{{ __($plan['name']) }}</h5>
                        <p class="mt-1 text-xs text-gray-400">{{ __($plan['tag']) }}</p>
                        <div class="mt-4 flex items-end gap-1">
                            <span class="text-4xl font-bold text-gray-800">$<span x-text="yearly ? {{ $plan['price'] * 10 }} : {{ $plan['price'] }}"></span></span>
                            <span class="mb-1 text-sm font-normal text-gray-400" x-text="yearly ? '/{{ __('year') }}' : '/{{ __('month') }}'"></span>
                        </div>
                        <hr class="my-5 border-gray-100">
                        <ul class="flex-1 space-y-3">
                            @foreach ($plan['features'] as [$label, $included])
                                <li @class(['flex items-center gap-3 text-sm', 'text-gray-600' => $included, 'text-gray-300' => ! $included])>
                                    @if ($included)
                                        <i class="ik ik-check text-base text-green-500"></i>
                                    @else
                                        <i class="ik ik-x text-base"></i>
                                    @endif
                                    {{ __($label) }}
                                </li>
                            @endforeach
                        </ul>
                        <x-button :variant="$plan['featured'] ? 'primary' : 'outline'" href="#" size="lg" class="mt-6 w-full">
                            {{ $plan['price'] === 0 ? __('Get Started') : __('Choose :name', ['name' => __($plan['name'])]) }}
                        </x-button>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- VARIATION 2 — Gradient / dark highlighted cards             --}}
        {{-- ============================================================ --}}
        @php
            $gradientPlans = [
                [
                    'name' => 'Starter', 'price' => '$9', 'icon' => 'ik ik-zap', 'popular' => false,
                    'desc' => 'Everything you need to launch.',
                    'features' => ['5 projects', '50 GB storage', 'Email support', 'Basic analytics'],
                ],
                [
                    'name' => 'Pro', 'price' => '$29', 'icon' => 'ik ik-award', 'popular' => true,
                    'desc' => 'For teams that want to move fast.',
                    'features' => ['Unlimited projects', '150 GB storage', 'Priority support', 'Advanced analytics', 'Custom domains'],
                ],
                [
                    'name' => 'Enterprise', 'price' => '$99', 'icon' => 'ik ik-users', 'popular' => false,
                    'desc' => 'Advanced controls & security.',
                    'features' => ['Everything in Pro', 'Unlimited storage', 'Dedicated manager', 'SSO & SAML', 'Audit logs'],
                ],
            ];
        @endphp
        <div x-show="tab === 2" x-transition x-cloak>
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                @foreach ($gradientPlans as $plan)
                    @if ($plan['popular'])
                        <div class="relative flex flex-col overflow-hidden rounded-2xl bg-gradient-to-br from-primary-500 to-primary-600 p-7 text-white shadow-lg shadow-primary-500/30 lg:-mt-3 lg:mb-3">
                            <span class="absolute right-5 top-5 rounded-full bg-white/20 px-3 py-1 text-xs font-semibold backdrop-blur">{{ __('Recommended') }}</span>
                            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/15 text-2xl">
                                <i class="{{ $plan['icon'] }}"></i>
                            </span>
                            <h5 class="mt-4 text-lg font-bold">{{ __($plan['name']) }}</h5>
                            <p class="mt-1 text-sm text-white/70">{{ __($plan['desc']) }}</p>
                            <div class="mt-5 flex items-end gap-1">
                                <span class="text-4xl font-extrabold">{{ $plan['price'] }}</span>
                                <span class="mb-1 text-sm text-white/70">/{{ __('month') }}</span>
                            </div>
                            <ul class="mt-6 flex-1 space-y-3">
                                @foreach ($plan['features'] as $feature)
                                    <li class="flex items-center gap-3 text-sm text-white/90">
                                        <i class="ik ik-check-circle text-base text-white"></i>{{ __($feature) }}
                                    </li>
                                @endforeach
                            </ul>
                            <a href="#" class="mt-7 inline-flex w-full items-center justify-center rounded-lg bg-white px-5 py-2.5 text-base font-semibold text-primary-600 transition hover:bg-primary-50">
                                {{ __('Start Free Trial') }}
                            </a>
                        </div>
                    @else
                        <div class="flex flex-col rounded-2xl border border-gray-100 bg-white p-7 shadow-sm shadow-black/5">
                            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-50 text-2xl text-primary-600">
                                <i class="{{ $plan['icon'] }}"></i>
                            </span>
                            <h5 class="mt-4 text-lg font-bold text-gray-800">{{ __($plan['name']) }}</h5>
                            <p class="mt-1 text-sm text-gray-400">{{ __($plan['desc']) }}</p>
                            <div class="mt-5 flex items-end gap-1">
                                <span class="text-4xl font-extrabold text-gray-800">{{ $plan['price'] }}</span>
                                <span class="mb-1 text-sm text-gray-400">/{{ __('month') }}</span>
                            </div>
                            <ul class="mt-6 flex-1 space-y-3">
                                @foreach ($plan['features'] as $feature)
                                    <li class="flex items-center gap-3 text-sm text-gray-600">
                                        <i class="ik ik-check-circle text-base text-green-500"></i>{{ __($feature) }}
                                    </li>
                                @endforeach
                            </ul>
                            <x-button variant="outline" href="#" size="lg" class="mt-7 w-full">{{ __('Choose :name', ['name' => __($plan['name'])]) }}</x-button>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- VARIATION 3 — Comparison table                              --}}
        {{-- ============================================================ --}}
        @php
            $cmpPlans = [
                ['name' => 'Free', 'price' => '$0', 'recommended' => false],
                ['name' => 'Pro', 'price' => '$29', 'recommended' => true],
                ['name' => 'Enterprise', 'price' => '$99', 'recommended' => false],
            ];
            // value per plan: true = check, false = cross, or a string value
            $cmpRows = [
                ['Users', [1, 'Unlimited', 'Unlimited']],
                ['Storage', ['5 GB', '150 GB', 'Unlimited']],
                ['Public projects', [true, true, true]],
                ['Private projects', [false, true, true]],
                ['Custom domains', [false, true, true]],
                ['Advanced analytics', [false, true, true]],
                ['Priority support', [false, true, true]],
                ['SSO & SAML', [false, false, true]],
                ['Audit logs', [false, false, true]],
                ['Dedicated manager', [false, false, true]],
            ];
        @endphp
        <div x-show="tab === 3" x-transition x-cloak>
            <x-card no-padding>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="p-5 align-bottom text-sm font-semibold text-gray-500">{{ __('Features') }}</th>
                                @foreach ($cmpPlans as $i => $plan)
                                    <th @class([
                                        'p-5 text-center align-top',
                                        'bg-primary-50/60' => $plan['recommended'],
                                    ])>
                                        @if ($plan['recommended'])
                                            <span class="mb-2 inline-block rounded-full bg-primary-500 px-2.5 py-0.5 text-[10px] font-semibold uppercase text-white">{{ __('Recommended') }}</span>
                                        @endif
                                        <div class="text-base font-bold text-gray-800">{{ __($plan['name']) }}</div>
                                        <div class="mt-1 text-2xl font-extrabold text-gray-800">{{ $plan['price'] }}<span class="text-xs font-normal text-gray-400">/mo</span></div>
                                        <x-button :variant="$plan['recommended'] ? 'primary' : 'outline'" href="#" size="sm" class="mt-3 w-full">{{ __('Select') }}</x-button>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($cmpRows as [$label, $values])
                                <tr class="hover:bg-gray-50/60">
                                    <td class="p-4 text-sm font-medium text-gray-600">{{ __($label) }}</td>
                                    @foreach ($values as $i => $value)
                                        <td @class([
                                            'p-4 text-center text-sm',
                                            'bg-primary-50/40' => $cmpPlans[$i]['recommended'],
                                        ])>
                                            @if ($value === true)
                                                <i class="ik ik-check text-lg text-green-500"></i>
                                            @elseif ($value === false)
                                                <i class="ik ik-x text-lg text-gray-300"></i>
                                            @else
                                                <span class="font-medium text-gray-700">{{ __($value) }}</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        {{-- ============================================================ --}}
        {{-- VARIATION 4 — Simple / compact cards                        --}}
        {{-- ============================================================ --}}
        @php
            $compactPlans = [
                ['name' => 'Free', 'price' => '$0', 'icon' => 'ik ik-gift', 'featured' => false,
                 'features' => ['1 user', '5 GB storage', 'Community support']],
                ['name' => 'Starter', 'price' => '$9', 'icon' => 'ik ik-zap', 'featured' => false,
                 'features' => ['5 users', '50 GB storage', 'Email support']],
                ['name' => 'Pro', 'price' => '$29', 'icon' => 'ik ik-award', 'featured' => true,
                 'features' => ['Unlimited users', '150 GB storage', 'Priority support']],
                ['name' => 'Enterprise', 'price' => '$99', 'icon' => 'ik ik-users', 'featured' => false,
                 'features' => ['Custom limits', 'Unlimited storage', 'Dedicated manager']],
            ];
        @endphp
        <div x-show="tab === 4" x-transition x-cloak>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($compactPlans as $plan)
                    <div @class([
                        'flex flex-col rounded-2xl border bg-white p-6 text-center shadow-sm shadow-black/5 transition hover:-translate-y-0.5 hover:shadow-md',
                        'border-primary-200 ring-1 ring-primary-100' => $plan['featured'],
                        'border-gray-100' => ! $plan['featured'],
                    ])>
                        <span @class([
                            'mx-auto flex h-12 w-12 items-center justify-center rounded-full text-xl',
                            'bg-primary-500 text-white' => $plan['featured'],
                            'bg-primary-50 text-primary-600' => ! $plan['featured'],
                        ])>
                            <i class="{{ $plan['icon'] }}"></i>
                        </span>
                        <h5 class="mt-4 text-sm font-semibold uppercase tracking-wide text-gray-500">{{ __($plan['name']) }}</h5>
                        <div class="mt-2 text-3xl font-bold text-gray-800">{{ $plan['price'] }}<span class="text-sm font-normal text-gray-400">/mo</span></div>
                        <ul class="my-5 flex-1 space-y-2 text-sm text-gray-600">
                            @foreach ($plan['features'] as $feature)
                                <li class="flex items-center justify-center gap-2">
                                    <i class="ik ik-check text-green-500"></i>{{ __($feature) }}
                                </li>
                            @endforeach
                        </ul>
                        <x-button :variant="$plan['featured'] ? 'primary' : 'outline'" href="#" class="w-full">{{ __('Choose Plan') }}</x-button>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
