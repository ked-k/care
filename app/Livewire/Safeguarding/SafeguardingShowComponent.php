<?php

namespace App\Livewire\Safeguarding;

use App\Models\SafeguardingReport;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SafeguardingShowComponent extends Component
{
    public string $safeguardingReportId;

    public array $managerOptions = [];

    // Action drawer state
    public string $escalateToId = '';
    public string $escalateNote = '';
    public string $investigationNote = '';
    public string $resolutionNote = '';
    public string $closeNote = '';

    public function mount(string $safeguardingReportId): void
    {
        $this->safeguardingReportId = $safeguardingReportId;

        $this->managerOptions = User::where('agency_id', Auth::user()->agency_id)
            ->role(['Admin', 'Super Admin'])
            ->orderBy('name')->pluck('name', 'id')->toArray();
    }

    protected function report(): SafeguardingReport
    {
        return SafeguardingReport::with(['serviceUser', 'reportedBy', 'escalatedTo', 'photo'])
            ->findOrFail($this->safeguardingReportId);
    }

    public function canManage(): bool
    {
        $user = Auth::user();
        return $user->can('safeguarding.manage') || $user->hasRole(['Admin', 'Super Admin']);
    }

    protected function authorizeManage(): void
    {
        abort_unless($this->canManage(), 403, 'Only a manager can take this action.');
    }

    public function openEscalateForm(): void
    {
        $this->authorizeManage();
        $this->reset(['escalateToId', 'escalateNote']);
        $this->dispatch('open-drawer', 'safeguarding-escalate');
    }

    public function escalate(): void
    {
        $this->authorizeManage();
        $this->validate([
            'escalateToId' => 'required|exists:users,id',
            'escalateNote' => 'nullable|string|max:2000',
        ]);

        $this->report()->escalate(Auth::user(), User::findOrFail($this->escalateToId), $this->escalateNote ?: null);

        $this->dispatch('close-drawer', 'safeguarding-escalate');
        $this->dispatch('toast', message: 'Report escalated.', type: 'success');
    }

    public function openInvestigationForm(): void
    {
        $this->authorizeManage();
        $this->reset(['investigationNote']);
        $this->dispatch('open-drawer', 'safeguarding-investigate');
    }

    public function addInvestigationNote(): void
    {
        $this->authorizeManage();
        $this->validate(['investigationNote' => 'required|string|max:2000']);

        $this->report()->addInvestigationNote(Auth::user(), $this->investigationNote);

        $this->dispatch('close-drawer', 'safeguarding-investigate');
        $this->dispatch('toast', message: 'Investigation note added.', type: 'success');
    }

    public function openResolveForm(): void
    {
        $this->authorizeManage();
        $this->reset(['resolutionNote']);
        $this->dispatch('open-drawer', 'safeguarding-resolve');
    }

    public function markResolved(): void
    {
        $this->authorizeManage();
        $this->validate(['resolutionNote' => 'required|string|max:2000']);

        $this->report()->resolve(Auth::user(), $this->resolutionNote);

        $this->dispatch('close-drawer', 'safeguarding-resolve');
        $this->dispatch('toast', message: 'Report marked resolved.', type: 'success');
    }

    public function openCloseForm(): void
    {
        $this->authorizeManage();
        $this->reset(['closeNote']);
        $this->dispatch('open-drawer', 'safeguarding-close');
    }

    public function close(): void
    {
        $this->authorizeManage();
        $report = $this->report();
        abort_unless($report->canClose(), 422, 'Only a resolved report can be closed.');

        $report->close(Auth::user(), $this->closeNote ?: null);

        $this->dispatch('close-drawer', 'safeguarding-close');
        $this->dispatch('toast', message: 'Report closed.', type: 'success');
    }

    public function render()
    {
        $report = $this->report();

        return view('livewire.safeguarding.safeguarding-show', [
            'report' => $report,
            'timeline' => array_reverse($report->timeline()),
            'canManage' => $this->canManage(),
        ]);
    }
}
