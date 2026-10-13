<?php

namespace App\Livewire\Audit;

use App\Models\ActivityLogEntry;
use App\Models\Audit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Batch 20: repointed at Spatie's `activity_log` table (via the thin
 * ActivityLogEntry subclass) now that every meaningful model writes there
 * automatically through the LogsActivity trait, instead of the hand-rolled
 * AuditLogger/Audit combination Batch 2 built. The old `audits` table isn't
 * dropped and AuditLogger isn't deleted — they just stop gaining new rows —
 * so the "Legacy Audit Log" toggle below keeps that history reachable
 * rather than orphaning it.
 */
class AuditIndexComponent extends Component
{
    use WithPagination;

    public string $view = 'activity'; // 'activity' | 'legacy'

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

    public function switchView(string $view): void
    {
        $this->view = in_array($view, ['activity', 'legacy'], true) ? $view : 'activity';
        $this->userFilter = '';
        $this->actionFilter = '';
        $this->resetPage();
    }

    protected function agencyStaffIds()
    {
        return User::where('agency_id', Auth::user()->agency_id)->pluck('id');
    }

    protected function activityQuery(): Builder
    {
        $agencyStaffIds = $this->agencyStaffIds();

        return ActivityLogEntry::with('causer')
            ->where(function ($q) use ($agencyStaffIds) {
                $q->where(fn ($sq) => $sq->where('causer_type', User::class)->whereIn('causer_id', $agencyStaffIds))
                  ->orWhereNull('causer_id');
            })
            ->when($this->userFilter, fn ($q) => $q->where('causer_type', User::class)->where('causer_id', $this->userFilter))
            ->when($this->actionFilter, fn ($q) => $q->where(function ($sq) {
                $sq->where('description', 'like', "%{$this->actionFilter}%")
                    ->orWhere('log_name', 'like', "%{$this->actionFilter}%")
                    ->orWhere('event', 'like', "%{$this->actionFilter}%");
            }))
            ->when($this->fromDate, fn ($q) => $q->whereDate('created_at', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate('created_at', '<=', $this->toDate))
            ->orderByDesc('created_at');
    }

    protected function legacyQuery(): Builder
    {
        $agencyStaffIds = $this->agencyStaffIds();

        return Audit::with('user')
            ->whereIn('user_id', $agencyStaffIds)
            ->when($this->userFilter, fn ($q) => $q->where('user_id', $this->userFilter))
            ->when($this->actionFilter, fn ($q) => $q->where('action', 'like', "%{$this->actionFilter}%"))
            ->when($this->fromDate, fn ($q) => $q->whereDate('created_at', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate('created_at', '<=', $this->toDate))
            ->orderByDesc('created_at');
    }

    /**
     * There's no PDF/Excel library installed in this app (no dompdf,
     * no maatwebsite/excel) and no shell access to add one, so "export"
     * here means a CSV — it opens directly in Excel/Sheets and needs no
     * new dependency. Respects the same filters and view (activity/legacy)
     * as the on-screen list.
     */
    public function exportCsv(): StreamedResponse
    {
        abort_unless(
            Auth::user()->can('compliance.manage') || Auth::user()->hasRole(['Admin', 'Super Admin']),
            403
        );

        if ($this->view === 'legacy') {
            $audits = $this->legacyQuery()->get();

            return response()->streamDownload(function () use ($audits) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['When', 'User', 'Action', 'Entity', 'IP Address']);

                foreach ($audits as $audit) {
                    fputcsv($out, [
                        $audit->created_at->format('Y-m-d H:i:s'),
                        $audit->user->name ?? 'System',
                        $audit->action,
                        $audit->entityLabel(),
                        $audit->ip_address,
                    ]);
                }

                fclose($out);
            }, 'audit-log-legacy-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
        }

        $activities = $this->activityQuery()->get();

        return response()->streamDownload(function () use ($activities) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['When', 'User', 'Log', 'Event', 'Description', 'Entity']);

            foreach ($activities as $activity) {
                fputcsv($out, [
                    $activity->created_at->format('Y-m-d H:i:s'),
                    $activity->causerLabel(),
                    $activity->log_name,
                    $activity->event,
                    $activity->description,
                    $activity->entityLabel(),
                ]);
            }

            fclose($out);
        }, 'audit-log-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $agencyStaffIds = $this->agencyStaffIds();
        $userOptions = User::whereIn('id', $agencyStaffIds)->orderBy('name')->pluck('name', 'id');

        return view('livewire.audit.audit-index', [
            'activities' => $this->view === 'activity' ? $this->activityQuery()->paginate(25) : null,
            'legacyAudits' => $this->view === 'legacy' ? $this->legacyQuery()->paginate(25) : null,
            'userOptions' => $userOptions,
        ]);
    }
}
