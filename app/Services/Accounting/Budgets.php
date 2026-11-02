<?php

namespace App\Services\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\AccountingSetting;
use App\Models\Accounting\Budget;
use App\Models\Accounting\BudgetLine;
use App\Models\Accounting\FinancialYear;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Batch 44c: budgets — a figure per income / expense account per month of
 * a financial year — and the budget-vs-actual comparison. Budget figures
 * are always positive amounts (income expected, or spending expected);
 * actuals come straight from the ledger, leaving out opening balances and
 * the year-end close.
 */
class Budgets
{
    public function __construct(public AccountingSetting $settings)
    {
    }

    public static function for(string $agencyId): self
    {
        return new self(AccountingSetting::forAgency($agencyId));
    }

    /** @return Carbon[] the first day of each of the year's 12 months */
    public static function months(FinancialYear $year): array
    {
        $out = [];
        for ($i = 0; $i < 12; $i++) {
            $out[] = $year->starts_on->copy()->addMonthsNoOverflow($i)->startOfMonth();
        }

        return $out;
    }

    /** Income and expense accounts, in the order budgets show them. */
    public function accounts(): Collection
    {
        return Account::where('agency_id', $this->settings->agency_id)->whereIn('type', ['income', 'expense'])
            ->orderByRaw("case when type = 'income' then 0 else 1 end")->orderBy('code')->get();
    }

    /**
     * Actual figure per account per month of a year (income as credit −
     * debit, expenses as debit − credit), so they compare with the budget.
     *
     * @return array<string, float[]> account id => 12 months
     */
    public function actualsByMonth(FinancialYear $year): array
    {
        $rows = DB::table('acc_journal_lines as l')->join('acc_journals as j', 'j.id', '=', 'l.journal_id')
            ->join('acc_accounts as a', 'a.id', '=', 'l.account_id')
            ->where('l.agency_id', $this->settings->agency_id)->whereIn('a.type', ['income', 'expense'])
            ->whereNotIn('j.type', ['opening', 'year_end'])
            ->whereBetween('l.date', [$year->starts_on->toDateString(), $year->ends_on->toDateString()])
            ->get(['l.account_id', 'l.date', 'l.debit', 'l.credit', 'a.type']);
        $months = array_map(fn (Carbon $m) => $m->format('Y-m'), self::months($year));
        $idx = array_flip($months);
        $out = [];
        foreach ($rows as $r) {
            $i = $idx[substr((string) $r->date, 0, 7)] ?? null;
            if ($i === null) {
                continue;
            }
            $out[$r->account_id] ??= array_fill(0, 12, 0.0);
            $out[$r->account_id][$i] += $r->type === 'income' ? (float) $r->credit - (float) $r->debit : (float) $r->debit - (float) $r->credit;
        }

        return array_map(fn ($m) => array_map(fn ($v) => round($v, 2), $m), $out);
    }

    // -- Creating and editing ---------------------------------------------------------

    /**
     * @param  string  $start  blank | actuals (last year's figures) | budget (copy another budget)
     */
    public function create(FinancialYear $year, string $name, string $start = 'blank', ?string $sourceId = null, float $uplift = 0, ?int $userId = null): Budget
    {
        return DB::transaction(function () use ($year, $name, $start, $sourceId, $uplift, $userId) {
            $first = ! Budget::where('agency_id', $this->settings->agency_id)->where('financial_year_id', $year->id)->exists();
            $budget = Budget::create([
                'agency_id' => $this->settings->agency_id, 'financial_year_id' => $year->id,
                'name' => $name, 'is_primary' => $first, 'created_by' => $userId,
            ]);
            $factor = 1 + $uplift / 100;
            $grid = [];
            if ($start === 'actuals') {
                $prev = $year->previous() ?? throw new LedgerException(__('There is no earlier year to copy figures from.'));
                $grid = $this->actualsByMonth($prev);
            } elseif ($start === 'budget' && $sourceId) {
                $src = Budget::where('agency_id', $this->settings->agency_id)->with('lines')->findOrFail($sourceId);
                $grid = $src->lines->mapWithKeys(fn ($l) => [$l->account_id => $l->months])->all();
            }
            if ($grid) {
                $grid = array_map(fn ($m) => array_map(fn ($v) => round(max(0, (float) $v) * $factor, 2), $m), $grid);
                $this->save($budget, $grid);
            }

            return $budget;
        });
    }

