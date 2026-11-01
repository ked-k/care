<?php

namespace App\Services\Accounting;

use App\Models\Accounting\PostingIssue;
use App\Models\Invoice;
use App\Models\PayrollRun;
use App\Models\Shop\ShopGrn;
use App\Models\Shop\ShopOrder;
use App\Models\Shop\ShopStockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Batch 43: hooks the ledger onto the rest of CareTrust. Registered from
 * AppServiceProvider::boot(). Each hook waits until the surrounding database
 * transaction has committed (so a sale's stock batches, a run's payslips
 * etc. are all saved), then makes the books match the record.
 *
 * A posting that fails never stops the action itself — issuing invoices,
 * approving payroll or taking an order carries on, and the problem is kept
 * in acc_posting_issues for Finance → Dashboard to show and retry.
 */
class AutoPosting
{
    public static bool $enabled = true;

    public static function register(): void
    {
        $hook = fn (string $type, callable $sync) => function ($model) use ($type, $sync) {
            if (! self::$enabled) {
                return;
            }
            DB::afterCommit(fn () => self::run($type, $model->getKey(), fn () => $sync($model->fresh() ?? $model)));
        };

        Invoice::saved($hook('care_invoice', fn ($m) => CarePosting::syncCareInvoice($m)));
        Invoice::deleting(function (Invoice $m) {
            if (self::$enabled) {
                self::run('care_invoice', $m->getKey(), fn () => CarePosting::careInvoiceRemoved($m));
            }
        });
        PayrollRun::saved($hook('payroll_run', fn ($m) => CarePosting::syncPayrollRun($m)));

        if (class_exists(ShopOrder::class)) {
            ShopOrder::saved($hook('shop_order', fn ($m) => ShopPosting::syncOrder($m)));
            ShopStockMovement::created($hook('shop_stock_movement', fn ($m) => ShopPosting::syncMovement($m)));
            ShopGrn::saved($hook('shop_grn', fn ($m) => ShopPosting::syncGrn($m)));
        }
    }

    /** Run one sync, recording (or clearing) a posting issue for it. */
    public static function run(string $type, string $id, callable $sync): bool
    {
        static $ready = null;
        $ready ??= Schema::hasTable('acc_posting_issues');
        if (! $ready) {
            return false; // Finance not migrated yet
        }
        try {
            $sync();
            PostingIssue::where('source_type', $type)->where('source_id', $id)->whereNull('resolved_at')
                ->update(['resolved_at' => now()]);

            return true;
        } catch (Throwable $e) {
            Log::warning("Accounts: couldn't post {$type} {$id}: ".$e->getMessage());
            try {
                $issue = PostingIssue::firstOrNew(['source_type' => $type, 'source_id' => $id]);
                $issue->fill([
                    'agency_id' => $issue->agency_id ?? self::agencyFor($type, $id),
                    'label' => $issue->label ?? self::labelFor($type, $id),
                    'message' => $e instanceof LedgerException ? $e->getMessage() : mb_substr(class_basename($e).': '.$e->getMessage(), 0, 2000),
                    'attempts' => $issue->exists ? $issue->attempts + 1 : 1,
                    'resolved_at' => null,
                ])->save();
            } catch (Throwable) {
                // never let bookkeeping break the page
            }

            return false;
        }
    }

    /** Retry one recorded issue. */
    public static function retry(PostingIssue $issue): bool
    {
        $sync = match ($issue->source_type) {
            'care_invoice' => fn () => ($m = Invoice::find($issue->source_id)) ? CarePosting::syncCareInvoice($m) : null,
            'payroll_run' => fn () => ($m = PayrollRun::find($issue->source_id)) ? CarePosting::syncPayrollRun($m) : null,
            'shop_order' => fn () => ($m = ShopOrder::find($issue->source_id)) ? ShopPosting::syncOrder($m) : null,
            'shop_stock_movement' => fn () => ($m = ShopStockMovement::find($issue->source_id)) ? ShopPosting::syncMovement($m) : null,
            'shop_grn' => fn () => ($m = ShopGrn::find($issue->source_id)) ? ShopPosting::syncGrn($m) : null,
            default => fn () => null,
        };

        return self::run($issue->source_type, $issue->source_id, $sync);
    }

    private static function agencyFor(string $type, string $id): ?string
    {
        return match ($type) {
            'care_invoice' => Invoice::find($id)?->invoiceRun?->agency_id,
            'payroll_run' => PayrollRun::find($id)?->agency_id,
            'shop_order' => ShopPosting::agencyForStore(ShopOrder::find($id)?->store_id),
            'shop_grn' => ShopPosting::agencyForStore(ShopGrn::find($id)?->store_id),
            'shop_stock_movement' => ShopPosting::agencyForStore(DB::table('shop_products')->where('id', ShopStockMovement::find($id)?->product_id)->value('store_id')),
            default => null,
        };
    }

    private static function labelFor(string $type, string $id): ?string
    {
        return match ($type) {
            'care_invoice' => Invoice::find($id)?->invoice_number,
            'payroll_run' => PayrollRun::find($id)?->reference,
            'shop_order' => ShopOrder::find($id)?->number,
            'shop_grn' => ShopGrn::find($id)?->number,
            'shop_stock_movement' => ($m = ShopStockMovement::find($id)) ? trim(($m->reference ?? '').' '.($m->product?->name ?? '')) : null,
            default => null,
        };
    }
}
