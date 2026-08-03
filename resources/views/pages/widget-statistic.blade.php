@extends('layouts.main')
@section('title', 'Widget Statistic')
@section('content')
    <x-page-header title="{{ __('Widget Statistic') }}" subtitle="{{ __('lorem ipsum dolor sit amet, consectetur adipisicing elit') }}" icon="ik ik-layers" :breadcrumbs="['Home' => url('dashboard'), 'Widgets' => '#', 'Widget Statistic' => null]" />

    <!-- page statistic gradient stat cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $pageStats = [
                ['2,563', 'Visits', 'ik ik-user', 'accent', '+6.2%', true, [8,12,9,14,11,16]],
                ['3,612', 'Orders', 'ik ik-shopping-cart', 'primary', '+8.1%', true, [5,8,6,9,12,14]],
                ['8,654', 'Likes', 'ik ik-thumbs-up', 'green', '+12.4%', true, [10,12,11,14,13,17]],
                ['3,550', 'Reviews', 'ik ik-volume-2', 'amber', '-1.2%', false, [14,12,15,11,13,10]],
            ];
        @endphp
        @foreach ($pageStats as [$value, $label, $icon, $color, $trend, $up, $spark])
            <x-stat-card :value="$value" :label="__($label)" :icon="$icon" :color="$color"
                         :trend="$trend" :trendUp="$up" :spark="$spark" variant="gradient" />
        @endforeach
    </div>

    <!-- Project statistic -->
    <div class="mt-5">
        <x-card>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
                @php
                    $projStats = [
                        ['Published Project', '532', '+1.69%', 'text-green-600', 25, 'bg-accent-500'],
                        ['Completed Task', '4,569', '-0.5%', 'text-accent-600', 65, 'bg-primary-500'],
                        ['Successfull Task', '89%', '+0.99%', 'text-green-600', 85, 'bg-green-500'],
                        ['Ongoing Project', '365', '+0.35%', 'text-green-600', 45, 'bg-amber-500'],
                    ];
                @endphp
                @foreach ($projStats as [$label, $value, $delta, $deltaClass, $width, $barColor])
                    <div>
                        <h6 class="text-sm font-medium text-gray-500">{{ __($label) }}</h6>
                        <h5 class="mb-3 text-xl font-bold text-gray-700">{{ $value }}<span class="ml-2 text-sm font-medium {{ $deltaClass }}">{{ $delta }}</span></h5>
                        <div class="h-1.5 rounded-full bg-gray-100">
                            <div class="h-full rounded-full {{ $barColor }}" style="width:{{ $width }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>
    </div>

    <!-- social statistic -->
    <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @php
            $social = [
                ['fab fa-facebook-f', '3.56k', 'Likes', 'fas fa-arrow-up', 'text-green-500'],
                ['fab fa-twitter', '3k', 'Followers', 'fas fa-arrow-up', 'text-green-500'],
                ['fab fa-linkedin-in', '2k', 'Connections', 'fas fa-arrow-down', 'text-accent-500'],
            ];
        @endphp
        @foreach ($social as [$icon, $value, $label, $arrow, $arrowClass])
            <x-card>
                <div class="flex items-center justify-between">
                    <h3 class="text-2xl font-bold text-gray-700"><i class="{{ $icon }} mr-2 text-primary-500"></i>{{ $value }}</h3>
                    <h5 class="font-medium text-gray-500">{{ $label }}</h5>
                    <i class="{{ $arrow }} {{ $arrowClass }}"></i>
                </div>
            </x-card>
        @endforeach
    </div>

    <!-- impression, goal, impact -->
    <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @php
            $compCards = [
                ['Impressions', '1,563', 'May 23 - June 01 (2017)', 'fas fa-eye', 'from-primary-500 to-primary-600'],
                ['Goal', '30,564', 'May 23 - June 01 (2017)', 'fas fa-bullseye', 'from-green-500 to-green-600'],
                ['Impact', '42.6%', 'May 23 - June 01 (2017)', 'fas fa-hand-paper', 'from-amber-400 to-amber-500'],
            ];
        @endphp
        @foreach ($compCards as [$label, $value, $sub, $icon, $gradient])
            <x-card>
                <div class="flex items-center justify-between">
                    <div>
                        <h6 class="mb-2 text-sm text-gray-500">{{ $label }}</h6>
                        <h3 class="text-2xl font-bold text-gray-700">{{ $value }}</h3>
                        <p class="mt-1 text-xs text-gray-400">{{ $sub }}</p>
                    </div>
                    <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-gradient-to-br {{ $gradient }} text-white">
                        <i class="{{ $icon }}"></i>
                    </span>
                </div>
            </x-card>
        @endforeach
    </div>

    <!-- project-ticket -->
    <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @php
            $projTickets = [
                ['far fa-calendar-check', 'Ticket Answered', '327', '10 Days', '53%', 'text-accent-500', 'bg-accent-500'],
                ['fas fa-paper-plane', 'Project Launched', '3 Year', '623', '76%', 'text-green-500', 'bg-green-500'],
                ['fas fa-lightbulb', 'Unique Innovation', '1 Month', '248', '73%', 'text-amber-500', 'bg-amber-500'],
            ];
        @endphp
        @foreach ($projTickets as [$icon, $title, $left, $right, $pct, $textClass, $bgClass])
            <x-card class="relative">
                <div class="mb-6 flex items-center gap-3">
                    <i class="{{ $icon }} {{ $textClass }} text-3xl"></i>
                    <div>
                        <h6 class="text-sm font-semibold text-gray-700">{{ $title }}</h6>
                        <h6 class="text-sm {{ $textClass }}">Live Update</h6>
                    </div>
                </div>
                <div class="grid grid-cols-3 items-center text-center">
                    <h6 class="font-semibold text-gray-700">{{ $left }}</h6>
                    <i class="fas fa-exchange-alt {{ $textClass }}"></i>
                    <h6 class="font-semibold text-gray-700">{{ $right }}</h6>
                </div>
                <span class="absolute right-5 top-5 rounded {{ $bgClass }} px-2 py-0.5 text-xs font-medium text-white">{{ $pct }}</span>
            </x-card>
        @endforeach
    </div>

    <!-- product profit -->
    <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $prodProfit = [
                ['Total Profit', '$1,783', 'fa fa-money-bill-alt', '+11%', 'accent', [8,11,9,13,12,16]],
                ['Total Orders', '15,830', 'fas fa-database', '+12%', 'primary', [6,9,7,11,10,14]],
                ['Average Price', '$6,780', 'fas fa-dollar-sign', '+52%', 'green', [9,12,10,15,13,18]],
                ['Product Sold', '6,784', 'fas fa-tags', '+52%', 'amber', [10,13,11,16,14,19]],
            ];
        @endphp
        @foreach ($prodProfit as [$label, $value, $icon, $delta, $color, $spark])
            <x-stat-card :value="$value" :label="$label" :icon="$icon" :color="$color"
                         :trend="$delta" :trendUp="true" :spark="$spark" variant="gradient" />
        @endforeach
    </div>

    <!-- ticket, proj, client -->
    <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $tickets = [
                ['fas fa-folder-open', 'Open Tickets', '128', 'Tickets', 'fas fa-caret-down', 'text-accent-500', 'bg-accent-500'],
                ['fas fa-file-archive', 'Close Tickets', '134', 'Tickets', 'fas fa-caret-up', 'text-primary-600', 'bg-primary-500'],
                ['fas fa-users', 'New Clients', '307', 'Clients', 'fas fa-caret-up', 'text-green-500', 'bg-green-500'],
                ['fas fa-database', 'New Orders', '231', 'Orders', 'fas fa-caret-up', 'text-amber-500', 'bg-amber-500'],
            ];
        @endphp
        @foreach ($tickets as [$icon, $label, $value, $unit, $caret, $textClass, $bgClass])
            <x-card>
                <p class="mb-6 inline-flex items-center gap-2 rounded {{ $bgClass }} px-3 py-1.5 text-sm font-medium text-white"><i class="{{ $icon }}"></i> {{ $label }}</p>
                <div class="text-center">
                    <h2 class="inline-block text-3xl font-bold {{ $textClass }}">{{ $value }}</h2>
                    <p class="ml-2 inline-block text-gray-500">{{ $unit }}</p>
                    <p class="mt-4 text-sm text-gray-500"><i class="{{ $caret }} mr-2 {{ $textClass }}"></i>From Previous Month</p>
                </div>
            </x-card>
        @endforeach
    </div>

    <!-- analytic cards -->
    <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @php
            $analytics = [
                ['fas fa-shopping-cart', '15,678', 'Total Sales', 'Total Income : ', '$2,451', 'fas fa-caret-up', '10%', 'from-green-500 to-green-600'],
                ['fas fa-users', '1,678', 'Total Users', 'Total Revenue : ', '$2,451', 'fas fa-caret-up', '30%', 'from-primary-500 to-primary-600'],
                ['fas fa-file-code', '15,678', 'Total Project', 'Active Projects : ', '$2,451', 'fas fa-caret-down', '10%', 'from-accent-500 to-accent-600'],
            ];
        @endphp
        @foreach ($analytics as [$icon, $value, $label, $subLabel, $subValue, $caret, $pct, $gradient])
            <div class="rounded-xl bg-gradient-to-br {{ $gradient }} p-5 text-white shadow-sm">
                <div class="mb-6 flex items-center justify-between">
                    <i class="{{ $icon }} text-xl text-white/40"></i>
                    <div class="text-right">
                        <h3 class="mb-1 text-2xl font-bold">{{ $value }}</h3>
                        <h6 class="text-sm text-white/80">{{ $label }}</h6>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <p class="text-sm text-white/90">{{ $subLabel }}<span class="ml-2 font-semibold">{{ $subValue }}</span></p>
                    <h6 class="text-sm font-medium"><i class="{{ $caret }} mr-2"></i>{{ $pct }}</h6>
                </div>
            </div>
        @endforeach
    </div>

    <!-- social source -->
    <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $sources = [
                ['Facebook Source', [['Page Profile', 25], ['Favorite', 85], ['Like Story', 65]]],
                ['Twitter Source', [['Wall Profile', 85], ['Favorite', 25], ['Like Tweets', 65]]],
                ['Google+ Source', [['Profile', 65], ['Connect', 15], ['Like News', 95]]],
                ['Linkedin Source', [['Profile', 45], ['Connect', 85], ['Like Posts', 35]]],
            ];
        @endphp
        @foreach ($sources as [$title, $rows])
            <x-card>
                <x-slot:header>{{ __($title) }}</x-slot:header>
                @foreach ($rows as [$rowLabel, $width])
                    <p class="mb-2 text-sm text-gray-600">{{ $rowLabel }}</p>
                    <div class="@if (! $loop->last) mb-6 @endif h-2 rounded-full bg-gray-100">
                        <div class="h-full rounded-full bg-primary-500" style="width:{{ $width }}%"></div>
                    </div>
                @endforeach
            </x-card>
        @endforeach
    </div>

    <!-- product statistic -->
    <div class="mt-5">
        <x-card>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
                @php
                    $prodProgress = [
                        ['fas fa-cube', '2476', 'Total Product', 'fas fa-long-arrow-alt-up', '64%', 'text-primary-600', 'bg-primary-500', 45],
                        ['fas fa-tag', '843', 'New Orders', 'fas fa-long-arrow-alt-down', '34%', 'text-accent-600', 'bg-accent-500', 75],
                        ['fas fa-random', '63%', 'Conversion Rate', 'fas fa-long-arrow-alt-up', '64%', 'text-amber-600', 'bg-amber-500', 65],
                        ['fas fa-dollar-sign', '41M', 'Conversion Rate', 'fas fa-long-arrow-alt-up', '54%', 'text-green-600', 'bg-green-500', 35],
                    ];
                @endphp
                @foreach ($prodProgress as [$icon, $value, $label, $arrow, $pct, $textClass, $barClass, $width])
                    <div>
                        <div class="mb-5 flex items-center justify-between">
                            <i class="{{ $icon }} text-2xl text-gray-400"></i>
                            <h2 class="text-2xl font-bold {{ $textClass }}">{{ $value }}</h2>
                        </div>
                        <div class="mb-3 flex items-center justify-between">
                            <p class="text-sm text-gray-600">{{ $label }}</p>
                            <p class="text-sm {{ $textClass }}"><i class="{{ $arrow }} mr-2"></i>{{ $pct }}</p>
                        </div>
                        <div class="h-2 rounded-full bg-gray-100">
                            <div class="h-full rounded-full {{ $barClass }}" style="width:{{ $width }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>
    </div>

    <!-- social count cards -->
    <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $socialCards = [
                ['fab fa-facebook-f', '6,750', 'Likes', 'fas fa-caret-up', '223 from last 7 days', 'text-green-500'],
                ['fab fa-twitter', '9,752', 'Tweets', 'fas fa-caret-up', '623 from last 7 days', 'text-green-500'],
                ['fab fa-dribbble', '8,752', 'Followers', 'fas fa-caret-up', '623 from last 7 days', 'text-green-500'],
                ['fab fa-linkedin-in', '952', 'Followers', 'fas fa-caret-down', '98 from last 7 days', 'text-accent-500'],
            ];
        @endphp
        @foreach ($socialCards as [$icon, $value, $label, $caret, $note, $caretClass])
            <x-card>
                <div class="text-center">
                    <h2 class="mb-5 text-3xl text-primary-500"><i class="{{ $icon }}"></i></h2>
                    <h3 class="text-2xl font-bold text-gray-700">{{ $value }}</h3>
                    <p class="text-gray-500">{{ $label }}</p>
                    <p class="mt-4 text-sm text-gray-500"><i class="{{ $caret }} mr-2 {{ $caretClass }}"></i>{{ $note }}</p>
                </div>
            </x-card>
        @endforeach
    </div>

    <!-- social content cards -->
    <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @php
            $socialContent = [
                ['fab fa-facebook-f', 'from-primary-500 to-primary-600'],
                ['fab fa-twitter', 'from-primary-400 to-primary-500'],
                ['fab fa-google-plus-g', 'from-accent-500 to-accent-600'],
            ];
        @endphp
        @foreach ($socialContent as [$icon, $gradient])
            <div class="relative overflow-hidden rounded-xl bg-gradient-to-br {{ $gradient }} p-5 text-white shadow-sm">
                <p class="max-w-[80%] text-sm text-white/90">Lorem Ipsum is simply of the dumy scrambled it to make a type specimen book.</p>
                <div class="mt-4 flex gap-6">
                    <div class="text-sm"><i class="far fa-thumbs-up mr-2"></i>5</div>
                    <div class="text-sm"><i class="far fa-comments mr-2"></i>1</div>
                </div>
                <i class="{{ $icon }} absolute -bottom-4 -right-2 text-7xl text-white/20"></i>
            </div>
        @endforeach
    </div>
@endsection
