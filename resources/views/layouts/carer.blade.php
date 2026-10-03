<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<title>@yield('title','') | {{ config('app.name') }}</title>
	@include('include.head')

	{{--
		Batch 11: mobile-app treatment for the carer's on-shift screens,
		mirroring layouts/family.blade.php's Batch 9 polish (viewport-fit
		for the safe area, "Add to Home Screen" tags).

		Batch 12: this layout now also carries every other page a plain
		Carer account lands on (Dashboard, Timesheets, Notifications,
		Profile — see App\Models\User::isCarerOnly()), not just My Rota and
		the shift visit screen. Those pages are otherwise unrelated full
		Livewire routes, not tabs of one component, so the nav below lives
		in the header as a small dropdown menu rather than a bottom tab
		bar — a bottom bar here would also collide with ShiftVisitComponent's
		own bottom tab bar (Overview/Tasks/Medications/Notes), which is
		scoped to one shift and stays exactly as it was.
	--}}
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<meta name="theme-color" content="#1e3a5f">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">

	@livewireStyles
</head>
<body class="min-h-screen bg-[#f4f6f8] font-sans text-[15px] text-[#4a5361] antialiased" x-data="{ navOpen: false }">

	<header class="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-gray-100 bg-white/90 px-4 backdrop-blur sm:h-16 sm:px-6"
		style="padding-top: max(0px, env(safe-area-inset-top));">
		<div class="flex items-center gap-2">
			<button type="button" @click="navOpen = ! navOpen"
				class="flex h-9 w-9 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100"
				:aria-expanded="navOpen" aria-label="{{ __('Menu') }}">
				<i class="ik text-lg" :class="navOpen ? 'ik-x' : 'ik-menu'"></i>
			</button>
			<a href="{{ route('dashboard') }}" wire:navigate class="flex items-center text-gray-800">
				<x-brand-logo markClass="h-7 w-7 sm:h-8 sm:w-8" textClass="text-base sm:text-lg" />
			</a>
		</div>
		<div class="flex items-center gap-3 text-sm sm:gap-4">
			<span class="hidden text-gray-500 sm:inline">{{ auth()->user()->name }}</span>
			<a href="{{ url('/logout') }}" class="font-medium text-primary-600 hover:underline">{{ __('Log out') }}</a>
		</div>
	</header>

	{{-- Nav dropdown: the carer's whole set of destinations, since this
	     layout replaces the admin sidebar entirely for these pages. --}}
	<div x-show="navOpen" x-transition.opacity @click="navOpen = false"
		class="fixed inset-0 z-30 bg-black/30" style="display:none"></div>

	<nav x-show="navOpen" x-transition @click.outside="navOpen = false"
		class="fixed inset-x-0 top-14 z-40 border-b border-gray-100 bg-white shadow-lg sm:top-16"
		style="display:none">
		<div class="mx-auto max-w-3xl space-y-1 p-3">
			@foreach ([
				['label' => __('Dashboard'), 'icon' => 'ik-home', 'route' => 'dashboard', 'is_active' => request()->routeIs('dashboard')],
				['label' => __('My Rota'), 'icon' => 'ik-calendar', 'route' => 'rota.mine', 'is_active' => request()->routeIs('rota.mine') || request()->routeIs('rota.visit')],
				['label' => __('Timesheets'), 'icon' => 'ik-clipboard', 'route' => 'timesheets.index', 'is_active' => request()->routeIs('timesheets.*')],
				['label' => __('Notifications'), 'icon' => 'ik-bell', 'route' => 'notifications.index', 'is_active' => request()->routeIs('notifications.*')],
				['label' => __('Profile'), 'icon' => 'ik-user', 'route' => 'profile.show', 'is_active' => request()->routeIs('profile.show')],
			] as $item)
				<a href="{{ route($item['route']) }}" wire:navigate @click="navOpen = false"
					class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium {{ $item['is_active'] ? 'bg-primary-50 text-primary-600' : 'text-gray-600 hover:bg-gray-50' }}">
					<i class="ik {{ $item['icon'] }} text-lg"></i>{{ $item['label'] }}
				</a>
			@endforeach
		</div>
	</nav>

	<main class="mx-auto max-w-3xl px-4 pb-24 pt-4 sm:px-6 sm:pb-10 sm:pt-6"
		style="padding-bottom: calc(6rem + env(safe-area-inset-bottom));">
		{{ $slot ?? '' }}
	</main>

	<x-toast />
	@livewireScripts
	@include('include.script')
</body>
</html>
