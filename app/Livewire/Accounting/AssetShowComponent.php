<?php

namespace App\Livewire\Accounting;

use App\Models\Accounting\FixedAsset;
use App\Services\Accounting\FixedAssets;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/** Batch 44a: one fixed asset — its depreciation history and projection, edit, dispose. */
class AssetShowComponent extends AccountsComponent
{
    public string $assetId;
    public array $edit = [];
    public array $dispose = [];

    public function mount(string $assetId): void
    {
        $a = FixedAsset::where('agency_id', $this->agencyId())->findOrFail($assetId);
        $this->assetId = $a->id;
        $this->edit = ['name' => $a->name, 'serial_number' => (string) $a->serial_number, 'location' => (string) $a->location, 'notes' => (string) $a->notes];
        $this->dispose = ['date' => today()->toDateString(), 'proceeds' => 0, 'bank_account_id' => $this->settings()->account('bank')->id];
    }

    private function asset(): FixedAsset
    {
        return FixedAsset::where('agency_id', $this->agencyId())->with('entries.journal:id,number', 'assetAccount', 'accumulatedAccount', 'expenseAccount', 'creator:id,name')->findOrFail($this->assetId);
    }

    public function saveDetails(): void
    {
        $this->validate(['edit.name' => 'required|string|max:255', 'edit.serial_number' => 'nullable|string|max:120', 'edit.location' => 'nullable|string|max:120', 'edit.notes' => 'nullable|string|max:2000']);
        $this->asset()->update(array_map(fn ($v) => $v === '' ? null : $v, $this->edit));
        $this->dispatch('close-drawer', 'asset-edit');
        $this->toast(__('Saved.'));
    }

    public function depreciateNow(): void
    {
        $to = today()->subMonthNoOverflow()->endOfMonth()->toDateString();
        $j = $this->attempt(fn () => FixedAssets::run($this->agencyId(), $to, Auth::id(), $this->asset()));
        $this->toast($j ? __('Depreciation of :a posted.', ['a' => $this->settings()->money($j->total)]) : __('Already depreciated to :d.', ['d' => Carbon::parse($to)->format('M Y')]), $j ? 'success' : 'warning');
    }

    public function disposeAsset(): void
    {
        $this->validate(['dispose.date' => 'required|date', 'dispose.proceeds' => 'required|numeric|min:0', 'dispose.bank_account_id' => 'nullable|string']);
        if ($this->attempt(fn () => FixedAssets::dispose($this->asset(), $this->dispose['date'], (float) $this->dispose['proceeds'], $this->dispose['bank_account_id'] ?: null, Auth::id()), __('Disposal posted.'))) {
            $this->js("window.dispatchEvent(new CustomEvent('close-modal',{detail:'dispose'}))");
        }
    }

    public function undoDisposal(): void
    {
        $this->attemptOk(fn () => FixedAssets::undoDisposal($this->asset(), Auth::id()), __('Disposal reversed — the asset is back on the register.'));
    }

    public function render()
    {
        $a = $this->asset();
        // Projection: the next five years at the current method and rate.
        $projection = [];
        if (! $a->isDisposed() && $a->method !== 'none') {
            $acc = $a->accumulated();
            $month = ($a->depreciated_to ?? $a->depreciation_start->copy()->subMonthNoOverflow())->copy()->addDay()->endOfMonth();
            for ($y = 0; $y < 5; $y++) {
                $sum = 0;
                for ($m = 0; $m < 12; $m++) {
                    $c = FixedAssets::monthlyCharge($a, $acc);
                    $acc += $c;
                    $sum += $c;
                    $month = $month->copy()->addDay()->endOfMonth();
                }
                if ($sum <= 0) {
                    break;
                }
                $projection[] = ['to' => $month->copy()->format('M Y'), 'charge' => round($sum, 2), 'nbv' => round((float) $a->cost - $acc, 2)];
            }
        }
        $byYear = $a->entries->groupBy(fn ($e) => $this->settings()->yearFor($e->period_end)->name)->map(fn ($g) => round($g->sum('amount'), 2));

        return $this->page('livewire.accounting.asset-show', [
            'a' => $a, 'acc' => $a->accumulated(), 'nbv' => $a->bookValue(), 'projection' => $projection, 'byYear' => $byYear,
            'banks' => $this->moneyAccounts(),
        ], $a->name);
    }
}
