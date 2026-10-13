<?php
namespace App\Livewire\Compliance;

use App\Models\Assessment;
use App\Models\CarePlan;
use App\Models\Policy;
use App\Models\PolicyAcknowledgment;
use App\Models\SafeguardingReport;
use App\Models\ServiceUser;
use App\Models\TrainingProgress;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Batch 19: one cross-service-user worklist instead of checking care-plan
 * reviews, assessments and safeguarding separately (previously only
 * available as three separate aggregate counts on the Compliance
 * Dashboard) — plus, per this batch's scope, whether any carer actually
 * assigned to that service user has an outstanding mandatory-policy
 * acknowledgment or incomplete training module. The staff-side check
 * reuses exactly the same "mandatory policy" / "training completion"
 * concepts ComplianceDashboardComponent::metrics() already established —
 * see its own doc comment on why training compliance is a completion
 * ratio rather than a true mandatory-training figure (no "mandatory"
 * flag on TrainingModule).
 */
class MissingDocumentationComponent extends Component
{
    public bool $showCompliantToo = false;

    public function mount(): void
    {
        abort_unless($this->canManage(), 403, __('Only a manager can view this.'));
    }

    public function canManage(): bool
    {
        $user = Auth::user();

        return $user->can('compliance.manage') || $user->hasRole(['Admin', 'Super Admin']);
    }

    /**
     * One row per service user in the agency, with whatever's missing.
     * Shared by render() and exportCsv() so the export always matches
     * whichever rows are (or would be, before the toggle) on screen.
     */
    protected function buildRows(): Collection
    {
        $agencyId = Auth::user()->agency_id;

        $serviceUsers = ServiceUser::where('agency_id', $agencyId)
            ->with([
                'carePlans' => fn ($q) => $q->where('is_active', true),
                'assessments',
                'carers',
            ])
            ->orderBy('name')
            ->get();

        $openSafeguardingCounts = SafeguardingReport::whereIn('status', ['open', 'investigating'])
            ->whereIn('service_user_id', $serviceUsers->pluck('id'))
            ->get()
            ->groupBy('service_user_id')
            ->map(fn ($reports) => $reports->count());

        $activeStaffIds = User::where('agency_id', $agencyId)->where('is_active', true)->pluck('id');

        $mandatoryPolicies = Policy::where(fn ($q) => $q->where('agency_id', $agencyId)->orWhereNull('agency_id'))
            ->where('is_mandatory_reading', true)
            ->where('is_active', true)
            ->get();

        $acknowledgedByPolicy = PolicyAcknowledgment::whereIn('policy_id', $mandatoryPolicies->pluck('id'))
            ->whereIn('user_id', $activeStaffIds)
            ->get()
            ->groupBy('policy_id')
            ->map(fn ($acks) => $acks->pluck('user_id')->unique()->all());

        $incompleteTraining = TrainingProgress::whereIn('user_id', $activeStaffIds)
            ->where('status', '!=', TrainingProgress::STATUS_COMPLETED)
            ->with('module')
            ->get()
            ->groupBy('user_id');

        $carerNames = User::whereIn('id', $activeStaffIds)->pluck('name', 'id');

        return $serviceUsers->map(function (ServiceUser $su) use (
            $openSafeguardingCounts, $mandatoryPolicies, $acknowledgedByPolicy, $incompleteTraining, $carerNames
        ) {
            $activeCarePlan = $su->carePlans->first();
            $carePlanGap = match (true) {
                ! $activeCarePlan => __('No active care plan'),
                $activeCarePlan->review_date && $activeCarePlan->review_date->isPast() =>
                    __('Review overdue (due :date)', ['date' => $activeCarePlan->review_date->format('d M Y')]),
                default => null,
            };

            $latestAssessment = $su->assessments->sortByDesc('created_at')->first();
            $assessmentGap = match (true) {
                ! $latestAssessment => __('No assessment on file'),
                $latestAssessment->isDue() =>
                    __('Review overdue (due :date)', ['date' => $latestAssessment->review_date->format('d M Y')]),
                default => null,
            };

            $safeguardingCount = $openSafeguardingCounts->get($su->id, 0);

            // Batch 18 added carer availability etc., but "which carers are
            // assigned to this service user" already has two sources in
            // this app: the explicit service_user_carer pivot, and whoever
            // actually has a Shift with them. Neither is reliably complete
            // on its own, so this takes the union of both.
            $carerIds = $su->carers->pluck('id')
                ->merge($su->shifts()->whereNotNull('assigned_to')->distinct()->pluck('assigned_to'))
                ->unique()
                ->values();

            $staffGaps = collect();
            foreach ($mandatoryPolicies as $policy) {
                $acked = $acknowledgedByPolicy->get($policy->id, []);
                foreach ($carerIds as $carerId) {
                    if (! in_array($carerId, $acked, true)) {
                        $staffGaps->push(__(':carer hasn\'t acknowledged ":policy"', [
                            'carer' => $carerNames[$carerId] ?? __('Unknown carer'),
                            'policy' => $policy->title,
                        ]));
                    }
                }
            }
            foreach ($carerIds as $carerId) {
                foreach ($incompleteTraining->get($carerId, collect()) as $progress) {
                    $staffGaps->push(__(':carer hasn\'t completed ":module"', [
                        'carer' => $carerNames[$carerId] ?? __('Unknown carer'),
                        'module' => $progress->module->title ?? __('a training module'),
                    ]));
                }
            }

            return [
                'serviceUser' => $su,
                'carePlanGap' => $carePlanGap,
                'assessmentGap' => $assessmentGap,
                'safeguardingCount' => $safeguardingCount,
                'staffGaps' => $staffGaps->values(),
                'hasGap' => (bool) ($carePlanGap || $assessmentGap || $safeguardingCount > 0 || $staffGaps->isNotEmpty()),
            ];
        });
    }

    protected function visibleRows(): Collection
    {
        $rows = $this->buildRows();

        return $this->showCompliantToo ? $rows : $rows->filter(fn ($r) => $r['hasGap'])->values();
    }

    public function exportCsv(): StreamedResponse
    {
        abort_unless($this->canManage(), 403);

        $rows = $this->visibleRows();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Service User', 'Care Plan', 'Assessment', 'Open Safeguarding Cases', 'Staff Compliance Gaps']);

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['serviceUser']->name,
                    $row['carePlanGap'] ?? 'OK',
                    $row['assessmentGap'] ?? 'OK',
                    $row['safeguardingCount'],
                    $row['staffGaps']->implode('; ') ?: 'OK',
                ]);
            }

            fclose($out);
        }, 'missing-documentation-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        return view('livewire.compliance.missing-documentation', ['rows' => $this->visibleRows()]);
    }
}
