<?php

namespace App\Livewire\Alert;

use App\Models\Alert;
use App\Models\User;
use App\Support\AlertGenerator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Batch 16: the Alerts page — a real workflow around the late/missed
 * calls, missing records/evidence, and medication concerns App\Support\
 * AlertGenerator detects, instead of only a dashboard number. Every
 * alert is visible to any manager/admin at the agency (not a private
 * queue), but carries a named "assigned to" so it's always clear whose
 * job it is to act — see the care_coordinator_id field on ServiceUser.
 */
class AlertsManagerComponent extends Component
{
    use WithPagination;

    #[Url]
    public string $statusFilter = 'open'; // open|acknowledged|resolved|all — "open" here also includes acknowledged, see render()

    #[Url]
    public string $typeFilter = '';

    // Resolve drawer state
    public ?string $resolvingAlertId = null;
    public string $resolutionNotes = '';

    public array $responderOptions = [];

    public function mount(): void
    {
        abort_unless(! Auth::user()->isCarerOnly(), 403, __("You don't have access to alerts."));

        $this->responderOptions = User::where('agency_id', Auth::user()->agency_id)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Manager', 'Admin', 'Super Admin']))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        AlertGenerator::run(Auth::user()->agency);
    }

    public function refreshAlerts(): void
    {
        AlertGenerator::run(Auth::user()->agency);
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function acknowledge(string $alertId): void
    {
        $alert = Alert::where('agency_id', Auth::user()->agency_id)->findOrFail($alertId);
        $alert->acknowledge(Auth::user());
        $this->dispatch('toast', message: 'Alert acknowledged.', type: 'success');
    }

    public function openResolveForm(string $alertId): void
    {
        $this->resolvingAlertId = $alertId;
        $this->resolutionNotes = '';
        $this->resetErrorBag();
        $this->dispatch('open-drawer', 'alert-resolve-form');
    }

    public function resolve(): void
    {
        $this->validate(['resolutionNotes' => 'nullable|string|max:2000']);

        $alert = Alert::where('agency_id', Auth::user()->agency_id)->findOrFail($this->resolvingAlertId);
        $alert->resolve(Auth::user(), $this->resolutionNotes ?: null);

        $this->resolvingAlertId = null;
        $this->dispatch('close-drawer', 'alert-resolve-form');
        $this->dispatch('toast', message: 'Alert resolved.', type: 'success');
    }

    public function reopen(string $alertId): void
    {
        $alert = Alert::where('agency_id', Auth::user()->agency_id)->findOrFail($alertId);
        $alert->reopen();
        $this->dispatch('toast', message: 'Alert reopened.', type: 'success');
    }

    public function reassign(string $alertId, string $userId): void
    {
        $alert = Alert::where('agency_id', Auth::user()->agency_id)->findOrFail($alertId);
        $user = User::findOrFail($userId);
        $alert->reassign($user);
        $this->dispatch('toast', message: "Reassigned to {$user->name}.", type: 'success');
    }

    public function render()
    {
        $agencyId = Auth::user()->agency_id;

        $alerts = Alert::where('agency_id', $agencyId)
            ->with(['serviceUser', 'assignedTo', 'acknowledgedBy', 'resolvedBy'])
            ->when($this->statusFilter === 'open', fn ($q) => $q->whereIn('status', [Alert::STATUS_OPEN, Alert::STATUS_ACKNOWLEDGED]))
            ->when(in_array($this->statusFilter, ['acknowledged', 'resolved'], true), fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
            ->orderByRaw("status = 'open' desc")
            ->orderByDesc('created_at')
            ->paginate(20);

        $statusCounts = Alert::where('agency_id', $agencyId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = [
            'open' => $statusCounts->get(Alert::STATUS_OPEN, 0),
            'acknowledged' => $statusCounts->get(Alert::STATUS_ACKNOWLEDGED, 0),
            'resolved' => $statusCounts->get(Alert::STATUS_RESOLVED, 0),
        ];

        return view('livewire.alert.alerts-manager', compact('alerts', 'stats'));
    }
}
