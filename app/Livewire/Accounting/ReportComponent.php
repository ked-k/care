<?php

namespace App\Livewire\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\FinancialYear;
use App\Services\Accounting\ReportBuilder;
use Livewire\Attributes\Url;

/** Batch 43: one report on screen, with its filters, print and CSV. */
class ReportComponent extends AccountsComponent
{
    public string $report;

    #[Url]
    public string $year = '';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    #[Url(as: 'as_at')]
    public string $asAt = '';

    #[Url]
    public string $account = '';

    #[Url]
    public bool $compare = true;

    public function mount(string $report): void
    {
        abort_unless(isset(ReportBuilder::REPORTS[$report]), 404);
        $this->report = $report;
    }

    public function updatedYear(): void
    {
        // Picking a year resets the dates to that year.
        $this->from = $this->to = $this->asAt = '';
    }

    private function input(): array
    {
        return array_filter([
            'year' => $this->year, 'from' => $this->from, 'to' => $this->to, 'as_at' => $this->asAt, 'account' => $this->account,
        ]) + ['compare' => $this->compare];
    }

    public function render()
    {
        $builder = new ReportBuilder($this->settings());
        $rep = $builder->build($this->report, $this->input());
        // Show the dates actually used (defaults included) in the filter boxes.
        $this->from = $rep['params']['from'];
        $this->to = $rep['params']['to'];
        $this->asAt = $rep['params']['as_at'];
        if ($this->report === 'ledger') {
            $this->account = $rep['params']['account'];
        }

        return $this->page('livewire.accounting.report', [
            'rep' => $rep,
            'years' => FinancialYear::where('agency_id', $this->agencyId())->orderByDesc('starts_on')->get(),
            'accountGroups' => $this->report === 'ledger' ? Account::where('agency_id', $this->agencyId())->orderBy('code')->get()->groupBy('type') : collect(),
            'query' => ['report' => $this->report] + $rep['params'],
        ], $rep['title']);
    }
}
