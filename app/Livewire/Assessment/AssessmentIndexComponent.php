<?php

namespace App\Livewire\Assessment;

use App\Models\Assessment;
use App\Models\ServiceUser;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AssessmentIndexComponent extends Component
{
    public string $serviceUserId;

    public string $formAssessmentType = 'initial';

    /** @var array<int, array{question: string, answer: string}> */
    public array $formQuestions = [];

    public string $formScore = '';
    public string $formRiskLevel = 'low';
    public string $formRecommendations = '';
    public string $formReviewDate = '';

    public function mount(string $serviceUserId): void
    {
        $this->serviceUserId = $serviceUserId;
        $this->resetForm();
    }

    protected function serviceUser(): ServiceUser
    {
        return ServiceUser::where('agency_id', Auth::user()->agency_id)->findOrFail($this->serviceUserId);
    }

    protected function resetForm(): void
    {
        $this->reset(['formScore', 'formRecommendations', 'formReviewDate']);
        $this->formAssessmentType = 'initial';
        $this->formRiskLevel = 'low';
        $this->formQuestions = [['question' => '', 'answer' => '']];
    }

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->resetErrorBag();
        $this->dispatch('open-drawer', 'assessment-form');
    }

    public function addQuestionRow(): void
    {
        $this->formQuestions[] = ['question' => '', 'answer' => ''];
    }

    public function removeQuestionRow(int $index): void
    {
        unset($this->formQuestions[$index]);
        $this->formQuestions = array_values($this->formQuestions);

        if (empty($this->formQuestions)) {
            $this->formQuestions = [['question' => '', 'answer' => '']];
        }
    }

    public function saveAssessment(): void
    {
        $this->validate([
            'formAssessmentType' => 'required|string|max:255',
            'formRiskLevel' => 'required|in:low,medium,high',
            'formReviewDate' => 'nullable|date',
            'formScore' => 'nullable|numeric|min:0|max:999',
            'formQuestions.*.question' => 'nullable|string|max:500',
            'formQuestions.*.answer' => 'nullable|string|max:2000',
        ]);

        $qa = collect($this->formQuestions)
            ->filter(fn ($row) => trim($row['question'] ?? '') !== '')
            ->map(fn ($row) => ['question' => trim($row['question']), 'answer' => trim($row['answer'] ?? '')])
            ->values()
            ->toArray();

        if (empty($qa)) {
            $this->addError('formQuestions', __('Add at least one question and answer.'));
            return;
        }

        Assessment::create([
            'service_user_id' => $this->serviceUserId,
            'conducted_by' => Auth::id(),
            'assessment_type' => $this->formAssessmentType,
            'questions_and_answers' => $qa,
            'score' => $this->formScore !== '' ? $this->formScore : null,
            'risk_level' => $this->formRiskLevel,
            'recommendations' => $this->formRecommendations ?: null,
            'review_date' => $this->formReviewDate ?: null,
            'created_by' => Auth::id(),
        ]);

        $this->resetForm();
        $this->dispatch('close-drawer', 'assessment-form');
        $this->dispatch('toast', message: 'Assessment recorded.', type: 'success');
    }

    public function render()
    {
        $serviceUser = $this->serviceUser();

        $assessments = $serviceUser->assessments()
            ->with('conductedBy')
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.assessment.assessment-index', [
            'serviceUser' => $serviceUser,
            'assessments' => $assessments,
            'types' => Assessment::TYPES,
        ]);
    }
}
