<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>{{ config('app.name') }} — {{ config('app.tagline') }}</title>
    <meta name="description" content="{{ config('app.description') }}">
    <meta name="keywords" content="Laravel admin template, Tailwind admin, roles and permissions, Laravel 12, Alpine.js">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="{{ asset('img/radminly-mark.svg') }}" />
    <link rel="alternate icon" href="{{ asset('favicon.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('plugins/icon-kit/dist/css/iconkit.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-white font-sans text-gray-700 antialiased">
    <div class="mx-auto max-w-5xl px-6">
        {{-- Header --}}
        <header class="flex items-center justify-between py-6">
            <a href="{{ url('/') }}" class="text-gray-800">
                <x-brand-logo markClass="h-8 w-8" textClass="text-lg" />
            </a>
            <nav class="flex items-center gap-2">
                <x-button variant="ghost" href="https://themicly.com/radminly/docs/">{{ __('Docs') }}</x-button>
                <x-button variant="primary" href="{{ url('login') }}/">{{ __('Live demo') }}</x-button>
            </nav>
        </header>

        {{-- Hero --}}
        <section class="mx-auto max-w-2xl pt-16 pb-12 text-center sm:pt-24">
            <span class="inline-flex items-center gap-2 rounded-full border border-gray-200 px-3 py-1 text-xs font-medium text-gray-500">
                <span class="h-1.5 w-1.5 rounded-full bg-primary-500"></span>
                {{ __('Laravel 12 · Tailwind · Alpine.js') }}
            </span>
            <h1 class="mt-6 text-4xl font-extrabold leading-[1.1] tracking-tight text-gray-900 sm:text-5xl">
                {{ __('Ship beautiful admin') }}<br class="hidden sm:block"> {{ __('panels, faster.') }}
            </h1>
            <p class="mx-auto mt-5 max-w-lg text-lg leading-relaxed text-gray-500">
                {{ __('Roles, permissions, ready-made modules and a refined Tailwind UI — ready out of the box.') }}
            </p>
            <div class="mt-8 flex items-center justify-center gap-3">
                <x-button variant="primary" size="lg" href="{{ url('login') }}/">{{ __('View live demo') }}</x-button>
                <x-button variant="outline" size="lg" href="https://themicly.com/radminly/docs/">{{ __('Documentation') }}</x-button>
            </div>
        </section>

        {{-- Preview: live dashboard mockup --}}
        <section id="live-preview" class="pt-4 pb-20">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-300/40">
                <div class="flex items-center gap-1.5 border-b border-slate-100 bg-slate-50 px-4 py-3">
                    <span class="h-3 w-3 rounded-full bg-red-400"></span>
                    <span class="h-3 w-3 rounded-full bg-amber-400"></span>
                    <span class="h-3 w-3 rounded-full bg-green-400"></span>
                    <span class="ml-3 rounded-md bg-white px-3 py-1 text-xs text-slate-400 ring-1 ring-slate-200">radminly.themicly.com</span>
                </div>
                <div class="flex h-[26rem] text-left">
                    {{-- faux sidebar --}}
                    <aside class="hidden w-52 shrink-0 flex-col gap-1 bg-slate-900 p-4 sm:flex">
                        <div class="mb-4 flex items-center gap-2 px-1">
                            <img src="{{ asset('img/radminly-mark.svg') }}" class="h-7 w-7" alt="">
                            <span class="font-bold text-white">Radminly</span>
                        </div>
                        <div class="flex items-center gap-2 rounded-lg brand-grad px-3 py-2 text-sm font-medium text-white">Dashboard</div>
                        <div class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-300">Inventory</div>
                        <div class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-300">Accounting</div>
                        <div class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-300">Roles</div>
                        <div class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-300">Reports</div>
                        <div class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-300">Settings</div>
                    </aside>
                    {{-- faux content --}}
                    <div class="flex-1 overflow-hidden bg-slate-50 p-5">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <div class="h-4 w-32 rounded bg-slate-300"></div>
                                <div class="mt-2 h-3 w-44 rounded bg-slate-200"></div>
                            </div>
                            <div class="h-9 w-28 rounded-lg brand-grad"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                            <div class="rounded-xl border border-slate-100 bg-white p-4">
                                <div class="h-2.5 w-12 rounded bg-slate-200"></div>
                                <div class="mt-2 h-5 w-16 rounded bg-slate-800"></div>
                                <div class="mt-2 h-2 w-10 rounded bg-emerald-300"></div>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-white p-4">
                                <div class="h-2.5 w-12 rounded bg-slate-200"></div>
                                <div class="mt-2 h-5 w-16 rounded bg-slate-800"></div>
                                <div class="mt-2 h-2 w-10 rounded bg-blue-300"></div>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-white p-4">
                                <div class="h-2.5 w-12 rounded bg-slate-200"></div>
                                <div class="mt-2 h-5 w-16 rounded bg-slate-800"></div>
                                <div class="mt-2 h-2 w-10 rounded bg-violet-300"></div>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-white p-4">
                                <div class="h-2.5 w-12 rounded bg-slate-200"></div>
                                <div class="mt-2 h-5 w-16 rounded bg-slate-800"></div>
                                <div class="mt-2 h-2 w-10 rounded bg-amber-300"></div>
                            </div>
                        </div>
                        <div class="mt-3 grid grid-cols-1 gap-3 lg:grid-cols-3">
                            <div class="rounded-xl border border-slate-100 bg-white p-4 lg:col-span-2">
                                <div class="h-3 w-24 rounded bg-slate-200"></div>
                                <div class="mt-4 flex h-32 items-end gap-2">
                                    <div class="w-full rounded-t brand-grad" style="height:40%"></div>
                                    <div class="w-full rounded-t brand-grad" style="height:65%"></div>
                                    <div class="w-full rounded-t brand-grad" style="height:50%"></div>
                                    <div class="w-full rounded-t brand-grad" style="height:80%"></div>
                                    <div class="w-full rounded-t brand-grad" style="height:55%"></div>
                                    <div class="w-full rounded-t brand-grad" style="height:90%"></div>
                                    <div class="w-full rounded-t brand-grad" style="height:70%"></div>
                                </div>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-white p-4">
                                <div class="h-3 w-20 rounded bg-slate-200"></div>
                                <div class="mt-4 space-y-3">
                                    <div class="flex items-center gap-2"><div class="h-7 w-7 rounded-full bg-slate-200"></div><div class="h-2.5 flex-1 rounded bg-slate-200"></div></div>
                                    <div class="flex items-center gap-2"><div class="h-7 w-7 rounded-full bg-slate-200"></div><div class="h-2.5 flex-1 rounded bg-slate-200"></div></div>
                                    <div class="flex items-center gap-2"><div class="h-7 w-7 rounded-full bg-slate-200"></div><div class="h-2.5 flex-1 rounded bg-slate-200"></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- Footer --}}
    <footer class="border-t border-gray-100">
        <div class="mx-auto flex max-w-5xl flex-col items-center justify-between gap-4 px-6 py-8 sm:flex-row">
            <p class="text-sm text-gray-400">&copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}</p>
            <div class="flex items-center gap-2">
                <a href="https://themicly.com" aria-label="Website" class="flex h-9 w-9 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"><i class="ik ik-globe"></i></a>
                <a href="https://github.com/themicly" aria-label="GitHub" class="flex h-9 w-9 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"><i class="fab fa-github"></i></a>
                <a href="https://twitter.com/themiclyBd" aria-label="Twitter" class="flex h-9 w-9 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"><i class="fab fa-twitter"></i></a>
                <a href="https://www.linkedin.com/in/rakibulislam95" aria-label="LinkedIn" class="flex h-9 w-9 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"><i class="fab fa-linkedin"></i></a>
            </div>
        </div>
    </footer>
</body>
</html>
