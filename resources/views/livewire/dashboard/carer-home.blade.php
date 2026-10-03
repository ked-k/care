{{--
    Batch 12: the plain carer's home screen (/dashboard), replacing the
    agency-wide analytics dashboard for this role — see
    AnalyticsDashboardComponent::renderCarerHome().
--}}
<div>
    <div class="mb-5">
        <h1 class="text-lg font-semibold text-gray-800">{{ __('Hi, :name', ['name' => explode(' ', auth()->user()->name)[0] ?? auth()->user()->name]) }}</h1>
        <p class="text-sm text-gray-400">{{ now()->format('l, d M Y') }}</p>
    </div>

    <div class="space-y-4">
        <x-card hover>
            <x-slot:header>{{ __('Next shift') }}</x-slot:header>
            @if ($nextShift)
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <div class="font-semibold text-gray-700">{{ $nextShift->serviceUser->name ?? __('Unassigned service user') }}</div>
                        <div class="text-sm text-gray-500">
                            {{ $nextShift->scheduled_start->format('D, d M · H:i') }} – {{ $nextShift->scheduled_end->format('H:i') }}
                        </div>
                    </div>
                    <a href="{{ route('rota.visit', $nextShift->id) }}" wire:navigate
                        class="shrink-0 rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700">
                        {{ __('Open visit') }}
                    </a>
                </div>
            @else
                <p class="text-sm text-gray-400">{{ __('No upcoming shift on the published rota yet.') }}</p>
            @endif
        </x-card>

        <div class="grid grid-cols-2 gap-3">
            <a href="{{ route('rota.mine') }}" wire:navigate
                class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm hover:border-primary-200">
                <i class="ik ik-calendar text-xl text-primary-500"></i>
                <div class="mt-2 font-semibold text-gray-700">{{ __('My Rota') }}</div>
            </a>
            <a href="{{ route('timesheets.index') }}" wire:navigate
                class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm hover:border-primary-200">
                <i class="ik ik-clipboard text-xl text-primary-500"></i>
                <div class="mt-2 font-semibold text-gray-700">{{ __('Timesheets') }}</div>
            </a>
            <a href="{{ route('notifications.index') }}" wire:navigate
                class="relative rounded-xl border border-gray-100 bg-white p-4 shadow-sm hover:border-primary-200">
                <i class="ik ik-bell text-xl text-primary-500"></i>
                <div class="mt-2 font-semibold text-gray-700">{{ __('Notifications') }}</div>
                @if ($unreadNotifications > 0)
                    <span class="absolute right-3 top-3 flex h-5 min-w-5 items-center justify-center rounded-full bg-primary-500 px-1 text-[10px] font-semibold text-white">
                        {{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}
                    </span>
                @endif
            </a>
            <a href="{{ route('profile.show') }}" wire:navigate
                class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm hover:border-primary-200">
                <i class="ik ik-user text-xl text-primary-500"></i>
                <div class="mt-2 font-semibold text-gray-700">{{ __('Profile') }}</div>
            </a>
        </div>
    </div>
</div>
