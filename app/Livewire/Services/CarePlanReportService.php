<?php
namespace App\Services;

use App\Models\care\CarePlan;
use App\Models\ServiceUser;
use Carbon\Carbon;
use PDF;

class CarePlanReportService
{
    protected $carePlan;
    protected $serviceUser;
    protected $agency;

    public function __construct(CarePlan $carePlan)
    {
        $this->carePlan    = $carePlan;
        $this->serviceUser = $carePlan->serviceUser;
        $this->agency      = $this->serviceUser->agency;
    }

    /**
     * Generate a complete care plan report data array
     */
    public function generateReportData(): array
    {
        $planData = $this->carePlan->plan_data ?? [];

        return [
            // Report Metadata
            'report_meta'          => [
                'generated_at' => Carbon::now()->format('d/m/Y H:i'),
                'generated_by' => auth()->user()->full_name,
                'report_id'    => 'CPR-' . strtoupper(substr($this->carePlan->id, 0, 8)),
                'version'      => '1.0',
            ],

            // Agency Information
            'agency'               => [
                'name'    => $this->agency->name,
                'address' => $this->agency->address,
                'phone'   => $this->agency->phone,
                'email'   => $this->agency->contact_email,
                'logo'    => $this->agency->logo_path,
            ],

            // Service User Information
            'service_user'         => [
                'name'                => $this->serviceUser->name,
                'display_name'        => $this->serviceUser->display_name,
                'age'                 => $this->serviceUser->age,
                'dob'                 => $this->serviceUser->dob?->format('F j, Y'),
                'gender'              => $this->serviceUser->gender,
                'address'             => $this->serviceUser->address,
                'nhs_number'          => $this->serviceUser->nhs_number,
                'next_of_kin_name'    => $this->serviceUser->next_of_kin_name,
                'next_of_kin_contact' => $this->serviceUser->next_of_kin_contact,
                'consent_status'      => $this->serviceUser->consent_status,
                'metadata'            => $this->serviceUser->metadata ?? [],
            ],

            // Care Plan Details
            'care_plan'            => [
                'title'       => $this->carePlan->title,
                'summary'     => $this->carePlan->summary,
                'review_date' => $this->carePlan->review_date?->format('F j, Y'),
                'is_active'   => $this->carePlan->is_active,
                'created_at'  => $this->carePlan->created_at?->format('F j, Y'),
                'created_by'  => $this->carePlan->creator?->full_name,
            ],

            // Care Needs Assessment
            'care_needs'           => $this->formatCareNeeds($planData['care_needs'] ?? []),

            // Risk Assessment
            'risk_assessments'     => $this->formatRiskAssessments($planData['risk_assessments'] ?? []),

            // Daily Routine
            'daily_routine'        => $this->formatDailyRoutine($planData['daily_routine'] ?? []),

            // Emergency Contacts
            'emergency_contacts'   => $planData['emergency_contacts'] ?? [],

            // Equipment & Aids
            'equipment'            => $planData['equipment'] ?? [],

            // Dietary Requirements
            'dietary_requirements' => $planData['dietary_requirements'] ?? [],

            // Communication Needs
            'communication_needs'  => $planData['communication_needs'] ?? [],

            // Goals & Outcomes
            'goals'                => $this->formatGoals($planData['goals'] ?? []),

            // Tasks Summary
            'tasks_summary'        => $this->getTasksSummary(),

            // Signatures
            'signatures'           => [
                'care_manager'                => auth()->user()->signature ?? null,
                'service_user_representative' => null,
                'date'                        => Carbon::now()->format('F j, Y'),
            ],
        ];
    }

    protected function formatCareNeeds(array $careNeeds): array
    {
        $formatted = [];
        foreach ($careNeeds as $need) {
            $formatted[] = [
                'need'          => $need['need'] ?? $need,
                'support_level' => $need['support_level'] ?? 'Standard',
                'frequency'     => $need['frequency'] ?? 'Daily',
                'notes'         => $need['notes'] ?? '',
            ];
        }
        return $formatted;
    }

    protected function formatRiskAssessments(array $risks): array
    {
        $formatted = [];
        foreach ($risks as $risk) {
            $level       = $risk['level'] ?? 'medium';
            $formatted[] = [
                'type'        => ucfirst($risk['type'] ?? $risk),
                'level'       => $level,
                'level_badge' => $this->getRiskBadge($level),
                'mitigation'  => $risk['mitigation'] ?? 'To be reviewed',
                'review_date' => $risk['review_date'] ?? 'Monthly',
            ];
        }
        return $formatted;
    }

    protected function formatDailyRoutine(array $routine): array
    {
        // Sort by time
        usort($routine, function ($a, $b) {
            return strcmp($a['time'] ?? '', $b['time'] ?? '');
        });

        return $routine;
    }

    protected function formatGoals(array $goals): array
    {
        $formatted = [];
        foreach ($goals as $goal) {
            $formatted[] = [
                'description' => $goal['description'] ?? '',
                'target_date' => $goal['target_date'] ?? 'Not specified',
                'status'      => $goal['status'] ?? 'In Progress',
                'progress'    => $goal['progress'] ?? 0,
            ];
        }
        return $formatted;
    }

    protected function getTasksSummary(): array
    {
        $tasks = $this->carePlan->tasks;

        return [
            'total'       => $tasks->count(),
            'by_type'     => $tasks->groupBy('type')->map->count(),
            'by_priority' => $tasks->groupBy('priority')->map->count(),
            'recurring'   => $tasks->whereNotNull('recurring_pattern')->count(),
        ];
    }

    protected function getRiskBadge(string $level): string
    {
        return match (strtolower($level)) {
            'high'   => 'danger',
            'medium' => 'warning',
            'low'    => 'success',
            default  => 'secondary'
        };
    }

    /**
     * Generate PDF report
     */
    public function generatePDF()
    {
        $data = $this->generateReportData();

        $pdf = PDF::loadView('pdf.care-plan-report', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf;
    }

    /**
     * Generate HTML report
     */
    public function generateHTML(): string
    {
        $data = $this->generateReportData();
        return view('pdf.care-plan-report', $data)->render();
    }
}
