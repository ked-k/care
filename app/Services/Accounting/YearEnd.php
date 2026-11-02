<?php

namespace App\Services\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\AccountingSetting;
use App\Models\Accounting\Document;
use App\Models\Accounting\FinancialYear;
use App\Models\Accounting\FixedAsset;
use App\Models\Accounting\Journal;
use App\Models\Accounting\PostingIssue;
use App\Models\Accounting\Reconciliation;
use Illuminate\Support\Facades\DB;

/**
 * Batch 44a: closing a financial year.
 *
 * 1. Closing entries (journal type "year_end", dated the last day of the
 *    year): every income and expense account is cleared to zero and the
 *    year's result goes to retained earnings / accumulated surplus.
 * 2. Appropriations (also "year_end"): the shares the organisation sets
 *    aside from the result — a statutory reserve, a development fund, an
 *    education fund — move from retained earnings into their reserve
 *    accounts, as set up in Finance → Settings.
 * 3. The year is marked closed and the books are locked to its last day.
 *
 * Year-end journals are left out of the profit and loss, so the year's
 * statements still show what happened in it. Re-opening reverses both
 * journals (also as "year_end") and unlocks the year.
 */
class YearEnd
{
    /** Everything to check, and what would be posted. */
    public static function preview(FinancialYear $year): array
    {
        $s = AccountingSetting::forAgency($year->agency_id);
        $reports = new Reports($s);
        $from = $year->starts_on->toDateString();
        $to = $year->ends_on->toDateString();
        $is = $reports->incomeStatement($from, $to);
        $result = $is['result']['current'];
        $turnover = $is['revenue']['current'];

        // Closing lines: clear each income/expense account's net for the year.
        $nets = $reports->nets($from, $to, true);
        $accounts = Account::where('agency_id', $year->agency_id)->whereIn('type', ['income', 'expense'])->get()->keyBy('id');
        $closing = [];
        foreach ($nets as $id => $net) {
            if (isset($accounts[$id]) && abs($net) >= 0.005) {
                $closing[] = ['account' => $accounts[$id], 'net' => $net];
            }
        }

        $appropriations = [];
        foreach ($s->appropriations ?? [] as $a) {
            $account = Account::where('agency_id', $year->agency_id)->find($a['account_id'] ?? null);
            $base = match ($a['basis']) { 'turnover' => $turnover, 'surplus' => max(0, $result), default => null };
            $amount = $base === null ? (float) $a['rate'] : $base * (float) $a['rate'] / 100;
            $amount = round($result > 0 ? $amount : 0, 2); // nothing to share out of a loss
            $appropriations[] = ['name' => $a['name'], 'account' => $account, 'basis' => $a['basis'], 'rate' => (float) $a['rate'], 'amount' => $amount];
        }
        $appropriated = round(array_sum(array_column($appropriations, 'amount')), 2);

        $tb = $reports->trialBalance($to);
        $checks = [
            ['ok' => abs($tb['debit'] - $tb['credit']) < 0.01, 'block' => true,
                'label' => __('Trial balance balances'), 'fix' => __('Debits and credits differ — contact support before closing.')],
            ['ok' => ! $year->ends_on->isFuture(), 'block' => true,
                'label' => __('The year has ended'), 'fix' => __(':y runs until :d.', ['y' => $year->name, 'd' => $year->ends_on->format('d M Y')])],
            ['ok' => ($prev = $year->previous()) === null || $prev->isClosed(), 'block' => false,
                'label' => __('The year before is closed'), 'fix' => __('Close :y first so its result is carried forward in order.', ['y' => $prev?->name])],
            ['ok' => ! Document::where('agency_id', $year->agency_id)->where('status', 'draft')->whereBetween('issue_date', [$from, $to])->exists(), 'block' => false,
                'label' => __('No draft invoices or bills dated in the year'), 'fix' => __('Approve or delete them, or they stay out of this year’s figures.')],
            ['ok' => ! PostingIssue::where('agency_id', $year->agency_id)->whereNull('resolved_at')->exists(), 'block' => false,
                'label' => __('Nothing waiting to post'), 'fix' => __('Retry the items on the Finance dashboard.')],
            ['ok' => ! FixedAsset::where('agency_id', $year->agency_id)->where('status', 'active')->where('method', '!=', 'none')
                ->whereDate('depreciation_start', '<=', $to)
                ->where(fn ($q) => $q->whereNull('depreciated_to')->orWhereDate('depreciated_to', '<', $to))->exists(), 'block' => false,
                'label' => __('Depreciation charged to the year end'), 'fix' => __('Run depreciation to :d under Fixed assets.', ['d' => $year->ends_on->format('M Y')])],
            ['ok' => self::banksReconciled($year), 'block' => false,
                'label' => __('Bank accounts reconciled to the year end'), 'fix' => __('Reconcile each bank account to its statement at :d.', ['d' => $year->ends_on->format('d M Y')])],
            ['ok' => $appropriated <= max(0, $result) + 0.005, 'block' => true,
                'label' => __('Appropriations are no more than the :r', ['r' => mb_strtolower($s->term('result_short'))]), 'fix' => __('Lower the rates in Finance → Settings.')],
            ['ok' => collect($appropriations)->every(fn ($a) => $a['account'] && $a['account']->type === 'equity'), 'block' => true,
                'label' => __('Every appropriation goes to a reserve (equity) account'), 'fix' => __('Choose the reserve account in Finance → Settings.')],
        ];

        return compact('year', 'is', 'result', 'turnover', 'closing', 'appropriations', 'appropriated', 'checks') + [
            'retained' => round($result - $appropriated, 2),
            'canClose' => collect($checks)->where('block', true)->every(fn ($c) => $c['ok']) && ! $year->isClosed(),
        ];
    }

