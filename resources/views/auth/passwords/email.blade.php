@php $inputCls = 'w-full rounded-xl border bg-gray-50 py-3 pl-11 pr-3 text-sm outline-none transition focus:bg-white focus:ring-4'; @endphp

<x-auth-split title="{{ __('Forgot password') }} | {{ config('app.name') }}"
              heading="{{ __('Forgot your password?') }}"
              subheading="{{ __('Enter your email and we’ll send you a link to reset it.') }}"
              panelHeading="{{ __('Reset your access in seconds.') }}"
              panelText="{{ __('We’ll email you a secure link to set a new password — no support ticket required.') }}">

    @if (session('status'))
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            <i class="ik ik-check-circle mt-0.5"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        {{-- Email --}}
        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Email address') }}</label>
            <div class="relative">
                <i class="ik ik-mail pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input id="email" type="email" name="email" placeholder="you@example.com" value="{{ old('email') }}" required autocomplete="email" autofocus
                       @class([
                           $inputCls,
                           'border-gray-200 focus:border-primary-400 focus:ring-primary-100' => ! $errors->has('email'),
                           'border-accent-400 focus:ring-accent-400/20' => $errors->has('email'),
                       ])>
            </div>
            @error('email')<p class="mt-1.5 text-xs text-accent-600">{{ $message }}</p>@enderror
        </div>

        <button type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-primary-500 to-primary-600 py-3 text-sm font-semibold text-white shadow-lg shadow-primary-500/25 transition hover:from-primary-600 hover:to-primary-700">
            {{ __('Send reset link') }} <i class="ik ik-send"></i>
        </button>

        <p class="text-center text-sm text-gray-500">
            <a href="{{ url('login') }}" class="inline-flex items-center gap-1 font-semibold text-primary-600 hover:underline">
                <i class="ik ik-arrow-left text-xs"></i> {{ __('Back to sign in') }}
            </a>
        </p>
    </form>
</x-auth-split>
