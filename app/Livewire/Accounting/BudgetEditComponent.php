<?php

namespace App\Livewire\Accounting;

use App\Models\Accounting\Budget;
use App\Services\Accounting\Budgets;

/**
 * Batch 44c: the budget grid — a row per income / expense account, a
 * column per month. Row totals and "spread a year's figure evenly" work
 * in the browser; Save sends the whole grid.
 */
class BudgetEditComponent extends AccountsComponent
{
    public string $budgetId;
    public array $grid = [];
    public string $name = '';
    public string $notes = '';
    public bool $all = false;
    public string $addAccount = '';
    public float $adjust = 0;

    public function mount(string $budgetId): void
    {
        $b = $this->budget();
        $this->budgetId = $b->id;
        $this->name = $b->name;
        $this->notes = (string) $b->notes;
        $this->load($b);
    }

    private function budget(): Budget
    {
        return Budget::where('agency_id', $this->agencyId())->with('year', 'lines')->findOrFail($this->budgetId ?? request()->route('budgetId'));
    }

    private function load(Budget $b): void
    {
        $this->grid = $b->lines->mapWithKeys(fn ($l) => [$l->account_id => array_map(fn ($v) => (float) $v, array_pad($l->months, 12, 0))])->all();
    }

    public function updatedAddAccount($id): void
    {
        if ($id && ! isset($this->grid[$id])) {
            $this->grid[$id] = array_fill(0, 12, 0);
        }
        $this->addAccount = '';
    }

    public function removeRow(string $id): void
    {
        unset($this->grid[$id]);
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:120',
            'notes' => 'nullable|string|max:2000',
            'grid' => 'array',
            'grid.*' => 'array|size:12',
            'grid.*.*' => 'nullable|numeric|min:0|max:999999999',
        ], ['grid.*.*.min' => __('Budget figures are positive amounts — income expected or spending expected.')]);
        $b = $this->budget();
        $b->update(['name' => trim($this->name), 'notes' => trim($this->notes) ?: null]);
        // Rows taken off the grid are deleted.
        $b->lines()->whereNotIn('account_id', array_keys($this->grid) ?: ['-'])->delete();
        Budgets::for($this->agencyId())->save($b, $this->grid);
        $this->load($b->fresh('lines'));
        $this->toast(__('Budget saved.'));
    }

    /** Copy last year's actual figures into every row (or add rows for them). */
    public function fillFromActuals(): void
    {
        $prev = $this->budget()->year->previous();
        if (! $prev) {
            $this->toast(__('There is no earlier year to copy figures from.'), 'warning');

            return;
        }
        $factor = 1 + $this->adjust / 100;
        foreach (Budgets::for($this->agencyId())->actualsByMonth($prev) as $id => $months) {
            $this->grid[$id] = array_map(fn ($v) => round(max(0, $v) * $factor, 2), $months);
        }
        $this->js("window.dispatchEvent(new CustomEvent('close-modal',{detail:'budget-tools'}))");
        $this->toast(__('Filled from :y actuals — check the figures, then Save.', ['y' => $prev->name]), 'info');
    }

    /** Scale every figure up or down by a percentage. */
    public function scale(): void
    {
        $factor = 1 + $this->adjust / 100;
        foreach ($this->grid as $id => $months) {
            $this->grid[$id] = array_map(fn ($v) => round((float) $v * $factor, 2), $months);
        }
        $this->js("window.dispatchEvent(new CustomEvent('close-modal',{detail:'budget-tools'}))");
        $this->toast(__('Every figure adjusted by :p% — Save to keep it.', ['p' => $this->adjust]), 'info');
    }

    public function render()
    {
        $b = $this->budget();
        $budgets = Budgets::for($this->agencyId());
        $accounts = $budgets->accounts();
        $shown = $this->all ? $accounts : $accounts->filter(fn ($a) => array_key_exists($a->id, $this->grid));
        $actuals = $budgets->actualsByMonth($b->year);
        $this->dispatch('budget-refreshed'); // re-total the grid in the browser after each update

        return $this->page('livewire.accounting.budget-edit', [
            'budget' => $b,
            'months' => Budgets::months($b->year),
            'sections' => $shown->groupBy('type'),
            'addable' => $accounts->reject(fn ($a) => array_key_exists($a->id, $this->grid))->groupBy('type'),
            'actuals' => $actuals,
            'hasPrevious' => (bool) $b->year->previous(),
        ], $b->name);
    }
}
