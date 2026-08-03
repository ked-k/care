<?php

namespace App\Livewire\CarePlan;

use App\Models\CarePlan;
use App\Models\ServiceUser;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class CarePlanIndexComponent extends Component
{
    use WithPagination;

    public string $newServiceUserId = '';
    public string $newTitle = '';
    public string $newSummary = '';
    public string $newReviewDate = '';

    public array $serviceUserOptions = [];

    public function mount(): void
    {
        $this->serviceUserOptions = ServiceUser::where('agency_id', Auth::user()->agency_id)
            ->orderBy('name')->pluck('name', 'id')->toArray();
    }

    public function createCarePlan(): void
    {
        $this->validate([
            'newServiceUserId' => 'required',
            'newTitle' => 'required|string|max:255',
            'newReviewDate' => 'nullable|date',
        ]);

        $plan = CarePlan::create([
            'service_user_id' => $this->newServiceUserId,
            'title' => $this->newTitle,
            'summary' => $this->newSummary ?: null,
            'review_date' => $this->newReviewDate ?: null,
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);

        $this->redirect(route('care-plans.show', $plan), navigate: true);
    }

    public function render()
    {
        $agencyId = Auth::user()->agency_id;

        $plans = CarePlan::whereHas('serviceUser', fn ($q) => $q->where('agency_id', $agencyId))
            ->with('serviceUser')
            ->withCount('tasks')
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.care-plan.care-plan-index', compact('plans'));
    }
}
