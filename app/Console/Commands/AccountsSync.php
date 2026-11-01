<?php

namespace App\Console\Commands;

use App\Models\Accounting\AccountingSetting;
use App\Models\Agency;
use App\Services\Accounting\Backfill;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Batch 43: `php artisan accounts:sync` — post care invoices, payroll and
 * shop activity that isn't in the ledger yet. Safe to re-run.
 */
class AccountsSync extends Command
{
    protected $signature = 'accounts:sync
        {--agency= : Agency id or name (default: every agency)}
        {--from= : Change the "post from" date first (YYYY-MM-DD)}';

    protected $description = 'Post care invoices, payroll runs and shop activity into the Finance ledger';

    public function handle(): int
    {
        $agencies = $this->option('agency')
            ? Agency::where('id', $this->option('agency'))->orWhere('name', $this->option('agency'))->get()
            : Agency::all();
        if ($agencies->isEmpty()) {
            $this->error('No agency found.');

            return self::FAILURE;
        }

        foreach ($agencies as $agency) {
            $settings = AccountingSetting::forAgency($agency);
            if ($from = $this->option('from')) {
                $settings->update(['posting_from' => Carbon::parse($from)->toDateString()]);
                AccountingSetting::forgetCache();
            }
            $this->info("{$agency->name} — posting records from ".(AccountingSetting::forAgency($agency)->posting_from?->format('d M Y') ?? 'the beginning'));
            $result = Backfill::run($agency->id, function ($label, $r) {
                $this->line(sprintf('  %-18s %5d checked%s', $label, $r['checked'], $r['failed'] ? "  <comment>{$r['failed']} couldn't post</comment>" : ''));
            });
            $issues = Backfill::openIssues($agency->id)->get();
            foreach ($issues->take(10) as $i) {
                $this->warn("  ! {$i->sourceLabel()} {$i->label}: {$i->message}");
            }
            if ($issues->count() > 10) {
                $this->warn('  … and '.($issues->count() - 10).' more — see Finance → Dashboard.');
            }
        }

        return self::SUCCESS;
    }
}
