<?php

namespace App\Services\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\AccountingSetting;
use App\Models\Accounting\FixedAsset;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Batch 44b: the statement of cash flows (indirect method) and the
 * statement of changes in equity — both built from the ledger, so they tie
 * to the income statement and balance sheet.
 */
class Statements
{
    public function __construct(public AccountingSetting $settings)
    {
    }

    public static function for(string $agencyId): self
    {
        return new self(AccountingSetting::forAgency($agencyId));
    }

    /**
     * Net movement (debit − credit) per account in a period, leaving out
     * opening-balance and year-end journals (neither is a cash event).
     *
     * @return Collection<string, float>
     */
    private function movements(string $from, string $to, array $onlyTypes = [], array $events = []): Collection
    {
        $q = DB::table('acc_journal_lines as l')->join('acc_journals as j', 'j.id', '=', 'l.journal_id')
            ->where('l.agency_id', $this->settings->agency_id)
            ->whereBetween('l.date', [$from, $to]);
        $onlyTypes ? $q->whereIn('j.type', $onlyTypes) : $q->whereNotIn('j.type', ['opening', 'year_end']);
        if ($events) {
            $q->where(fn ($w) => collect($events)->each(fn ($e) => $w->orWhere('j.source_event', 'like', $e)));
        }

        return $q->groupBy('l.account_id')->selectRaw('l.account_id, sum(l.debit) - sum(l.credit) as m')
            ->pluck('m', 'account_id')->map(fn ($v) => round((float) $v, 2));
    }

    private function accounts(): Collection
    {
        return Account::where('agency_id', $this->settings->agency_id)->get()->keyBy('id');
    }

    /** Accumulated-depreciation accounts (contra assets) — used by the register, or named so. */
    private function accumulatedIds(Collection $accounts): array
    {
        $fromRegister = FixedAsset::where('agency_id', $this->settings->agency_id)->pluck('accumulated_account_id')->all();

        return $accounts->filter(fn ($a) => $a->subtype === 'non_current_asset'
            && (in_array($a->id, $fromRegister, true) || preg_match('/accum|depreciation/i', $a->name)))->keys()->all();
    }

    // -- Cash flow -------------------------------------------------------------

    public function cashFlow(string $from, string $to, bool $compare = true): array
    {
        $cur = $this->cashFlowFor($from, $to);
        $prev = $compare ? $this->cashFlowFor(Carbon::parse($from)->subYear()->toDateString(), Carbon::parse($to)->subYear()->toDateString()) : null;

        // Merge the two into rows with current / previous.
        $sections = [];
        foreach (['operating', 'investing', 'financing'] as $s) {
            $labels = array_unique(array_merge(array_keys($cur[$s]['rows']), $prev ? array_keys($prev[$s]['rows']) : []));
            $rows = [];
            foreach ($labels as $label) {
                $c = $cur[$s]['rows'][$label] ?? 0;
                $p = $prev ? ($prev[$s]['rows'][$label] ?? 0) : 0;
                if (abs($c) >= 0.005 || abs($p) >= 0.005) {
                    $rows[] = ['label' => $label, 'current' => $c, 'previous' => $p];
                }
            }
            $sections[$s] = ['rows' => $rows, 'current' => $cur[$s]['total'], 'previous' => $prev[$s]['total'] ?? 0];
        }
        $pick = fn ($k) => ['current' => $cur[$k], 'previous' => $prev[$k] ?? 0];

        return [
            'sections' => $sections,
            'result' => $pick('result'),
            'net' => $pick('net'),
            'opening' => $pick('opening'),
            'brought_in' => $pick('brought_in'),
            'closing' => $pick('closing'),
            'check' => $cur['check'],
            'from' => $from, 'to' => $to, 'compare' => $compare,
            'cashAccounts' => $cur['cashAccounts'],
        ];
    }

