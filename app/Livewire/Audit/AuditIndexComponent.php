<?php

namespace App\Livewire\Audit;

use App\Models\Audit;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * There's no PDF/Excel library installed in this app (no dompdf,
     * no maatwebsite/excel) and no shell access to add one, so "export"
     * here means a CSV — it opens directly in Excel/Sheets and needs no
     * new dependency. Respects the same filters as the on-screen list.
     */
    public function exportCsv(): StreamedResponse
    {
        abort_unless(
            Auth::user()->can('compliance.manage') || Auth::user()->hasRole(['Admin', 'Super Admin']),
            403
        );

        $agencyStaffIds = User::where('agency_id', Auth::user()->agency_id)->pluck('id');

        $audits = Audit::with('user')
            ->whereIn('user_id', $agencyStaffIds)
            ->when($this->userFilter, fn ($q) => $q->where('user_id', $this->userFilter))
            ->when($this->actionFilter, fn ($q) => $q->where('action', 'like', "%{$this->actionFilter}%"))
            ->when($this->fromDate, fn ($q) => $q->whereDate('created_at', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate('created_at', '<=', $this->toDate))
            ->orderByDesc('created_at')
            ->get();

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
        }, 'audit-log-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
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
