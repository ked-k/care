<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-gray-800">{{ __('Welcome, :name', ['name' => auth()->user()->name]) }}</h1>
        <p class="text-sm text-gray-400">{{ __('Here\'s who you have access to updates for.') }}</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        @forelse ($links as $link)
            <a href="{{ route('family.service-user', $link->service_user_id) }}" wire:navigate
                class="flex items-center gap-3 rounded-xl border border-gray-100 bg-white p-5 shadow-sm transition hover:border-primary-300 hover:shadow-md active:scale-[0.98]">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary-500 to-primary-600 text-base font-semibold text-white">
                    {{ collect(explode(' ', $link->serviceUser->name ?? '?'))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') }}
                </span>
                <div class="min-w-0">
                    <div class="truncate font-semibold text-gray-800">{{ $link->serviceUser->name ?? __('Unknown') }}</div>
                    <div class="text-xs text-gray-400">{{ ucfirst($link->relationship) }}</div>
                </div>
                <i class="ik ik-chevron-right ml-auto text-gray-300"></i>
            </a>
        @empty
            <div class="sm:col-span-2">
                <x-empty-state title="{{ __('No one linked to your account yet') }}"
                    description="{{ __('Once your family member\'s care agency links your account, they\'ll appear here.') }}"
                    icon="ik ik-users" />
            </div>
        @endforelse
    </div>
</div>
