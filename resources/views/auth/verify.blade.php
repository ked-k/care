<x-auth-layout title="{{ __('Verify Your Email Address') }} | {{ config('app.name') }}">
    <div class="mb-6 text-center">
        <a href="{{ url('/') }}" class="inline-flex justify-center text-gray-800"><x-brand-logo markClass="h-9 w-9" textClass="text-xl" /></a>
        <h5 class="mt-4 text-lg font-semibold text-gray-800">{{ __('Verify Your Email Address') }}</h5>
    </div>

    @if (session('resent'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ __('A fresh verification link has been sent to your email address.') }}
        </div>
    @endif

    <p class="text-sm text-gray-600">
        {{ __('Before proceeding, please check your email for a verification link.') }}
        {{ __('If you did not receive the email') }},
    </p>

    <form method="POST" action="{{ route('verification.resend') }}" class="mt-4">
        @csrf
        <x-button type="submit" class="w-full">{{ __('click here to request another') }}</x-button>
    </form>
</x-auth-layout>