    private static function banksReconciled(FinancialYear $year): bool
    {
        $banks = Account::where('agency_id', $year->agency_id)->money()->where('is_active', true)->get();
        foreach ($banks as $b) {
            $hasEntries = DB::table('acc_journal_lines')->where('account_id', $b->id)->where('date', '<=', $year->ends_on->toDateString())->exists();
            $done = Reconciliation::where('account_id', $b->id)->where('status', 'completed')->whereDate('statement_date', '>=', $year->ends_on)->exists();
            if ($hasEntries && ! $done) {
                return false;
            }
        }

        return true;
    }

    public static function close(FinancialYear $year, ?int $userId): void
    {
        $p = self::preview($year);
        if (! $p['canClose']) {
            throw new LedgerException(__('Fix the items marked as required first.'));
        }
        $s = AccountingSetting::forAgency($year->agency_id);
        $date = $year->ends_on->toDateString();

        DB::transaction(function () use ($year, $p, $s, $date, $userId) {
            if ($p['closing']) {
                $lines = [];
                foreach ($p['closing'] as $c) {
                    // net = debit − credit; clear it with the opposite side.
                    $lines[] = ['account' => $c['account'], 'description' => __('Close :y', ['y' => $year->name]),
                        $c['net'] > 0 ? 'credit' : 'debit' => abs($c['net'])];
                }
                $lines[] = ['account' => 'retained_earnings', 'description' => $s->term('result').' '.$year->name,
                    $p['result'] >= 0 ? 'credit' : 'debit' => abs($p['result'])];
                Ledger::post($year->agency_id, [
                    'date' => $date, 'type' => 'year_end', 'reference' => $year->name,
                    'memo' => __(':y — income and expenses closed to :r', ['y' => $year->name, 'r' => mb_strtolower($s->term('retained'))]),
                    'source_type' => 'financial_year', 'source_id' => $year->id, 'source_event' => self::event($year, 'close'),
                    'created_by' => $userId, 'system' => true,
                ], $lines);
            }

            $approp = array_filter($p['appropriations'], fn ($a) => $a['amount'] > 0);
            if ($approp) {
                $lines = [['account' => 'retained_earnings', 'debit' => $p['appropriated'], 'description' => __('Appropriations :y', ['y' => $year->name])]];
                foreach ($approp as $a) {
                    $lines[] = ['account' => $a['account'], 'credit' => $a['amount'], 'description' => $a['name']];
                }
                Ledger::post($year->agency_id, [
                    'date' => $date, 'type' => 'year_end', 'reference' => $year->name,
                    'memo' => __(':y — appropriation of the :r', ['y' => $year->name, 'r' => mb_strtolower($s->term('result_short'))]),
                    'source_type' => 'financial_year', 'source_id' => $year->id, 'source_event' => self::event($year, 'approp'),
                    'created_by' => $userId, 'system' => true,
                ], $lines);
            }

            $year->update(['status' => 'closed', 'closed_at' => now(), 'closed_by' => $userId]);
            if (! $s->lock_date || $s->lock_date->lt($year->ends_on)) {
                $s->update(['lock_date' => $date]);
            }
            AccountingSetting::forgetCache();
            activity('accounts')->performedOn($year)->causedBy($userId)
                ->withProperties(['result' => $p['result'], 'appropriated' => $p['appropriated']])->log(__(':y closed', ['y' => $year->name]));
        });
    }

    /** Re-open: unlock, then reverse the year-end journals. */
    public static function reopen(FinancialYear $year, string $reason, ?int $userId): void
    {
        DB::transaction(function () use ($year, $reason, $userId) {
            $s = AccountingSetting::forAgency($year->agency_id);
            $later = FinancialYear::where('agency_id', $year->agency_id)->where('starts_on', '>', $year->ends_on)->where('status', 'closed')->exists();
            if ($later) {
                throw new LedgerException(__('A later year is closed. Re-open that one first.'));
            }
            $year->update(['status' => 'open', 'closed_at' => null, 'closed_by' => null]);
            if ($s->lock_date && $s->lock_date->gte($year->starts_on)) {
                $s->update(['lock_date' => $year->starts_on->copy()->subDay()->toDateString()]);
            }
            AccountingSetting::forgetCache();
            foreach (Journal::where('agency_id', $year->agency_id)->where('source_type', 'financial_year')->where('source_id', $year->id)
                ->where('type', 'year_end')->where('status', 'posted')->whereNull('reverses_id')->get() as $j) {
                Ledger::reverse($j, $reason);
            }
            activity('accounts')->performedOn($year)->causedBy($userId)->withProperties(['reason' => $reason])->log(__(':y re-opened', ['y' => $year->name]));
        });
    }

    /** Numbered so a year closed, re-opened and closed again gets fresh journals. */
    private static function event(FinancialYear $year, string $what): string
    {
        $n = Journal::where('source_type', 'financial_year')->where('source_id', $year->id)->where('source_event', 'like', $what.'-%')->count() + 1;

        return $what.'-'.$n;
    }
}
