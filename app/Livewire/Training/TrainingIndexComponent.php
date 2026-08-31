<?php

namespace App\Livewire\Training;

use App\Models\TrainingModule;
use App\Models\TrainingProgress;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TrainingIndexComponent extends Component
{
    // Create-module form (drawer) state
    public string $formTitle = '';
    public string $formDescription = '';
    public string $formUrl = '';
    public string $formCategory = 'other';
    public ?int $formDurationMinutes = null;

    // Log-progress form (drawer) state
    public string $loggingModuleId = '';
    public string $logStatus = 'completed';
    public string $logScore = '';

    public function canManage(): bool
    {
        $user = Auth::user();
        return $user->can('training.manage') || $user->hasRole(['Admin', 'Super Admin']);
    }

    protected function authorizeManage(): void
    {
        abort_unless($this->canManage(), 403, 'Only a manager can manage training modules.');
    }

    public function openCreateForm(): void
    {
        $this->authorizeManage();
        $this->reset(['formTitle', 'formDescription', 'formUrl', 'formDurationMinutes']);
        $this->formCategory = 'other';
        $this->dispatch('open-drawer', 'training-module-form');
    }

    public function saveModule(): void
    {
        $this->authorizeManage();

        $this->validate([
            'formTitle' => 'required|string|max:255',
            'formUrl' => 'nullable|url|max:255',
            'formDurationMinutes' => 'nullable|integer|min:1|max:1000',
        ]);

        TrainingModule::create([
            'title' => $this->formTitle,
            'description' => $this->formDescription ?: null,
            'url' => $this->formUrl ?: null,
            'category' => $this->formCategory,
            'duration_minutes' => $this->formDurationMinutes,
        ]);

        $this->dispatch('close-drawer', 'training-module-form');
        $this->dispatch('toast', message: 'Training module added.', type: 'success');
    }

    public function openLogForm(string $moduleId): void
    {
        $this->loggingModuleId = $moduleId;
        $this->logStatus = 'completed';
        $this->logScore = '';
        $this->dispatch('open-drawer', 'training-log-form');
    }

    public function logProgress(): void
    {
        $this->validate([
            'logStatus' => 'required|in:started,completed',
            'logScore' => 'nullable|numeric|min:0|max:100',
        ]);

        TrainingProgress::updateOrCreate(
            ['user_id' => Auth::id(), 'module_id' => $this->loggingModuleId],
            [
                'status' => $this->logStatus,
                'score' => $this->logScore !== '' ? $this->logScore : null,
                'completed_at' => $this->logStatus === 'completed' ? now() : null,
            ]
        );

        $this->dispatch('close-drawer', 'training-log-form');
        $this->dispatch('toast', message: 'Training progress updated.', type: 'success');
    }

    public function render()
    {
        $userId = Auth::id();

        $modules = TrainingModule::with(['progress' => fn ($q) => $q->where('user_id', $userId)])
            ->orderBy('category')->orderBy('title')
            ->get();

        return view('livewire.training.training-index', [
            'modules' => $modules,
            'canManage' => $this->canManage(),
        ]);
    }
}
