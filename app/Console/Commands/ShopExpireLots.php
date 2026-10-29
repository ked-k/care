<?php

namespace App\Console\Commands;

use App\Services\Shop\StockLedger;
use Illuminate\Console\Command;

/**
 * Batch 42: take expired batches off sale. Runs every night just after
 * midnight; each write-off is a stock movement of type "expired" with the lot
 * number, so it shows in Inventory → Stock history and the stock value.
 */
class ShopExpireLots extends Command
{
    protected $signature = 'shop:expire-lots';

    protected $description = 'Write off shop stock batches whose expiry date has passed';

    public function handle(): int
    {
        $units = StockLedger::writeOffExpired();
        $this->info($units ? "Wrote off {$units} expired units." : 'Nothing has expired.');

        return self::SUCCESS;
    }
}
