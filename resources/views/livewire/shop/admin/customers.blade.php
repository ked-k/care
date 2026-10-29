<div>
    <x-page-header title="{{ __('Customers') }}" subtitle="{{ __('People who have ordered or created an account') }}" icon="ik ik-users"
                   :breadcrumbs="['Shop' => route('shop.admin.dashboard'), 'Customers' => null]" />

    <x-table :paginator="$customers" :exports="false" :print="false">
        <x-slot:filters>
            <div class="relative">
                <i class="ik ik-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="search" wire:model.live.debounce.400ms="search" placeholder="{{ __('Name, email, organisation or phone') }}"
                       class="w-72 rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-3 text-sm outline-none focus:border-primary-500">
            </div>
            <select wire:model.live="type" class="rounded-lg border border-gray-200 bg-white py-2 pl-3 pr-9 text-sm text-gray-700 outline-none focus:border-primary-500" aria-label="{{ __('Customer type') }}">
                <option value="">{{ __('Everyone') }}</option>
                <option value="account">{{ __('With an account') }}</option>
                <option value="guest">{{ __('Guest checkouts') }}</option>
                <option value="marketing">{{ __('Opted in to emails') }}</option>
            </select>
        </x-slot:filters>

        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="px-5 py-3 font-medium">{{ __('Customer') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Type') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Orders') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Spent') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('Since') }}</th>
                    <th class="px-5 py-3 text-right font-medium">{{ __('Action') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($customers as $c)
                    <tr class="hover:bg-gray-50" wire:key="cu-{{ $c->id }}">
                        <td class="px-5 py-3">
                            <span class="block font-medium text-gray-700">{{ $c->full_name ?: '—' }}</span>
                            <span class="block text-xs text-gray-400">{{ $c->email }}@if ($c->company) — {{ $c->company }}@endif</span>
                        </td>
                        <td class="px-5 py-3">
                            <x-badge color="{{ $c->hasAccount() ? 'primary' : 'gray' }}">{{ $c->hasAccount() ? __('Account') : __('Guest') }}</x-badge>
                            @if ($c->is_blocked)<x-badge color="danger">{{ __('Blocked') }}</x-badge>@endif
                        </td>
                        <td class="px-5 py-3 text-right text-gray-700">{{ $c->orders_count }}</td>
                        <td class="whitespace-nowrap px-5 py-3 text-right font-semibold text-gray-700">{{ $store->money($c->spent ?? 0) }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ $c->created_at->format('d M Y') }}</td>
                        <td class="px-5 py-3 text-right"><button type="button" wire:click="view('{{ $c->id }}')" class="text-sm font-medium text-primary-600 hover:underline">{{ __('Open') }}</button></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10"><x-empty-state title="{{ __('No customers yet') }}" icon="ik ik-users" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </x-table>

    <x-drawer name="customer-view" title="{{ $viewing?->full_name ?: __('Customer') }}" width="w-[30rem]">
        @if ($viewing)
            <div class="space-y-5">
                <div class="text-sm">
                    <p><a href="mailto:{{ $viewing->email }}" class="text-primary-600 hover:underline">{{ $viewing->email }}</a></p>
                    @if ($viewing->phone)<p class="text-gray-600">{{ $viewing->phone }}</p>@endif
                    @if ($viewing->company)<p class="text-gray-600">{{ $viewing->company }}</p>@endif
                    <p class="mt-2 text-xs text-gray-400">
                        {{ $viewing->hasAccount() ? __('Account — last signed in :when', ['when' => $viewing->last_login_at?->diffForHumans() ?? __('never')]) : __('Guest — no password set') }}
                        — {{ $viewing->marketing_opt_in ? __('opted in to emails') : __('no marketing emails') }}
                    </p>
                </div>

                <div>
                    <h4 class="mb-2 text-sm font-semibold text-gray-800">{{ __('Orders') }}</h4>
                    <ul class="divide-y divide-gray-50 rounded-xl border border-gray-100 text-sm">
                        @forelse ($viewing->orders as $o)
                            <li class="flex items-center justify-between gap-2 px-3 py-2">
                                <a href="{{ route('shop.admin.orders.show', $o->id) }}" wire:navigate class="font-medium text-primary-600 hover:underline">{{ $o->number }}</a>
                                <span class="text-xs text-gray-400">{{ $o->created_at->format('d M Y') }}</span>
                                @include('livewire.shop.admin.partials.order-status', ['order' => $o])
                                <span class="font-semibold text-gray-700">{{ $store->money($o->total) }}</span>
                            </li>
                        @empty
                            <li class="px-3 py-4 text-center text-gray-400">{{ __('No orders yet.') }}</li>
                        @endforelse
                    </ul>
                </div>

                @if ($viewing->addresses->isNotEmpty())
                    <div>
                        <h4 class="mb-2 text-sm font-semibold text-gray-800">{{ __('Saved addresses') }}</h4>
                        <div class="space-y-2">
                            @foreach ($viewing->addresses as $a)
                                <div class="rounded-xl border border-gray-100 p-3">@include('livewire.shop.admin.partials.address', ['address' => $a->toOrderArray()])</div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div>
                    <x-form.textarea name="notes" label="{{ __('Staff notes') }}" wire:model="notes" rows="3" hint="{{ __('Only your team sees these.') }}" />
                    <x-button size="sm" variant="outline" class="mt-2" wire:click="saveNotes">{{ __('Save notes') }}</x-button>
                </div>
            </div>
        @endif
        <x-slot:footer>
            @if ($viewing && $viewing->hasAccount())
                <x-button variant="{{ $viewing->is_blocked ? 'outline' : 'ghost' }}" class="mr-auto" wire:click="toggleBlocked"
                          wire:confirm="{{ $viewing->is_blocked ? __('Let this customer sign in again?') : __('Stop this customer signing in? Their orders are kept.') }}">
                    {{ $viewing->is_blocked ? __('Unblock account') : __('Block account') }}
                </x-button>
            @endif
            <x-button variant="ghost" @click="$dispatch('close-drawer', 'customer-view')">{{ __('Close') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
