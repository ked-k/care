<?php

namespace App\Livewire\DataProtection;

use App\Models\BreachReport;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BreachReportIndexComponent extends Component
{
    public string $formDescription = '';
    public string $formSeverity = 'low';

    public string $resolvingId = '';
    public string $actionTaken = '';
    public bool $reportedToIco = false;

    public function canManage(): bool
    {
        $user = Auth::user();
        return $user->can('data-protection.manage') || $user->hasRole(['Admin', 'Super Admin']);
    }

    protected function authorizeManage(): void
    {
        abort_unless($this->canManage(), 403, 'Only a manager can record the response to a data incident.');
    }

    public function openReportForm(): void
    {
        $this->reset(['formDescription']);
        $this->formSeverity = 'low';
        $this->dispatch('open-drawer', 'breach-report-form');
    }

    public function submitReport(): void
    {
        $this->validate([
            'formDescription' => 'required|string|max:4000',
            'formSeverity' => 'required|in:low,medium,high,critical',
        ]);

        $report = BreachReport::create([
            'reported_by' => Auth::id(),
            'agency_id' => Auth::user()->agency_id,
            'description' => $this->formDescription,
            'severity' => $this->formSeverity,
            'created_by' => Auth::id(),
        ]);

        AuditLogger::log('DATA_BREACH_REPORTED', $report, ['severity' => $this->formSeverity]);

        $this->dispatch('close-drawer', 'breach-report-form');
        $this->dispatch('toast', message: 'Incident reported.', type: 'success');
    }

    public function openResolveForm(string $breachId): void
    {
        $this->authorizeManage();
        $breach = BreachReport::findOrFail($breachId);
        $this->resolvingId = $breach->id;
        $this->actionTaken = $breach->action_taken ?? '';
        $this->reportedToIco = (bool) $breach->reported_to_ico;
        $this->dispatch('open-drawer', 'breach-resolve');
    }

    public function recordAction(): void
    {
        $this->authorizeManage();
        $this->validate(['actionTaken' => 'required|string|max:4000']);

        $breach = BreachReport::findOrFail($this->resolvingId);
        $breach->update([
            'action_taken' => $this->actionTaken,
            'reported_to_ico' => $this->reportedToIco,
            'updated_by' => Auth::id(),
        ]);

        AuditLogger::log('DATA_BREACH_ACTIONED', $breach, ['reported_to_ico' => $this->reportedToIco]);

        $this->dispatch('close-drawer', 'breach-resolve');
        $this->dispatch('toast', message: 'Action recorded.', type: 'success');
    }

    public function render()
    {
        $reports = BreachReport::with('reporter')
            ->where('agency_id', Auth::user()->agency_id)
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.data-protection.breach-report-index', [
            'reports' => $reports,
            'canManage' => $this->canManage(),
        ]);
    }
}
