<?php

namespace App\Livewire\CarePlan;

use App\Models\CarePlan;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CarePlanShowComponent extends Component
{
    public string $carePlanId;

    public array $carerOptions = [];

    // Batch 31: the Monday (Y-m-d) of the week shown on the "Week chart"
    // tab — see chartDays()/render() below. Kept server-side rather than
    // just "now" so Prev/Next week navigation survives a Livewire
    // round-trip.
    public string $chartWeekStart;

    // Task form (drawer) state
    public ?string $editingTaskId = null;
    public string $formTitle = '';
    public string $formDescription = '';
    public string $formType = 'general';
    public string $formScheduledAt = '';
    public string $formDueAt = '';
    public string $formAssignedTo = '';
    public int $formPriority = 1;
    public bool $formRequiresPhoto = false;
    public bool $formRequiresSignature = false;

    // Batch 25: formRecurringPattern holds '', 'daily', 'weekly', 'monthly',
    // or 'custom' for the UI; a 'custom' selection is combined with
    // formRecurringCustomDays into the stored 'every_n_days:N' string — see
    // buildRecurringPattern()/applyStoredRecurringPattern() below. Keeping
    // the custom day count as its own field means the dropdown never has to
    // carry a fixed "every 3 days" option again.
    public string $formRecurringPattern = '';
    public int $formRecurringCustomDays = 3;

    public function mount(string $carePlanId): void
    {
        $this->carePlanId = $carePlanId;
        abort_unless(Auth::user()->canAccessServiceUser(CarePlan::findOrFail($carePlanId)->serviceUser), 403, __("You don't have access to this person's record."));

        $this->carerOptions = User::where('agency_id', Auth::user()->agency_id)
            ->orderBy('name')->pluck('name', 'id')->toArray();

        $this->chartWeekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();
    }

    public function prevChartWeek(): void
    {
        $this->chartWeekStart = Carbon::parse($this->chartWeekStart)->subDays(7)->toDateString();
    }

    public function nextChartWeek(): void
    {
        $this->chartWeekStart = Carbon::parse($this->chartWeekStart)->addDays(7)->toDateString();
    }

    public function goToCurrentChartWeek(): void
    {
        $this->chartWeekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();
    }

    /** The 7 Carbon dates (Mon-Sun) the "Week chart" tab currently shows. */
    protected function chartDays(): array
    {
        $start = Carbon::parse($this->chartWeekStart);

        return collect(range(0, 6))->map(fn ($i) => $start->copy()->addDays($i))->all();
    }

    protected function carePlan(): CarePlan
    {
        return CarePlan::with('serviceUser')->findOrFail($this->carePlanId);
    }

    /** Carers read care plans and complete tasks; changing the plan or its tasks is a manager/admin job. */
    public function canManage(): bool
    {
        return ! Auth::user()->isCarerOnly();
    }

    protected function authorizeManage(): void
    {
        abort_unless($this->canManage(), 403, __('Only a manager can change care plans.'));
    }

    /** A task on this care plan only — ids from the browser are never trusted on their own. */
    protected function task(string $taskId): Task
    {
        return Task::where('care_plan_id', $this->carePlanId)->findOrFail($taskId);
    }

    public function toggleActive(): void
    {
        $this->authorizeManage();
        $plan = $this->carePlan();
        $plan->update(['is_active' => ! $plan->is_active]);
        $this->dispatch('toast', message: $plan->is_active ? 'Care plan reactivated.' : 'Care plan deactivated.', type: 'success');
    }

    public function openCreateTaskForm(): void
    {
        $this->authorizeManage();
        $this->resetTaskForm();
        $this->dispatch('open-drawer', 'task-form');
    }

    public function openEditTaskForm(string $taskId): void
    {
        $this->authorizeManage();
        $task = $this->task($taskId);

        $this->editingTaskId = $task->id;
        $this->formTitle = $task->title;
        $this->formDescription = $task->description ?? '';
        $this->formType = $task->type ?? 'general';
        $this->formScheduledAt = $task->scheduled_at?->format('Y-m-d\TH:i') ?? '';
        $this->formDueAt = $task->due_at?->format('Y-m-d\TH:i') ?? '';
        $this->formAssignedTo = (string) $task->assigned_to;
        $this->formPriority = $task->priority;
        $this->formRequiresPhoto = $task->requires_photo;
        $this->formRequiresSignature = $task->requires_signature;
        $this->applyStoredRecurringPattern($task->recurring_pattern);
        $this->dispatch('open-drawer', 'task-form');
    }

    protected function resetTaskForm(): void
    {
        $this->reset([
            'editingTaskId', 'formTitle', 'formDescription', 'formScheduledAt', 'formDueAt',
            'formAssignedTo', 'formRequiresPhoto', 'formRequiresSignature', 'formRecurringPattern',
        ]);
        $this->formType = 'general';
        $this->formPriority = 1;
        $this->formRecurringCustomDays = 3;
    }

    /** Splits a stored 'every_n_days:N' pattern back into the dropdown value + its day count. */
    protected function applyStoredRecurringPattern(?string $pattern): void
    {
        if ($pattern && str_starts_with($pattern, 'every_n_days:')) {
            $this->formRecurringPattern = 'custom';
            $this->formRecurringCustomDays = max(1, (int) substr($pattern, 13));
        } else {
            $this->formRecurringPattern = $pattern ?? '';
        }
    }

    /** Combines the dropdown value back into the single string Task::recurring_pattern stores. */
    protected function buildRecurringPattern(): ?string
    {
        if ($this->formRecurringPattern === 'custom') {
            return 'every_n_days:'.max(1, $this->formRecurringCustomDays);
        }

        return $this->formRecurringPattern ?: null;
    }

    public function saveTask(): void
    {
        $this->authorizeManage();
        if ($this->editingTaskId) {
            $this->task($this->editingTaskId);
        }

        $this->validate([
            'formTitle' => 'required|string|max:255',
            'formAssignedTo' => 'nullable|in:'.implode(',', array_keys($this->carerOptions)),
            'formScheduledAt' => 'nullable|date',
            'formDueAt' => 'nullable|date',
            'formPriority' => 'required|integer|min:1|max:5',
            'formRecurringPattern' => 'nullable|in:daily,weekly,monthly,custom',
            'formRecurringCustomDays' => $this->formRecurringPattern === 'custom' ? 'required|integer|min:1|max:365' : 'nullable|integer|min:1|max:365',
        ]);

        $data = [
            'care_plan_id' => $this->carePlanId,
            'title' => $this->formTitle,
            'description' => $this->formDescription ?: null,
            'type' => $this->formType,
            'scheduled_at' => $this->formScheduledAt ?: null,
            'due_at' => $this->formDueAt ?: null,
            'assigned_to' => $this->formAssignedTo ?: null,
            'priority' => $this->formPriority,
            'requires_photo' => $this->formRequiresPhoto,
            'requires_signature' => $this->formRequiresSignature,
            'recurring_pattern' => $this->buildRecurringPattern(),
            'updated_by' => Auth::id(),
        ];

        if ($this->editingTaskId) {
            Task::whereKey($this->editingTaskId)->update($data);
        } else {
            $data['created_by'] = Auth::id();
            Task::create($data);
        }

        $this->resetTaskForm();
        $this->dispatch('close-drawer', 'task-form');
        $this->dispatch('toast', message: 'Task saved.', type: 'success');
    }

    public function deleteTask(string $taskId): void
    {
        $this->authorizeManage();
        $this->task($taskId)->delete();
        $this->dispatch('toast', message: 'Task removed.', type: 'warning');
    }

    public function render()
    {
        $plan = $this->carePlan();
        $tasks = $plan->tasks()->with(['assignee', 'latestLog'])->orderBy('due_at')->get();

        // Batch 31: "Week chart" tab — same care plan, same tasks, just
        // grouped by day across a 7-day window instead of one flat list,
        // so a repeating task's upcoming occurrences (now pre-generated by
        // tasks:generate-occurrences, Batch 30) are actually visible in
        // advance instead of only ever showing "today".
        $chartDays = $this->chartDays();
        $rangeStart = $chartDays[0]->copy()->startOfDay();
        $rangeEnd = $chartDays[6]->copy()->endOfDay();

        $chartQuery = $plan->tasks()->with(['assignee', 'latestLog'])
            ->where(function ($q) use ($rangeStart, $rangeEnd) {
                $q->whereBetween('due_at', [$rangeStart, $rangeEnd])
                  ->orWhere(function ($q2) use ($rangeStart, $rangeEnd) {
                      $q2->whereNull('due_at')->whereBetween('scheduled_at', [$rangeStart, $rangeEnd]);
                  });
            });

        // Same visibility rule as TaskListComponent::tasksQuery(): without
        // manage_tasks, a carer sees only what's assigned to them, not
        // every task on this client.
        if (! (Auth::user()->can('manage_tasks') ?? false)) {
            $chartQuery->where('assigned_to', Auth::id());
        }

        $chartTasks = $chartQuery->orderBy('due_at')->get()
            ->groupBy(fn (Task $task) => ($task->due_at ?? $task->scheduled_at)?->toDateString());

        return view('livewire.care-plan.care-plan-show', compact('plan', 'tasks', 'chartDays', 'chartTasks'));
    }
}
