<?php

namespace App\Livewire\Timesheet;

use App\Models\ServiceUser;
use App\Models\Timesheet;
use App\Models\TimesheetEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class WeeklyTimesheetComponent extends Component
{
    public string $timesheetId;

    public array $header = [
        'employee_no' => '',
        'employee_name' => '',
        'manager_name' => '',
        'week_commencing' => '',
        'status' => 'draft',
    ];

    // Keyed by date (Y-m-d) => row data, always rendered Monday..Sunday even when blank.
    public array $rows = [];

    public array $serviceUserOptions = [];

    public float $weeklyTotalHours = 0;

    public float $overtimeHours = 0;

    public bool $canApprove = false;

    public bool $readOnly = false;

    public function mount(string $timesheetId): void
    {
        $this->timesheetId = $timesheetId;

        $this->serviceUserOptions = ServiceUser::where('agency_id', Auth::user()->agency_id)
            ->orderBy('name')->pluck('name', 'id')->toArray();

        $this->loadTimesheet();
    }

    public function loadTimesheet(): void
    {
        $timesheet = Timesheet::with(['entries', 'user', 'manager'])->findOrFail($this->timesheetId);

        $this->header = [
            'employee_no' => $timesheet->payProfile()?->employee_no ?? '',
            'employee_name' => $timesheet->user->name ?? '',
            'manager_name' => $timesheet->manager->name ?? '',
            'week_commencing' => $timesheet->week_commencing->toDateString(),
            'status' => $timesheet->status,
        ];

        $this->canApprove = Auth::user()->can('approve_timesheets') ?? false;
        $this->readOnly = in_array($timesheet->status, ['approved', 'paid']) && ! $this->canApprove;

        $entriesByDate = $timesheet->entries->keyBy(fn ($e) => $e->entry_date->toDateString());
        $weekStart = Carbon::parse($timesheet->week_commencing);

        $this->rows = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $weekStart->copy()->addDays($i);
            $key = $date->toDateString();
            $entry = $entriesByDate->get($key);

            $this->rows[$key] = [
                'day_label' => $date->format('l'),
                'date' => $date->format('d/m/y'),
                'day_shift_start' => $entry?->day_shift_start ? substr($entry->day_shift_start, 0, 5) : '',
                'day_shift_end' => $entry?->day_shift_end ? substr($entry->day_shift_end, 0, 5) : '',
                'night_shift_start' => $entry?->night_shift_start ? substr($entry->night_shift_start, 0, 5) : '',
                'night_shift_end' => $entry?->night_shift_end ? substr($entry->night_shift_end, 0, 5) : '',
                'break_minutes' => $entry?->break_minutes ?? 0,
                'total_hours' => (float) ($entry?->total_hours ?? 0),
                'service_user_id' => $entry?->service_user_id ?? '',
                'initials' => $entry?->initials ?? '',
            ];
        }

        $this->weeklyTotalHours = (float) $timesheet->total_regular_hours + (float) $timesheet->total_overtime_hours;
        $this->overtimeHours = (float) $timesheet->total_overtime_hours;
    }

    /**
     * Recompute a single row's total_hours live as start/end/break are edited,
     * without waiting for a save round-trip.
     */
    public function updated(string $name): void
    {
        if (! str_starts_with($name, 'rows.')) {
            return;
        }

        [, $date] = explode('.', $name, 3);
        $this->recalculateRow($date);
    }

    protected function recalculateRow(string $date): void
    {
        $row = $this->rows[$date];
        $minutes = 0;

        if ($row['day_shift_start'] && $row['day_shift_end']) {
            $minutes += $this->diffMinutes($row['day_shift_start'], $row['day_shift_end']);
        }
        if ($row['night_shift_start'] && $row['night_shift_end']) {
            $minutes += $this->diffMinutes($row['night_shift_start'], $row['night_shift_end']);
        }

        $minutes -= (int) $row['break_minutes'];

        $this->rows[$date]['total_hours'] = round(max($minutes, 0) / 60, 2);
    }

    protected function diffMinutes(string $start, string $end): int
    {
        $start = Carbon::createFromFormat('H:i', $start);
        $end = Carbon::createFromFormat('H:i', $end);
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay(); // shift crosses midnight
        }

        return $end->diffInMinutes($start);
    }

    /**
     * Persist all seven rows and roll the week up into regular/overtime totals.
     */
    public function save(): void
    {
        $timesheet = Timesheet::findOrFail($this->timesheetId);

        foreach ($this->rows as $date => $row) {
            if (! $row['day_shift_start'] && ! $row['night_shift_start']) {
                continue; // day not worked — leave no entry, matching the paper form's blank rows
            }

            TimesheetEntry::updateOrCreate(
                ['timesheet_id' => $timesheet->id, 'entry_date' => $date],
                [
                    'day_of_week' => strtolower(Carbon::parse($date)->englishDayOfWeek),
                    'day_shift_start' => $row['day_shift_start'] ?: null,
                    'day_shift_end' => $row['day_shift_end'] ?: null,
                    'night_shift_start' => $row['night_shift_start'] ?: null,
                    'night_shift_end' => $row['night_shift_end'] ?: null,
                    'break_minutes' => $row['break_minutes'] ?: 0,
                    'service_user_id' => $row['service_user_id'] ?: null,
                    'initials' => $row['initials'] ?: null,
                ]
            );
        }

        $timesheet->recalculateTotals();
        $this->loadTimesheet();

        $this->dispatch('toast', message: 'Timesheet saved.', type: 'success');
    }

    public function submit(): void
    {
        $this->save();
        Timesheet::findOrFail($this->timesheetId)->submit();
        $this->loadTimesheet();
        $this->dispatch('toast', message: 'Timesheet submitted for approval.', type: 'success');
    }

    public function approve(): void
    {
        abort_unless($this->canApprove, 403);

        Timesheet::findOrFail($this->timesheetId)->approve(Auth::id());
        $this->loadTimesheet();
        $this->dispatch('toast', message: 'Timesheet approved.', type: 'success');
    }

    public function reject(): void
    {
        abort_unless($this->canApprove, 403);

        Timesheet::findOrFail($this->timesheetId)->update(['status' => 'rejected']);
        $this->loadTimesheet();
        $this->dispatch('toast', message: 'Timesheet rejected — sent back to the carer.', type: 'warning');
    }

    public function render()
    {
        return view('livewire.timesheet.weekly-timesheet');
    }
}