    /** @param  array<string, array<int, mixed>>  $grid  account id => 12 figures */
    public function save(Budget $budget, array $grid): void
    {
        $valid = Account::where('agency_id', $this->settings->agency_id)->whereIn('type', ['income', 'expense'])->pluck('id')->flip();
        DB::transaction(function () use ($budget, $grid, $valid) {
            foreach ($grid as $accountId => $months) {
                if (! isset($valid[$accountId])) {
                    continue;
                }
                $months = array_map(fn ($v) => round((float) (is_numeric($v) ? $v : 0), 2), array_pad(array_slice(array_values((array) $months), 0, 12), 12, 0));
                $total = round(array_sum($months), 2);
                if (abs($total) < 0.005 && ! array_filter($months)) {
                    BudgetLine::where('budget_id', $budget->id)->where('account_id', $accountId)->delete();

                    continue;
                }
                BudgetLine::updateOrCreate(['budget_id' => $budget->id, 'account_id' => $accountId], ['months' => $months, 'total' => $total]);
            }
            $budget->touch();
        });
    }

    public function makePrimary(Budget $budget): void
    {
        DB::transaction(function () use ($budget) {
            Budget::where('agency_id', $budget->agency_id)->where('financial_year_id', $budget->financial_year_id)->update(['is_primary' => false]);
            $budget->update(['is_primary' => true]);
        });
    }

    /** The budget reports use for a year: the primary one, else the newest. */
    public function defaultFor(FinancialYear $year): ?Budget
    {
        return Budget::where('agency_id', $this->settings->agency_id)->where('financial_year_id', $year->id)
            ->orderByDesc('is_primary')->orderByDesc('created_at')->first();
    }

    // -- Budget vs actual ------------------------------------------------------------------

    /**
     * Compare a budget with the ledger for whole months $fromMonth..$toMonth
     * (0 = first month of the year). Variance is shown so that positive is
     * good: income above budget, or spending below it.
     */
    public function vsActual(Budget $budget, int $fromMonth = 0, int $toMonth = 11): array
    {
        $year = $budget->year;
        $fromMonth = max(0, min(11, $fromMonth));
        $toMonth = max($fromMonth, min(11, $toMonth));
        $lines = $budget->lines()->get()->keyBy('account_id');
        $actuals = $this->actualsByMonth($year);
        $range = range($fromMonth, $toMonth);

        $sections = ['income' => [], 'expense' => []];
        foreach ($this->accounts() as $a) {
            $b = $lines[$a->id]->months ?? null;
            $act = $actuals[$a->id] ?? null;
            if (! $b && ! $act) {
                continue;
            }
            $budgetP = round(collect($range)->sum(fn ($i) => (float) ($b[$i] ?? 0)), 2);
            $actualP = round(collect($range)->sum(fn ($i) => (float) ($act[$i] ?? 0)), 2);
            if (abs($budgetP) < 0.005 && abs($actualP) < 0.005 && ! (float) ($lines[$a->id]->total ?? 0)) {
                continue;
            }
            $variance = $a->type === 'income' ? $actualP - $budgetP : $budgetP - $actualP;
            $sections[$a->type][] = [
                'account' => $a,
                'budget' => $budgetP,
                'actual' => $actualP,
                'variance' => round($variance, 2),
                'pct' => abs($budgetP) >= 0.005 ? round($variance / abs($budgetP) * 100, 1) : null,
                'year' => round((float) ($lines[$a->id]->total ?? 0), 2),
                'months' => ['budget' => $b ?? array_fill(0, 12, 0), 'actual' => $act ?? array_fill(0, 12, 0)],
            ];
        }
        $sum = fn (string $k, array $rows) => round(collect($rows)->sum($k), 2);
        $tot = [];
        foreach (['income', 'expense'] as $t) {
            $tot[$t] = ['budget' => $sum('budget', $sections[$t]), 'actual' => $sum('actual', $sections[$t]), 'year' => $sum('year', $sections[$t])];
            $tot[$t]['variance'] = round($t === 'income' ? $tot[$t]['actual'] - $tot[$t]['budget'] : $tot[$t]['budget'] - $tot[$t]['actual'], 2);
        }
        $result = [
            'budget' => round($tot['income']['budget'] - $tot['expense']['budget'], 2),
            'actual' => round($tot['income']['actual'] - $tot['expense']['actual'], 2),
            'year' => round($tot['income']['year'] - $tot['expense']['year'], 2),
        ];
        $result['variance'] = round($result['actual'] - $result['budget'], 2);

        // Month-by-month result line, for the chart.
        $monthly = [];
        foreach (range(0, 11) as $i) {
            $bm = 0;
            $am = 0;
            foreach (['income' => 1, 'expense' => -1] as $t => $sign) {
                foreach ($sections[$t] as $r) {
                    $bm += $sign * (float) ($r['months']['budget'][$i] ?? 0);
                    $am += $sign * (float) ($r['months']['actual'][$i] ?? 0);
                }
            }
            $monthly[] = ['budget' => round($bm, 2), 'actual' => round($am, 2)];
        }

        return [
            'budget' => $budget, 'year' => $year, 'sections' => $sections, 'totals' => $tot, 'result' => $result,
            'fromMonth' => $fromMonth, 'toMonth' => $toMonth, 'months' => self::months($year), 'monthly' => $monthly,
        ];
    }
}
