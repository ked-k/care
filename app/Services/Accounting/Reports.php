<?php

namespace App\Services\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\AccountingSetting;
use App\Models\Accounting\Contact;
use App\Models\Accounting\Document;
use App\Models\Accounting\JournalLine;
use App\Models\Accounting\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Batch 43: every report reads journal lines only — never invoices or
 * payroll directly — so the statements always agree with each other.
 */
class Reports
{
    public function __construct(public AccountingSetting $settings)
    {
    }

    public static function for(string $agencyId): self
    {
        return new self(AccountingSetting::forAgency($agencyId));
    }

    /**
     * Net (debit − credit) per account.
     *
     * @return Collection<string, float>  account id => net
     */
    public function nets(?string $from, string $to, bool $excludeYearEnd = false): Collection
    {
        $q = DB::table('acc_journal_lines as l')
            ->where('l.agency_id', $this->settings->agency_id)
            ->where('l.date', '<=', $to);
        if ($from) {
            $q->where('l.date', '>=', $from);
        }
        if ($excludeYearEnd) {
            $q->join('acc_journals as j', 'j.id', '=', 'l.journal_id')->where('j.type', '!=', 'year_end');
        }

        return $q->groupBy('l.account_id')->selectRaw('l.account_id, sum(l.debit) - sum(l.credit) as net')
            ->pluck('net', 'account_id')->map(fn ($v) => round((float) $v, 2));
    }

    private function accounts(): Collection
    {
        return Account::where('agency_id', $this->settings->agency_id)->orderBy('code')->get()->keyBy('id');
    }

    // -- Trial balance -----------------------------------------------------------

    /** @return array{rows: array, debit: float, credit: float} */
    public function trialBalance(string $asAt): array
    {
        $nets = $this->nets(null, $asAt);
        $rows = [];
        $dr = 0;
        $cr = 0;
        foreach ($this->accounts() as $a) {
            $net = $nets[$a->id] ?? 0;
            if (abs($net) < 0.005) {
                continue;
            }
            $rows[] = ['account' => $a, 'debit' => $net > 0 ? $net : 0, 'credit' => $net < 0 ? -$net : 0];
            $net > 0 ? $dr += $net : $cr -= $net;
        }

        return ['rows' => $rows, 'debit' => round($dr, 2), 'credit' => round($cr, 2)];
    }

    // -- General ledger ----------------------------------------------------------

    /** @return array{account: Account, opening: float, lines: Collection, closing: float} */
    public function ledger(Account $account, string $from, string $to, ?string $contactId = null): array
    {
        $base = JournalLine::where('agency_id', $this->settings->agency_id)->where('account_id', $account->id)
            ->when($contactId, fn ($q) => $q->where('contact_id', $contactId));
        $opening = round((float) (clone $base)->where('date', '<', $from)->sum(DB::raw('debit - credit')), 2);
        $lines = (clone $base)->whereBetween('date', [$from, $to])
            ->with('journal:id,number,type,reference,memo,source_type,source_id,status', 'contact:id,name')
            ->orderBy('date')->orderBy('created_at')->orderBy('sort_order')->get();
        $running = $opening;
        foreach ($lines as $l) {
            $running = round($running + (float) $l->debit - (float) $l->credit, 2);
            $l->running = $running;
        }

        return ['account' => $account, 'opening' => $opening, 'lines' => $lines, 'closing' => $running];
    }

    // -- Income statement --------------------------------------------------------

    /**
     * Grouped like the audited accounts: revenue, cost of sales, gross
     * result, other income, then expenditure by heading, and the result.
     * $compare = [from, to] for a comparative column.
     */
    public function incomeStatement(string $from, string $to, ?array $compare = null): array
    {
        $cur = $this->nets($from, $to, true);
        $prev = $compare ? $this->nets($compare[0], $compare[1], true) : collect();
        $accounts = $this->accounts()->filter(fn ($a) => in_array($a->type, ['income', 'expense'], true));

        $section = function (array $subtypes, bool $credit) use ($accounts, $cur, $prev) {
            $rows = [];
            $t = [0, 0];
            foreach ($accounts->filter(fn ($a) => in_array($a->subtype, $subtypes, true)) as $a) {
                $c = ($cur[$a->id] ?? 0) * ($credit ? -1 : 1);
                $p = ($prev[$a->id] ?? 0) * ($credit ? -1 : 1);
                if (abs($c) < 0.005 && abs($p) < 0.005) {
                    continue;
                }
                $rows[] = ['account' => $a, 'current' => round($c, 2), 'previous' => round($p, 2)];
                $t[0] += $c;
                $t[1] += $p;
            }

            return ['rows' => $rows, 'current' => round($t[0], 2), 'previous' => round($t[1], 2)];
        };

        $revenue = $section(['revenue'], true);
        $cos = $section(['cost_of_sales'], false);
        $other = $section(['other_income'], true);
        $expenseHeadings = ['staff_costs', 'admin_expenses', 'governance', 'depreciation', 'finance_costs', 'other_expenses'];
        $expenses = [];
        foreach ($expenseHeadings as $h) {
            $s = $section([$h], false);
            if ($s['rows']) {
                $expenses[$h] = $s + ['label' => __(Account::SUBTYPES[$h][1])];
            }
        }
        $tax = $section(['tax'], false);
        $totalExp = ['current' => round(collect($expenses)->sum('current'), 2), 'previous' => round(collect($expenses)->sum('previous'), 2)];
        $gross = ['current' => round($revenue['current'] - $cos['current'], 2), 'previous' => round($revenue['previous'] - $cos['previous'], 2)];
        $before = ['current' => round($gross['current'] + $other['current'] - $totalExp['current'], 2), 'previous' => round($gross['previous'] + $other['previous'] - $totalExp['previous'], 2)];
        $result = ['current' => round($before['current'] - $tax['current'], 2), 'previous' => round($before['previous'] - $tax['previous'], 2)];

        return compact('revenue', 'cos', 'gross', 'other', 'expenses', 'totalExp', 'before', 'tax', 'result') + [
            'from' => $from, 'to' => $to, 'compare' => $compare,
        ];
    }

