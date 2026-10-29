<div>
    <x-page-header title="{{ __('Shop') }}" subtitle="{{ $store->is_live ? __('Open to the public at :url', ['url' => route('shop.home', $store->slug)]) : __('Not open yet — only your staff can see the storefront') }}"
                   icon="ik ik-shopping-bag">
        <div class="flex flex-wrap items-center gap-2">
            <select wire:model.live="range" class="rounded-lg border border-gray-200 bg-white py-2 pl-3 pr-9 text-sm text-gray-700 outline-none focus:border-primary-500" aria-label="{{ __('Period') }}">
                <option value="7">{{ __('Last 7 days') }}</option>
                <option value="30">{{ __('Last 30 days') }}</option>
                <option value="90">{{ __('Last 90 days') }}</option>
            </select>
            <x-button variant="outline" href="{{ route('shop.admin.storefront') }}" target="_blank"><i class="ik ik-external-link"></i>{{ __('View shop') }}</x-button>
        </div>
    </x-page-header>

    @if (collect($setup)->contains('done', false))
        @php $doneCount = collect($setup)->where('done', true)->count(); @endphp
        <x-card class="mb-6" title="{{ __('Get your shop ready') }}" subtitle="{{ __(':done of :total done', ['done' => $doneCount, 'total' => count($setup)]) }}" icon="ik ik-check-square">
            <div class="mb-4 h-2 overflow-hidden rounded-full bg-gray-100">
                <div class="h-2 rounded-full bg-green-500" style="width: {{ round($doneCount / count($setup) * 100) }}%"></div>
            </div>
            <div class="grid grid-cols-1 gap-2 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($setup as $step)
                    <a href="{{ $step['route'] }}" wire:navigate class="flex items-center gap-3 rounded-lg border border-gray-100 px-3 py-2.5 text-sm transition hover:bg-gray-50">
                        @if ($step['done'])
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-green-500 text-white"><i class="ik ik-check text-xs"></i></span>
                            <span class="text-gray-400 line-through">{{ $step['label'] }}</span>
                        @else
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-gray-200 text-gray-400"><i class="ik ik-arrow-right text-xs"></i></span>
                            <span class="font-medium text-gray-700">{{ $step['label'] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </x-card>
    @endif

    <div class="mb-6 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card value="{{ $store->money($revenue) }}" label="{{ __('Paid sales') }}" icon="ik ik-trending-up" color="primary"
                     :trend="$revenueChange !== null ? ($revenueChange >= 0 ? '+' : '').$revenueChange.'% '.__('vs previous') : null" :trendUp="($revenueChange ?? 0) >= 0" :spark="$series" />
        <x-stat-card value="{{ number_format($orders) }}" label="{{ __('Orders placed') }}" icon="ik ik-shopping-cart" color="green" />
        <x-stat-card value="{{ $store->money($avg) }}" label="{{ __('Average paid order') }}" icon="ik ik-bar-chart-2" color="purple" />
        <x-stat-card value="{{ number_format($activeCount) }}" label="{{ __('Products on sale (of :n)', ['n' => number_format($productCount)]) }}" icon="ik ik-package" color="amber" />
    </div>

    <div class="mb-6 grid grid-cols-1 gap-5 md:grid-cols-2">
        <a href="{{ route('shop.admin.orders', ['status' => 'pending']) }}" wire:navigate class="flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm transition hover:shadow-md">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600"><i class="ik ik-clock text-xl"></i></span>
            <div class="flex-1">
                <h4 class="text-2xl font-bold text-gray-800">{{ $needsAction['awaiting_payment'] }}</h4>
                <p class="text-sm text-gray-500">{{ __('Waiting for payment — bank transfer or pay on delivery') }}</p>
            </div>
            <i class="ik ik-chevron-right text-gray-400"></i>
        </a>
        <a href="{{ route('shop.admin.orders', ['status' => 'processing']) }}" wire:navigate class="flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm transition hover:shadow-md">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary-500/10 text-primary-600"><i class="ik ik-package text-xl"></i></span>
            <div class="flex-1">
                <h4 class="text-2xl font-bold text-gray-800">{{ $needsAction['to_dispatch'] }}</h4>
                <p class="text-sm text-gray-500">{{ __('Paid and ready to pack and dispatch') }}</p>
            </div>
            <i class="ik ik-chevron-right text-gray-400"></i>
        </a>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <x-card no-padding class="lg:col-span-2" title="{{ __('Recent orders') }}" icon="ik ik-list">
            <x-slot:actions><a href="{{ route('shop.admin.orders') }}" wire:navigate class="text-sm font-medium text-primary-600 hover:underline">{{ __('All orders') }}</a></x-slot:actions>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-50">
                        @forelse ($recent as $o)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3"><a href="{{ route('shop.admin.orders.show', $o->id) }}" wire:navigate class="font-semibold text-primary-600 hover:underline">{{ $o->number }}</a></td>
                                <td class="px-5 py-3 text-gray-600">{{ $o->billing_address['name'] ?? $o->email }}</td>
                                <td class="px-5 py-3 text-gray-400">{{ $o->created_at->diffForHumans() }}</td>
                                <td class="px-5 py-3">@include('livewire.shop.admin.partials.order-status', ['order' => $o])</td>
                                <td class="px-5 py-3 text-right font-semibold text-gray-700">{{ $store->money($o->total) }}</td>
                            </tr>
                        @empty
                            <tr><td class="px-5 py-10"><x-empty-state compact title="{{ __('No orders yet') }}" description="{{ __('Orders from your storefront appear here as they come in.') }}" icon="ik ik-shopping-cart" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <div class="space-y-5">
            <x-card no-padding title="{{ __('Running low') }}" icon="ik ik-alert-triangle">
                <x-slot:actions><a href="{{ route('shop.admin.inventory') }}" wire:navigate class="text-sm font-medium text-primary-600 hover:underline">{{ __('Inventory') }}</a></x-slot:actions>
                <ul class="divide-y divide-gray-50 text-sm">
                    @forelse ($lowStock as $p)
                        <li class="flex items-center justify-between gap-3 px-5 py-2.5">
                            <span class="min-w-0 truncate text-gray-700">{{ $p->name }}</span>
                            <x-badge color="{{ $p->stock_qty <= 0 ? 'danger' : 'warning' }}">{{ $p->stock_qty }}</x-badge>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-gray-400">{{ __('Nothing low on stock.') }}</li>
                    @endforelse
                </ul>
            </x-card>

            <x-card no-padding title="{{ __('Best sellers') }}" icon="ik ik-award">
                <ul class="divide-y divide-gray-50 text-sm">
                    @forelse ($topProducts as $tp)
                        <li class="flex items-center justify-between gap-3 px-5 py-2.5">
                            <span class="min-w-0">
                                <span class="block truncate text-gray-700">{{ $tp->name }}</span>
                                <span class="text-xs text-gray-400">{{ trans_choice(':count unit|:count units', (int) $tp->units, ['count' => (int) $tp->units]) }}</span>
                            </span>
                            <span class="font-semibold text-gray-700">{{ $store->money($tp->revenue) }}</span>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-gray-400">{{ __('No paid sales in this period yet.') }}</li>
                    @endforelse
                </ul>
            </x-card>
        </div>
    </div>
</div>
