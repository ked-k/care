<?php

namespace App\Livewire\Compliance;

use App\Models\BreachReport;
use App\Models\CarePlan;
use App\Models\ComplianceCheck;
use App\Models\MedicationAdministration;
use App\Models\Policy;
use App\Models\SafeguardingReport;
use App\Models\Shift;
use App\Models\TrainingProgress;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The metrics here are named after the vision doc's example dashboard, but
 * several are computed from what the current schema actually supports
 * rather than fields that don't exist yet (e.g. training modules have no
 * "mandatory" flag, so training compliance is a completion ratio, not a
 * true mandatory-training-compliance figure — see CHANGES.md).
 */
class ComplianceDashboardComponent extends Component
{
    // New compliance-check (drawer) state
    public string $formCategory = '';
    public string $formNextDueAt = '';
    public string $formNotes = '';

    public function canManage(): bool
    {
        $user = Auth::user();
        return $user->can('compliance.manage') || $user->hasRole(['Admin', 'Super Admin']);
    }

    protected function authorizeManage(): void
    {
        abort_unless($this->canManage(), 403, 'Only a manager can manage compliance checks.');
    }

    public function openCreateForm(): void
    {
        $this->authorizeManage();
        $this->reset(['formCategory', 'formNextDueAt', 'formNotes']);
        $this->dispatch('open-drawer', 'compliance-check-form');
    }

    public function saveCheck(): void
    {
        $this->authorizeManage();
        $this->validate([
            'formCategory' => 'required|string|max:255',
            'formNextDueAt' => 'nullable|date',
            'formNotes' => 'nullable|string|max:2000',
        ]);

        ComplianceCheck::create([
            'agency_id' => Auth::user()->agency_id,
            'category' => $this->formCategory,
            'next_due_at' => $this->formNextDueAt ?: null,
            'notes' => $this->formNotes ?: null,
            'created_by' => Auth::id(),
        ]);

        $this->dispatch('close-drawer', 'compliance-check-form');
        $this->dispatch('toast', message: 'Compliance check added.', type: 'success');
    }

    public function advanceCheck(string $checkId): void
    {
        $this->authorizeManage();
        $check = ComplianceCheck::findOrFail($checkId);

        $check->status = match ($check->status) {
            ComplianceCheck::STATUS_NOT_STARTED => ComplianceCheck::STATUS_IN_PROGRESS,
            ComplianceCheck::STATUS_IN_PROGRESS => ComplianceCheck::STATUS_COMPLETE,
            default => ComplianceCheck::STATUS_NOT_STARTED,
        };
        $check->updated_by = Auth::id();
        $check->save();
    }