    // -- Balance sheet -----------------------------------------------------------

    public function balanceSheet(string $asAt, ?string $compareAsAt = null): array
    {
        $build = function (string $date) {
            $nets = $this->nets(null, $date);
            $year = $this->settings->yearFor($date);
            $yearResult = $this->nets($year->starts_on->toDateString(), $date, true);

            return [$nets, $yearResult];
        };
        [$cur, $curYr] = $build($asAt);
        [$prev, $prevYr] = $compareAsAt ? $build($compareAsAt) : [collect(), collect()];
        $accounts = $this->accounts();

        $group = function (array $subtypes, bool $credit) use ($accounts, $cur, $prev) {
            $rows = [];
            $t = [0, 0];
            foreach ($accounts->filter(fn ($a) => in_array($a->subtype, $subtypes, true)) as $a) {
                $c = ($cur[$a->id] ?? 0) * ($credit ? -1 : 1);
                $p = ($prev[$a->id] ?? 0) * ($credit ? -1 : 1);
                if (abs($c) < 0.005 && abs($p) < 0.005) {
                    continue;
                }
                $rows[] = ['account' => $a, 'current' => round($c, 2), 'previous' => round($p, 2)];
                $t[0] += $c;
                $t[1] += $p;
            }

            return ['rows' => $rows, 'current' => round($t[0], 2), 'previous' => round($t[1], 2)];
        };

        $nonCurrent = $group(['non_current_asset'], false);
        $current = $group(['inventory', 'receivable', 'current_asset', 'bank', 'cash', 'mobile_money'], false);
        $currentLiab = $group(['payable', 'vat', 'payroll_liability', 'current_liability'], true);
        $longLiab = $group(['non_current_liability'], true);
        $equity = $group(['share_capital', 'reserves', 'equity', 'retained_earnings'], true);

        // Income and expense not yet closed off: this year's result, and
        // anything older rolled into retained earnings.
        $ie = fn ($nets) => -round($accounts->filter(fn ($a) => in_array($a->type, ['income', 'expense'], true))
            ->sum(fn ($a) => $nets[$a->id] ?? 0), 2);
        $yearC = $ie($curYr);
        $yearP = $compareAsAt ? $ie($prevYr) : 0;
        $priorC = round($ie($cur) - $yearC, 2);
        $priorP = $compareAsAt ? round($ie($prev) - $yearP, 2) : 0;
        if (abs($priorC) >= 0.005 || abs($priorP) >= 0.005) {
            $equity['rows'][] = ['account' => null, 'label' => $this->settings->term('retained').' '.__('brought forward'), 'current' => $priorC, 'previous' => $priorP];
        }
        $equity['rows'][] = ['account' => null, 'label' => $this->settings->term('result'), 'current' => $yearC, 'previous' => $yearP];
        $equity['current'] = round($equity['current'] + $priorC + $yearC, 2);
        $equity['previous'] = round($equity['previous'] + $priorP + $yearP, 2);

        $assets = ['current' => round($nonCurrent['current'] + $current['current'], 2), 'previous' => round($nonCurrent['previous'] + $current['previous'], 2)];
        $liabilities = ['current' => round($currentLiab['current'] + $longLiab['current'], 2), 'previous' => round($currentLiab['previous'] + $longLiab['previous'], 2)];
        $check = round($assets['current'] - $liabilities['current'] - $equity['current'], 2);

        return compact('nonCurrent', 'current', 'currentLiab', 'longLiab', 'equity', 'assets', 'liabilities', 'check') + [
            'asAt' => $asAt, 'compareAsAt' => $compareAsAt,
            'netAssets' => ['current' => round($assets['current'] - $liabilities['current'], 2), 'previous' => round($assets['previous'] - $liabilities['previous'], 2)],
        ];
    }

    // -- Aged receivables / payables --------------------------------------------

