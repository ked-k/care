{{-- Batch 40: an order's fulfilment + payment state as two compact badges. --}}
<span class="inline-flex flex-wrap items-center gap-1">
    <x-badge color="{{ match ($order->status) {
        'pending' => 'warning',
        'processing' => 'primary',
        'shipped' => 'dark',
        'completed' => 'success',
        default => 'danger',
    } }}">{{ $order->statusLabel() }}</x-badge>
    @if (! in_array($order->status, ['cancelled', 'refunded'], true))
        <x-badge color="{{ $order->payment_status === 'paid' ? 'success' : ($order->payment_status === 'failed' ? 'danger' : 'gray') }}">{{ $order->paymentStatusLabel() }}</x-badge>
    @endif
</span>
