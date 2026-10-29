<div>
    <x-page-header title="{{ __('Suppliers') }}" subtitle="{{ __('Who your stock comes from') }}" icon="ik ik-truck"
                   :breadcrumbs="['Shop' => route('shop.admin.dashboard'), 'Suppliers' => null]">
        <x-button wire:click="create"><i class="ik ik-plus"></i>{{ __('Add supplier') }}</x-button>
    </x-page-header>

    <x-card no-padding>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 font-medium">{{ __('Supplier') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Contact') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Account no.') }}</th>
                        <th class="px-5 py-3 text-right font-medium">{{ __('Deliveries') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Last delivery') }}</th>
                        <th class="px-5 py-3 text-right font-medium">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($suppliers as $s)
                        <tr @class(['hover:bg-gray-50', 'opacity-50' => ! $s->is_active]) wire:key="sup-{{ $s->id }}">
                            <td class="px-5 py-3">
                                <button type="button" wire:click="edit('{{ $s->id }}')" class="font-medium text-gray-700 hover:text-primary-600">{{ $s->name }}</button>
                                @if ($s->website)<a href="{{ $s->website }}" target="_blank" rel="noopener" class="ml-1 text-gray-400 hover:text-gray-600"><i class="ik ik-external-link"></i></a>@endif
                                @unless ($s->is_active)<span class="ml-1 text-xs text-gray-400">({{ __('hidden') }})</span>@endunless
                            </td>
                            <td class="px-5 py-3 text-gray-500">
                                {{ $s->contact_name ?: '—' }}
                                @if ($s->email)<span class="block text-xs">{{ $s->email }}</span>@endif
                                @if ($s->phone)<span class="block text-xs">{{ $s->phone }}</span>@endif
                            </td>
                            <td class="px-5 py-3 font-mono text-xs text-gray-500">{{ $s->account_number ?: '—' }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-gray-700">{{ $s->grns_count }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ $s->grns_max_received_on ? \Illuminate\Support\Carbon::parse($s->grns_max_received_on)->format('d M Y') : '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right">
                                <a href="{{ route('shop.admin.receiving.create', ['supplier' => $s->id]) }}" wire:navigate class="text-sm font-medium text-primary-600 hover:underline">{{ __('Receive delivery') }}</a>
                                <a href="{{ route('shop.admin.receiving', ['supplier' => $s->id]) }}" wire:navigate class="ml-3 text-sm text-gray-500 hover:underline">{{ __('History') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10"><x-empty-state title="{{ __('No suppliers yet') }}" icon="ik ik-truck" description="{{ __('Add the companies you buy stock from.') }}" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <x-drawer name="supplier-form" title="{{ $editingId ? __('Edit supplier') : __('Add supplier') }}" width="w-[30rem]">
        <form wire:submit="save" id="supplier-form" class="space-y-4">
            <x-form.input name="form.name" label="{{ __('Name') }}" wire:model="form.name" required />
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <x-form.input name="form.contact_name" label="{{ __('Contact name') }}" wire:model="form.contact_name" />
                <x-form.input name="form.phone" label="{{ __('Phone') }}" wire:model="form.phone" />
            </div>
            <x-form.input type="email" name="form.email" label="{{ __('Email') }}" wire:model="form.email" />
            <x-form.input name="form.website" label="{{ __('Website') }}" wire:model="form.website" placeholder="https://" />
            <x-form.input name="form.account_number" label="{{ __('Your account number with them') }}" wire:model="form.account_number" />
            <x-form.textarea name="form.address" label="{{ __('Address') }}" wire:model="form.address" rows="3" />
            <x-form.textarea name="form.notes" label="{{ __('Notes') }}" wire:model="form.notes" rows="3" placeholder="{{ __('Order cut-off times, minimum order, rep details…') }}" />
            <x-form.checkbox name="form.is_active" label="{{ __('Show in the supplier list for new deliveries') }}" wire:model="form.is_active" />
        </form>
        <x-slot:footer>
            @if ($editingId)
                <x-button variant="ghost" class="mr-auto" wire:click="delete('{{ $editingId }}')" wire:confirm="{{ __('Delete this supplier?') }}"><i class="ik ik-trash-2"></i>{{ __('Delete') }}</x-button>
            @endif
            <x-button variant="ghost" @click="$dispatch('close-drawer', 'supplier-form')">{{ __('Cancel') }}</x-button>
            <x-button type="submit" form="supplier-form">{{ __('Save') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
