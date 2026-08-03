<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<title>@yield('title','') | {{ config('app.name') }} — {{ config('app.tagline') }}</title>
	@include('include.head')
</head>
<body class="min-h-screen bg-body font-sans text-[15px] text-[#4a5361] antialiased"
      x-data="{ sidebarOpen: false, chatOpen: false }"
      :class="{ 'overflow-hidden': sidebarOpen }">

	<a href="#content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[200] focus:rounded-lg focus:bg-primary-600 focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-white">{{ __('Skip to content') }}</a>

	<div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
	     class="fixed inset-0 z-30 bg-black/50 lg:hidden" style="display:none"></div>

	@include('accounting.sidebar')
	@include('include.header')

	<main id="content" class="min-h-screen pt-16 lg:pl-60">
		<div class="p-4 sm:p-6">
			@yield('content')
		</div>
		@include('include.footer')
	</main>

	@include('include.chat')
	@include('include.modalmenu')
	<x-theme-customizer />
	<x-command-palette />
	<x-toast />
	<x-confirm-modal />
	@include('include.script')
</body>
</html>