    private function cashFlowFor(string $from, string $to): array
    {
        $accounts = $this->accounts();
        $m = $this->movements($from, $to);
        $by = fn (array $subtypes) => $accounts->filter(fn ($a) => in_array($a->subtype, $subtypes, true))->keys()->all();
        $sum = fn (array $ids) => round(collect($ids)->sum(fn ($id) => $m[$id] ?? 0), 2);

        $cashIds = $by(Account::MONEY_SUBTYPES);
        $ieIds = $accounts->filter(fn ($a) => in_array($a->type, ['income', 'expense'], true))->keys()->all();
        $result = -$sum($ieIds);

        // Non-cash items added back.
        $dep = $sum($by(['depreciation']));
        $s = $this->settings;
        $gainLossIds = array_filter([
            $accounts->first(fn ($a) => $a->system_key === 'disposal_gain')?->id,
            $accounts->first(fn ($a) => $a->system_key === 'disposal_loss')?->id,
        ]);
        $disposal = $sum($gainLossIds);

        $wc = [
            __('(Increase) / decrease in inventory') => -$sum($by(['inventory'])),
            __('(Increase) / decrease in receivables') => -$sum($by(['receivable', 'current_asset'])),
            __('Increase / (decrease) in payables') => -$sum($by(['payable', 'current_liability'])),
            __('Increase / (decrease) in VAT owed') => -$sum($by(['vat'])),
            __('Increase / (decrease) in payroll liabilities') => -$sum($by(['payroll_liability'])),
        ];
        $operatingRows = [$s->term('result') => $result, __('Depreciation') => $dep, __('(Profit) / loss on disposal of assets') => $disposal] + $wc;
        $operating = round(array_sum($operatingRows), 2);

        $nca = $by(['non_current_asset']);
        $accumulated = $this->accumulatedIds($accounts);
        $costIds = array_values(array_diff($nca, $accumulated));
        // Net spend on asset cost accounts, leaving out disposals (and undone
        // disposals), whose cash shows as sale proceeds instead.
        $purchases = -round((float) DB::table('acc_journal_lines as l')->join('acc_journals as j', 'j.id', '=', 'l.journal_id')
            ->whereIn('l.account_id', $costIds ?: ['-'])->whereBetween('l.date', [$from, $to])
            ->whereNotIn('j.type', ['opening', 'year_end'])
            ->where(fn ($q) => $q->whereNull('j.source_event')->orWhere(fn ($w) => $w->where('j.source_event', 'not like', 'disposal-%')->where('j.source_event', 'not like', 'rev-disposal-%')))
            ->sum(DB::raw('l.debit - l.credit')), 2);
        $investing = round(-$sum($nca) - $dep - $disposal, 2);
        $investingRows = [__('Purchase of property, plant and equipment') => $purchases, __('Proceeds from sale of assets') => round($investing - $purchases, 2)];

        $financingRows = [
            __('Share capital issued / (repaid)') => -$sum($by(['share_capital'])),
            __('Loans received / (repaid)') => -$sum($by(['non_current_liability'])),
            __('Other movements in equity (drawings, dividends)') => -$sum($by(['reserves', 'equity', 'retained_earnings'])),
        ];
        $financing = round(array_sum($financingRows), 2);

        $net = round($operating + $investing + $financing, 2);
        $opening = round((float) DB::table('acc_journal_lines')->where('agency_id', $this->settings->agency_id)
            ->whereIn('account_id', $cashIds ?: ['-'])->where('date', '<', $from)->sum(DB::raw('debit - credit')), 2);
        $broughtIn = round(collect($cashIds)->sum(fn ($id) => $this->movements($from, $to, ['opening'])[$id] ?? 0), 2);
        $closing = round((float) DB::table('acc_journal_lines')->where('agency_id', $this->settings->agency_id)
            ->whereIn('account_id', $cashIds ?: ['-'])->where('date', '<=', $to)->sum(DB::raw('debit - credit')), 2);

        return [
            'operating' => ['rows' => $operatingRows, 'total' => $operating],
            'investing' => ['rows' => $investingRows, 'total' => $investing],
            'financing' => ['rows' => $financingRows, 'total' => $financing],
            'result' => $result, 'net' => $net, 'opening' => $opening, 'brought_in' => $broughtIn, 'closing' => $closing,
            'check' => round($opening + $broughtIn + $net - $closing, 2),
            'cashAccounts' => $accounts->only($cashIds)->values(),
        ];
    }

    // -- Changes in equity -----------------------------------------------------

    public function changesInEquity(string $from, string $to): array
    {
        $accounts = $this->accounts();
        $equity = $accounts->filter(fn ($a) => $a->type === 'equity')->sortBy('code');
        $re = $this->settings->account('retained_earnings');
        $ieIds = $accounts->filter(fn ($a) => in_array($a->type, ['income', 'expense'], true))->keys()->all();

        $balanceAt = function (string $date) use ($equity, $re, $ieIds) {
            $nets = DB::table('acc_journal_lines')->where('agency_id', $this->settings->agency_id)->where('date', '<=', $date)
                ->groupBy('account_id')->selectRaw('account_id, sum(debit) - sum(credit) as n')->pluck('n', 'account_id');
            $cols = [];
            foreach ($equity as $a) {
                $cols[$a->id] = -round((float) ($nets[$a->id] ?? 0), 2);
            }
            // Results not yet closed off sit with retained earnings.
            $cols[$re->id] = round(($cols[$re->id] ?? 0) - collect($ieIds)->sum(fn ($id) => (float) ($nets[$id] ?? 0)), 2);

            return $cols;
        };
        $start = $balanceAt(Carbon::parse($from)->subDay()->toDateString());
        $end = $balanceAt($to);

        $result = (new Reports($this->settings))->incomeStatement($from, $to)['result']['current'];
        $approp = $this->movements($from, $to, ['year_end'], ['approp-%', 'rev-approp-%']);
        $opening = $this->movements($from, $to, ['opening']);
        $other = $this->movements($from, $to);

        $rows = [
            'result' => array_fill_keys($equity->keys()->all(), 0.0),
            'approp' => array_fill_keys($equity->keys()->all(), 0.0),
            'opening' => array_fill_keys($equity->keys()->all(), 0.0),
            'other' => array_fill_keys($equity->keys()->all(), 0.0),
        ];
        $rows['result'][$re->id] = $result;
        foreach ($equity as $id => $a) {
            $rows['approp'][$id] = -round((float) ($approp[$id] ?? 0), 2);
            $rows['opening'][$id] = -round((float) ($opening[$id] ?? 0), 2);
            $rows['other'][$id] = -round((float) ($other[$id] ?? 0), 2);
        }

        // Hide columns that are empty throughout.
        $cols = $equity->filter(fn ($a, $id) => abs($start[$id]) >= 0.005 || abs($end[$id]) >= 0.005
            || collect($rows)->contains(fn ($r) => abs($r[$id]) >= 0.005) || $id === $re->id)->values();
        $check = [];
        foreach ($cols as $a) {
            $check[$a->id] = round($start[$a->id] + collect($rows)->sum(fn ($r) => $r[$a->id]) - $end[$a->id], 2);
        }

        return compact('cols', 'start', 'end', 'rows', 'check', 'from', 'to') + [
            'reId' => $re->id,
            'balanced' => collect($check)->every(fn ($v) => abs($v) < 0.01),
        ];
    }
}
