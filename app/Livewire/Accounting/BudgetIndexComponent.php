<?php

namespace App\Livewire\Accounting;

use App\Models\Accounting\Budget;
use App\Models\Accounting\FinancialYear;
use App\Services\Accounting\Budgets;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;

/** Batch 44c: budgets by financial year — create one blank, from last year’s figures, or as a copy. */
class BudgetIndexComponent extends AccountsComponent
{
    #[Url]
    public string $year = '';

    public array $form = [];

    public function mount(): void
    {
        $this->year = $this->year ?: $this->settings()->yearFor(today())->id;
        $this->blank();
    }

    private function blank(): void
    {
        $y = $this->selectedYear();
        $this->form = [
            'year' => $y->id,
            'name' => __(':y budget', ['y' => $y->name]),
            'start' => $y->previous() ? 'actuals' : 'blank',
            'source' => '',
            'uplift' => 0,
        ];
        $this->resetErrorBag();
    }

    private function selectedYear(): FinancialYear
    {
        return FinancialYear::where('agency_id', $this->agencyId())->find($this->year) ?? $this->settings()->yearFor(today());
    }

    public function updatedYear(): void
    {
        $this->blank();
    }

    public function create(): void
    {
        $this->blank();
        $this->js("window.dispatchEvent(new CustomEvent('open-modal',{detail:'new-budget'}))");
    }

    public function save(): void
    {
        $this->validate([
            'form.year' => ['required', Rule::in(FinancialYear::where('agency_id', $this->agencyId())->pluck('id'))],
            'form.name' => 'required|string|max:120',
            'form.start' => 'required|in:blank,actuals,budget',
            'form.source' => ['required_if:form.start,budget', 'nullable', Rule::in(Budget::where('agency_id', $this->agencyId())->pluck('id'))],
            'form.uplift' => 'nullable|numeric|min:-100|max:1000',
        ], ['form.source.required_if' => __('Choose the budget to copy.')]);
        $year = FinancialYear::findOrFail($this->form['year']);
        $budget = $this->attempt(fn () => Budgets::for($this->agencyId())->create($year, trim($this->form['name']), $this->form['start'],
            $this->form['source'] ?: null, (float) ($this->form['uplift'] ?: 0), Auth::id()));
        if ($budget) {
            session()->flash('success', __('Budget created — fill in or adjust the monthly figures.'));
            $this->redirectRoute('accounts.budgets.edit', $budget->id, navigate: true);
        }
    }

    public function makePrimary(string $id): void
    {
        $b = Budget::where('agency_id', $this->agencyId())->findOrFail($id);
        Budgets::for($this->agencyId())->makePrimary($b);
        $this->toast(__(':n is now the main budget for its year.', ['n' => $b->name]));
    }

    public function delete(string $id): void
    {
        $b = Budget::where('agency_id', $this->agencyId())->findOrFail($id);
        $wasPrimary = $b->is_primary;
        $b->delete();
        if ($wasPrimary && ($next = Budget::where('agency_id', $this->agencyId())->where('financial_year_id', $b->financial_year_id)->oldest()->first())) {
            $next->update(['is_primary' => true]);
        }
        $this->toast(__('Budget deleted.'));
    }

    public function render()
    {
        $year = $this->selectedYear();
        $budgets = Budget::where('agency_id', $this->agencyId())->where('financial_year_id', $year->id)
            ->withSum(['lines as income' => fn ($q) => $q->whereHas('account', fn ($a) => $a->where('type', 'income'))], 'total')
            ->withSum(['lines as spend' => fn ($q) => $q->whereHas('account', fn ($a) => $a->where('type', 'expense'))], 'total')
            ->with('creator:id,name')->orderByDesc('is_primary')->orderBy('name')->get();

        return $this->page('livewire.accounting.budgets', [
            'budgets' => $budgets,
            'selected' => $year,
            'years' => FinancialYear::where('agency_id', $this->agencyId())->orderByDesc('starts_on')->get(),
            'allBudgets' => Budget::where('agency_id', $this->agencyId())->with('year:id,name')->orderByDesc('created_at')->get(),
        ], __('Budgets'));
    }
}
