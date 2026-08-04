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
                <a href="{{ url('/') }}" class="inline-flex text-gray-800"><x-brand-logo markClass="h-9 w-9"
                        textClass="text-xl" /></a>
                <a href="{{ url('/') }}"
                    class="inline-flex items-center gap-1 text-sm font-medium text-gray-400 transition hover:text-gray-600">
                    <i class="ik ik-arrow-left text-xs"></i> {{ __('Back to site') }}
                </a>
            </div>

            <div class="flex flex-1 items-center justify-center py-10">
                <div class="w-full max-w-sm">
                    <h1 class="text-2xl font-bold tracking-tight text-gray-800">{{ $heading }}</h1>
                    @if ($subheading)
                        <p class="mt-2 text-sm text-gray-500">{{ $subheading }}</p>
                    @endif

                    <x-alert class="mt-5" />

                    <div class="mt-7">{{ $slot }}</div>
                </div>
            </div>

            <p class="text-center text-xs text-gray-400 lg:text-left">© {{ date('Y') }} {{ config('app.name') }}.
                {{ __('All rights reserved.') }}</p>
        </div>

        {{-- Brand panel --}}
        <div
            class="relative hidden w-1/2 overflow-hidden bg-gradient-to-br from-primary-600 via-primary-600 to-violet-600 lg:block">
            <div class="absolute -left-16 top-10 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
            <div class="absolute -right-10 bottom-0 h-80 w-80 rounded-full bg-violet-400/20 blur-3xl"></div>
            <div class="absolute inset-0"
                style="background-image:radial-gradient(circle at 1px 1px, rgba(255,255,255,.14) 1px, transparent 0); background-size:26px 26px;">
            </div>

            <div class="relative flex h-full flex-col justify-center px-14 text-white xl:px-20">
                <span class="inline-flex items-center gap-2.5">
                    <span
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM12 5C13.66 5 15 6.34 15 8C15 9.66 13.66 11 12 11C10.34 11 9 9.66 9 8C9 6.34 10.34 5 12 5ZM12 19.2C9.5 19.2 7.29 17.92 6 15.98C6.03 13.99 10 12.9 12 12.9C13.99 12.9 17.97 13.99 18 15.98C16.71 17.92 14.5 19.2 12 19.2Z"
                                fill="white" />
                            <circle cx="12" cy="8" r="2" fill="rgba(255,255,255,0.5)" />
                            <path
                                d="M12 14C9.5 14 5.97 15.99 6 17.98C7.29 19.92 9.5 21.2 12 21.2C14.5 21.2 16.71 19.92 18 17.98C18.03 15.99 14.5 14 12 14Z"
                                fill="rgba(255,255,255,0.3)" />
                        </svg>
                    </span>
                    <span class="text-xl font-extrabold tracking-tight">{{ config('app.name') }}</span>
                </span>

                <h2 class="mt-12 max-w-md text-4xl font-extrabold leading-tight">
                    {{ $panelHeading ?? __('Complete Care Management Platform') }}
                </h2>
                <p class="mt-4 max-w-md text-base text-white/80">
                    {{ $panelText ?? __('Empower your care agency with comprehensive tools for care planning, medication management, shift scheduling, and family engagement—all in one place.') }}
                </p>

                <ul class="mt-9 space-y-3.5 text-sm text-white/90">
                    <li class="flex items-center gap-3">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-white/15">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                        </span>
                        {{ __('Person-centred care plans & assessments') }}
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-white/15">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                        </span>
                        {{ __('MAR charts & medication administration') }}
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-white/15">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                        </span>
                        {{ __('Shift scheduling with GPS check-in/out') }}
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-white/15">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                        </span>
                        {{ __('Compliance, safeguarding & audit trails') }}
                    </li>
                </ul>

                <div
                    class="mt-12 flex max-w-md items-center gap-4 rounded-2xl bg-white/10 p-4 ring-1 ring-white/15 backdrop-blur">
                    <div class="flex -space-x-2">
                        <span
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-white/25 text-xs font-bold ring-2 ring-primary-600">HC</span>
                        <span
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-white/25 text-xs font-bold ring-2 ring-primary-600">CA</span>
                        <span
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-white/25 text-xs font-bold ring-2 ring-primary-600">+</span>
                    </div>
                    <p class="text-sm text-white/85">
                        {{ __('Trusted by care agencies delivering quality person-centred care.') }}</p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
