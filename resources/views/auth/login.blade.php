@php $inputCls = 'w-full rounded-xl border bg-gray-50 py-3 pl-11 pr-3 text-sm outline-none transition focus:bg-white focus:ring-4'; @endphp

<x-auth-split title="{{ __('Sign in') }} | {{ config('app.name') }}"
              heading="{{ __('Welcome back') }}"
              subheading="{{ __('Sign in to your dashboard to continue.') }}">

    <form method="POST" action="{{ route('login') }}" class="space-y-5" x-data="{ show: false }">
        @csrf

        {{-- Email --}}
        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Email address') }}</label>
            <div class="relative">
                <i class="ik ik-mail pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input id="email" type="email" name="email" placeholder="you@example.com" value="{{ old('email', 'admin@test.com') }}" required autocomplete="email" autofocus
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
            <div class="mb-1.5 flex items-center justify-between">
                <label for="password" class="block text-sm font-medium text-gray-700">{{ __('Password') }}</label>
                <a class="text-xs font-medium text-primary-600 hover:underline" href="{{ route('password.forget') }}">{{ __('Forgot password?') }}</a>
            </div>
            <div class="relative">
                <i class="ik ik-lock pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input id="password" name="password" placeholder="••••••••" value="1234" required :type="show ? 'text' : 'password'"
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

        {{-- Remember --}}
        <label class="flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" name="remember" value="1"
                   class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-200">
            {{ __('Remember me for 30 days') }}
        </label>

        <button type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-primary-500 to-primary-600 py-3 text-sm font-semibold text-white shadow-lg shadow-primary-500/25 transition hover:from-primary-600 hover:to-primary-700">
            {{ __('Sign in') }} <i class="ik ik-arrow-right"></i>
        </button>

        <p class="text-center text-sm text-gray-500">
            {{ __("Don't have an account?") }}
            <a href="{{ url('register') }}" class="font-semibold text-primary-600 hover:underline">{{ __('Create one') }}</a>
        </p>
    </form>
</x-auth-split>
