<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<title>@yield('title','') | {{ config('app.name') }} Family Portal</title>
	@include('include.head')
	@livewireStyles
</head>
<body class="min-h-screen bg-body font-sans text-[15px] text-[#4a5361] antialiased" x-data>

	<header class="flex h-16 items-center justify-between border-b border-gray-100 bg-white px-4 sm:px-6">
		<a href="{{ route('family.portal') }}" class="flex items-center text-gray-800">
			<x-brand-logo markClass="h-8 w-8" textClass="text-lg" />
			<span class="ml-2 text-sm font-medium text-gray-400">{{ __('Family Portal') }}</span>
		</a>
		<div class="flex items-center gap-4 text-sm">
			<span class="text-gray-500">{{ auth()->user()->name }}</span>
			<a href="{{ url('/logout') }}" class="font-medium text-primary-600 hover:underline">{{ __('Log out') }}</a>
		</div>
	</header>

	<main class="mx-auto max-w-4xl p-4 sm:p-6">
		{{ $slot ?? '' }}
	</main>

	<x-toast />
	@livewireScripts
	@include('include.script')
</body>
</html>
