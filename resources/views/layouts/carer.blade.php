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
		the shift visit screen.

		Batch 13: replaced the header hamburger + dropdown with a
		persistent bottom tab bar (Home / Rota / Notes / Messages / More),
		matching the family-portal bottom-nav pattern the user pointed to.
		"More" keeps the Batch 12 dropdown panel for the less-frequent
		destinations (Timesheets, Notifications, Profile, Log out) instead
		of throwing that work away. The bar is hidden on the shift-visit
		screen, which already has its own bottom tab bar scoped to one
		shift (Overview/Tasks/Medications/Notes) — two stacked bottom bars
		would collide.
	--}}
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<meta name="theme-color" content="#1e3a5f">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">

	{{-- Batch 24: actually installable now, not just "feels like an app"
	     once added to the home screen manually — see public/sw.js
	     (static-asset caching only, see that file's own doc comment for why
	     this never risks showing stale care data).

	     Batch 27: now points at the single shared public/manifest.json
	     instead of its own manifest-carer.json — there's one installable
	     app for everyone, since login already routes each person to their
	     own portal (Admin/Manager -> /dashboard, Carer -> /dashboard,
	     Family -> /family/). manifest-carer.json is left in place, just
	     unreferenced (this session can't delete files on your machine). --}}
	<link rel="manifest" href="{{ asset('manifest.json') }}">
	<link rel="apple-touch-icon" href="{{ asset('images/brand/pwa/apple-touch-icon.png') }}">

	@livewireStyles
</head>
<body class="min-h-screen bg-[#f4f6f8] font-sans text-[15px] text-[#4a5361] antialiased" x-data="{ navOpen: false }">

	@include('include.pwa-install-prompt')

	@php
		// Cheap, layout-level presence checks for the two "More" badges —
		// this layout is shared by many unrelated Livewire root components,
		// so it can't rely on any one of them to pass these in.
		$carerHasUnreadNotifications = \App\Models\Notification::where('user_id', auth()->id())->whereNull('read_at')->exists();
		$carerAssignedServiceUserIds = \App\Models\Shift::where('assigned_to', auth()->id())->distinct()->pluck('service_user_id');
		$carerHasUnreadMessages = \App\Models\ChatSession::whereIn('service_user_id', $carerAssignedServiceUserIds)
			->whereHas('messages', fn ($q) => $q->whereNull('read_at')->whereHas('sender.roles', fn ($q2) => $q2->where('name', 'Family')))
			->exists();

		// Batch 23: same cheap layout-level check as $carerHasUnreadMessages
		// above, for the new free-form Conversation model's "More" dropdown
		// entry below.
		$carerHasUnreadConversations = \App\Models\Conversation::whereHas('participants', fn ($q) => $q->where('user_id', auth()->id()))
			->get()
			->contains(fn ($c) => $c->unreadCountFor(auth()->user()) > 0);
	@endphp

	<header class="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-gray-100 bg-white/90 px-4 backdrop-blur sm:h-16 sm:px-6"
		style="padding-top: max(0px, env(safe-area-inset-top));">
		<a href="{{ route('dashboard') }}" wire:navigate class="flex items-center text-gray-800">
			<x-brand-logo markClass="h-7 w-7 sm:h-8 sm:w-8" textClass="text-base sm:text-lg" />
		</a>
		<div class="flex items-center gap-3 text-sm sm:gap-4">
			<span class="hidden text-gray-500 sm:inline">{{ auth()->user()->name }}</span>
			<a href="{{ url('/logout') }}" class="font-medium text-primary-600 hover:underline">{{ __('Log out') }}</a>
		</div>
	</header>

	@unless (request()->routeIs('rota.visit'))
		{{-- "More" dropdown: the less-frequent destinations, kept out of the
			 5-tab bottom bar so it stays uncluttered. --}}
		<div x-show="navOpen" x-transition.opacity @click="navOpen = false"
			class="fixed inset-0 z-30 bg-black/30" style="display:none"></div>

		<nav x-show="navOpen" x-transition @click.outside="navOpen = false"
			class="fixed inset-x-0 bottom-16 z-40 border-t border-gray-100 bg-white shadow-lg"
			style="display:none; margin-bottom: env(safe-area-inset-bottom);">
			<div class="mx-auto max-w-3xl space-y-1 p-3">
				@foreach ([
					['label' => __('Timesheets'), 'icon' => 'ik-clipboard', 'route' => 'timesheets.index', 'is_active' => request()->routeIs('timesheets.*'), 'badge' => false],
					['label' => __('Notifications'), 'icon' => 'ik-bell', 'route' => 'notifications.index', 'is_active' => request()->routeIs('notifications.*'), 'badge' => $carerHasUnreadNotifications],
					['label' => __('Conversations'), 'icon' => 'ik-message-square', 'route' => 'conversations.index', 'is_active' => request()->routeIs('conversations.*'), 'badge' => $carerHasUnreadConversations],
					['label' => __('Profile'), 'icon' => 'ik-user', 'route' => 'profile.show', 'is_active' => request()->routeIs('profile.show'), 'badge' => false],
				] as $item)
					<a href="{{ route($item['route']) }}" wire:navigate @click="navOpen = false"
						class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium {{ $item['is_active'] ? 'bg-primary-50 text-primary-600' : 'text-gray-600 hover:bg-gray-50' }}">
						<span class="relative">
							<i class="ik {{ $item['icon'] }} text-lg"></i>
							@if ($item['badge'])
								<span class="absolute -right-0.5 -top-0.5 h-2 w-2 rounded-full bg-accent-500"></span>
							@endif
						</span>
						{{ $item['label'] }}
					</a>
				@endforeach
				<a href="{{ url('/logout') }}"
					class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium text-gray-600 hover:bg-gray-50">
					<i class="ik ik-power text-lg"></i>{{ __('Log out') }}
				</a>
			</div>
		</nav>
	@endunless

	<main class="mx-auto max-w-3xl px-4 pb-24 pt-4 sm:px-6 sm:pb-10 sm:pt-6"
		style="padding-bottom: calc(6rem + env(safe-area-inset-bottom));">
		{{ $slot ?? '' }}
	</main>

	@unless (request()->routeIs('rota.visit'))
		{{-- Bottom tab bar: the carer's 5 main destinations, always on screen. --}}
		<nav class="fixed inset-x-0 bottom-0 z-30 bg-white/95 shadow-[0_-4px_16px_rgba(42,21,114,0.08)] backdrop-blur"
			style="padding-bottom: env(safe-area-inset-bottom);">
			<div class="brand-grad h-[2px] w-full"></div>
			<div class="mx-auto grid max-w-3xl grid-cols-5">
				@foreach ([
					['label' => __('Home'), 'icon' => 'ik-home', 'route' => 'dashboard', 'is_active' => request()->routeIs('dashboard'), 'badge' => false, 'action' => null],
					['label' => __('Rota'), 'icon' => 'ik-calendar', 'route' => 'rota.mine', 'is_active' => request()->routeIs('rota.mine') || request()->routeIs('rota.visit'), 'badge' => false, 'action' => null],
					['label' => __('Notes'), 'icon' => 'ik-edit-3', 'route' => 'notes.quick', 'is_active' => request()->routeIs('notes.quick'), 'badge' => false, 'action' => null],
					['label' => __('Messages'), 'icon' => 'ik-message-square', 'route' => 'messages.carer', 'is_active' => request()->routeIs('messages.carer'), 'badge' => $carerHasUnreadMessages, 'action' => null],
					['label' => __('More'), 'icon' => 'ik-menu', 'route' => null, 'is_active' => false, 'badge' => $carerHasUnreadNotifications, 'action' => 'navOpen = ! navOpen'],
				] as $item)
					@if ($item['route'])
						<a href="{{ route($item['route']) }}" wire:navigate
							class="relative flex flex-col items-center gap-0.5 py-2.5 text-[11px] font-medium {{ $item['is_active'] ? 'tab-active text-primary-600' : 'text-gray-400' }}">
							<span class="relative">
								<i class="ik {{ $item['icon'] }} text-lg"></i>
								@if ($item['badge'])
									<span class="absolute -right-1 -top-0.5 h-2 w-2 rounded-full bg-accent-500"></span>
								@endif
							</span>
							{{ $item['label'] }}
						</a>
					@else
						<button type="button" @click="{{ $item['action'] }}"
							:class="navOpen ? 'tab-active text-primary-600' : 'text-gray-400'"
							class="relative flex flex-col items-center gap-0.5 py-2.5 text-[11px] font-medium">
							<span class="relative">
								<i class="ik {{ $item['icon'] }} text-lg"></i>
								@if ($item['badge'])
									<span class="absolute -right-1 -top-0.5 h-2 w-2 rounded-full bg-accent-500"></span>
								@endif
							</span>
							{{ $item['label'] }}
						</button>
					@endif
				@endforeach
			</div>
		</nav>
	@endunless

	<x-toast />
	{{-- Batch 23: the same floating "Start a conversation" button as
	     layouts.admin-layout — this is a plain carer's first way to message
	     a staff colleague directly, not just their assigned families via
	     the Messages tab. See App\Models\User::canStartConversations(). --}}
	@if (auth()->user()->canStartConversations())
		@livewire('messaging.new-conversation-component')
	@endif
	@livewireScripts
	@include('include.script')
	@include('include.pwa-register')
</body>
</html>
