@extends('layouts.main')
@section('title', 'Session Timeout')
@section('content')
    <x-page-header title="{{ __('Session Timeout') }}" subtitle="{{ __('lorem ipsum dolor sit amet, consectetur adipisicing elit') }}" icon="ik ik-package"
        :breadcrumbs="['Home' => route('dashboard'), 'UI' => '#', 'Extra' => '#', 'Session Timeout' => null]" />

    <div x-data="{ open: false }" x-init="setTimeout(() => open = true, 3000)">
        <x-card>
            <x-slot:header>{{ __('Session Timeout') }}</x-slot:header>
            <p class="text-sm text-gray-600">
                With these settings, the session timeout warning dialog launches in a fixed amount of time regardless of user activity. In this demo the warning dialog appears <b>after 3 seconds</b> of page load.
            </p>
            <div class="mt-4">
                <x-button variant="primary" x-on:click="open = true">{{ __('Show Warning Now') }}</x-button>
            </div>
        </x-card>

        <template x-teleport="body">
            <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" x-transition.opacity>
                <div class="absolute inset-0 bg-black/50" x-on:click="open = false"></div>
                <div class="relative z-10 w-full max-w-md rounded-xl bg-white p-6 text-center shadow-xl">
                    <i class="ik ik-clock text-5xl text-amber-500"></i>
                    <h5 class="mt-4 text-lg font-semibold text-gray-800">{{ __('Your Session is About to Expire!') }}</h5>
                    <p class="mt-2 text-sm text-gray-600">{{ __('You will be logged out due to inactivity. Choose to stay signed in or to log off.') }}</p>
                    <div class="mt-5 flex justify-center gap-2">
                        <x-button variant="secondary" x-on:click="open = false">{{ __('Log Off') }}</x-button>
                        <x-button variant="primary" x-on:click="open = false">{{ __('Stay Logged In') }}</x-button>
                    </div>
                </div>
            </div>
        </template>
    </div>
@endsection
