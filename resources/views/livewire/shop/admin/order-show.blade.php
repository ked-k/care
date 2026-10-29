<div>
    @php
        $closed = in_array($order->status, ['cancelled', 'refunded'], true);
        $collect = $order->isCollection();
    @endphp

    <x-page-header title="{{ __('Order :n', ['n' => $order->number]) }}" subtitle="{{ __('Placed :date', ['date' => $order->created_at->format('j M Y, H:i')]) }}" icon="ik ik-shopping-cart"
                   :breadcrumbs="['Shop' => route('shop.admin.dashboard'), 'Orders' => route('shop.admin.orders'), $order->number => null]">
        <div class="flex flex-wrap items-center gap-2">
            @include('livewire.shop.admin.partials.order-status', ['order' => $order])
            <x-button variant="outline" size="sm" href="{{ route('shop.admin.orders.packing-slip', $order->id) }}" target="_blank"><i class="ik ik-printer"></i>{{ __('Packing slip') }}</x-button>
        </div>
    </x-page-header>

    {{-- What to do next --}}
    @unless ($closed || $order->status === 'completed')
        <div class="mb-5 flex flex-col gap-3 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-gray-600">
                @if ($order->payment_status !== 'paid')
                    <i class="ik ik-clock mr-1 text-amber-600"></i>
                    {{ $order->payment_method === 'bank_transfer'
                        ? __('Waiting for a bank transfer of :amount with reference :ref.', ['amount' => $store->money($order->total), 'ref' => $order->number])
                        : ($order->payment_method === 'pay_on_delivery'
                            ? __('Customer pays :amount on delivery or collection.', ['amount' => $store->money($order->total)])
                            : __('Online payment not completed yet.')) }}
                @elseif ($order->status === 'processing')
                    <i class="ik ik-package mr-1 text-primary-600"></i>{{ $collect ? __('Paid. Pack it and mark it ready to collect.') : __('Paid. Pack it and mark it dispatched.') }}
                @elseif ($order->status === 'shipped')
                    <i class="ik ik-truck mr-1 text-primary-600"></i>{{ $collect ? __('Waiting for the customer to collect.') : __('On its way to the customer.') }}
                @endif
            </p>
            <div class="flex flex-wrap gap-2">
                @if ($order->payment_status !== 'paid')
                    <x-button variant="success" size="sm" @click="$dispatch('open-modal', 'record-payment')"><i class="ik ik-check"></i>{{ __('Record payment') }}</x-button>
                @endif
                @if (in_array($order->status, ['pending', 'processing'], true))
                    <x-button variant="primary" size="sm" @click="$dispatch('open-modal', 'dispatch-order')"><i class="ik ik-truck"></i>{{ $collect ? __('Ready to collect') : __('Mark dispatched') }}</x-button>
                @endif
                @if ($order->status === 'shipped')
                    <x-button variant="primary" size="sm" wire:click="markCompleted"><i class="ik ik-check-circle"></i>{{ $collect ? __('Mark collected') : __('Mark delivered') }}</x-button>
                    <x-button variant="ghost" size="sm" wire:click="setProcessing">{{ __('Undo dispatch') }}</x-button>
                @endif
                <x-button variant="ghost" size="sm" @click="$dispatch('open-modal', 'cancel-order')"><i class="ik ik-x-circle"></i>{{ __('Cancel order') }}</x-button>
            </div>
        </div>
    @endunless

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <x-card no-padding title="{{ __('Items') }}" icon="ik ik-package">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                                <th class="px-5 py-3 font-medium">{{ __('Item') }}</th>
                                <th class="px-5 py-3 text-right font-medium">{{ __('Qty') }}</th>
                                <th class="px-5 py-3 text-right font-medium">{{ __('Each') }}</th>
                                <th class="px-5 py-3 text-right font-medium">{{ __('VAT') }}</th>
                                <th class="px-5 py-3 text-right font-medium">{{ __('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($order->items as $item)
                                <tr>
                                    <td class="px-5 py-3">
                                        <span class="block font-medium text-gray-700">{{ $item->name }}</span>
                                        <span class="font-mono text-xs text-gray-400">{{ $item->sku }}</span>
                                        @foreach ($item->lotsUsed() as $used)
                                            <span class="mt-0.5 block text-xs text-gray-500"><i class="ik ik-tag"></i> {{ $used['qty'] }} × {{ $used['lot']->label() }}</span>
                                        @endforeach
                                    </td>
                                    <td class="px-5 py-3 text-right font-semibold text-gray-700">{{ $item->qty }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-gray-600">{{ $store->money($item->unit_price) }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-gray-400">{{ rtrim(rtrim(number_format((float) $item->vat_rate, 2), '0'), '.') }}%</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right font-semibold text-gray-700">{{ $store->money($item->line_total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-5 py-4">
                    <dl class="ml-auto max-w-xs space-y-1.5 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">{{ __('Items') }}</dt><dd class="text-gray-700">{{ $store->money($order->subtotal) }}</dd></div>
                        @if ($order->discount_total > 0)
                            <div class="flex justify-between"><dt class="text-gray-500">{{ __('Discount') }} <span class="font-mono text-xs">{{ $order->discount_code }}</span></dt><dd class="text-green-600">−{{ $store->money($order->discount_total) }}</dd></div>
                        @endif
                        <div class="flex justify-between"><dt class="text-gray-500">{{ $order->delivery_name ?: __('Delivery') }}</dt><dd class="text-gray-700">{{ $store->money($order->delivery_price) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">{{ $order->prices_include_vat ? __('VAT included') : __('VAT') }}</dt><dd class="text-gray-700">{{ $store->money($order->vat_total) }}</dd></div>
                        <div class="flex justify-between border-t border-gray-100 pt-2 text-base"><dt class="font-semibold text-gray-800">{{ __('Total') }}</dt><dd class="font-bold text-gray-800">{{ $store->money($order->total) }}</dd></div>
                    </dl>
                </div>
            </x-card>

            <x-card title="{{ __('Timeline') }}" icon="ik ik-activity">
                <form wire:submit="addNote" class="mb-5 space-y-2">
                    <x-form.textarea name="note" wire:model="note" rows="2" placeholder="{{ __('Add a note about this order…') }}" />
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <label class="flex items-center gap-2 text-sm text-gray-600">
                            <input type="checkbox" wire:model="noteVisible" class="h-4 w-4 rounded border-gray-300 text-primary-600">
                            {{ __('Show this to the customer on their order page') }}
                        </label>
                        <x-button type="submit" size="sm">{{ __('Add note') }}</x-button>
                    </div>
                </form>
                <ol class="space-y-4">
                    @foreach ($order->events as $event)
                        <li class="flex gap-3">
                            <span @class([
                                'mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full',
                                'bg-green-500/10 text-green-600' => in_array($event->type, ['paid', 'placed'], true),
                                'bg-accent-500/10 text-accent-600' => in_array($event->type, ['email_failed', 'payment_warning', 'refunded'], true),
                                'bg-gray-100 text-gray-500' => ! in_array($event->type, ['paid', 'placed', 'email_failed', 'payment_warning', 'refunded'], true),
                            ])>
                                <i class="{{ match ($event->type) {
                                    'placed' => 'ik ik-shopping-cart', 'paid' => 'ik ik-check', 'status' => 'ik ik-truck',
                                    'note' => 'ik ik-message-square', 'email' => 'ik ik-mail', 'email_failed' => 'ik ik-alert-triangle',
                                    default => 'ik ik-info',
                                } }} text-sm"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-gray-700">{{ $event->message }}</p>
                                <p class="text-xs text-gray-400">
                                    {{ $event->created_at->format('d M Y, H:i') }}
                                    @if ($event->user) — {{ $event->user->name }}@endif
                                    @if ($event->visible_to_customer) — <span class="text-primary-600">{{ __('customer can see this') }}</span>@endif
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </x-card>
        </div>

        <div class="space-y-5">
            <x-card title="{{ __('Customer') }}" icon="ik ik-user">
                <p class="font-semibold text-gray-800">{{ $order->billing_address['name'] ?? '—' }}</p>
                @if (! empty($order->billing_address['company']))<p class="text-sm text-gray-500">{{ $order->billing_address['company'] }}</p>@endif
                <p class="mt-2 text-sm"><a href="mailto:{{ $order->email }}" class="text-primary-600 hover:underline">{{ $order->email }}</a></p>
                @if ($order->phone)<p class="text-sm text-gray-600">{{ $order->phone }}</p>@endif
                <p class="mt-2 text-xs text-gray-400">
                    {{ $order->customer?->hasAccount() ? __('Has an account') : __('Guest checkout') }}
                    @if ($order->customer) — {{ trans_choice(':count order|:count orders', $order->customer->orders()->count(), ['count' => $order->customer->orders()->count()]) }}@endif
                </p>
                @if ($order->customer_notes)
                    <div class="mt-3 rounded-lg bg-amber-50 p-3 text-sm text-gray-700"><span class="font-semibold">{{ __('Note from customer') }}:</span> {{ $order->customer_notes }}</div>
                @endif
                <div class="mt-3 flex flex-wrap gap-2">
                    <x-button variant="outline" size="sm" wire:click="resendConfirmation"><i class="ik ik-mail"></i>{{ __('Resend confirmation') }}</x-button>
                    @if ($customerUrl)
                        <x-button variant="ghost" size="sm" href="{{ $customerUrl }}" target="_blank"><i class="ik ik-external-link"></i>{{ __('Customer’s view') }}</x-button>
                    @endif
                </div>
            </x-card>

            <x-card title="{{ $collect ? __('Click & collect') : __('Delivery') }}" icon="ik ik-truck">
                <p class="mb-2 text-sm font-medium text-gray-700">{{ $order->delivery_name ?: '—' }}</p>
                @if ($order->shipping_address)
                    @include('livewire.shop.admin.partials.address', ['address' => $order->shipping_address])
                @else
                    <p class="text-sm text-gray-500">{{ __('Customer collects from your address.') }}</p>
                @endif
                @if ($order->tracking_number)
                    <p class="mt-3 text-sm text-gray-600">{{ __('Tracking') }}: <span class="font-mono">{{ $order->carrier ? $order->carrier.' ' : '' }}{{ $order->tracking_number }}</span></p>
                @endif
            </x-card>

            <x-card title="{{ __('Payment') }}" icon="ik ik-credit-card">
                <p class="text-sm text-gray-700">{{ $order->paymentMethodLabel() }} — <span class="font-semibold">{{ $order->paymentStatusLabel() }}</span></p>
                @if ($order->paid_at)<p class="text-xs text-gray-400">{{ __('Paid :date', ['date' => $order->paid_at->format('d M Y, H:i')]) }}</p>@endif
                @if ($order->payments->isNotEmpty())
                    <ul class="mt-3 space-y-2 text-xs">
                        @foreach ($order->payments as $p)
                            <li class="flex justify-between gap-2 rounded-lg bg-gray-50 px-3 py-2">
                                <span class="min-w-0">
                                    <span class="block font-medium text-gray-600">{{ \App\Services\Shop\Payments\PaymentManager::label($p->gateway) }} — {{ $p->status }}</span>
                                    <span class="block truncate font-mono text-gray-400">{{ $p->provider_ref }}</span>
                                    @if ($p->recorder)<span class="block text-gray-400">{{ __('by :name', ['name' => $p->recorder->name]) }}</span>@endif
                                </span>
                                <span class="font-semibold text-gray-700">{{ $store->money($p->amount) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
                @if ($order->payment_status === 'paid' && in_array($order->status, ['cancelled'], true))
                    <x-button variant="warning" size="sm" class="mt-3" wire:click="markRefunded" wire:confirm="{{ __('Only do this once the money has actually been returned. Mark as refunded?') }}">{{ __('Mark refunded') }}</x-button>
                @endif
                <div class="mt-3 border-t border-gray-100 pt-3">
                    <p class="mb-1 text-xs font-medium text-gray-500">{{ __('Billing address') }}</p>
                    @include('livewire.shop.admin.partials.address', ['address' => $order->billing_address])
                </div>
            </x-card>
        </div>
    </div>

    <x-modal name="record-payment" title="{{ __('Record a payment') }}">
        <p class="mb-4 text-sm text-gray-600">{{ __('Use this once the money has actually arrived — e.g. a bank transfer showing on your statement, or card/cash taken on delivery.') }}</p>
        <x-form.input name="paymentReference" label="{{ __('Reference (optional)') }}" wire:model="paymentReference" placeholder="{{ __('e.g. bank statement reference') }}" />
        <p class="mt-3 text-sm text-gray-700">{{ __('Amount') }}: <span class="font-semibold">{{ $store->money($order->total) }}</span></p>
        <x-slot:footer>
            <x-button variant="ghost" @click="$dispatch('close-modal', 'record-payment')">{{ __('Cancel') }}</x-button>
            <x-button variant="success" wire:click="recordPayment" @click="$dispatch('close-modal', 'record-payment')">{{ __('Record payment') }}</x-button>
        </x-slot:footer>
    </x-modal>

    <x-modal name="dispatch-order" title="{{ $collect ? __('Ready to collect') : __('Mark as dispatched') }}">
        @unless ($collect)
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <x-form.input name="carrier" label="{{ __('Courier') }}" wire:model="carrier" placeholder="{{ __('e.g. Royal Mail, DPD') }}" />
                <x-form.input name="trackingNumber" label="{{ __('Tracking number') }}" wire:model="trackingNumber" />
            </div>
        @endunless
        @if ($order->payment_status !== 'paid')
            <p class="mt-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-600">{{ __('This order hasn’t been paid yet.') }}</p>
        @endif
        <label class="mt-4 flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" wire:model="notifyCustomer" class="h-4 w-4 rounded border-gray-300 text-primary-600">
            {{ __('Email the customer') }}
        </label>
        <x-slot:footer>
            <x-button variant="ghost" @click="$dispatch('close-modal', 'dispatch-order')">{{ __('Cancel') }}</x-button>
            <x-button wire:click="markDispatched" @click="$dispatch('close-modal', 'dispatch-order')">{{ $collect ? __('Mark ready') : __('Mark dispatched') }}</x-button>
        </x-slot:footer>
    </x-modal>

    <x-modal name="cancel-order" title="{{ __('Cancel this order?') }}">
        <p class="mb-3 text-sm text-gray-600">{{ __('Stock goes back on sale. Payments aren’t refunded automatically — if it was paid, refund it in Stripe, PayPal or by bank, then mark it refunded here.') }}</p>
        <x-form.input name="cancelReason" label="{{ __('Reason (optional)') }}" wire:model="cancelReason" />
        <label class="mt-4 flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" wire:model="notifyCustomer" class="h-4 w-4 rounded border-gray-300 text-primary-600">
            {{ __('Email the customer') }}
        </label>
        <x-slot:footer>
            <x-button variant="ghost" @click="$dispatch('close-modal', 'cancel-order')">{{ __('Keep order') }}</x-button>
            <x-button variant="danger" wire:click="cancel" @click="$dispatch('close-modal', 'cancel-order')">{{ __('Cancel order') }}</x-button>
        </x-slot:footer>
    </x-modal>
</div>
