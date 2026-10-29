<div class="s-wrap">
    <div class="s-auth">
        @if ($sent)
            <div class="s-panel s-panel-pad">
                <h1>{{ __('Check your email') }}</h1>
                <p>{{ __('If :email has an account here, we’ve sent a link to reset the password. It works for 60 minutes.', ['email' => $email]) }}</p>
                <a href="{{ route('shop.login', $store->slug) }}" wire:navigate class="s-btn s-btn-ink s-btn-block">{{ __('Back to sign in') }}</a>
            </div>
        @else
            <form wire:submit="send" class="s-panel s-panel-pad">
                <h1>{{ __('Reset your password') }}</h1>
                <p>{{ __('Enter your email and we’ll send you a link to choose a new password.') }}</p>
                <div class="s-field">
                    <label for="email">{{ __('Email') }}</label>
                    <input id="email" type="email" class="s-input @error('email') is-invalid @enderror" wire:model="email" autocomplete="email" autofocus>
                    @error('email')<span class="s-error">{{ $message }}</span>@enderror
                </div>
                <button type="submit" class="s-btn s-btn-primary s-btn-lg s-btn-block" wire:loading.attr="disabled">{{ __('Send reset link') }}</button>
                <a href="{{ route('shop.login', $store->slug) }}" wire:navigate class="s-btn-link s-btn">{{ __('Back to sign in') }}</a>
            </form>
        @endif
    </div>
</div>
