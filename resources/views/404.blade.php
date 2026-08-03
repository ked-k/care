<x-auth-layout title="{{ __('404 Not Found') }} | {{ config('app.name') }}">
    <div class="text-center">
        <p class="text-7xl font-extrabold text-primary-500">404</p>
        <h4 class="mt-4 text-xl font-semibold text-gray-800">{{ __('Page Not Found') }}</h4>
        <p class="mt-2 text-sm text-gray-500">{{ __('The page you are looking for could not be found or you do not have permission to access it.') }}</p>
        <x-button href="{{ url('dashboard') }}" class="mt-6">
            <i class="ik ik-home"></i> {{ __('Back to Dashboard') }}
        </x-button>
    </div>
</x-auth-layout>
