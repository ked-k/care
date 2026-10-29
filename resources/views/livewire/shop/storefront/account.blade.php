<div class="s-wrap" style="padding-bottom:48px">
    <div class="s-between" style="padding-top:24px">
        <div>
            <h1 class="s-page-title">{{ __('Hello, :name', ['name' => $customer->first_name]) }}</h1>
            <p class="s-page-sub">{{ $customer->email }}</p>
        </div>
        <form method="post" action="{{ route('shop.logout', $store->slug) }}">
            @csrf
            <button type="submit" class="s-btn s-btn-ghost s-btn-sm"><i class="fas fa-sign-out-alt"></i> {{ __('Sign out') }}</button>
        </form>
    </div>

    <div class="s-tabs s-mt" role="tablist">
        @foreach (['orders' => __('Orders'), 'addresses' => __('Addresses'), 'details' => __('Details')] as $key => $label)
            <button type="button" role="tab" wire:click="$set('tab', '{{ $key }}')" @class(['is-active' => $tab === $key]) aria-selected="{{ $tab === $key ? 'true' : 'false' }}">{{ $label }}</button>
        @endforeach
    </div>

    @if ($tab === 'orders')
        @if ($orders->isEmpty())
            <div class="s-empty">
                <i class="fas fa-receipt"></i>
                <h3>{{ __('No orders yet') }}</h3>
                <p>{{ __('When you place an order it’ll appear here.') }}</p>
                <a href="{{ route('shop.catalogue', $store->slug) }}" wire:navigate class="s-btn s-btn-primary">{{ __('Start shopping') }}</a>
            </div>
        @else
            <div class="s-panel s-table-wrap">
                <table class="s-table">
                    <thead>
                        <tr>
                            <th>{{ __('Order') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Items') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="num">{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr wire:key="o-{{ $order->id }}">
                                <td><a href="{{ route('shop.order', [$store->slug, $order->number]) }}" wire:navigate class="s-btn-link s-btn">{{ $order->number }}</a></td>
                                <td>{{ $order->created_at->format('j M Y') }}</td>
                                <td class="num">{{ $order->items->sum('qty') }}</td>
                                <td>
                                    <span class="s-tag {{ in_array($order->status, ['cancelled', 'refunded'], true) ? 's-tag-danger' : ($order->status === 'completed' ? 's-tag-ok' : 's-tag-action') }}">{{ $order->statusLabel() }}</span>
                                    @if ($order->payment_status !== 'paid' && ! in_array($order->status, ['cancelled', 'refunded'], true))
                                        <span class="s-tag s-tag-warn">{{ $order->paymentStatusLabel() }}</span>
                                    @endif
                                </td>
                                <td class="num s-strong">{{ $store->money($order->total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $orders->links('shop.partials.pager') }}
        @endif
    @elseif ($tab === 'addresses')
        @if ($editingAddressId)
            <form wire:submit="saveAddress" class="s-panel s-panel-pad s-stack" style="max-width:640px">
                <h2>{{ $editingAddressId === 'new' ? __('Add an address') : __('Edit address') }}</h2>
                <div class="s-grid-2">
                    <div class="s-field">
                        <label for="a-label">{{ __('Label') }} <span class="s-opt">{{ __('(optional)') }}</span></label>
                        <input id="a-label" class="s-input" wire:model="address.label" placeholder="{{ __('e.g. Home, Care home') }}">
                    </div>
                    <div class="s-field">
                        <label for="a-name">{{ __('Name') }}</label>
                        <input id="a-name" class="s-input @error('address.name') is-invalid @enderror" wire:model="address.name">
                        @error('address.name')<span class="s-error">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="s-field">
                    <label for="a-company">{{ __('Organisation') }} <span class="s-opt">{{ __('(optional)') }}</span></label>
                    <input id="a-company" class="s-input" wire:model="address.company">
                </div>
                <div class="s-field">
                    <label for="a-line1">{{ __('Address') }}</label>
                    <input id="a-line1" class="s-input @error('address.line1') is-invalid @enderror" wire:model="address.line1">
                    @error('address.line1')<span class="s-error">{{ $message }}</span>@enderror
                    <input class="s-input" wire:model="address.line2" aria-label="{{ __('Address line 2') }}">
                </div>
                <div class="s-grid-3">
                    <div class="s-field">
                        <label for="a-city">{{ __('Town or city') }}</label>
                        <input id="a-city" class="s-input @error('address.city') is-invalid @enderror" wire:model="address.city">
                        @error('address.city')<span class="s-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="s-field">
                        <label for="a-county">{{ __('County') }}</label>
                        <input id="a-county" class="s-input" wire:model="address.county">
                    </div>
                    <div class="s-field">
                        <label for="a-postcode">{{ __('Postcode') }}</label>
                        <input id="a-postcode" class="s-input @error('address.postcode') is-invalid @enderror" wire:model="address.postcode" style="text-transform:uppercase">
                        @error('address.postcode')<span class="s-error">{{ $message }}</span>@enderror
                    </div>
                </div>
                <label class="s-check"><input type="checkbox" wire:model="address.is_default"> {{ __('Use as my default address') }}</label>
                <div class="s-row">
                    <button type="submit" class="s-btn s-btn-primary">{{ __('Save address') }}</button>
                    <button type="button" class="s-btn s-btn-ghost" wire:click="cancelAddress">{{ __('Cancel') }}</button>
                </div>
            </form>
        @else
            <div class="s-addr-grid">
                @foreach ($addresses as $a)
                    <div class="s-addr-card" wire:key="a-{{ $a->id }}">
                        <div class="s-between">
                            <strong>{{ $a->label ?: __('Address') }}</strong>
                            @if ($a->is_default)<span class="s-tag s-tag-action">{{ __('Default') }}</span>@endif
                        </div>
                        @include('shop.partials.address', ['address' => $a->toOrderArray()])
                        <div class="s-row">
                            <button type="button" class="s-btn-link s-btn s-small" wire:click="editAddress('{{ $a->id }}')">{{ __('Edit') }}</button>
                            <button type="button" class="s-btn-link s-btn s-small" style="color:var(--danger)" wire:click="deleteAddress('{{ $a->id }}')" wire:confirm="{{ __('Remove this address?') }}">{{ __('Remove') }}</button>
                        </div>
                    </div>
                @endforeach
                <button type="button" class="s-addr-card" style="align-items:center;justify-content:center;border-style:dashed;cursor:pointer;min-height:160px" wire:click="editAddress">
                    <i class="fas fa-plus" style="font-size:20px"></i>
                    <span class="s-strong">{{ __('Add an address') }}</span>
                </button>
            </div>
        @endif
    @else
        <div class="s-grid-2" style="align-items:start">
            <form wire:submit="saveProfile" class="s-panel s-panel-pad s-stack">
                <h2>{{ __('Your details') }}</h2>
                <div class="s-grid-2">
                    <div class="s-field">
                        <label for="p-first">{{ __('First name') }}</label>
                        <input id="p-first" class="s-input @error('profile.first_name') is-invalid @enderror" wire:model="profile.first_name">
                        @error('profile.first_name')<span class="s-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="s-field">
                        <label for="p-last">{{ __('Last name') }}</label>
                        <input id="p-last" class="s-input @error('profile.last_name') is-invalid @enderror" wire:model="profile.last_name">
                        @error('profile.last_name')<span class="s-error">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="s-field">
                    <label for="p-phone">{{ __('Phone') }}</label>
                    <input id="p-phone" type="tel" class="s-input" wire:model="profile.phone">
                </div>
                <div class="s-field">
                    <label for="p-company">{{ __('Organisation') }}</label>
                    <input id="p-company" class="s-input" wire:model="profile.company">
                </div>
                <label class="s-check"><input type="checkbox" wire:model="profile.marketing_opt_in"> {{ __('Email me about offers and new products') }}</label>
                <div><button type="submit" class="s-btn s-btn-primary">{{ __('Save details') }}</button></div>
            </form>

            <form wire:submit="changePassword" class="s-panel s-panel-pad s-stack">
                <h2>{{ __('Change password') }}</h2>
                <div class="s-field">
                    <label for="pw-current">{{ __('Current password') }}</label>
                    <input id="pw-current" type="password" class="s-input @error('currentPassword') is-invalid @enderror" wire:model="currentPassword" autocomplete="current-password">
                    @error('currentPassword')<span class="s-error">{{ $message }}</span>@enderror
                </div>
                <div class="s-field">
                    <label for="pw-new">{{ __('New password') }}</label>
                    <input id="pw-new" type="password" class="s-input @error('newPassword') is-invalid @enderror" wire:model="newPassword" autocomplete="new-password">
                    @error('newPassword')<span class="s-error">{{ $message }}</span>@else<span class="s-hint">{{ __('At least 8 characters.') }}</span>@enderror
                </div>
                <div><button type="submit" class="s-btn s-btn-ink">{{ __('Change password') }}</button></div>
            </form>
        </div>
    @endif
</div>
