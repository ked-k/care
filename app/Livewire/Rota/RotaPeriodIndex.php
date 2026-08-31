<?php
namespace App\Livewire\Rota;

use App\Models\RotaPeriod;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

class RotaPeriodIndex extends Component
{
    use WithPagination;

    #[Validate('required|date')]
    public string $newWeekCommencing = '';

    public string $newNotes = '';

    public function mount(): void
    {
        // Batch 4: same gap as RotaBuilder (see its mount()) — this list/
        // create screen had no authorization check either.
        abort_unless(Auth::user()->can('manage_rota') || Auth::user()->hasRole(['Admin', 'Super Admin']), 403);
    }

    public function createPeriod()
    {
        // dd('createPeriod');
        $this->validate();

        $period = RotaPeriod::create([
            'agency_id'       => Auth::user()->agency_id,
            'week_commencing' => $this->newWeekCommencing,
            'notes'           => $this->newNotes ?: null,
            'status'          => 'draft',
            'created_by'      => Auth::id(),
        ]);

        $this->reset(['newWeekCommencing', 'newNotes']);
        $this->redirect(route('rota.builder', $period), navigate: true);
    }

    public function publish(string $rotaPeriodId): void
    {
        RotaPeriod::findOrFail($rotaPeriodId)->publish();
        $this->dispatch('toast', message: 'Rota period published.', type: 'success');
    }

    public function generateTimesheets(string $rotaPeriodId): void
    {
        RotaPeriod::findOrFail($rotaPeriodId)->generateTimesheets();
        $this->dispatch('toast', message: 'Timesheets generated for every scheduled carer.', type: 'success');
    }

    public function render()
    {
        $periods = RotaPeriod::where('agency_id', Auth::user()->agency_id)
            ->withCount('shifts')
            ->orderByDesc('week_commencing')
            ->paginate(10);

        return view('livewire.rota.rota-period-index', compact('periods'));
    }
}
