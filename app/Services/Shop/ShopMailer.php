<?php

namespace App\Services\Shop;

use App\Mail\Shop\OrderMail;
use App\Models\Shop\ShopOrder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Batch 40: every shop email goes through here. A mail failure is logged and
 * noted on the order's timeline — it never breaks checkout or an admin
 * action, since the order itself is already saved.
 */
class ShopMailer
{
    /** Offline payment (bank transfer / pay on delivery): confirm the order straight away. */
    public static function orderPlaced(ShopOrder $order): void
    {
        self::send($order, $order->email, 'placed');
        self::notifyStaff($order);
    }

    /** Online payment succeeded. */
    public static function orderConfirmed(ShopOrder $order): void
    {
        self::send($order, $order->email, 'confirmed');
        self::notifyStaff($order);
    }

    public static function statusChanged(ShopOrder $order, string $kind): void
    {
        self::send($order, $order->email, $kind);
    }

    private static function notifyStaff(ShopOrder $order): void
    {
        $to = $order->store->orders_email ?: $order->store->contact_email;
        if ($to) {
            self::send($order, $to, 'staff_new_order', false);
        }
    }

    private static function send(ShopOrder $order, string $to, string $kind, bool $logToTimeline = true): void
    {
        try {
            Mail::to($to)->send(new OrderMail($order->fresh(['items', 'store']), $kind));
            if ($logToTimeline) {
                $order->log('email', __('Email sent to :email (:kind)', ['email' => $to, 'kind' => OrderMail::subjectFor($kind, $order)]));
            }
        } catch (\Throwable $e) {
            Log::warning('Shop email failed', ['order' => $order->number, 'kind' => $kind, 'error' => $e->getMessage()]);
            $order->log('email_failed', __('Couldn’t send the :kind email to :email — check the mail settings.', ['kind' => $kind, 'email' => $to]));
        }
    }
}
