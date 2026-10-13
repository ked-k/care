<?php
namespace App\Livewire\Rota;

use App\Models\ServiceUser;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Batch 19: an agency-wide visit (Shift) list across a date range —
 * didn't exist before this batch. My Rota (App\Livewire\Rota\
 * MyRotaComponent) is one carer's own week; the Care Call Timeline is
 * one service user's one day. Neither lets a manager see "what actually
 * happened, across everyone, this month" — this page and its CSV export
 * fill that gap, matching the Compliance Dashboard's existing "missed
 * visits" terminology (Shift rows, not CareCall welfare checks — those
 * stay a separate, non-payroll record per the Batch 14/18 design).
 */
class VisitHistoryComponent extends Component
{
    use WithPagination;

    public string $fromDate = '';
    public string $toDate = '';
    public string $serviceUserId = '';
    public string $carerId = '';
    public string $statusFilter = '';
    public string $shiftTypeFilter = '';

    public array $serviceUsers = [];
    public array $carers = [];

    public function mount(): void
    {
        abort_unless(Auth::user()->can('manage_rota') || Auth::user()->hasRole(['Admin', 'Super Admin']), 403);

        $this->fromDate = now()->startOfWeek()->toDateString();
        $this->toDate = now()->endOfWeek()->toDateString();

        $agencyId = Auth::user()->agency_id;
        $this->serviceUsers = ServiceUser::where('agency_id', $agencyId)->orderBy('name')->pluck('name', 'id')->toArray();
        $this->carers = User::where('agency_id', $agencyId)->orderBy('name')->pluck('name', 'id')->toArray();
    }

    public function updating(): void
    {
        $this->resetPage();
    }

    /** Shared by render() and exportCsv() so export always matches the on-screen filters. */
    protected function visitsQuery()
    {
        $agencyId = Auth::user()->agency_id;

        return Shift::with(['serviceUser', 'carer'])
            ->where('agency_id', $agencyId)
            ->when($this->fromDate, fn ($q) => $q->where('scheduled_start', '>=', $this->fromDate.' 00:00:00'))
            ->when($this->toDate, fn ($q) => $q->where('scheduled_start', '<=', $this->toDate.' 23:59:59'))
            ->when($this->serviceUserId, fn ($q) => $q->where('service_user_id', $this->serviceUserId))
            ->when($this->carerId, fn ($q) => $q->where('assigned_to', $this->carerId))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->shiftTypeFilter, fn ($q) => $q->where('shift_type', $this->shiftTypeFilter))
            ->orderByDesc('scheduled_start');
    }

    public function exportCsv(): StreamedResponse
    {
        $visits = $this->visitsQuery()->get();

        return response()->streamDownload(function () use ($visits) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Service User', 'Carer', 'Type', 'Start', 'End', 'Status', 'Double-handed', 'Break (min)', 'Notes']);

            foreach ($visits as $visit) {
                fputcsv($out, [
                    $visit->scheduled_start->format('Y-m-d'),
                    $visit->serviceUser->name ?? '—',
                    $visit->carer->name ?? __('Unassigned'),
                    ucfirst(str_replace('_', '-', $visit->shift_type ?? 'day')),
                    $visit->scheduled_start->format('H:i'),
                    $visit->scheduled_end->format('H:i'),
                    ucfirst($visit->status),
                    $visit->isDoubleHanded() ? 'Yes' : 'No',
                    $visit->break_minutes,
                    $visit->notes,
                ]);
            }

            fclose($out);
        }, 'visit-history-'.$this->fromDate.'-to-'.$this->toDate.'.csv', ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $visits = $this->visitsQuery()->paginate(20);

        return view('livewire.rota.visit-history', compact('visits'));
    }
}
