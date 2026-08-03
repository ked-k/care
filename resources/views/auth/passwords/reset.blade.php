@php $inputCls = 'w-full rounded-xl border bg-gray-50 py-3 pl-11 pr-3 text-sm outline-none transition focus:bg-white focus:ring-4'; @endphp

<x-auth-split title="{{ __('Reset password') }} | {{ config('app.name') }}"
              heading="{{ __('Set a new password') }}"
              subheading="{{ __('Choose a strong password you don’t use anywhere else.') }}"
              panelHeading="{{ __('Almost there.') }}"
              panelText="{{ __('Pick a new password and you’ll be back in your dashboard right away.') }}">

    <form method="POST" action="{{ route('password.update') }}" class="space-y-5" x-data="{ show: false }">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        {{-- Email --}}
        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Email address') }}</label>
            <div class="relative">
                <i class="ik ik-mail pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input id="email" type="email" name="email" placeholder="you@example.com" value="{{ old('email', $email ?? '') }}" required autocomplete="email" autofocus
                       @class([
                           $inputCls,
                           'border-gray-200 focus:border-primary-400 focus:ring-primary-100' => ! $errors->has('email'),
                           'border-accent-400 focus:ring-accent-400/20' => $errors->has('email'),
                       ])>
            </div>
            @error('email')<p class="mt-1.5 text-xs text-accent-600">{{ $message }}</p>@enderror
        </div>

        {{-- Password --}}
        <div>
            <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('New password') }}</label>
            <div class="relative">
                <i class="ik ik-lock pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input id="password" name="password" placeholder="{{ __('At least 8 characters') }}" required :type="show ? 'text' : 'password'"
                       @class([
                           str_replace('pr-3', 'pr-11', $inputCls),
                           'border-gray-200 focus:border-primary-400 focus:ring-primary-100' => ! $errors->has('password'),
                           'border-accent-400 focus:ring-accent-400/20' => $errors->has('password'),
                       ])>
                <button type="button" @click="show = !show" tabindex="-1"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 transition hover:text-gray-600"
                        :aria-label="show ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'">
                    <i class="ik" :class="show ? 'ik-eye-off' : 'ik-eye'"></i>
                </button>
            </div>
            @error('password')<p class="mt-1.5 text-xs text-accent-600">{{ $message }}</p>@enderror
        </div>

        {{-- Confirm password --}}
        <div>
            <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Confirm new password') }}</label>
            <div class="relative">
                <i class="ik ik-lock pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input id="password_confirmation" name="password_confirmation" placeholder="{{ __('Re-enter password') }}" required :type="show ? 'text' : 'password'"
                       class="{{ str_replace('pr-3', 'pr-11', $inputCls) }} border-gray-200 focus:border-primary-400 focus:ring-primary-100">
            </div>
        </div>

        <button type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-primary-500 to-primary-600 py-3 text-sm font-semibold text-white shadow-lg shadow-primary-500/25 transition hover:from-primary-600 hover:to-primary-700">
            {{ __('Reset password') }} <i class="ik ik-arrow-right"></i>
        </button>

        <p class="text-center text-sm text-gray-500">
            <a href="{{ url('login') }}" class="inline-flex items-center gap-1 font-semibold text-primary-600 hover:underline">
                <i class="ik ik-arrow-left text-xs"></i> {{ __('Back to sign in') }}
            </a>
        </p>
    </form>
</x-auth-split>
