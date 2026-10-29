<div class="s-wrap">
    <div class="s-auth">
        <form wire:submit="resetPassword" class="s-panel s-panel-pad">
            <h1>{{ __('Choose a new password') }}</h1>
            <div class="s-field">
                <label for="email">{{ __('Email') }}</label>
                <input id="email" type="email" class="s-input @error('email') is-invalid @enderror" wire:model="email" autocomplete="email">
                @error('email')<span class="s-error">{{ $message }}</span>@enderror
            </div>
            <div class="s-field">
                <label for="password">{{ __('New password') }}</label>
                <input id="password" type="password" class="s-input @error('password') is-invalid @enderror" wire:model="password" autocomplete="new-password" autofocus>
                @error('password')<span class="s-error">{{ $message }}</span>@else<span class="s-hint">{{ __('At least 8 characters.') }}</span>@enderror
            </div>
            <button type="submit" class="s-btn s-btn-primary s-btn-lg s-btn-block" wire:loading.attr="disabled">{{ __('Save password and sign in') }}</button>
        </form>
    </div>
</div>
