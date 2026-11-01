<div>
    @php $customers = $role === 'customer'; @endphp
    <x-page-header :title="($title)" :subtitle="($customers ? __('Who you invoice — care payers are added automatically from care invoicing') : __('Who you buy from — shop suppliers are added automatically from deliveries'))"
                   :icon="'ik ik-'.($customers ? 'users' : 'truck')" :breadcrumbs="['Finance' => route('accounts.dashboard'), $title => null]">
        <x-button wire:click="create"><i class="ik ik-plus"></i>{{ $customers ? __('Add customer') : __('Add supplier') }}</x-button>
    </x-page-header>

    <x-card no-padding>
        <div class="flex flex-col gap-3 border-b border-gray-100 p-4 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <i class="ik ik-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Name or email…') }}"
                       class="w-full rounded-lg border border-gray-200 py-2 pl-9 pr-3 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-100">
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-600"><input type="checkbox" wire:model.live="owing" class="rounded border-gray-300 text-primary-600">{{ __('Only with a balance') }}</label>
            <span class="text-sm text-gray-500">{{ $customers ? __('Total owed to you') : __('Total you owe') }}: <strong class="text-gray-800">{{ $cur->money($total) }}</strong></span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 font-medium">{{ __('Name') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Contact') }}</th>
                        <th class="px-5 py-3 text-right font-medium">{{ $customers ? __('Open invoices') : __('Open bills') }}</th>
                        <th class="px-5 py-3 text-right font-medium">{{ $customers ? __('Owes you') : __('You owe') }}</th>
                        <th class="px-5 py-3 text-right font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($contacts as $c)
                        <tr @class(['hover:bg-gray-50', 'opacity-50' => ! $c->is_active]) wire:key="c-{{ $c->id }}">
                            <td class="px-5 py-3">
                                <a href="{{ route('accounts.contacts.show', $c->id) }}" wire:navigate class="font-medium text-gray-700 hover:text-primary-600">{{ $c->name }}</a>
                                @if ($c->is_customer && $c->is_supplier)<x-badge class="ml-1">{{ __('Both') }}</x-badge>@endif
                                @if ($c->service_user_id)<i class="ik ik-heart ml-1 text-xs text-accent-500" title="{{ __('Linked to a service user') }}"></i>@endif
                            </td>
                            <td class="px-5 py-3 text-gray-500">{{ $c->email ?: '—' }}@if ($c->phone)<span class="block text-xs">{{ $c->phone }}</span>@endif</td>
                            <td class="px-5 py-3 text-right text-gray-600">{{ $c->open_count ?: '—' }}</td>
                            <td @class(['whitespace-nowrap px-5 py-3 text-right font-semibold', 'text-gray-700' => $c->balance >= 0, 'text-green-600' => $c->balance < 0])>
                                {{ abs($c->balance) < 0.01 ? '—' : $cur->money($c->balance) }}@if ($c->balance <= -0.01)<span class="block text-xs font-normal">{{ __('in credit') }}</span>@endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-right">
                                <button type="button" wire:click="edit('{{ $c->id }}')" class="text-sm text-gray-500 hover:text-primary-600">{{ __('Edit') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10"><x-empty-state :title="(__('Nobody here yet'))" icon="ik ik-users" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <x-drawer name="contact-form" :title="($editingId ? __('Edit') : ($customers ? __('Add customer') : __('Add supplier')))" width="w-[30rem]">
        <form wire:submit="save" id="contact-form" class="space-y-4">
            <x-form.input name="form.name" :label="(__('Name'))" wire:model="form.name" required />
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <x-form.input name="form.contact_name" :label="(__('Contact person'))" wire:model="form.contact_name" />
                <x-form.input name="form.phone" :label="(__('Phone'))" wire:model="form.phone" />
            </div>
            <x-form.input type="email" name="form.email" :label="(__('Email'))" wire:model="form.email" />
            <x-form.textarea name="form.address" :label="(__('Address'))" wire:model="form.address" rows="3" />
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <x-form.input name="form.tax_number" :label="(__('VAT / tax number'))" wire:model="form.tax_number" />
                <x-form.input type="number" name="form.payment_terms_days" :label="(__('Payment terms (days)'))" wire:model="form.payment_terms_days" :placeholder="($cur->payment_terms_days)" />
            </div>
            @if ($customers && $serviceUsers->isNotEmpty())
                <x-form.select name="form.service_user_id" :label="(__('Service user'))" wire:model="form.service_user_id" :hint="(__('If this customer is (or pays for) one of your clients'))">
                    <option value="">{{ __('— none —') }}</option>
                    @foreach ($serviceUsers as $su)<option value="{{ $su->id }}">{{ $su->name }}</option>@endforeach
                </x-form.select>
            @endif
            <div class="flex flex-wrap gap-4">
                <x-form.checkbox name="form.is_customer" :label="(__('Customer'))" wire:model="form.is_customer" />
                <x-form.checkbox name="form.is_supplier" :label="(__('Supplier'))" wire:model="form.is_supplier" />
                <x-form.checkbox name="form.is_active" :label="(__('Active'))" wire:model="form.is_active" />
            </div>
            @error('form.is_customer')<p class="text-xs text-accent-600">{{ $message }}</p>@enderror
            <x-form.textarea name="form.notes" :label="(__('Notes'))" wire:model="form.notes" rows="2" />
        </form>
        <x-slot:footer>
            <x-button variant="ghost" @click="$dispatch('close-drawer', 'contact-form')">{{ __('Cancel') }}</x-button>
            <x-button type="submit" form="contact-form">{{ __('Save') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
