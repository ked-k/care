<?php
namespace App\Livewire\Dashboard;

use App\Models\CarePlan;
use App\Models\care\SafeguardingReport;
use App\Models\MedicationAdministration;
use App\Models\PayrollRun;
use App\Models\ServiceUser;
use App\Models\Shift;
use App\Models\Timesheet;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

// #[Layout('layouts.main', 'content')]
class AnalyticsDashboardComponent extends Component
{
    #[Url]
    public string $range = '30'; // days

    public function mount(): void
    {
        // abort_unless(Auth::user()->hasAnyRole(['admin', 'manager']), 403);
    }

    protected function agencyId(): string
    {
        return Auth::user()->agency_id;
    }

    protected function periodStart(): Carbon
    {
        return now()->subDays((int) $this->range)->startOfDay();
    }

    // ---------- Service users ----------
    protected function serviceUserStats(): array
    {
        $agencyId = $this->agencyId();

        $active    = ServiceUser::where('agency_id', $agencyId)->count();
        $inactive  = ServiceUser::withTrashed()->where('agency_id', $agencyId)->count() - $active;
        $consented = ServiceUser::where('agency_id', $agencyId)->where('consent_status', true)->count();

        return [
            'active'          => $active,
            'inactive'        => $inactive,
            'consented'       => $consented,
            'consent_pending' => $active - $consented,
        ];
    }

    // ---------- Shifts ----------
    protected function shiftStats(): array
    {
        $shifts = Shift::where('agency_id', $this->agencyId())
            ->where('scheduled_start', '>=', $this->periodStart())
            ->get(['scheduled_start', 'scheduled_end', 'actual_end']);

        $scheduled = $shifts->count();
        $completed = $shifts->whereNotNull('actual_end')->count();
        $missed    = $shifts->filter(fn($s) => is_null($s->actual_end) && Carbon::parse($s->scheduled_end)->isPast())->count();
        $upcoming  = $scheduled - $completed - $missed;

        // Weekly completion rate for the last 8 weeks, for the trend line.
        $weekly = [];
        for ($i = 7; $i >= 0; $i--) {
            $weekStart  = now()->subWeeks($i)->startOfWeek();
            $weekEnd    = $weekStart->copy()->endOfWeek();
            $weekShifts = Shift::where('agency_id', $this->agencyId())
                ->whereBetween('scheduled_start', [$weekStart, $weekEnd])
                ->get(['actual_end']);
            $total    = $weekShifts->count();
            $done     = $weekShifts->whereNotNull('actual_end')->count();
            $weekly[] = $total > 0 ? round(($done / $total) * 100) : 0;
        }

        return compact('scheduled', 'completed', 'missed', 'upcoming', 'weekly');
    }

    // ---------- Medication adherence ----------
    protected function medicationStats(): array
    {
        $admins = MedicationAdministration::whereHas('medication.serviceUser', fn($q) => $q->where('agency_id', $this->agencyId()))
            ->where('scheduled_time', '>=', $this->periodStart())
            ->get(['status']);

        $total    = $admins->count();
        $given    = $admins->where('status', 'given')->count();
        $prompted = $admins->where('status', 'prompted')->count();
        $refused  = $admins->where('status', 'refused')->count();
        $missed   = $admins->where('status', 'missed')->count();

        $adherenceRate = $total > 0 ? round((($given + $prompted) / $total) * 100) : null;

        return compact('total', 'given', 'prompted', 'refused', 'missed', 'adherenceRate');
    }

    // ---------- Safeguarding ----------
    protected function safeguardingStats(): array
    {
        $reports = SafeguardingReport::whereHas('reportedBy', fn($q) => $q->where('agency_id', $this->agencyId()))
            ->get(['status']);

        $counts = [
            'open'          => $reports->where('status', 'open')->count(),
            'escalated'     => $reports->where('status', 'escalated')->count(),
            'investigating' => $reports->where('status', 'investigating')->count(),
            'resolved'      => $reports->where('status', 'resolved')->count(),
            'closed'        => $reports->where('status', 'closed')->count(),
        ];

        return $counts + [
            'total'            => array_sum($counts),
            'total_open_cases' => $counts['open'] + $counts['escalated'] + $counts['investigating'],
        ];
    }

    // ---------- Timesheets & payroll ----------
    protected function timesheetStats(): array
    {
        $timesheets = Timesheet::where('agency_id', $this->agencyId())
            ->where('week_commencing', '>=', $this->periodStart())
            ->get(['status']);

        return [
            'draft'     => $timesheets->where('status', 'draft')->count(),
            'submitted' => $timesheets->where('status', 'submitted')->count(),
            'approved'  => $timesheets->where('status', 'approved')->count(),
            'rejected'  => $timesheets->where('status', 'rejected')->count(),
            'paid'      => $timesheets->where('status', 'paid')->count(),
        ];
    }

    protected function payrollStats(): array
    {
        $latestPaidRun = PayrollRun::where('agency_id', $this->agencyId())
            ->where('status', 'paid')
            ->orderByDesc('pay_period_end')
            ->first();

        return [
            'latest_run' => $latestPaidRun,
        ];
    }

    // ---------- Care plans ----------
    protected function carePlanStats(): array
    {
        $plans = CarePlan::whereHas('serviceUser', fn($q) => $q->where('agency_id', $this->agencyId()))
            ->where('is_active', true)
            ->get(['review_date']);

        $overdue = $plans->filter(fn($p) => $p->review_date && $p->review_date->isPast())->count();
        $dueSoon = $plans->filter(fn($p) => $p->review_date && $p->review_date->between(now(), now()->addDays(14)))->count();

        return [
            'total_active' => $plans->count(),
            'overdue'      => $overdue,
            'due_soon'     => $dueSoon,
            'on_track'     => $plans->count() - $overdue - $dueSoon,
        ];
    }

    public function render()
    {
        return view('livewire.dashboard.analytics-dashboard', [
            'serviceUsers' => $this->serviceUserStats(),
            'shifts'       => $this->shiftStats(),
            'medications'  => $this->medicationStats(),
            'safeguarding' => $this->safeguardingStats(),
            'timesheets'   => $this->timesheetStats(),
            'payroll'      => $this->payrollStats(),
            'carePlans'    => $this->carePlanStats(),
        ]);
    }
}
