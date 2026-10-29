<?php

namespace App\Console\Commands;

use App\Models\Shop\ShopOrder;
use Illuminate\Console\Command;

/**
 * Batch 40: an order paid by card or PayPal holds its stock from the moment
 * it's placed. If the customer abandons the payment page, that stock would
 * stay reserved forever — this cancels such orders after a grace period and
 * puts the stock back. Bank transfer and pay-on-delivery orders are left
 * alone: those are expected to sit unpaid until staff record the payment.
 */
class ShopReleaseUnpaidOrders extends Command
{
    protected $signature = 'shop:release-unpaid {--hours=24 : How long to wait for an online payment before cancelling}';

    protected $description = 'Cancel unpaid card/PayPal shop orders older than the grace period and return their stock';

    public function handle(): int
    {
        $cutoff = now()->subHours(max(1, (int) $this->option('hours')));

        $orders = ShopOrder::where('status', 'pending')
            ->where('payment_status', 'unpaid')
            ->whereIn('payment_method', ['stripe', 'paypal'])
            ->where('created_at', '<', $cutoff)
            ->get();

        foreach ($orders as $order) {
            $order->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            $order->releaseStock();
            $order->log('status', __('Cancelled automatically — online payment wasn’t completed within :h hours.', ['h' => $this->option('hours')]), true);
        }

        $this->info("Released {$orders->count()} unpaid order(s).");

        return self::SUCCESS;
    }
}
