<?php

namespace App\Livewire\Accounting;

use App\Models\Accounting\FinancialYear;
use App\Models\Accounting\Journal;
use App\Services\Accounting\Reports;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Batch 43: financial years and the lock date.
 *
 * Lock date — nothing dated on or before it can be posted, edited or
 * reversed into (set it once a month is reconciled, or once the auditors
 * have signed off). Closing a year locks the whole year; the year-end
 * appropriations (reserves, transfers to retained earnings) arrive with
 * Batch 44.
 */
class YearsComponent extends AccountsComponent
{
    public string $lockDate = '';
    public string $reopenReason = '';
    public ?string $reopenId = null;

    public function mount(): void
    {
        $this->lockDate = (string) $this->settings()->lock_date?->toDateString();
        // Make sure the current year exists.
        $this->settings()->yearFor(today());
    }

    public function saveLock(): void
    {
        $this->validate(['lockDate' => 'nullable|date|before_or_equal:today'], ['lockDate.before_or_equal' => __('The lock date can’t be in the future.')]);
        $old = $this->settings()->lock_date?->toDateString();
        $this->settings()->update(['lock_date' => $this->lockDate ?: null]);
        activity('accounts')->causedBy(Auth::user())->withProperties(['from' => $old, 'to' => $this->lockDate ?: null])
            ->log(__('Lock date changed'));
        $this->toast($this->lockDate ? __('Books locked up to :d.', ['d' => Carbon::parse($this->lockDate)->format('d M Y')]) : __('Lock date removed.'));
    }

    public function addNext(): void
    {
        $last = FinancialYear::where('agency_id', $this->agencyId())->orderByDesc('ends_on')->first();
        $year = $this->settings()->yearFor($last ? $last->ends_on->copy()->addDay() : today());
        $this->toast(__(':y added.', ['y' => $year->name]));
    }

    public function addPrevious(): void
    {
        $first = FinancialYear::where('agency_id', $this->agencyId())->orderBy('starts_on')->first();
        $year = $this->settings()->yearFor($first ? $first->starts_on->copy()->subDay() : today()->subYear());
        $this->toast(__(':y added — use it for opening balances and comparatives.', ['y' => $year->name]));
    }

    public function askReopen(string $id): void
    {
        $this->reopenId = $id;
        $this->reopenReason = '';
        $this->dispatch('open-drawer', 'reopen');
    }

    public function reopen(): void
    {
        $this->validate(['reopenReason' => 'required|string|min:5|max:500'], ['reopenReason.required' => __('Say why — re-opening a closed year is recorded in the audit log.')]);
        $y = FinancialYear::where('agency_id', $this->agencyId())->findOrFail($this->reopenId);
        // Batch 44a: also reverses the year-end closing and appropriation journals.
        if ($this->attemptOk(fn () => \App\Services\Accounting\YearEnd::reopen($y, $this->reopenReason, Auth::id()), __(':y re-opened.', ['y' => $y->name]))) {
            $this->lockDate = (string) $this->settings()->fresh()->lock_date?->toDateString();
            \App\Models\Accounting\AccountingSetting::forgetCache();
            $this->dispatch('close-drawer', 'reopen');
        }
    }

    public function render()
    {
        $reports = new Reports($this->settings());
        $years = FinancialYear::where('agency_id', $this->agencyId())->orderByDesc('starts_on')->get()->map(function ($y) use ($reports) {
            $y->journal_count = Journal::where('financial_year_id', $y->id)->count();
            $y->result = $reports->incomeStatement($y->starts_on->toDateString(), min($y->ends_on, today())->toDateString())['result']['current'];

            return $y;
        });

        return $this->page('livewire.accounting.years', [
            'years' => $years,
            'log' => \Spatie\Activitylog\Models\Activity::where('log_name', 'accounts')->latest()->limit(15)->get(),
        ], __('Financial years'));
    }
}
