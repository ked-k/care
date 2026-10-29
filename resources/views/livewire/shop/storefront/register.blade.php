<div class="s-wrap">
    <div class="s-auth">
        <form wire:submit="register" class="s-panel s-panel-pad">
            <h1>{{ __('Create an account') }}</h1>
            <p>{{ __('Track orders, save addresses and reorder in a couple of clicks.') }}</p>

            <div class="s-grid-2">
                <div class="s-field">
                    <label for="firstName">{{ __('First name') }}</label>
                    <input id="firstName" class="s-input @error('firstName') is-invalid @enderror" wire:model="firstName" autocomplete="given-name">
                    @error('firstName')<span class="s-error">{{ $message }}</span>@enderror
                </div>
                <div class="s-field">
                    <label for="lastName">{{ __('Last name') }}</label>
                    <input id="lastName" class="s-input @error('lastName') is-invalid @enderror" wire:model="lastName" autocomplete="family-name">
                    @error('lastName')<span class="s-error">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="s-field">
                <label for="email">{{ __('Email') }}</label>
                <input id="email" type="email" class="s-input @error('email') is-invalid @enderror" wire:model="email" autocomplete="email">
                @error('email')<span class="s-error">{{ $message }}</span>@enderror
            </div>
            <div class="s-grid-2">
                <div class="s-field">
                    <label for="phone">{{ __('Phone') }} <span class="s-opt">{{ __('(optional)') }}</span></label>
                    <input id="phone" type="tel" class="s-input" wire:model="phone" autocomplete="tel">
                </div>
                <div class="s-field">
                    <label for="company">{{ __('Organisation') }} <span class="s-opt">{{ __('(optional)') }}</span></label>
                    <input id="company" class="s-input" wire:model="company" autocomplete="organization">
                </div>
            </div>
            <div class="s-field">
                <label for="password">{{ __('Password') }}</label>
                <input id="password" type="password" class="s-input @error('password') is-invalid @enderror" wire:model="password" autocomplete="new-password">
                @error('password')<span class="s-error">{{ $message }}</span>@else<span class="s-hint">{{ __('At least 8 characters.') }}</span>@enderror
            </div>
            <label class="s-check"><input type="checkbox" wire:model="marketingOptIn"> {{ __('Email me about offers and new products') }}</label>

            <button type="submit" class="s-btn s-btn-primary s-btn-lg s-btn-block" wire:loading.attr="disabled">{{ __('Create account') }}</button>
            <p class="s-small">{{ __('Already have an account?') }} <a href="{{ route('shop.login', $store->slug) }}" wire:navigate class="s-btn-link s-btn">{{ __('Sign in') }}</a></p>
        </form>
    </div>
</div>
