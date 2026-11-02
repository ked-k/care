<?php

namespace App\Livewire\Accounting;

use App\Models\Accounting\FinancialYear;
use App\Services\Accounting\YearEnd;
use Illuminate\Support\Facades\Auth;

/** Batch 44a: close a financial year — checks, the result, appropriations, then lock. */
class YearEndComponent extends AccountsComponent
{
    public string $yearId;
    public bool $confirmed = false;

    public function mount(string $yearId): void
    {
        $this->yearId = FinancialYear::where('agency_id', $this->agencyId())->findOrFail($yearId)->id;
    }

    public function close(): void
    {
        if (! $this->confirmed) {
            $this->addError('confirmed', __('Tick the box to confirm.'));

            return;
        }
        $year = FinancialYear::findOrFail($this->yearId);
        if ($this->attemptOk(fn () => YearEnd::close($year, Auth::id()))) {
            session()->flash('success', __(':y is closed and locked.', ['y' => $year->name]));
            $this->redirectRoute('accounts.years', navigate: true);
        }
    }

    public function render()
    {
        $p = YearEnd::preview(FinancialYear::findOrFail($this->yearId));

        return $this->page('livewire.accounting.year-end', ['p' => $p, 'year' => $p['year']], __('Close :y', ['y' => $p['year']->name]));
    }
}
