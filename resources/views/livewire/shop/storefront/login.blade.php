<div class="s-wrap">
    <div class="s-auth">
        <form wire:submit="login" class="s-panel s-panel-pad">
            <h1>{{ __('Sign in') }}</h1>
            <p>{{ __('See your orders and check out faster.') }}</p>

            <div class="s-field">
                <label for="email">{{ __('Email') }}</label>
                <input id="email" type="email" class="s-input @error('email') is-invalid @enderror" wire:model="email" autocomplete="email" autofocus>
                @error('email')<span class="s-error">{{ $message }}</span>@enderror
            </div>
            <div class="s-field">
                <div class="s-between">
                    <label for="password">{{ __('Password') }}</label>
                    <a href="{{ route('shop.password.request', $store->slug) }}" wire:navigate class="s-btn-link s-btn s-small">{{ __('Forgotten it?') }}</a>
                </div>
                <input id="password" type="password" class="s-input @error('password') is-invalid @enderror" wire:model="password" autocomplete="current-password">
                @error('password')<span class="s-error">{{ $message }}</span>@enderror
            </div>
            <label class="s-check"><input type="checkbox" wire:model="remember"> {{ __('Keep me signed in on this device') }}</label>

            <button type="submit" class="s-btn s-btn-primary s-btn-lg s-btn-block" wire:loading.attr="disabled">{{ __('Sign in') }}</button>
            <hr class="s-hr">
            <p class="s-small">{{ __('New here?') }} <a href="{{ route('shop.register', $store->slug) }}" wire:navigate class="s-btn-link s-btn">{{ __('Create an account') }}</a> — {{ __('or just check out as a guest.') }}</p>
        </form>
    </div>
</div>
