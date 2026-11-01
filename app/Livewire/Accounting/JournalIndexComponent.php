<?php

namespace App\Livewire\Accounting;

use App\Models\Accounting\Journal;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/** Batch 43: every posting in the books, newest first. */
class JournalIndexComponent extends AccountsComponent
{
    use WithPagination;

    #[Url]
    public string $type = '';

    #[Url]
    public string $search = '';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public function updating($name): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $journals = Journal::where('agency_id', $this->agencyId())
            ->when($this->type, fn ($q) => $q->where('type', $this->type))
            ->when($this->from, fn ($q) => $q->whereDate('date', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('date', '<=', $this->to))
            ->when(trim($this->search) !== '', function ($q) {
                $t = '%'.trim($this->search).'%';
                $q->where(fn ($w) => $w->where('number', 'like', $t)->orWhere('reference', 'like', $t)->orWhere('memo', 'like', $t));
            })
            ->with('creator:id,name')->orderByDesc('date')->orderByDesc('number')->paginate(30);

        return $this->page('livewire.accounting.journals', ['journals' => $journals], __('Journals'));
    }
}
