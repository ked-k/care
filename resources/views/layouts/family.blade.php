<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<title>@yield('title','') | {{ config('app.name') }} Family Portal</title>
	@include('include.head')

	{{-- Batch 9: mobile-app polish. A second viewport tag (browsers use the
	     last one) adds viewport-fit=cover so content can sit flush with a
	     notch/home-indicator safe area; the apple-mobile-web-app tags make
	     "Add to Home Screen" on iOS open without Safari's chrome, so a
	     family member who does that gets something that feels like a real
	     app icon rather than a bookmark. No manifest/service-worker here —
	     see CHANGES9.md for why an offline-caching PWA is deliberately out
	     of scope for a portal that's always showing live data. --}}
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<meta name="theme-color" content="#1e3a5f">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<meta name="apple-mobile-web-app-title" content="{{ config('app.name') }} Family">

	@livewireStyles
</head>
<body class="min-h-screen bg-[#f4f6f8] font-sans text-[15px] text-[#4a5361] antialiased" x-data>

	<header class="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-gray-100 bg-white/90 px-4 backdrop-blur sm:h-16 sm:px-6"
		style="padding-top: max(0px, env(safe-area-inset-top));">
		<a href="{{ route('family.portal') }}" wire:navigate class="flex items-center text-gray-800">
			<x-brand-logo markClass="h-7 w-7 sm:h-8 sm:w-8" textClass="text-base sm:text-lg" />
			<span class="ml-2 hidden text-sm font-medium text-gray-400 sm:inline">{{ __('Family Portal') }}</span>
		</a>
		<div class="flex items-center gap-3 text-sm sm:gap-4">
			<span class="hidden text-gray-500 sm:inline">{{ auth()->user()->name }}</span>
			<a href="{{ url('/logout') }}" class="font-medium text-primary-600 hover:underline">{{ __('Log out') }}</a>
		</div>
	</header>

	<main class="mx-auto max-w-3xl px-4 pb-24 pt-4 sm:px-6 sm:pb-10 sm:pt-6"
		style="padding-bottom: calc(6rem + env(safe-area-inset-bottom));">
		{{ $slot ?? '' }}
	</main>

	<x-toast />
	@livewireScripts
	@include('include.script')
</body>
</html>
