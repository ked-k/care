@props([
    'title' => null,
    'heading' => '',
    'subheading' => null,
    'panelHeading' => null,
    'panelText' => null,
])

@php $title = $title ?? config('app.name').' — '.config('app.tagline'); @endphp

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('img/radminly-mark.svg') }}" />
    <link rel="alternate icon" href="{{ asset('favicon.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('plugins/icon-kit/dist/css/iconkit.min.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white font-sans text-gray-600 antialiased">
    <div class="flex min-h-screen">

        {{-- Form column --}}
        <div class="flex w-full flex-col px-6 py-8 sm:px-10 lg:w-1/2 xl:px-16">
            <div class="flex items-center justify-between">
                <a href="{{ url('/') }}" class="inline-flex text-gray-800"><x-brand-logo markClass="h-9 w-9" textClass="text-xl" /></a>
                <a href="{{ url('/') }}" class="inline-flex items-center gap-1 text-sm font-medium text-gray-400 transition hover:text-gray-600">
                    <i class="ik ik-arrow-left text-xs"></i> {{ __('Back to site') }}
                </a>
            </div>

            <div class="flex flex-1 items-center justify-center py-10">
                <div class="w-full max-w-sm">
                    <h1 class="text-2xl font-bold tracking-tight text-gray-800">{{ $heading }}</h1>
                    @if ($subheading)<p class="mt-2 text-sm text-gray-500">{{ $subheading }}</p>@endif

                    <x-alert class="mt-5" />

                    <div class="mt-7">{{ $slot }}</div>
                </div>
            </div>

            <p class="text-center text-xs text-gray-400 lg:text-left">© {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}</p>
        </div>

        {{-- Brand panel --}}
        <div class="relative hidden w-1/2 overflow-hidden bg-gradient-to-br from-primary-600 via-primary-600 to-violet-600 lg:block">
            <div class="absolute -left-16 top-10 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
            <div class="absolute -right-10 bottom-0 h-80 w-80 rounded-full bg-violet-400/20 blur-3xl"></div>
            <div class="absolute inset-0" style="background-image:radial-gradient(circle at 1px 1px, rgba(255,255,255,.14) 1px, transparent 0); background-size:26px 26px;"></div>

            <div class="relative flex h-full flex-col justify-center px-14 text-white xl:px-20">
                <span class="inline-flex items-center gap-2.5">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25">
                        <img src="{{ asset('img/radminly-mark.svg') }}" alt="" class="h-7 w-7">
                    </span>
                    <span class="text-xl font-extrabold tracking-tight">{{ config('app.name') }}</span>
                </span>

                <h2 class="mt-12 max-w-md text-4xl font-extrabold leading-tight">
                    {{ $panelHeading ?? __('Ship beautiful admin panels, faster.') }}
                </h2>
                <p class="mt-4 max-w-md text-base text-white/80">
                    {{ $panelText ?? __('The modern Laravel 12 admin starter — roles, permissions, 25+ Tailwind components and a polished, dark-mode-ready UI.') }}
                </p>

                <ul class="mt-9 space-y-3.5 text-sm text-white/90">
                    <li class="flex items-center gap-3"><span class="flex h-6 w-6 items-center justify-center rounded-full bg-white/15"><i class="ik ik-check text-xs"></i></span> {{ __('Granular roles & permissions') }}</li>
                    <li class="flex items-center gap-3"><span class="flex h-6 w-6 items-center justify-center rounded-full bg-white/15"><i class="ik ik-check text-xs"></i></span> {{ __('25+ reusable Blade components') }}</li>
                    <li class="flex items-center gap-3"><span class="flex h-6 w-6 items-center justify-center rounded-full bg-white/15"><i class="ik ik-check text-xs"></i></span> {{ __('Dark mode & live theme customizer') }}</li>
                </ul>

                <div class="mt-12 flex max-w-md items-center gap-4 rounded-2xl bg-white/10 p-4 ring-1 ring-white/15 backdrop-blur">
                    <div class="flex -space-x-2">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white/25 text-xs font-bold ring-2 ring-primary-600">RA</span>
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white/25 text-xs font-bold ring-2 ring-primary-600">TM</span>
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white/25 text-xs font-bold ring-2 ring-primary-600">+</span>
                    </div>
                    <p class="text-sm text-white/85">{{ __('Trusted by developers building admin panels on Laravel.') }}</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
