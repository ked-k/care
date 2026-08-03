<x-auth-layout title="{{ __('Confirm Password') }} | {{ config('app.name') }}">
    <div class="mb-6 text-center">
        <a href="{{ url('/') }}" class="inline-flex justify-center text-gray-800"><x-brand-logo markClass="h-9 w-9" textClass="text-xl" /></a>
        <h5 class="mt-4 text-lg font-semibold text-gray-800">{{ __('Confirm Password') }}</h5>
    </div>

    <p class="mb-4 text-sm text-gray-600">{{ __('Please confirm your password before continuing.') }}</p>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf
        <x-form.input name="password" type="password" label="{{ __('Password') }}" required autocomplete="current-password" />

        <x-button type="submit" class="w-full">{{ __('Confirm Password') }}</x-button>

        @if (Route::has('password.request'))
            <p class="text-center text-sm text-gray-500">
                <a href="{{ route('password.request') }}" class="font-medium text-primary-600 hover:underline">{{ __('Forgot Your Password?') }}</a>
            </p>
        @endif
    </form>
</x-auth-layout>
