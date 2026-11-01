<?php

namespace App\Services\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\AccountingSetting;
use App\Models\Accounting\FinancialYear;
use Illuminate\Support\Carbon;

/**
 * Batch 43: turns a report name + filters into the data, the title and a
 * CSV table — shared by the on-screen report, the print page and the CSV
 * download so all three always show the same figures.
 */
class ReportBuilder
{
    public const REPORTS = [
        'income-statement' => ['income_statement', 'What came in and went out over a period, with last year alongside', 'ik ik-trending-up'],
        'balance-sheet' => ['balance_sheet', 'What the organisation owns and owes on a date, with last year alongside', 'ik ik-layers'],
        'trial-balance' => ['Trial balance', 'Every account’s balance on a date — debits must equal credits', 'ik ik-check-square'],
        'ledger' => ['General ledger', 'Every entry on one account, with a running balance', 'ik ik-book-open'],
        'aged-receivables' => ['Aged debtors', 'Who owes you, and how overdue it is', 'ik ik-download'],
        'aged-payables' => ['Aged creditors', 'Who you owe, and how overdue it is', 'ik ik-upload'],
    ];

    public function __construct(public AccountingSetting $settings)
    {
    }

    public static function title(AccountingSetting $s, string $report): string
    {
        $t = self::REPORTS[$report][0] ?? $report;

        return in_array($t, ['income_statement', 'balance_sheet'], true) ? $s->term($t) : __($t);
    }

    /** Fill in sensible defaults for whatever filters weren't given. */
    public function params(string $report, array $in): array
    {
        $s = $this->settings;
        $year = ! empty($in['year']) ? FinancialYear::where('agency_id', $s->agency_id)->find($in['year']) : null;
        $year ??= $s->yearFor(today());
        $yearEnd = $year->ends_on->min(today());
        $p = [
            'year' => $year->id,
            'from' => $in['from'] ?? null ?: $year->starts_on->toDateString(),
            'to' => $in['to'] ?? null ?: $yearEnd->toDateString(),
            'as_at' => $in['as_at'] ?? null ?: $yearEnd->toDateString(),
            'compare' => array_key_exists('compare', $in) ? filter_var($in['compare'], FILTER_VALIDATE_BOOLEAN) : true,
            'account' => $in['account'] ?? null ?: $s->account('bank')->id,
        ];
        if (! empty($in['year']) && empty($in['from'])) {
            $p['from'] = $year->starts_on->toDateString();
            $p['to'] = $yearEnd->toDateString();
            $p['as_at'] = $yearEnd->toDateString();
        }

        return $p;
    }