    protected function metrics(): array
    {
        $agencyId = Auth::user()->agency_id;

        $carePlansQuery = CarePlan::where('is_active', true)
            ->whereHas('serviceUser', fn ($q) => $q->where('agency_id', $agencyId));

        $overdueReviews = (clone $carePlansQuery)->whereDate('review_date', '<', now())->count();
        $dueSoonReviews = (clone $carePlansQuery)
            ->whereBetween('review_date', [now()->toDateString(), now()->addDays(14)->toDateString()])
            ->count();

        $activeStaffIds = User::where('agency_id', $agencyId)->where('is_active', true)->pluck('id');

        $trainingTotal = TrainingProgress::whereIn('user_id', $activeStaffIds)->count();
        $trainingCompleted = TrainingProgress::whereIn('user_id', $activeStaffIds)
            ->where('status', 'completed')->count();
        $trainingCompliance = $trainingTotal > 0 ? round($trainingCompleted / $trainingTotal * 100) : null;

        $recentAdmins = MedicationAdministration::whereHas(
            'medication.serviceUser',
            fn ($q) => $q->where('agency_id', $agencyId)
        )->where('scheduled_time', '>=', now()->subDays(7))->get();
        $medicationCompliance = $recentAdmins->count() > 0
            ? round($recentAdmins->where('status', 'given')->count() / $recentAdmins->count() * 100)
            : null;

        $openSafeguarding = SafeguardingReport::whereIn('status', ['open', 'investigating'])
            ->where(function ($q) use ($agencyId) {
                $q->whereHas('serviceUser', fn ($sq) => $sq->where('agency_id', $agencyId))
                  ->orWhere(function ($q2) use ($agencyId) {
                      $q2->whereNull('service_user_id')
                         ->whereHas('reportedBy', fn ($rq) => $rq->where('agency_id', $agencyId));
                  });
            })->count();

        $missedVisits = Shift::where('agency_id', $agencyId)
            ->where('status', 'missed')
            ->whereBetween('scheduled_start', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        $mandatoryPolicies = Policy::where(function ($q) use ($agencyId) {
                $q->where('agency_id', $agencyId)->orWhereNull('agency_id');
            })
            ->where('is_mandatory_reading', true)->where('is_active', true)->get();

        $pendingAcknowledgments = 0;
        $activeStaffCount = $activeStaffIds->count();
        foreach ($mandatoryPolicies as $policy) {
            $acknowledged = $policy->acknowledgments()->whereIn('user_id', $activeStaffIds)->distinct('user_id')->count('user_id');
            $pendingAcknowledgments += max(0, $activeStaffCount - $acknowledged);
        }

        $openDataIncidents = BreachReport::where('agency_id', $agencyId)
            ->where(fn ($q) => $q->whereNull('action_taken')->orWhere('action_taken', ''))
            ->count();

        return [
            'care_plans_due' => $dueSoonReviews,
            'overdue_reviews' => $overdueReviews,
            'training_compliance' => $trainingCompliance,
            'medication_compliance' => $medicationCompliance,
            'open_safeguarding' => $openSafeguarding,
            'missed_visits' => $missedVisits,
            'pending_acknowledgments' => $pendingAcknowledgments,
            'open_data_incidents' => $openDataIncidents,
        ];
    }

    /**
     * Same caveat as the audit log's export: no PDF/Excel library is
     * installed and none can be added without shell/composer access, so
     * this is a CSV snapshot of the dashboard metrics plus the current
     * checklist — not a formatted PDF report.
     */
    public function exportCsv(): StreamedResponse
    {
        $this->authorizeManage();

        $metrics = $this->metrics();
        $checks = ComplianceCheck::where('agency_id', Auth::user()->agency_id)
            ->orderByRaw("status = 'complete'")
            ->orderBy('next_due_at')
            ->get();

        return response()->streamDownload(function () use ($metrics, $checks) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Compliance Dashboard Snapshot', now()->format('Y-m-d H:i')]);
            fputcsv($out, []);
            fputcsv($out, ['Metric', 'Value']);
            fputcsv($out, ['Care plans due for review', $metrics['care_plans_due']]);
            fputcsv($out, ['Overdue reviews', $metrics['overdue_reviews']]);
            fputcsv($out, ['Training compliance (%)', $metrics['training_compliance'] ?? 'n/a']);
            fputcsv($out, ['Medication compliance (%)', $metrics['medication_compliance'] ?? 'n/a']);
            fputcsv($out, ['Open safeguarding cases', $metrics['open_safeguarding']]);
            fputcsv($out, ['Missed visits (this week)', $metrics['missed_visits']]);
            fputcsv($out, ['Pending policy acknowledgments', $metrics['pending_acknowledgments']]);
            fputcsv($out, ['Open data incidents', $metrics['open_data_incidents']]);
            fputcsv($out, []);
            fputcsv($out, ['Compliance Checklist']);
            fputcsv($out, ['Category', 'Status', 'Next Due', 'Notes']);

            foreach ($checks as $check) {
                fputcsv($out, [
                    $check->category,
                    $check->isOverdue() ? 'Overdue' : ucfirst(str_replace('_', ' ', $check->status)),
                    $check->next_due_at?->format('Y-m-d') ?? '',
                    $check->notes,
                ]);
            }

            fclose($out);
        }, 'compliance-summary-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $checks = ComplianceCheck::where('agency_id', Auth::user()->agency_id)
            ->orderByRaw("status = 'complete'")
            ->orderBy('next_due_at')
            ->get();

        return view('livewire.compliance.compliance-dashboard', [
            'metrics' => $this->metrics(),
            'checks' => $checks,
            'canManage' => $this->canManage(),
        ]);
    }
}
