<?php

namespace App\Livewire\Accounting;

use App\Models\Accounting\VatReturn;
use App\Services\Accounting\VatReturns;
use Illuminate\Support\Facades\Auth;

/** Batch 44c: one filed VAT return — the figures as filed, the journals behind them, paying it. */
class VatShowComponent extends AccountsComponent
{
    public string $returnId;
    public array $pay = [];

    public function mount(string $returnId): void
    {
        $r = VatReturn::where('agency_id', $this->agencyId())->findOrFail($returnId);
        $this->returnId = $r->id;
        $this->pay = ['bank_account_id' => $this->settings()->account('bank')->id, 'date' => today()->min($r->due_date ?? today())->max($r->period_end->copy()->addDay())->toDateString()];
    }

    private function ret(): VatReturn
    {
        return VatReturn::where('agency_id', $this->agencyId())->with('paymentJournal', 'filer')->findOrFail($this->returnId);
    }

    public function recordPayment(): void
    {
        $this->validate(['pay.bank_account_id' => 'required|string', 'pay.date' => 'required|date']);
        $ok = $this->attempt(fn () => VatReturns::for($this->agencyId())->pay($this->ret(), $this->pay['bank_account_id'], $this->pay['date'], Auth::id()));
        if ($ok) {
            $this->dispatch('close-drawer', 'vat-pay');
            $this->toast($ok->net() >= 0 ? __('VAT payment recorded.') : __('VAT refund recorded.'));
        }
    }

    public function undoPayment(): void
    {
        if ($this->attempt(fn () => VatReturns::for($this->agencyId())->undoPayment($this->ret(), Auth::id()))) {
            $this->toast(__('Payment reversed.'));
        }
    }

    public function reopen(): void
    {
        $ret = $this->ret();
        if ($this->attemptOk(fn () => VatReturns::for($this->agencyId())->reopen($ret, Auth::id()))) {
            session()->flash('success', __('Return re-opened — its entries are back on the next return.'));
            $this->redirectRoute('accounts.vat', ['period' => $ret->period_start->toDateString()], navigate: true);
        }
    }

    public function render()
    {
        $ret = $this->ret();
        $calc = VatReturns::for($this->agencyId())->compute($ret->period_start, $ret->period_end, $ret);
        $latest = VatReturns::for($this->agencyId())->latestFiled();

        return $this->page('livewire.accounting.vat-show', [
            'ret' => $ret, 'calc' => $calc, 'boxes' => VatReturns::BOXES,
            'banks' => $this->moneyAccounts(),
            'canReopen' => $latest?->id === $ret->id && ! $ret->paymentJournal,
        ], __('VAT return :p', ['p' => $ret->label()]));
    }
}