    public const AGE_BUCKETS = ['current' => 'Not yet due', '1_30' => '1–30 days', '31_60' => '31–60 days', '61_90' => '61–90 days', '90_plus' => 'Over 90 days'];

    /** Open invoices (or bills) by how overdue they are, per contact, as at a date. */
    public function aged(string $kind, string $asAt): array
    {
        $asAtDate = Carbon::parse($asAt);
        $docs = Document::where('agency_id', $this->settings->agency_id)->where('kind', $kind)
            ->whereNotIn('status', ['draft', 'void'])->whereDate('issue_date', '<=', $asAt)
            ->with('contact:id,name')->get();
        $paidBy = DB::table('acc_payment_allocations as a')->join('acc_payments as p', 'p.id', '=', 'a.payment_id')
            ->whereIn('a.document_id', $docs->pluck('id'))->where('p.date', '<=', $asAt)->where('p.status', 'posted')
            ->groupBy('a.document_id')->selectRaw('a.document_id, sum(a.amount) as paid')->pluck('paid', 'document_id');

        $contacts = [];
        $totals = array_fill_keys(array_keys(self::AGE_BUCKETS), 0) + ['total' => 0];
        foreach ($docs as $d) {
            $open = round((float) $d->total - (float) ($paidBy[$d->id] ?? 0), 2);
            if ($open <= 0.004) {
                continue;
            }
            $due = $d->due_date ?? $d->issue_date;
            $days = $due->isBefore($asAtDate) ? (int) $due->diffInDays($asAtDate) : 0;
            $bucket = match (true) {
                $days <= 0 => 'current', $days <= 30 => '1_30', $days <= 60 => '31_60', $days <= 90 => '61_90', default => '90_plus',
            };
            $cid = $d->contact_id ?? 'none';
            $contacts[$cid] ??= ['contact' => $d->contact, 'docs' => [], 'total' => 0] + array_fill_keys(array_keys(self::AGE_BUCKETS), 0);
            $contacts[$cid][$bucket] += $open;
            $contacts[$cid]['total'] += $open;
            $contacts[$cid]['docs'][] = ['doc' => $d, 'open' => $open, 'days' => $days, 'bucket' => $bucket];
            $totals[$bucket] += $open;
            $totals['total'] += $open;
        }
        // Unmatched money on account reduces what's owed.
        foreach (Payment::where('agency_id', $this->settings->agency_id)->where('kind', $kind === 'invoice' ? 'receipt' : 'payment')
            ->where('status', 'posted')->whereDate('date', '<=', $asAt)->with('contact:id,name')->get() as $p) {
            $free = round((float) $p->amount - (float) $p->allocations()->sum('amount'), 2);
            if ($free > 0.004) {
                $cid = $p->contact_id;
                $contacts[$cid] ??= ['contact' => $p->contact, 'docs' => [], 'total' => 0] + array_fill_keys(array_keys(self::AGE_BUCKETS), 0);
                $contacts[$cid]['credit'] = ($contacts[$cid]['credit'] ?? 0) + $free;
                $contacts[$cid]['total'] -= $free;
                $totals['credit'] = ($totals['credit'] ?? 0) + $free;
                $totals['total'] -= $free;
            }
        }
        uasort($contacts, fn ($a, $b) => $b['total'] <=> $a['total']);

        return ['contacts' => $contacts, 'totals' => $totals, 'asAt' => $asAt, 'kind' => $kind];
    }

    // -- Contact statement -------------------------------------------------------

    public function statement(Contact $contact, string $from, string $to): array
    {
        $control = $this->settings->account($contact->is_customer || ! $contact->is_supplier ? 'receivables' : 'payables');
        $ledger = $this->ledger($control, $from, $to, $contact->id);
        $sign = $control->system_key === 'payables' || $control->id === $this->settings->account('payables')->id ? -1 : 1;

        return $ledger + ['sign' => $sign, 'contact' => $contact];
    }

    /** What a contact owes you (+) or you owe them (−), right now. */
    public function contactBalances(array $contactIds = []): Collection
    {
        $rec = $this->settings->account('receivables')->id;
        $pay = $this->settings->account('payables')->id;

        return DB::table('acc_journal_lines')->where('agency_id', $this->settings->agency_id)
            ->whereIn('account_id', [$rec, $pay])->whereNotNull('contact_id')
            ->when($contactIds, fn ($q) => $q->whereIn('contact_id', $contactIds))
            ->groupBy('contact_id')->selectRaw('contact_id, sum(debit) - sum(credit) as bal')
            ->pluck('bal', 'contact_id')->map(fn ($v) => round((float) $v, 2));
    }

    /** Balance of each bank / cash account. */
    public function moneyBalances(?string $asAt = null): Collection
    {
        $nets = $this->nets(null, $asAt ?? today()->toDateString());

        return Account::where('agency_id', $this->settings->agency_id)->money()->orderBy('code')->get()
            ->map(fn ($a) => ['account' => $a, 'balance' => $nets[$a->id] ?? 0]);
    }
}
