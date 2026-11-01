<?php

namespace App\Livewire\Accounting;

use App\Models\Accounting\Document;
use App\Models\Accounting\Journal;
use App\Models\Accounting\PostingIssue;
use App\Services\Accounting\AutoPosting;
use App\Services\Accounting\Reports;
use Illuminate\Support\Facades\DB;

/** Batch 43: Finance home — cash, who owes what, this year's result, and anything that failed to post. */
class DashboardComponent extends AccountsComponent
{
    public function retry(string $issueId): void
    {
        $issue = PostingIssue::where('agency_id', $this->agencyId())->findOrFail($issueId);
        AutoPosting::retry($issue)
            ? $this->toast(__('Posted.'))
            : $this->toast(__('Still couldn’t post: :m', ['m' => $issue->fresh()->message]), 'error');
    }

    public function retryAll(): void
    {
        $ok = 0;
        foreach (PostingIssue::where('agency_id', $this->agencyId())->whereNull('resolved_at')->get() as $i) {
            $ok += AutoPosting::retry($i) ? 1 : 0;
        }
        $left = PostingIssue::where('agency_id', $this->agencyId())->whereNull('resolved_at')->count();
        $this->toast(__(':ok posted, :left still waiting.', ['ok' => $ok, 'left' => $left]), $left ? 'warning' : 'success');
    }

    public function render()
    {
        $s = $this->settings();
        $reports = new Reports($s);
        $today = today()->toDateString();
        $year = $s->yearFor($today);
        $money = $reports->moneyBalances();
        $nets = $reports->nets(null, $today);
        $owed = $nets[$s->account('receivables')->id] ?? 0;
        $owing = -($nets[$s->account('payables')->id] ?? 0);
        $is = $reports->incomeStatement($year->starts_on->toDateString(), $today);

        $open = fn ($kind) => Document::where('agency_id', $this->agencyId())->where('kind', $kind)->whereIn('status', ['posted', 'partial']);
        $overdue = (clone $open('invoice'))->whereDate('due_date', '<', $today);

        // Last six months: income vs expenses straight from the ledger.
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = today()->startOfMonth()->subMonths($i);
            $months[$m->format('Y-m')] = ['label' => $m->format('M'), 'income' => 0, 'expense' => 0];
        }
        $rows = DB::table('acc_journal_lines as l')->join('acc_accounts as a', 'a.id', '=', 'l.account_id')
            ->join('acc_journals as j', 'j.id', '=', 'l.journal_id')
            ->where('l.agency_id', $this->agencyId())->where('j.type', '!=', 'year_end')
            ->whereIn('a.type', ['income', 'expense'])
            ->where('l.date', '>=', today()->startOfMonth()->subMonths(5)->toDateString())
            ->selectRaw('substr(l.date, 1, 7) as ym, a.type, sum(l.credit) - sum(l.debit) as net')
            ->groupBy('ym', 'a.type')->get();
        foreach ($rows as $r) {
            if (isset($months[$r->ym])) {
                $months[$r->ym][$r->type === 'income' ? 'income' : 'expense'] = $r->type === 'income' ? (float) $r->net : -(float) $r->net;
            }
        }

        return $this->page('livewire.accounting.dashboard', [
            'year' => $year,
            'money' => $money,
            'cash' => $money->sum('balance'),
            'owed' => $owed,
            'owing' => $owing,
            'result' => $is['result']['current'],
            'income' => $is['revenue']['current'] + $is['other']['current'],
            'overdueCount' => $overdue->count(),
            'overdueTotal' => (float) (clone $overdue)->sum(DB::raw('total - amount_paid')),
            'billsDue' => (float) $open('bill')->whereDate('due_date', '<=', today()->addDays(7))->sum(DB::raw('total - amount_paid')),
            'months' => array_values($months),
            'recent' => Journal::where('agency_id', $this->agencyId())->latest('date')->latest('created_at')->limit(8)->get(),
            'issues' => PostingIssue::where('agency_id', $this->agencyId())->whereNull('resolved_at')->latest('updated_at')->limit(20)->get(),
            'isEmpty' => ! Journal::where('agency_id', $this->agencyId())->exists(),
        ], __('Dashboard'));
    }
}
