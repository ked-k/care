<?php

namespace App\Livewire\Accounting;

use App\Models\Accounting\VatReturn;
use App\Services\Accounting\VatReturns;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

/**
 * Batch 44c: VAT — the next return worked out from the ledger (boxes 1–9
 * with the journals behind each), filing it, and the returns filed so far.
 */
class VatComponent extends AccountsComponent
{
    /** First day of the period being looked at; blank = the next one to file. */
    #[Url]
    public string $period = '';

    #[Url]
    public string $side = '';

    public array $setup = [];
    public array $file = ['reference' => '', 'notes' => '', 'lock' => true];

    public function mount(): void
    {
        $s = $this->settings();
        $this->setup = [
            'vat_period_months' => (int) ($s->vat_period_months ?: 3),
            'vat_period_end_month' => (int) ($s->vat_period_end_month ?: 3),
            'vat_start_date' => $s->vat_start_date?->toDateString() ?? '',
        ];
    }

    private function vat(): VatReturns
    {
        return VatReturns::for($this->agencyId());
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function currentPeriod(): array
    {
        return $this->period ? $this->vat()->periodFor($this->period) : $this->vat()->nextPeriod();
    }

    public function move(int $step): void
    {
        [$start] = $this->currentPeriod();
        $months = (int) ($this->settings()->vat_period_months ?: 3);
        $this->period = $start->copy()->addMonthsNoOverflow($step * $months)->toDateString();
    }

    public function saveSetup(): void
    {
        $this->validate([
            'setup.vat_period_months' => 'required|in:1,3,12',
            'setup.vat_period_end_month' => 'required|integer|between:1,12',
            'setup.vat_start_date' => 'nullable|date',
        ]);
        if (VatReturn::where('agency_id', $this->agencyId())->exists()
            && ((int) $this->setup['vat_period_months'] !== (int) $this->settings()->vat_period_months
                || (int) $this->setup['vat_period_end_month'] !== (int) $this->settings()->vat_period_end_month)) {
            // Changing the stagger mid-way is allowed (HMRC does move people) but the next period follows the last one filed.
            $this->toast(__('Saved. The next return still starts the day after the last one filed.'), 'info');
        } else {
            $this->toast(__('VAT periods saved.'));
        }
        $this->settings()->update([
            'vat_period_months' => (int) $this->setup['vat_period_months'],
            'vat_period_end_month' => (int) $this->setup['vat_period_end_month'],
            'vat_start_date' => $this->setup['vat_start_date'] ?: null,
        ]);
        $this->period = '';
        $this->js("window.dispatchEvent(new CustomEvent('close-modal',{detail:'vat-setup'}))");
    }

    public function fileReturn(): void
    {
        $this->validate(['file.reference' => 'nullable|string|max:80', 'file.notes' => 'nullable|string|max:2000']);
        [$start, $end] = $this->currentPeriod();
        $ret = $this->attempt(fn () => $this->vat()->file($start->toDateString(), $end->toDateString(), $this->file, Auth::id()));
        if ($ret) {
            session()->flash('success', __('VAT return for :p filed.', ['p' => $ret->label()]));
            $this->redirectRoute('accounts.vat.show', $ret->id, navigate: true);
        }
    }

    public function render()
    {
        $vat = $this->vat();
        [$start, $end] = $this->currentPeriod();
        [$nextStart] = $vat->nextPeriod();
        $filed = VatReturn::where('agency_id', $this->agencyId())->where('period_start', $start->toDateString())->first();
        $calc = $vat->compute($start, $end, $filed);

        return $this->page('livewire.accounting.vat', [
            'start' => $start, 'end' => $end, 'calc' => $calc, 'filed' => $filed,
            'isNext' => ! $filed && $start->equalTo($nextStart),
            'canFile' => ! $filed && $start->equalTo($nextStart) && $end->lt(today()),
            'rows' => $this->side ? $calc['rows']->where('side', $this->side) : $calc['rows'],
            'returns' => VatReturn::where('agency_id', $this->agencyId())->orderByDesc('period_end')->limit(12)->get(),
            'boxes' => VatReturns::BOXES,
        ], __('VAT'));
    }
}
