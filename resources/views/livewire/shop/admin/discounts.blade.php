<div>
    <x-page-header title="{{ __('Discount codes') }}" subtitle="{{ __('Codes customers type at checkout') }}" icon="ik ik-tag"
                   :breadcrumbs="['Shop' => route('shop.admin.dashboard'), 'Discount codes' => null]">
        <x-button wire:click="create"><i class="ik ik-plus"></i>{{ __('New code') }}</x-button>
    </x-page-header>

    <x-card no-padding>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 font-medium">{{ __('Code') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Gives') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Conditions') }}</th>
                        <th class="px-5 py-3 text-right font-medium">{{ __('Used') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-5 py-3 text-right font-medium">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($discounts as $d)
                        @php
                            $expired = $d->ends_at && $d->ends_at->isPast();
                            $used = $d->usage_limit !== null && $d->used_count >= $d->usage_limit;
                        @endphp
                        <tr class="hover:bg-gray-50" wire:key="d-{{ $d->id }}">
                            <td class="px-5 py-3">
                                <span class="font-mono font-semibold text-gray-800">{{ $d->code }}</span>
                                @if ($d->description)<span class="block text-xs text-gray-400">{{ $d->description }}</span>@endif
                            </td>
                            <td class="px-5 py-3 text-gray-700">{{ $d->summary() }}</td>
                            <td class="px-5 py-3 text-xs text-gray-500">
                                @if ($d->min_subtotal)<span class="block">{{ __('Spend :amount+', ['amount' => $store->money($d->min_subtotal)]) }}</span>@endif
                                @if ($d->starts_at || $d->ends_at)<span class="block">{{ $d->starts_at?->format('d M Y') ?? __('Now') }} – {{ $d->ends_at?->format('d M Y') ?? __('no end') }}</span>@endif
                                @if ($d->once_per_customer)<span class="block">{{ __('Once per customer') }}</span>@endif
                                @if (! $d->min_subtotal && ! $d->starts_at && ! $d->ends_at && ! $d->once_per_customer)—@endif
                            </td>
                            <td class="px-5 py-3 text-right text-gray-700">{{ $d->used_count }}@if ($d->usage_limit) / {{ $d->usage_limit }}@endif</td>
                            <td class="px-5 py-3">
                                <button type="button" wire:click="toggle('{{ $d->id }}')">
                                    <x-badge color="{{ ! $d->is_active ? 'gray' : ($expired || $used ? 'warning' : 'success') }}">
                                        {{ ! $d->is_active ? __('Off') : ($expired ? __('Expired') : ($used ? __('Used up') : __('Live'))) }}
                                    </x-badge>
                                </button>
                            </td>
                            <td class="px-5 py-3 text-right"><button type="button" wire:click="edit('{{ $d->id }}')" class="text-sm font-medium text-primary-600 hover:underline">{{ __('Edit') }}</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10">
                            <x-empty-state title="{{ __('No discount codes') }}" description="{{ __('Create a code for a percentage off, money off, or free delivery.') }}" icon="ik ik-tag" />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <x-drawer name="discount-form" title="{{ $editingId ? __('Edit discount code') : __('New discount code') }}" width="w-[28rem]">
        <form wire:submit="save" id="discount-form" class="space-y-4">
            <x-form.input name="form.code" label="{{ __('Code') }}" wire:model="form.code" required hint="{{ __('Letters, numbers and dashes. Not case-sensitive for customers.') }}" />
            <x-form.input name="form.description" label="{{ __('Internal description') }}" wire:model="form.description" placeholder="{{ __('e.g. Care home welcome offer') }}" />
            <x-form.select name="form.type" label="{{ __('Gives') }}" wire:model.live="form.type">
                <option value="percent">{{ __('Percentage off the items') }}</option>
                <option value="fixed">{{ __('Fixed amount off') }}</option>
                <option value="free_delivery">{{ __('Free delivery') }}</option>
            </x-form.select>
            @if ($form['type'] !== 'free_delivery')
                <x-form.input type="number" step="0.01" min="0" name="form.value" label="{{ $form['type'] === 'percent' ? __('Percentage') : __('Amount (:symbol)', ['symbol' => trim($store->currencySymbol())]) }}" wire:model="form.value" required />
            @endif
            <x-form.input type="number" step="0.01" min="0" name="form.min_subtotal" label="{{ __('Minimum spend') }}" wire:model="form.min_subtotal" hint="{{ __('Optional') }}" />
            <div class="grid grid-cols-2 gap-3">
                <x-form.input type="date" name="form.starts_at" label="{{ __('Starts') }}" wire:model="form.starts_at" />
                <x-form.input type="date" name="form.ends_at" label="{{ __('Ends') }}" wire:model="form.ends_at" />
            </div>
            <x-form.input type="number" min="1" name="form.usage_limit" label="{{ __('Total uses allowed') }}" wire:model="form.usage_limit" hint="{{ __('Blank = unlimited') }}" />
            <x-form.checkbox name="form.once_per_customer" label="{{ __('Each customer can use it once') }}" wire:model="form.once_per_customer" />
            <x-form.checkbox name="form.is_active" label="{{ __('Code is switched on') }}" wire:model="form.is_active" />
        </form>
        <x-slot:footer>
            @if ($editingId)
                <x-button variant="ghost" class="mr-auto" wire:click="delete('{{ $editingId }}')" wire:confirm="{{ __('Delete this code?') }}"><i class="ik ik-trash-2"></i>{{ __('Delete') }}</x-button>
            @endif
            <x-button variant="ghost" @click="$dispatch('close-drawer', 'discount-form')">{{ __('Cancel') }}</x-button>
            <x-button type="submit" form="discount-form">{{ __('Save code') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
