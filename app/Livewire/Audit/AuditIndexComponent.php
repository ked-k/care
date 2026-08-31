<?php

namespace App\Livewire\Audit;

use App\Models\Audit;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class AuditIndexComponent extends Component
{
    use WithPagination;

    public string $userFilter = '';
    public string $actionFilter = '';
    public string $fromDate = '';
    public string $toDate = '';

    public function mount(): void
    {
        abort_unless(
            Auth::user()->can('compliance.manage') || Auth::user()->hasRole(['Admin', 'Super Admin']),
            403,
            'Only a manager can view the audit log.'
        );
    }

    public function updating(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $agencyStaffIds = User::where('agency_id', Auth::user()->agency_id)->pluck('id');

        $audits = Audit::with('user')
            ->whereIn('user_id', $agencyStaffIds)
            ->when($this->userFilter, fn ($q) => $q->where('user_id', $this->userFilter))
            ->when($this->actionFilter, fn ($q) => $q->where('action', 'like', "%{$this->actionFilter}%"))
            ->when($this->fromDate, fn ($q) => $q->whereDate('created_at', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate('created_at', '<=', $this->toDate))
            ->orderByDesc('created_at')
            ->paginate(25);

        $userOptions = User::whereIn('id', $agencyStaffIds)->orderBy('name')->pluck('name', 'id');

        return view('livewire.audit.audit-index', compact('audits', 'userOptions'));
    }
}
