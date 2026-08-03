<?php

namespace App\Livewire\Timesheet;

use App\Models\Timesheet;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class TimesheetIndexComponent extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = Auth::user();

        $query = Timesheet::with('user')->where('agency_id', $user->agency_id);

        // Carers only ever see their own timesheets; managers/approvers see the agency's.
        if (! ($user->can('approve_timesheets') ?? false)) {
            $query->where('user_id', $user->id);
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        $timesheets = $query->orderByDesc('week_commencing')->paginate(12);

        return view('livewire.timesheet.timesheet-index', compact('timesheets'));
    }
}
