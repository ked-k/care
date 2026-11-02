<?php

namespace App\Livewire\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\FixedAsset;
use App\Models\Accounting\Journal;
use App\Services\Accounting\FixedAssets;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;

/** Batch 44a: the fixed assets register — add assets, run depreciation. */
class AssetIndexComponent extends AccountsComponent
{
    #[Url]
    public bool $disposed = false;

    public array $form = [];
    public string $runTo = '';

    public function mount(): void
    {
        $this->blank();
        $this->runTo = today()->subMonthNoOverflow()->endOfMonth()->toDateString();
    }

    private function blank(): void
    {
        $this->form = [
            'name' => '', 'category' => 'computers', 'serial_number' => '', 'location' => '',
            'purchase_date' => today()->toDateString(), 'cost' => '', 'residual_value' => 0,
            'method' => 'straight_line', 'rate' => 25, 'depreciation_start' => today()->toDateString(),
            'opening_depreciation' => 0, 'funding' => 'already', 'bank_account_id' => $this->settings()->account('bank')->id, 'notes' => '',
        ];
        $this->resetErrorBag();
    }

    public function create(): void
    {
        $this->blank();
        $this->dispatch('open-drawer', 'asset-form');
    }

    public function updatedFormCategory($c): void
    {
        if ($d = FixedAsset::CATEGORIES[$c] ?? null) {
            $this->form['method'] = $d[1];
            $this->form['rate'] = $d[2];
        }
    }

    public function updatedFormPurchaseDate($d): void
    {
        $this->form['depreciation_start'] = $d;
    }

    public function save(): void
    {
        $this->validate([
            'form.name' => 'required|string|max:255',
            'form.category' => ['required', Rule::in(array_keys(FixedAsset::CATEGORIES))],
            'form.serial_number' => 'nullable|string|max:120',
            'form.location' => 'nullable|string|max:120',
            'form.purchase_date' => 'required|date',
            'form.cost' => 'required|numeric|min:0.01',
            'form.residual_value' => 'nullable|numeric|min:0|lt:form.cost',
            'form.method' => ['required', Rule::in(array_keys(FixedAsset::METHODS))],
            'form.rate' => 'required_unless:form.method,none|nullable|numeric|min:0|max:100',
            'form.depreciation_start' => 'required|date',
            'form.opening_depreciation' => 'nullable|numeric|min:0|lt:form.cost',
            'form.funding' => 'required|in:already,bank,opening',
            'form.bank_account_id' => ['required_if:form.funding,bank', 'nullable', Rule::in(Account::where('agency_id', $this->agencyId())->money()->pluck('id'))],
            'form.notes' => 'nullable|string|max:2000',
        ]);
        [$assetAcc, $accAcc] = FixedAssets::categoryAccounts($this->agencyId(), $this->form['category']);
        $data = [
            'name' => $this->form['name'], 'category' => $this->form['category'],
            'serial_number' => $this->form['serial_number'] ?: null, 'location' => $this->form['location'] ?: null,
            'asset_account_id' => $assetAcc->id, 'accumulated_account_id' => $accAcc->id,
            'expense_account_id' => $this->settings()->account('depreciation')->id,
            'purchase_date' => $this->form['purchase_date'], 'cost' => (float) $this->form['cost'],
            'residual_value' => (float) ($this->form['residual_value'] ?: 0), 'method' => $this->form['method'],
            'rate' => $this->form['method'] === 'none' ? 0 : (float) $this->form['rate'],
            'depreciation_start' => $this->form['depreciation_start'],
            'opening_depreciation' => $this->form['funding'] === 'opening' ? (float) ($this->form['opening_depreciation'] ?: 0) : 0,
            'notes' => $this->form['notes'] ?: null,
        ];
        $asset = $this->attempt(fn () => FixedAssets::create($this->agencyId(), $data,
            ['type' => $this->form['funding'], 'bank_account_id' => $this->form['bank_account_id']], Auth::id()));
        if ($asset) {
            $this->dispatch('close-drawer', 'asset-form');
            $this->toast(__(':n added to the register.', ['n' => $asset->number]));
        }
    }

    public function runDepreciation(): void
    {
        $this->validate(['runTo' => 'required|date']);
        $j = $this->attempt(fn () => FixedAssets::run($this->agencyId(), $this->runTo, Auth::id()));
        if ($j) {
            $this->toast(__('Depreciation of :a posted (:n).', ['a' => $this->settings()->money($j->total), 'n' => $j->number]));
            $this->js("window.dispatchEvent(new CustomEvent('close-modal',{detail:'run-dep'}))");
        } elseif ($j === null && ! $this->getErrorBag()->any()) {
            $this->toast(__('Nothing to charge — every asset is already depreciated to that month.'), 'warning');
        }
    }

    public function undoRun(): void
    {
        $r = $this->attempt(fn () => FixedAssets::undoLastRun($this->agencyId(), Auth::id()));
        $this->toast($r ? __('Last depreciation run reversed.') : __('There’s no run to undo.'), $r ? 'success' : 'warning');
    }

    public function render()
    {
        $assets = FixedAsset::where('agency_id', $this->agencyId())
            ->when(! $this->disposed, fn ($q) => $q->where('status', 'active'))
            ->withSum('entries', 'amount')->orderBy('number')->get()
            ->each(function ($a) {
                $a->acc = round((float) $a->opening_depreciation + (float) $a->entries_sum_amount, 2);
                $a->nbv = round((float) $a->cost - $a->acc, 2);
            });

        return $this->page('livewire.accounting.assets', [
            'assets' => $assets,
            'groups' => $assets->groupBy('category'),
            'totals' => ['cost' => $assets->where('status', 'active')->sum('cost'), 'acc' => $assets->where('status', 'active')->sum('acc'), 'nbv' => $assets->where('status', 'active')->sum('nbv')],
            'preview' => FixedAssets::preview($this->agencyId(), $this->runTo ?: today()->toDateString()),
            'lastRun' => Journal::where('agency_id', $this->agencyId())->where('type', 'depreciation')->where('status', 'posted')->latest('date')->first(),
            'banks' => $this->moneyAccounts(),
        ], __('Fixed assets'));
    }
}
