<?php

namespace App\Livewire\Timesheet;

use App\Models\Timesheet;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TimesheetIndexComponent extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    /** Batch 19: shared by render() and exportCsv() so export always matches the on-screen filter. */
    protected function timesheetsQuery()
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

        return $query->orderByDesc('week_commencing');
    }

    public function exportCsv(): StreamedResponse
    {
        $timesheets = $this->timesheetsQuery()->get();

        return response()->streamDownload(function () use ($timesheets) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Carer', 'Week Commencing', 'Regular Hours', 'Overtime Hours', 'Live-in Days', 'Live-in Pay', 'Status']);

            foreach ($timesheets as $timesheet) {
                fputcsv($out, [
                    $timesheet->user->name ?? '—',
                    $timesheet->week_commencing->format('Y-m-d'),
                    number_format((float) $timesheet->total_regular_hours, 2),
                    number_format((float) $timesheet->total_overtime_hours, 2),
                    (int) ($timesheet->total_live_in_days ?? 0),
                    number_format((float) ($timesheet->total_live_in_pay ?? 0), 2),
                    ucfirst($timesheet->status),
                ]);
            }

            fclose($out);
        }, 'timesheets-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $user = Auth::user();

        $timesheets = $this->timesheetsQuery()->paginate(12);

        // Batch 12: mobile carer shell for a plain carer — see
        // App\Models\User::isCarerOnly(). The query above already scopes a
        // carer to their own timesheets, so the content needs no change.
        return view('livewire.timesheet.timesheet-index', compact('timesheets'))
            ->layout($user->isCarerOnly() ? 'layouts.carer' : 'layouts.admin-layout');
    }
}
