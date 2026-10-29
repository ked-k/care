<div>
    <x-page-header title="{{ __('Delivery options') }}" subtitle="{{ __('What customers choose from at checkout') }}" icon="ik ik-truck"
                   :breadcrumbs="['Shop' => route('shop.admin.dashboard'), 'Delivery' => null]">
        <x-button wire:click="create"><i class="ik ik-plus"></i>{{ __('Add option') }}</x-button>
    </x-page-header>

    @if ($methods->isEmpty())
        <x-card>
            <x-empty-state title="{{ __('No delivery options yet') }}" description="{{ __('Customers can’t check out until there’s at least one. Start with the usual three and change the prices.') }}" icon="ik ik-truck">
                <x-slot:action><x-button wire:click="addDefaults"><i class="ik ik-zap"></i>{{ __('Add standard, next day and click & collect') }}</x-button></x-slot:action>
            </x-empty-state>
        </x-card>
    @else
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($methods as $m)
                <x-card wire:key="dm-{{ $m->id }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-500/10 text-primary-600"><i class="ik {{ $m->is_collection ? 'ik-map-pin' : 'ik-truck' }}"></i></span>
                            <div>
                                <h5 class="font-semibold text-gray-800">{{ $m->name }}</h5>
                                <p class="text-xs text-gray-400">{{ $m->description }}</p>
                            </div>
                        </div>
                        <button type="button" wire:click="toggle('{{ $m->id }}')"><x-badge color="{{ $m->is_active ? 'success' : 'gray' }}">{{ $m->is_active ? __('On') : __('Off') }}</x-badge></button>
                    </div>
                    <dl class="mt-4 space-y-1.5 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">{{ __('Price') }}</dt><dd class="font-semibold text-gray-700">{{ (float) $m->price > 0 ? $store->money($m->price) : __('Free') }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">{{ __('Free over') }}</dt><dd class="text-gray-700">{{ $m->free_over !== null ? $store->money($m->free_over) : '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">{{ __('Typical time') }}</dt><dd class="text-gray-700">{{ $m->eta() ?? '—' }}</dd></div>
                    </dl>
                    <div class="mt-4 border-t border-gray-100 pt-3 text-right">
                        <button type="button" wire:click="edit('{{ $m->id }}')" class="text-sm font-medium text-primary-600 hover:underline">{{ __('Edit') }}</button>
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif

    <x-drawer name="delivery-form" title="{{ $editingId ? __('Edit delivery option') : __('Add delivery option') }}" width="w-[28rem]">
        <form wire:submit="save" id="delivery-form" class="space-y-4">
            <x-form.input name="form.name" label="{{ __('Name') }}" wire:model="form.name" required placeholder="{{ __('e.g. Standard delivery') }}" />
            <x-form.input name="form.description" label="{{ __('Short description') }}" wire:model="form.description" placeholder="{{ __('e.g. Tracked courier') }}" />
            <div class="grid grid-cols-2 gap-3">
                <x-form.input type="number" step="0.01" min="0" name="form.price" label="{{ __('Price') }}" wire:model="form.price" required />
                <x-form.input type="number" step="0.01" min="0" name="form.free_over" label="{{ __('Free when items total') }}" wire:model="form.free_over" hint="{{ __('Optional') }}" />
                <x-form.input type="number" min="0" name="form.min_days" label="{{ __('Fastest (working days)') }}" wire:model="form.min_days" />
                <x-form.input type="number" min="0" name="form.max_days" label="{{ __('Slowest (working days)') }}" wire:model="form.max_days" />
            </div>
            <x-form.input type="number" min="0" name="form.sort_order" label="{{ __('Order at checkout') }}" wire:model="form.sort_order" />
            <x-form.checkbox name="form.is_collection" label="{{ __('Click & collect — no delivery address needed') }}" wire:model="form.is_collection" />
            <x-form.checkbox name="form.is_active" label="{{ __('Offer at checkout') }}" wire:model="form.is_active" />
        </form>
        <x-slot:footer>
            @if ($editingId)
                <x-button variant="ghost" class="mr-auto" wire:click="delete('{{ $editingId }}')" wire:confirm="{{ __('Delete this option?') }}"><i class="ik ik-trash-2"></i>{{ __('Delete') }}</x-button>
            @endif
            <x-button variant="ghost" @click="$dispatch('close-drawer', 'delivery-form')">{{ __('Cancel') }}</x-button>
            <x-button type="submit" form="delivery-form">{{ __('Save option') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
