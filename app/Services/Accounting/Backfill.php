<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountingSetting;
use App\Models\Accounting\Journal;
use App\Models\Accounting\PostingIssue;
use App\Models\Invoice;
use App\Models\PayrollRun;
use App\Models\Shop\ShopGrn;
use App\Models\Shop\ShopOrder;
use App\Models\Shop\ShopStockMovement;
use App\Models\Shop\ShopStore;
use Illuminate\Support\Facades\DB;

/**
 * Batch 43: posts everything that happened before Finance was switched on
 * (or while automatic posting was off) — care invoices, payroll runs, shop
 * orders, deliveries and stock movements dated on or after the agency's
 * "post from" date. Safe to run again: anything already posted is skipped.
 */
class Backfill
{
    /** @return array<string, array{checked: int, failed: int}> */
    public static function run(string $agencyId, ?callable $progress = null): array
    {
        $settings = AccountingSetting::forAgency($agencyId);
        $from = $settings->posting_from?->toDateString() ?? '1900-01-01';
        $out = [];
        $tally = function (string $key, string $type, $records, callable $sync) use (&$out, $progress) {
            $out[$key] = ['checked' => 0, 'failed' => 0];
            foreach ($records as $r) {
                $out[$key]['checked']++;
                if (! AutoPosting::run($type, $r->getKey(), fn () => $sync($r))) {
                    $out[$key]['failed']++;
                }
            }
            $progress && $progress($key, $out[$key]);
        };

        $tally('Care invoices', 'care_invoice',
            Invoice::whereHas('invoiceRun', fn ($q) => $q->where('agency_id', $agencyId))
                ->whereIn('status', ['issued', 'paid'])->orderBy('issued_at')->get(),
            fn ($m) => CarePosting::syncCareInvoice($m));

        $tally('Payroll runs', 'payroll_run',
            PayrollRun::where('agency_id', $agencyId)->whereIn('status', ['approved', 'paid', 'cancelled'])->orderBy('approved_at')->get(),
            fn ($m) => CarePosting::syncPayrollRun($m));

        $storeIds = class_exists(ShopStore::class) ? ShopStore::where('agency_id', $agencyId)->pluck('id') : collect();
        if ($storeIds->isNotEmpty()) {
            $tally('Shop deliveries', 'shop_grn',
                ShopGrn::whereIn('store_id', $storeIds)->whereIn('status', ['received', 'reversed'])->orderBy('received_on')->get(),
                fn ($m) => ShopPosting::syncGrn($m));

            $productIds = DB::table('shop_products')->whereIn('store_id', $storeIds)->pluck('id');
            $tally('Stock movements', 'shop_stock_movement',
                ShopStockMovement::whereIn('product_id', $productIds)->whereNull('grn_id')->where('created_at', '>=', $from)
                    ->whereNotIn('id', Journal::where('agency_id', $agencyId)->where('source_type', 'shop_stock_movement')->select('source_id'))
                    ->orderBy('created_at')->cursor(),
                fn ($m) => ShopPosting::syncMovement($m));

            $tally('Shop orders', 'shop_order',
                ShopOrder::whereIn('store_id', $storeIds)->whereIn('payment_status', ['paid', 'refunded'])->orderBy('paid_at')->get(),
                fn ($m) => ShopPosting::syncOrder($m));

            $opening = ShopPosting::openingStock($agencyId);
            $out['Opening stock'] = ['checked' => $opening ? 1 : 0, 'failed' => 0];
        }

        return $out;
    }

    public static function openIssues(string $agencyId)
    {
        return PostingIssue::where('agency_id', $agencyId)->whereNull('resolved_at')->latest('updated_at');
    }
}
