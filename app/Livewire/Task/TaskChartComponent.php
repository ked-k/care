<?php

namespace App\Livewire\Task;

use App\Models\CarePlan;
use App\Models\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Batch 32 — the caseload-wide follow-up to Batch 31's per-client Week
 * chart tab on a care plan's own page. Same 7-day grid idea, but one row
 * per active client instead of one client's tasks at a time, so a
 * manager can scan the whole caseload for gaps in one screen instead of
 * opening each client in turn.
 *
 * Deliberately its own small component rather than sharing a trait with
 * CarePlanShowComponent's chartWeekStart/prevChartWeek/etc. (Batch 31) —
 * that code already shipped and is in daily use; refactoring it to share
 * logic with a brand-new page wasn't worth the risk to something already
 * working. The week-navigation logic here is intentionally the same
 * shape, just not literally shared.
 */
class TaskChartComponent extends Component
{
    public string $weekStart;

    public function mount(): void
    {
        // Caseload-wide, so gated the same way Batch 30 confirmed the
        // per-client task list already is: manage_tasks, not an
        // Admin-vs-Manager split — both roles carry it.
        abort_unless(Auth::user()->can('manage_tasks') ?? false, 403, __("You don't have permission to view the caseload task chart."));

        $this->weekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();
    }

    public function prevWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->subDays(7)->toDateString();
    }

    public function nextWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->addDays(7)->toDateString();
    }

    public function goToCurrentWeek(): void
    {
        $this->weekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();
    }

    /** The 7 Carbon dates (Mon-Sun) currently shown. */
    protected function weekDays(): array
    {
        $start = Carbon::parse($this->weekStart);

        return collect(range(0, 6))->map(fn ($i) => $start->copy()->addDays($i))->all();
    }

    public function render()
    {
        $user = Auth::user();
        $days = $this->weekDays();
        $rangeStart = $days[0]->copy()->startOfDay();
        $rangeEnd = $days[6]->copy()->endOfDay();

        // Every active care plan in this agency — one row each. Inactive/
        // discharged plans are left out, same guard
        // GenerateRecurringTaskOccurrences (Batch 30) uses, so a client no
        // longer receiving care doesn't clutter this view.
        $plans = CarePlan::with('serviceUser')
            ->where('is_active', true)
            ->whereHas('serviceUser', fn ($q) => $q->where('agency_id', $user->agency_id))
            ->get()
            ->filter(fn (CarePlan $plan) => $plan->serviceUser !== null)
            ->sortBy(fn (CarePlan $plan) => $plan->serviceUser->name)
            ->values();

        $tasksFlat = Task::with(['assignee', 'latestLog'])
            ->whereIn('care_plan_id', $plans->pluck('id'))
            ->where(function ($q) use ($rangeStart, $rangeEnd) {
                $q->whereBetween('due_at', [$rangeStart, $rangeEnd])
                  ->orWhere(function ($q2) use ($rangeStart, $rangeEnd) {
                      $q2->whereNull('due_at')->whereBetween('scheduled_at', [$rangeStart, $rangeEnd]);
                  });
            })
            ->orderBy('due_at')
            ->get();

        // Batch 33: resolve each task's rota-derived carer before grouping,
        // so this caseload-wide view shows the same "who's actually
        // covering it" as the per-client list/week chart and the shift
        // visit screen, instead of whatever was copied onto the row the
        // last time its recurrence chain generated an occurrence.
        Task::attachEffectiveCarers($tasksFlat);

        $tasksByPlan = $tasksFlat->groupBy('care_plan_id');

        $rows = $plans->map(function (CarePlan $plan) use ($tasksByPlan) {
            $tasksByDay = $tasksByPlan->get($plan->id, collect())
                ->groupBy(fn (Task $task) => ($task->due_at ?? $task->scheduled_at)?->toDateString());

            return ['plan' => $plan, 'tasksByDay' => $tasksByDay];
        });

        return view('livewire.task.task-chart', [
            'days' => $days,
            'rows' => $rows,
        ]);
    }
}
