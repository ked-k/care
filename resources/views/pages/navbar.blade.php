@extends('layouts.main')
@section('title', 'Navigation')
@section('content')
    <x-page-header title="{{ __('Navigation') }}"
                   subtitle="{{ __('Live Tailwind + Alpine navigation components — navbars, dropdowns, tabs & breadcrumbs') }}"
                   icon="ik ik-menu"
                   :breadcrumbs="['Home' => url('dashboard'), 'Navigation' => null]" />

    @php
        $links = [
            ['label' => __('Home'),      'icon' => 'ik ik-home',       'active' => true],
            ['label' => __('Dashboard'), 'icon' => 'ik ik-pie-chart',  'active' => false],
            ['label' => __('Projects'),  'icon' => 'ik ik-layers',     'active' => false],
            ['label' => __('Reports'),   'icon' => 'ik ik-bar-chart-2','active' => false],
            ['label' => __('Settings'),  'icon' => 'ik ik-settings',   'active' => false],
        ];
    @endphp

    <div class="space-y-6">

        {{-- 1. LIGHT HORIZONTAL NAVBAR --}}
        <x-card no-padding>
            <x-slot:header>{{ __('Light Navbar') }}</x-slot:header>

            <nav x-data="{ open: false }" class="rounded-b-2xl bg-white">
                <div class="flex items-center justify-between gap-4 px-5 py-3">
                    {{-- Brand --}}
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-primary-500 to-primary-600 text-white">
                            <i class="ik ik-zap"></i>
                        </span>
                        <span class="text-lg font-bold text-gray-800">Radminly</span>
                    </div>

                    {{-- Desktop links --}}
                    <ul class="hidden items-center gap-1 lg:flex">
                        @foreach ($links as $link)
                            <li>
                                <a href="#"
                                   class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition
                                          {{ $link['active'] ? 'bg-primary-50 text-primary-600' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                                    <i class="{{ $link['icon'] }}"></i>{{ $link['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    {{-- Right: search + user --}}
                    <div class="flex items-center gap-2">
                        <div class="relative hidden md:block">
                            <i class="ik ik-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400"></i>
                            <input type="search" placeholder="{{ __('Search…') }}"
                                   class="w-44 rounded-lg border border-gray-200 bg-gray-50 py-2 pl-9 pr-3 text-sm text-gray-700 placeholder-gray-400 focus:border-primary-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-100">
                        </div>

                        <x-dropdown align="right" width="w-52">
                            <x-slot:trigger>
                                <button class="flex items-center gap-2 rounded-lg p-1 pr-2 hover:bg-gray-100">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 text-sm font-semibold text-primary-700">RA</span>
                                    <span class="hidden text-sm font-medium text-gray-700 sm:block">{{ __('Rakib') }}</span>
                                    <i class="ik ik-chevron-down text-xs text-gray-400"></i>
                                </button>
                            </x-slot:trigger>
                            <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"><i class="ik ik-user"></i>{{ __('Profile') }}</a>
                            <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"><i class="ik ik-settings"></i>{{ __('Settings') }}</a>
                            <div class="my-1 border-t border-gray-100"></div>
                            <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm text-accent-600 hover:bg-accent-50"><i class="ik ik-power"></i>{{ __('Logout') }}</a>
                        </x-dropdown>

                        {{-- Hamburger --}}
                        <button @click="open = ! open" class="rounded-lg p-2 text-gray-600 hover:bg-gray-100 lg:hidden">
                            <i class="ik" :class="open ? 'ik-x' : 'ik-menu'"></i>
                        </button>
                    </div>
                </div>

                {{-- Mobile collapsible menu --}}
                <div x-show="open" x-cloak x-collapse
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="border-t border-gray-100 px-5 py-3 lg:hidden">
                    <ul class="space-y-1">
                        @foreach ($links as $link)
                            <li>
                                <a href="#"
                                   class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium
                                          {{ $link['active'] ? 'bg-primary-50 text-primary-600' : 'text-gray-600 hover:bg-gray-100' }}">
                                    <i class="{{ $link['icon'] }}"></i>{{ $link['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </nav>
            <p class="px-5 py-3 text-xs text-gray-400">{{ __('Resize the window — the links collapse into the hamburger on small screens.') }}</p>
        </x-card>

        {{-- 2. DARK NAVBAR --}}
        <x-card no-padding>
            <x-slot:header>{{ __('Dark Navbar') }}</x-slot:header>

            <nav x-data="{ open: false }" class="bg-slate-900 text-slate-100">
                <div class="flex items-center justify-between gap-4 px-5 py-3">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-500 text-white">
                            <i class="ik ik-zap"></i>
                        </span>
                        <span class="text-lg font-bold">Radminly</span>
                    </div>

                    <ul class="hidden items-center gap-1 lg:flex">
                        @foreach ($links as $link)
                            <li>
                                <a href="#"
                                   class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition
                                          {{ $link['active'] ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                    <i class="{{ $link['icon'] }}"></i>{{ $link['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="flex items-center gap-2">
                        <div class="relative hidden md:block">
                            <i class="ik ik-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400"></i>
                            <input type="search" placeholder="{{ __('Search…') }}"
                                   class="w-44 rounded-lg border border-white/10 bg-white/5 py-2 pl-9 pr-3 text-sm text-gray-100 placeholder-gray-400 focus:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                        </div>

                        <x-dropdown align="right" width="w-52">
                            <x-slot:trigger>
                                <button class="flex items-center gap-2 rounded-lg p-1 pr-2 hover:bg-white/10">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-500 text-sm font-semibold text-white">RA</span>
                                    <span class="hidden text-sm font-medium sm:block">{{ __('Rakib') }}</span>
                                    <i class="ik ik-chevron-down text-xs text-gray-400"></i>
                                </button>
                            </x-slot:trigger>
                            <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"><i class="ik ik-user"></i>{{ __('Profile') }}</a>
                            <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"><i class="ik ik-settings"></i>{{ __('Settings') }}</a>
                            <div class="my-1 border-t border-gray-100"></div>
                            <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm text-accent-600 hover:bg-accent-50"><i class="ik ik-power"></i>{{ __('Logout') }}</a>
                        </x-dropdown>

                        <button @click="open = ! open" class="rounded-lg p-2 text-gray-300 hover:bg-white/10 lg:hidden">
                            <i class="ik" :class="open ? 'ik-x' : 'ik-menu'"></i>
                        </button>
                    </div>
                </div>

                <div x-show="open" x-cloak x-collapse class="border-t border-white/10 px-5 py-3 lg:hidden">
                    <ul class="space-y-1">
                        @foreach ($links as $link)
                            <li>
                                <a href="#"
                                   class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium
                                          {{ $link['active'] ? 'bg-white/10 text-white' : 'text-gray-300 hover:bg-white/5' }}">
                                    <i class="{{ $link['icon'] }}"></i>{{ $link['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </nav>
        </x-card>

        {{-- 3. COLORED / BRAND NAVBAR --}}
        <x-card no-padding>
            <x-slot:header>{{ __('Brand Navbar (gradient)') }}</x-slot:header>

            <nav x-data="{ open: false }" class="bg-gradient-to-r from-primary-500 to-primary-600 text-white">
                <div class="flex items-center justify-between gap-4 px-5 py-3">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/20 text-white">
                            <i class="ik ik-zap"></i>
                        </span>
                        <span class="text-lg font-bold">Radminly</span>
                    </div>

                    <ul class="hidden items-center gap-1 lg:flex">
                        @foreach ($links as $link)
                            <li>
                                <a href="#"
                                   class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition
                                          {{ $link['active'] ? 'bg-white/20 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                                    <i class="{{ $link['icon'] }}"></i>{{ $link['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="flex items-center gap-2">
                        <div class="relative hidden md:block">
                            <i class="ik ik-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-white/70"></i>
                            <input type="search" placeholder="{{ __('Search…') }}"
                                   class="w-44 rounded-lg border border-white/20 bg-white/10 py-2 pl-9 pr-3 text-sm text-white placeholder-white/70 focus:border-white/40 focus:bg-white/20 focus:outline-none">
                        </div>

                        <x-dropdown align="right" width="w-52">
                            <x-slot:trigger>
                                <button class="flex items-center gap-2 rounded-lg p-1 pr-2 hover:bg-white/15">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white/25 text-sm font-semibold text-white">RA</span>
                                    <span class="hidden text-sm font-medium sm:block">{{ __('Rakib') }}</span>
                                    <i class="ik ik-chevron-down text-xs text-white/80"></i>
                                </button>
                            </x-slot:trigger>
                            <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"><i class="ik ik-user"></i>{{ __('Profile') }}</a>
                            <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"><i class="ik ik-settings"></i>{{ __('Settings') }}</a>
                            <div class="my-1 border-t border-gray-100"></div>
                            <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm text-accent-600 hover:bg-accent-50"><i class="ik ik-power"></i>{{ __('Logout') }}</a>
                        </x-dropdown>

                        <button @click="open = ! open" class="rounded-lg p-2 text-white hover:bg-white/15 lg:hidden">
                            <i class="ik" :class="open ? 'ik-x' : 'ik-menu'"></i>
                        </button>
                    </div>
                </div>

                <div x-show="open" x-cloak x-collapse class="border-t border-white/20 px-5 py-3 lg:hidden">
                    <ul class="space-y-1">
                        @foreach ($links as $link)
                            <li>
                                <a href="#"
                                   class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium
                                          {{ $link['active'] ? 'bg-white/20 text-white' : 'text-white/80 hover:bg-white/10' }}">
                                    <i class="{{ $link['icon'] }}"></i>{{ $link['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </nav>
        </x-card>

        {{-- 4. NAVBAR WITH DROPDOWN MENUS --}}
        <x-card no-padding>
            <x-slot:header>{{ __('Navbar with Dropdown Menus') }}</x-slot:header>

            <nav class="rounded-b-2xl bg-white px-5 py-3">
                <ul class="flex flex-wrap items-center gap-1">
                    {{-- active link --}}
                    <li>
                        <a href="#" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold text-primary-600 underline decoration-2 underline-offset-8">
                            <i class="ik ik-home"></i>{{ __('Home') }}
                        </a>
                    </li>

                    {{-- dropdown 1 --}}
                    <li x-data="{ open: false }" class="relative" @click.outside="open = false">
                        <button @click="open = ! open"
                                class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900"
                                :class="open && 'bg-gray-100 text-gray-900'">
                            <i class="ik ik-layers"></i>{{ __('Projects') }}
                            <i class="ik ik-chevron-down text-xs transition" :class="open && 'rotate-180'"></i>
                        </button>
                        <div x-show="open" x-cloak style="display:none"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             class="absolute left-0 z-50 mt-2 w-56 origin-top-left rounded-lg border border-gray-100 bg-white py-1 shadow-lg shadow-black/5 ring-1 ring-black/5">
                            <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"><i class="ik ik-plus-circle"></i>{{ __('New Project') }}</a>
                            <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"><i class="ik ik-grid"></i>{{ __('All Projects') }}</a>
                            <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"><i class="ik ik-archive"></i>{{ __('Archived') }}</a>
                        </div>
                    </li>

                    {{-- dropdown 2 --}}
                    <li x-data="{ open: false }" class="relative" @click.outside="open = false">
                        <button @click="open = ! open"
                                class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900"
                                :class="open && 'bg-gray-100 text-gray-900'">
                            <i class="ik ik-bar-chart-2"></i>{{ __('Reports') }}
                            <i class="ik ik-chevron-down text-xs transition" :class="open && 'rotate-180'"></i>
                        </button>
                        <div x-show="open" x-cloak style="display:none"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             class="absolute left-0 z-50 mt-2 w-56 origin-top-left rounded-lg border border-gray-100 bg-white py-1 shadow-lg shadow-black/5 ring-1 ring-black/5">
                            <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"><i class="ik ik-trending-up"></i>{{ __('Sales') }}</a>
                            <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"><i class="ik ik-users"></i>{{ __('Traffic') }}</a>
                            <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"><i class="ik ik-download"></i>{{ __('Export CSV') }}</a>
                        </div>
                    </li>

                    <li>
                        <a href="#" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900">
                            <i class="ik ik-settings"></i>{{ __('Settings') }}
                        </a>
                    </li>
                </ul>
            </nav>
            <p class="px-5 py-3 text-xs text-gray-400">{{ __('Click "Projects" or "Reports" to open a dropdown — the active "Home" link is underlined.') }}</p>
        </x-card>

        {{-- 5. PILLS / TABS NAVIGATION --}}
        <x-card>
            <x-slot:header>{{ __('Pills / Tabs Navigation') }}</x-slot:header>

            @php
                $tabs = [
                    ['icon' => 'ik ik-activity', 'label' => __('Overview'),  'body' => __('A quick summary of everything that matters — KPIs, recent activity and shortcuts.')],
                    ['icon' => 'ik ik-bar-chart-2', 'label' => __('Analytics'), 'body' => __('Charts and trends. Track conversions, sessions and revenue over time.')],
                    ['icon' => 'ik ik-bell', 'label' => __('Notifications'), 'body' => __('Manage how and when you receive alerts across email, SMS and push.')],
                    ['icon' => 'ik ik-settings', 'label' => __('Settings'),  'body' => __('Configure your workspace preferences, security and integrations.')],
                ];
            @endphp

            <div x-data="{ tab: 0 }">
                <div class="inline-flex flex-wrap gap-1 rounded-xl bg-gray-100 p-1">
                    @foreach ($tabs as $i => $tab)
                        <button @click="tab = {{ $i }}"
                                :class="tab === {{ $i }} ? 'bg-primary-500 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                                class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition">
                            <i class="{{ $tab['icon'] }}"></i>{{ $tab['label'] }}
                        </button>
                    @endforeach
                </div>

                <div class="mt-4">
                    @foreach ($tabs as $i => $tab)
                        <div x-show="tab === {{ $i }}" x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="rounded-xl border border-gray-100 bg-gray-50 p-5">
                            <h6 class="mb-1 flex items-center gap-2 font-semibold text-gray-700"><i class="{{ $tab['icon'] }} text-primary-500"></i>{{ $tab['label'] }}</h6>
                            <p class="text-sm text-gray-500">{{ $tab['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </x-card>

        {{-- 6. BREADCRUMBS --}}
        <x-card>
            <x-slot:header>{{ __('Breadcrumbs') }}</x-slot:header>

            <div class="space-y-4">
                {{-- simple --}}
                <nav class="flex items-center text-sm text-gray-400">
                    <a href="#" class="hover:text-primary-600">{{ __('Home') }}</a>
                    <i class="ik ik-chevron-right mx-2 text-xs"></i>
                    <a href="#" class="hover:text-primary-600">{{ __('Projects') }}</a>
                    <i class="ik ik-chevron-right mx-2 text-xs"></i>
                    <span class="font-medium text-gray-700">{{ __('Radminly') }}</span>
                </nav>

                {{-- with icons --}}
                <nav class="flex items-center text-sm text-gray-400">
                    <a href="#" class="flex items-center gap-1 hover:text-primary-600"><i class="ik ik-home"></i>{{ __('Home') }}</a>
                    <i class="ik ik-chevron-right mx-2 text-xs"></i>
                    <a href="#" class="flex items-center gap-1 hover:text-primary-600"><i class="ik ik-bar-chart-2"></i>{{ __('Reports') }}</a>
                    <i class="ik ik-chevron-right mx-2 text-xs"></i>
                    <span class="flex items-center gap-1 font-medium text-gray-700"><i class="ik ik-file-text"></i>{{ __('Q2 Summary') }}</span>
                </nav>

                {{-- pill-style on tinted bar --}}
                <nav class="flex items-center rounded-lg bg-primary-50 px-4 py-2.5 text-sm text-primary-400">
                    <a href="#" class="font-medium text-primary-600 hover:text-primary-700">{{ __('Dashboard') }}</a>
                    <i class="ik ik-chevron-right mx-2 text-xs"></i>
                    <a href="#" class="font-medium text-primary-600 hover:text-primary-700">{{ __('Settings') }}</a>
                    <i class="ik ik-chevron-right mx-2 text-xs"></i>
                    <span class="font-semibold text-primary-700">{{ __('Profile') }}</span>
                </nav>
            </div>
        </x-card>

        {{-- 7. VERTICAL / SIDEBAR NAV --}}
        <x-card>
            <x-slot:header>{{ __('Vertical / Sidebar Nav') }}</x-slot:header>

            <div class="max-w-xs rounded-xl border border-gray-100 bg-gray-50 p-3">
                <p class="px-3 pb-2 pt-1 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Menu') }}</p>
                <ul class="space-y-1">
                    @foreach ($links as $link)
                        <li>
                            <a href="#"
                               class="flex items-center justify-between rounded-lg px-3 py-2 text-sm font-medium transition
                                      {{ $link['active'] ? 'bg-primary-500 text-white shadow-sm' : 'text-gray-600 hover:bg-white hover:text-gray-900' }}">
                                <span class="flex items-center gap-3"><i class="{{ $link['icon'] }}"></i>{{ $link['label'] }}</span>
                                @if ($link['active'])
                                    <x-badge color="dark" class="bg-white/20 text-white">{{ __('New') }}</x-badge>
                                @endif
                            </a>
                        </li>
                    @endforeach

                    {{-- collapsible sub-group --}}
                    <li x-data="{ open: true }">
                        <button @click="open = ! open"
                                class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-white hover:text-gray-900">
                            <span class="flex items-center gap-3"><i class="ik ik-folder"></i>{{ __('Resources') }}</span>
                            <i class="ik ik-chevron-down text-xs transition" :class="open && 'rotate-180'"></i>
                        </button>
                        <ul x-show="open" x-cloak x-collapse class="ml-7 mt-1 space-y-1 border-l border-gray-200 pl-3">
                            <li><a href="#" class="block rounded-lg px-3 py-1.5 text-sm text-gray-500 hover:bg-white hover:text-gray-800">{{ __('Documentation') }}</a></li>
                            <li><a href="#" class="block rounded-lg px-3 py-1.5 text-sm text-gray-500 hover:bg-white hover:text-gray-800">{{ __('Changelog') }}</a></li>
                            <li><a href="#" class="block rounded-lg px-3 py-1.5 text-sm text-gray-500 hover:bg-white hover:text-gray-800">{{ __('Support') }}</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </x-card>

    </div>
@endsection
