<?php

namespace App\Mail\Shop;

use App\Models\Shop\ShopOrder;
use App\Services\Shop\Payments\BankTransferGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Batch 40: every order email, one template (emails/shop/order.blade.php)
 * with a heading and intro that change by $kind:
 *   placed          — offline payment: order received, how to pay
 *   confirmed       — online payment taken
 *   dispatched      — on its way (with tracking if entered)
 *   ready           — click & collect order ready
 *   cancelled       — order cancelled
 *   staff_new_order — the store's own "you have a new order" alert
 */
class OrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ShopOrder $order, public string $kind)
    {
    }

    public static function subjectFor(string $kind, ShopOrder $order): string
    {
        return match ($kind) {
            'placed' => __('Order :n received', ['n' => $order->number]),
            'confirmed' => __('Order :n confirmed', ['n' => $order->number]),
            'dispatched' => __('Order :n is on its way', ['n' => $order->number]),
            'ready' => __('Order :n is ready to collect', ['n' => $order->number]),
            'cancelled' => __('Order :n has been cancelled', ['n' => $order->number]),
            'staff_new_order' => __('New order :n — :total', ['n' => $order->number, 'total' => $order->store->money($order->total)]),
            default => __('Order :n update', ['n' => $order->number]),
        };
    }

    public function envelope(): Envelope
    {
        $store = $this->order->store;

        return new Envelope(
            subject: $store->name.': '.self::subjectFor($this->kind, $this->order),
            replyTo: $store->contact_email ? [$store->contact_email] : [],
        );
    }

    public function content(): Content
    {
        $store = $this->order->store;

        return new Content(
            view: 'emails.shop.order',
            with: [
                'order' => $this->order,
                'store' => $store,
                'kind' => $this->kind,
                'heading' => self::subjectFor($this->kind, $this->order),
                'orderUrl' => $this->kind === 'staff_new_order'
                    ? route('shop.admin.orders.show', $this->order->id)
                    : route('shop.order', [$store->slug, $this->order->number]).'?t='.$this->order->access_token,
                'bankDetails' => $this->order->payment_method === 'bank_transfer' && $this->order->payment_status !== 'paid'
                    ? BankTransferGateway::details($store) : [],
                'bankInstructions' => $store->paymentSetting('bank_transfer', 'instructions'),
            ],
        );
    }
}
