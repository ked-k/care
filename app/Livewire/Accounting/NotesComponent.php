<?php

namespace App\Livewire\Accounting;

use App\Models\Accounting\FinancialYear;
use App\Models\Accounting\ReportNote;
use App\Services\Accounting\Notes;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

/** Batch 44b: write and edit the notes to a year's financial statements. */
class NotesComponent extends AccountsComponent
{
    #[Url]
    public string $year = '';

    public array $notes = [];

    public function mount(): void
    {
        $this->load();
    }

    private function fy(): FinancialYear
    {
        return ($this->year ? FinancialYear::where('agency_id', $this->agencyId())->find($this->year) : null)
            ?? $this->settings()->yearFor(today()->subYear()->max($this->settings()->posting_from ?? today()));
    }

    private function load(): void
    {
        $fy = $this->fy();
        $this->year = $fy->id;
        $this->notes = Notes::forYear($fy)->map(fn ($n) => ['id' => $n->id, 'title' => $n->title, 'body' => (string) $n->body])->all();
    }

    public function updatedYear(): void
    {
        $this->load();
    }

    public function save(): void
    {
        $this->validate(['notes.*.title' => 'required|string|max:255', 'notes.*.body' => 'nullable|string|max:20000']);
        foreach (array_values($this->notes) as $i => $n) {
            ReportNote::where('agency_id', $this->agencyId())->whereKey($n['id'])
                ->update(['title' => $n['title'], 'body' => $n['body'], 'sort_order' => $i + 1, 'updated_by' => Auth::id()]);
        }
        $this->toast(__('Notes saved.'));
    }

    public function add(): void
    {
        $n = ReportNote::create(['agency_id' => $this->agencyId(), 'financial_year_id' => $this->year, 'title' => __('New note'), 'body' => '',
            'sort_order' => count($this->notes) + 1, 'updated_by' => Auth::id()]);
        $this->notes[] = ['id' => $n->id, 'title' => $n->title, 'body' => ''];
    }

    public function remove(int $i): void
    {
        if (isset($this->notes[$i])) {
            ReportNote::where('agency_id', $this->agencyId())->whereKey($this->notes[$i]['id'])->delete();
            unset($this->notes[$i]);
            $this->notes = array_values($this->notes);
        }
    }

    public function move(int $i, int $dir): void
    {
        $j = $i + $dir;
        if (isset($this->notes[$i], $this->notes[$j])) {
            [$this->notes[$i], $this->notes[$j]] = [$this->notes[$j], $this->notes[$i]];
            $this->save();
        }
    }

    public function resetTemplate(): void
    {
        ReportNote::where('agency_id', $this->agencyId())->where('financial_year_id', $this->year)->delete();
        $fy = $this->fy();
        foreach (Notes::template($fy) as $i => $r) {
            ReportNote::create($r + ['agency_id' => $this->agencyId(), 'financial_year_id' => $fy->id, 'sort_order' => $i + 1]);
        }
        $this->load();
        $this->toast(__('Notes reset to the standard wording.'));
    }

    public function render()
    {
        return $this->page('livewire.accounting.notes', [
            'years' => FinancialYear::where('agency_id', $this->agencyId())->orderByDesc('starts_on')->get(),
            'fy' => $this->fy(),
        ], __('Notes to the accounts'));
    }
}
