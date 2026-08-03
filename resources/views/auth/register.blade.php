@php $inputCls = 'w-full rounded-xl border bg-gray-50 py-3 pl-11 pr-3 text-sm outline-none transition focus:bg-white focus:ring-4'; @endphp

<x-auth-split title="{{ __('Create account') }} | {{ config('app.name') }}"
              heading="{{ __('Create your account') }}"
              subheading="{{ __('Start building with :name in seconds.', ['name' => config('app.name')]) }}"
              panelHeading="{{ __('Join the modern Laravel admin.') }}"
              panelText="{{ __('Spin up roles, permissions and beautiful dashboards without the boilerplate.') }}">

    <form method="POST" action="{{ url('register') }}" class="space-y-5" x-data="{ show: false }">
        @csrf

        {{-- Name --}}
        <div>
            <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Full name') }}</label>
            <div class="relative">
                <i class="ik ik-user pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input id="name" type="text" name="name" placeholder="Jane Cooper" value="{{ old('name') }}" required autofocus
                       @class([
                           $inputCls,
                           'border-gray-200 focus:border-primary-400 focus:ring-primary-100' => ! $errors->has('name'),
                           'border-accent-400 focus:ring-accent-400/20' => $errors->has('name'),
                       ])>
            </div>
            @error('name')<p class="mt-1.5 text-xs text-accent-600">{{ $message }}</p>@enderror
        </div>

        {{-- Email --}}
        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Email address') }}</label>
            <div class="relative">
                <i class="ik ik-mail pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input id="email" type="email" name="email" placeholder="you@example.com" value="{{ old('email') }}" required autocomplete="email"
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
            <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Password') }}</label>
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
            <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Confirm password') }}</label>
            <div class="relative">
                <i class="ik ik-lock pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input id="password_confirmation" name="password_confirmation" placeholder="{{ __('Re-enter password') }}" required :type="show ? 'text' : 'password'"
                       class="{{ $inputCls }} border-gray-200 focus:border-primary-400 focus:ring-primary-100">
            </div>
        </div>

        <label class="flex items-start gap-2 text-sm text-gray-600">
            <input type="checkbox" name="terms" value="1" required
                   class="mt-0.5 h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-200">
            <span>{{ __('I agree to the') }} <a href="#" class="font-medium text-primary-600 hover:underline">{{ __('Terms') }}</a> {{ __('and') }} <a href="#" class="font-medium text-primary-600 hover:underline">{{ __('Privacy Policy') }}</a>.</span>
        </label>

        <button type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-primary-500 to-primary-600 py-3 text-sm font-semibold text-white shadow-lg shadow-primary-500/25 transition hover:from-primary-600 hover:to-primary-700">
            {{ __('Create account') }} <i class="ik ik-arrow-right"></i>
        </button>

        <p class="text-center text-sm text-gray-500">
            {{ __('Already have an account?') }}
            <a href="{{ url('login') }}" class="font-semibold text-primary-600 hover:underline">{{ __('Sign in') }}</a>
        </p>
    </form>
</x-auth-split>
