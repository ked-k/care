<?php

namespace App\Livewire\Shop\Admin;

use App\Models\Shop\ShopOrder;
use App\Services\Shop\ShopMailer;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;

/**
 * One order, with everything staff do to it: record a payment, mark it
 * dispatched / ready to collect / completed, cancel (stock goes back), add
 * tracking and internal notes. Every action lands on the order timeline.
 */
class OrderShowComponent extends ShopAdminComponent
{
    #[Locked]
    public string $orderId = '';

    public string $carrier = '';
    public string $trackingNumber = '';
    public string $note = '';
    public bool $noteVisible = false;
    public string $paymentReference = '';
    public bool $notifyCustomer = true;
    public string $cancelReason = '';

    public function mount(string $orderId): void
    {
        $order = ShopOrder::where('store_id', $this->store()->id)->findOrFail($orderId);
        $this->orderId = $order->id;
        $this->carrier = (string) $order->carrier;
        $this->trackingNumber = (string) $order->tracking_number;
    }

    private function order(): ShopOrder
    {
        return ShopOrder::where('store_id', $this->store()->id)
            ->with(['items.product', 'items.lotAllocations.lot', 'payments.recorder', 'events.user', 'customer'])
            ->findOrFail($this->orderId);
    }

    public function recordPayment(): void
    {
        $order = $this->order();
        if ($order->payment_status === 'paid') {
            return;
        }
        $order->markPaid('manual', $this->paymentReference ?: null, (float) $order->total, Auth::user(), [
            'method' => $order->payment_method,
            'reference' => $this->paymentReference ?: null,
        ]);
        $this->paymentReference = '';
        $this->dispatch('close-modal', 'record-payment');
        $this->toast(__('Payment recorded — order moved to Processing.'));
    }

    public function markDispatched(): void
    {
        $order = $this->order();
        $this->validate(['carrier' => 'nullable|string|max:60', 'trackingNumber' => 'nullable|string|max:120']);

        $order->update([
            'status' => 'shipped',
            'shipped_at' => now(),
            'carrier' => $this->carrier ?: null,
            'tracking_number' => $this->trackingNumber ?: null,
        ]);
        $collect = $order->isCollection();
        $order->log('status', $collect ? __('Ready to collect') : __('Dispatched').($this->trackingNumber ? ' — '.($this->carrier ? $this->carrier.' ' : '').$this->trackingNumber : ''), true, Auth::id());

        if ($this->notifyCustomer) {
            ShopMailer::statusChanged($order, $collect ? 'ready' : 'dispatched');
        }
        $this->dispatch('close-modal', 'dispatch-order');
        $this->toast($collect ? __('Marked ready to collect.') : __('Marked as dispatched.'));
    }

    public function markCompleted(): void
    {
        $order = $this->order();
        $order->update(['status' => 'completed', 'completed_at' => now()]);
        $order->log('status', $order->isCollection() ? __('Collected') : __('Delivered'), true, Auth::id());
        $this->toast(__('Order completed.'));
    }

    public function setProcessing(): void
    {
        $order = $this->order();
        $order->update(['status' => 'processing']);
        $order->log('status', __('Moved back to Processing'), false, Auth::id());
        $this->toast(__('Order moved to Processing.'));
    }

    public function cancel(): void
    {
        $order = $this->order();
        if (in_array($order->status, ['cancelled', 'refunded', 'completed'], true)) {
            return;
        }
        $order->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        $order->releaseStock(Auth::id());
        $order->log('status', __('Cancelled').($this->cancelReason ? ' — '.$this->cancelReason : ''), true, Auth::id());

        if ($this->notifyCustomer) {
            ShopMailer::statusChanged($order, 'cancelled');
        }
        $this->cancelReason = '';
        $this->dispatch('close-modal', 'cancel-order');
        $this->toast(
            $order->payment_status === 'paid'
                ? __('Order cancelled and stock returned. It was paid — refund it in Stripe/PayPal or by bank, then mark it refunded.')
                : __('Order cancelled and stock returned.'),
            $order->payment_status === 'paid' ? 'warning' : 'success'
        );
    }

    public function markRefunded(): void
    {
        $order = $this->order();
        $order->update(['status' => 'refunded', 'payment_status' => 'refunded']);
        $order->releaseStock(Auth::id());
        $order->payments()->where('status', 'succeeded')->update(['status' => 'refunded']);
        $order->log('refunded', __('Marked as refunded'), true, Auth::id());
        $this->toast(__('Marked as refunded.'));
    }

    public function addNote(): void
    {
        $this->validate(['note' => 'required|string|max:1000']);
        $this->order()->log('note', $this->note, $this->noteVisible, Auth::id());
        $this->reset('note', 'noteVisible');
        $this->toast(__('Note added.'));
    }

    public function resendConfirmation(): void
    {
        $order = $this->order();
        $order->payment_status === 'paid' ? ShopMailer::statusChanged($order, 'confirmed') : ShopMailer::statusChanged($order, 'placed');
        $this->toast(__('Confirmation email sent to :email.', ['email' => $order->email]));
    }

    public function render()
    {
        $order = $this->order();

        return $this->adminPage('livewire.shop.admin.order-show', [
            'order' => $order,
            'customerUrl' => $order->email ? route('shop.order', [$this->store()->slug, $order->number]).'?t='.$order->access_token : null,
        ], __('Order :n', ['n' => $order->number]));
    }
}
