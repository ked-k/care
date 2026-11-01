<?php

namespace App\Livewire\Accounting;

use App\Models\Accounting\Document;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/** Batch 43: lists of sales invoices, supplier bills, or money in/out. */
class DocumentIndexComponent extends AccountsComponent
{
    use WithPagination;

    public string $list = 'invoice'; // invoice | bill | money

    #[Url]
    public string $status = '';

    #[Url]
    public string $search = '';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public function mount(): void
    {
        $this->list = match (Route::currentRouteName()) {
            'accounts.bills' => 'bill',
            'accounts.money' => 'money',
            default => 'invoice',
        };
    }

    public function updating($name): void
    {
        if (in_array($name, ['status', 'search', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    private function query()
    {
        $kinds = $this->list === 'money' ? ['money_in', 'money_out'] : [$this->list];

        return Document::where('agency_id', $this->agencyId())->whereIn('kind', $kinds)
            ->when($this->status === 'overdue', fn ($q) => $q->whereIn('status', ['posted', 'partial'])->whereDate('due_date', '<', today()))
            ->when($this->status === 'open', fn ($q) => $q->whereIn('status', ['posted', 'partial']))
            ->when(in_array($this->status, ['draft', 'paid', 'void', 'partial'], true), fn ($q) => $q->where('status', $this->status))
            ->when(in_array($this->status, ['money_in', 'money_out'], true), fn ($q) => $q->where('kind', $this->status))
            ->when($this->from, fn ($q) => $q->whereDate('issue_date', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('issue_date', '<=', $this->to))
            ->when(trim($this->search) !== '', function ($q) {
                $t = '%'.trim($this->search).'%';
                $q->where(fn ($w) => $w->where('number', 'like', $t)->orWhere('reference', 'like', $t)
                    ->orWhereHas('contact', fn ($c) => $c->where('name', 'like', $t)));
            });
    }

    public function render()
    {
        $base = Document::where('agency_id', $this->agencyId())->whereIn('kind', $this->list === 'money' ? ['money_in', 'money_out'] : [$this->list]);
        $title = match ($this->list) {
            'bill' => __('Supplier bills'),
            'money' => __('Money in / out'),
            default => __('Sales invoices'),
        };

        return $this->page('livewire.accounting.documents', [
            'docs' => $this->query()->with('contact:id,name', 'bankAccount:id,name')->orderByDesc('issue_date')->orderByDesc('number')->paginate(25),
            'title' => $title,
            'totals' => [
                'open' => (float) (clone $base)->whereIn('status', ['posted', 'partial'])->sum(DB::raw('total - amount_paid')),
                'overdue' => (float) (clone $base)->whereIn('status', ['posted', 'partial'])->whereDate('due_date', '<', today())->sum(DB::raw('total - amount_paid')),
                'drafts' => (clone $base)->where('status', 'draft')->count(),
                'month' => (float) (clone $base)->whereNotIn('status', ['draft', 'void'])->whereDate('issue_date', '>=', today()->startOfMonth())->sum('total'),
            ],
        ], $title);
    }
}