    /** @return array{title: string, subtitle: string, view: string, data: array, csv: array} */
    public function build(string $report, array $in): array
    {
        abort_unless(isset(self::REPORTS[$report]), 404);
        $p = $this->params($report, $in);
        $r = new Reports($this->settings);
        $s = $this->settings;
        $d = fn ($v) => Carbon::parse($v)->format('j F Y');

        switch ($report) {
            case 'income-statement':
                $from = Carbon::parse($p['from']);
                $to = Carbon::parse($p['to']);
                $cmp = $p['compare'] ? [$from->copy()->subYear()->toDateString(), $to->copy()->subYear()->toDateString()] : null;
                $data = $r->incomeStatement($p['from'], $p['to'], $cmp);
                $subtitle = __('For the period :a to :b', ['a' => $d($p['from']), 'b' => $d($p['to'])]);
                $csv = $this->isCsv($data);
                break;
            case 'balance-sheet':
                $cmp = $p['compare'] ? Carbon::parse($p['as_at'])->subYear()->toDateString() : null;
                $data = $r->balanceSheet($p['as_at'], $cmp);
                $subtitle = __('As at :d', ['d' => $d($p['as_at'])]);
                $csv = $this->bsCsv($data);
                break;
            case 'trial-balance':
                $data = $r->trialBalance($p['as_at']);
                $subtitle = __('As at :d', ['d' => $d($p['as_at'])]);
                $csv = [[__('Code'), __('Account'), __('Debit'), __('Credit')]];
                foreach ($data['rows'] as $row) {
                    $csv[] = [$row['account']->code, $row['account']->name, $row['debit'] ?: '', $row['credit'] ?: ''];
                }
                $csv[] = ['', __('Total'), $data['debit'], $data['credit']];
                break;
            case 'ledger':
                $account = Account::where('agency_id', $s->agency_id)->findOrFail($p['account']);
                $data = $r->ledger($account, $p['from'], $p['to']);
                $subtitle = $account->label().' · '.$d($p['from']).' – '.$d($p['to']);
                $csv = [[__('Date'), __('Journal'), __('Type'), __('Details'), __('Contact'), __('Debit'), __('Credit'), __('Balance')]];
                $csv[] = [$p['from'], '', '', __('Opening balance'), '', '', '', $data['opening']];
                foreach ($data['lines'] as $l) {
                    $csv[] = [$l->date->toDateString(), $l->journal->number, $l->journal->typeLabel(), $l->description ?: $l->journal->memo,
                        $l->contact?->name, (float) $l->debit ?: '', (float) $l->credit ?: '', $l->running];
                }
                break;
            default: // aged
                $kind = $report === 'aged-receivables' ? 'invoice' : 'bill';
                $data = $r->aged($kind, $p['as_at']);
                $subtitle = __('As at :d', ['d' => $d($p['as_at'])]);
                $csv = [array_merge([__('Contact')], array_map('__', array_values(Reports::AGE_BUCKETS)), [__('Credit'), __('Total')])];
                foreach ($data['contacts'] as $c) {
                    $csv[] = array_merge([$c['contact']?->name ?? '—'], array_map(fn ($b) => round($c[$b], 2), array_keys(Reports::AGE_BUCKETS)), [round($c['credit'] ?? 0, 2), round($c['total'], 2)]);
                }
        }

        return [
            'report' => $report,
            'title' => self::title($s, $report),
            'subtitle' => $subtitle,
            'view' => 'accounting.reports.'.$report,
            'data' => $data,
            'params' => $p,
            'csv' => $csv,
        ];
    }

    private function isCsv(array $is): array
    {
        $s = $this->settings;
        $rows = [[__('Line'), __('Code'), __('This period'), __('Comparative')]];
        $sec = function (string $label, array $section) use (&$rows) {
            $rows[] = [$label, '', '', ''];
            foreach ($section['rows'] as $r) {
                $rows[] = ['  '.$r['account']->name, $r['account']->code, $r['current'], $r['previous']];
            }
            $rows[] = [__('Total').' '.mb_strtolower($label), '', $section['current'], $section['previous']];
        };
        $sec($s->term('income'), $is['revenue']);
        $sec(__('Cost of sales'), $is['cos']);
        $rows[] = [$s->term('gross'), '', $is['gross']['current'], $is['gross']['previous']];
        $sec(__('Other income'), $is['other']);
        foreach ($is['expenses'] as $e) {
            $sec($e['label'], $e);
        }
        $rows[] = [__('Total').' '.mb_strtolower($s->term('expenses')), '', $is['totalExp']['current'], $is['totalExp']['previous']];
        if ($is['tax']['rows']) {
            $sec(__('Taxation'), $is['tax']);
        }
        $rows[] = [$s->term('result'), '', $is['result']['current'], $is['result']['previous']];

        return $rows;
    }

    private function bsCsv(array $bs): array
    {
        $rows = [[__('Line'), __('Code'), __('This year'), __('Comparative')]];
        foreach ([__('Non-current assets') => 'nonCurrent', __('Current assets') => 'current', __('Current liabilities') => 'currentLiab',
            __('Non-current liabilities') => 'longLiab', __('Equity') => 'equity'] as $label => $k) {
            $rows[] = [$label, '', '', ''];
            foreach ($bs[$k]['rows'] as $r) {
                $rows[] = ['  '.($r['account']?->name ?? $r['label']), $r['account']?->code, $r['current'], $r['previous']];
            }
            $rows[] = [__('Total').' '.mb_strtolower($label), '', $bs[$k]['current'], $bs[$k]['previous']];
        }
        $rows[] = [__('Net assets'), '', $bs['netAssets']['current'], $bs['netAssets']['previous']];

        return $rows;
    }
}
