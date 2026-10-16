<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <title>@yield('title', '') | {{ config('app.name') }} — {{ config('app.tagline') }}</title>
    @include('include.head')

    {{-- Batch 24: installable PWA — a home-screen icon and a standalone
         app window on a manager's phone too, not just the two mobile-first
         layouts below. See public/manifest.json and public/sw.js (a
         static-asset-only cache, never pages or Livewire data — see that
         file's own doc comment for why). --}}
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#1e3a5f">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/pwa/apple-touch-icon.png') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">

    @livewireStyles
</head>

<body class="min-h-screen bg-body font-sans text-[15px] text-[#4a5361] antialiased" x-data="{ sidebarOpen: false, chatOpen: false }"
    :class="{ 'overflow-hidden': sidebarOpen }">

    <a href="#content"
        class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[200] focus:rounded-lg focus:bg-primary-600 focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-white">{{ __('Skip to content') }}</a>

    <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
        class="fixed inset-0 z-30 bg-black/50 lg:hidden" style="display:none"></div>

    @include('layouts.sidebar')
    @include('include.header')

    <main id="content" class="min-h-screen pt-16 lg:pl-60">
        <div class="p-4 sm:p-6">
            {{ $slot ?? '' }}
        </div>
        @include('include.footer')
    </main>

    {{-- The chat drawer (App\Livewire\Messaging\ChatDrawerComponent) is now
         embedded directly from include/header.blade.php, alongside its
         trigger button, so it can be a single Livewire instance shared by
         both — see that file. include.chat is no longer used. --}}
    @include('include.modalmenu')
    <x-theme-customizer />
    <x-command-palette />
    <x-toast />
    <x-confirm-modal />
    {{-- Batch 23: floating "Start a conversation" button — gated here, not
         just inside the component, so a user who can't start one never even
         gets the Livewire round-trip. See App\Models\User::canStartConversations(). --}}
    @if (auth()->user()->canStartConversations())
        @livewire('messaging.new-conversation-component')
    @endif
    @livewireScripts
    @include('include.script')
    @include('include.pwa-register')
</body>

</html>
