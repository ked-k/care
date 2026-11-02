<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountingSetting;
use App\Models\Accounting\FinancialYear;
use App\Models\Accounting\FixedAsset;
use App\Models\Accounting\ReportNote;
use Illuminate\Support\Collection;

/**
 * Batch 44b: the notes to the accounts. Written notes start from a
 * template (worded for a company, co-operative or charity, with the
 * depreciation rates actually in use) and are then edited freely; the
 * figure notes are built from the ledger each time.
 */
class Notes
{
    /** The year's written notes, creating the starting set the first time. */
    public static function forYear(FinancialYear $year): Collection
    {
        $notes = ReportNote::where('financial_year_id', $year->id)->orderBy('sort_order')->get();
        if ($notes->isNotEmpty()) {
            return $notes;
        }
        // Carry last year's wording forward if there is one.
        $prev = $year->previous();
        $source = $prev ? ReportNote::where('financial_year_id', $prev->id)->orderBy('sort_order')->get() : collect();
        $rows = $source->isNotEmpty()
            ? $source->map(fn ($n) => ['title' => $n->title, 'body' => $n->body])->all()
            : self::template($year);
        foreach ($rows as $i => $r) {
            ReportNote::create($r + ['agency_id' => $year->agency_id, 'financial_year_id' => $year->id, 'sort_order' => $i + 1]);
        }

        return ReportNote::where('financial_year_id', $year->id)->orderBy('sort_order')->get();
    }

    public static function template(FinancialYear $year): array
    {
        $s = AccountingSetting::forAgency($year->agency_id);
        $name = $s->legal_name ?: __('The organisation');
        $coop = $s->organisation_type === 'cooperative';
        $charity = $s->organisation_type === 'charity';
        $framework = $coop
            ? __('the Co-operative Societies Act and International Financial Reporting Standards for Small and Medium-sized Entities (IFRS for SMEs)')
            : ($charity ? __('the applicable charities regulations and the Financial Reporting Standard applicable in the UK (FRS 102)') : __('the Financial Reporting Standard applicable in the UK and Republic of Ireland (FRS 102), Section 1A small entities'));

        $rates = FixedAsset::where('agency_id', $year->agency_id)->get()
            ->groupBy('category')->map(fn ($g) => $g->first()->categoryLabel().' — '.$g->first()->methodLabel())->values()->all();
        $rateText = $rates ? implode("\n", array_map(fn ($r) => '• '.$r, $rates))
            : implode("\n", ['• '.__('Computers and IT equipment — 25% a year, straight line'), '• '.__('Motor vehicles — 25% a year, reducing balance'), '• '.__('Furniture and fittings — 12.5% a year, reducing balance')]);

        return [
            ['title' => __('General information'), 'body' => __(':name is registered:reg. Its principal activity is the provision of :act. The address of its registered office is :addr.', [
                'name' => $name,
                'reg' => $s->registration_number ? ' '.__('under number :n', ['n' => $s->registration_number]) : '',
                'act' => $coop ? __('services to its members') : __('home care and support services'),
                'addr' => $s->address ? str_replace("\n", ', ', $s->address) : __('[address]'),
            ])],
            ['title' => __('Basis of preparation'), 'body' => __('These financial statements have been prepared in accordance with :fw, under the historical cost convention. They are presented in :cur (:sym), rounded to the nearest :unit.', [
                'fw' => $framework, 'cur' => $s->currency_code, 'sym' => $s->currency_symbol, 'unit' => (int) $s->currency_decimals === 0 ? __('unit') : __('penny / cent'),
            ])."\n\n".__('The accounts have been prepared on a going concern basis.')],
            ['title' => __('Revenue'), 'body' => __('Revenue is recognised when the service is delivered or the goods are supplied, at the fair value of the amount receivable, excluding VAT. Care fees are recognised in the period the care is provided.')],
            ['title' => __('Property, plant and equipment'), 'body' => __('Property, plant and equipment are stated at cost less accumulated depreciation. Depreciation is charged so as to write off the cost of assets, less their residual value, over their useful lives, at the following rates:')."\n".$rateText],
            ['title' => __('Inventory'), 'body' => __('Inventory is stated at the lower of cost and net realisable value. Cost is the actual purchase cost of each batch; stock is issued earliest-expiry-first, otherwise first-in, first-out.')],
            ['title' => __('Receivables and payables'), 'body' => __('Trade receivables and payables are measured at the amount expected to be received or paid. A provision is made where there is evidence that a receivable will not be recovered in full.')],
            ['title' => __('Taxation'), 'body' => $coop
                ? __('Income tax is provided on taxable surpluses at the applicable rate, after allowable deductions for members\' transactions.')
                : __('Tax is recognised in the income statement, except where it relates to items recognised directly in equity.')],
            ['title' => $coop ? __('Reserves and appropriations') : __('Reserves'), 'body' => $coop
                ? __('In accordance with the society\'s by-laws, the surplus for the year is appropriated to the statutory reserve and other funds at the rates approved by the general meeting. The balance is carried forward as accumulated surplus.')
                : __('Retained earnings represent accumulated profits less distributions.')],
            ['title' => __('Events after the reporting date'), 'body' => __('There have been no significant events after the end of the year that require adjustment to, or disclosure in, these financial statements.')],
        ];
    }

    /** Figure notes generated from the ledger for the audit pack. */
    public static function figures(FinancialYear $year): array
    {
        $s = AccountingSetting::forAgency($year->agency_id);
        $r = new Reports($s);
        $to = $year->ends_on->toDateString();
        $prevTo = $year->ends_on->copy()->subYear()->toDateString();
        $bs = $r->balanceSheet($to, $prevTo);
        $is = $r->incomeStatement($year->starts_on->toDateString(), $to, [$year->starts_on->copy()->subYear()->toDateString(), $prevTo]);
        $pick = fn ($group) => $group['rows'];

        return [
            'revenue' => ['title' => $s->term('income'), 'rows' => array_merge($pick($is['revenue']), $pick($is['other'])),
                'total' => ['current' => $is['revenue']['current'] + $is['other']['current'], 'previous' => $is['revenue']['previous'] + $is['other']['previous']]],
            'ppe' => ['title' => __('Property, plant and equipment'), 'schedule' => FixedAssets::schedule($year->agency_id, $year->starts_on->toDateString(), $to)],
            'receivables' => ['title' => __('Trade and other receivables'), 'rows' => array_values(array_filter($bs['current']['rows'], fn ($x) => in_array($x['account']?->subtype, ['receivable', 'current_asset'], true)))],
            'cash' => ['title' => __('Cash and cash equivalents'), 'rows' => array_values(array_filter($bs['current']['rows'], fn ($x) => $x['account']?->isMoney()))],
            'payables' => ['title' => __('Trade and other payables'), 'rows' => $bs['currentLiab']['rows']],
            'equity' => ['title' => __('Share capital and reserves'), 'rows' => $bs['equity']['rows']],
        ];
    }
}
