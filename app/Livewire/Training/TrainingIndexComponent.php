<?php

namespace App\Livewire\Training;

use App\Models\MediaFile;
use App\Models\TrainingModule;
use App\Models\TrainingProgress;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class TrainingIndexComponent extends Component
{
    use WithFileUploads;

    // Create-module form (drawer) state
    public string $formTitle = '';
    public string $formDescription = '';
    public string $formUrl = '';
    public string $formCategory = 'other';
    public ?int $formDurationMinutes = null;

    // Batch 36: whether this module is mandatory, and how often it must be
    // renewed (in days) — null means it never expires. See the training
    // matrix (TrainingMatrixComponent) for where these actually get used.
    public bool $formIsMandatory = false;
    public ?int $formRenewalPeriodDays = null;

    // Log-progress form (drawer) state
    public string $loggingModuleId = '';
    public string $logStatus = 'completed';
    public string $logScore = '';

    // Batch 36: optional certificate upload + an explicit expiry override —
    // left blank, a completed mandatory-with-renewal module defaults its
    // expiry from TrainingModule::defaultExpiryFrom().
    public $logCertificate = null;
    public string $logExpiresAt = '';

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
        $this->reset(['formTitle', 'formDescription', 'formUrl', 'formDurationMinutes', 'formIsMandatory', 'formRenewalPeriodDays']);
        $this->formCategory = 'other';
        $this->formIsMandatory = false;
        $this->dispatch('open-drawer', 'training-module-form');
    }

    public function saveModule(): void
    {
        $this->authorizeManage();

        $this->validate([
            'formTitle' => 'required|string|max:255',
            'formUrl' => 'nullable|url|max:255',
            'formDurationMinutes' => 'nullable|integer|min:1|max:1000',
            'formRenewalPeriodDays' => 'nullable|integer|min:1|max:3650',
        ]);

        TrainingModule::create([
            'title' => $this->formTitle,
            'description' => $this->formDescription ?: null,
            'url' => $this->formUrl ?: null,
            'category' => $this->formCategory,
            'duration_minutes' => $this->formDurationMinutes,
            'is_mandatory' => $this->formIsMandatory,
            'renewal_period_days' => $this->formRenewalPeriodDays ?: null,
        ]);

        $this->reset(['formTitle', 'formDescription', 'formUrl', 'formDurationMinutes', 'formIsMandatory', 'formRenewalPeriodDays']);
        $this->dispatch('close-drawer', 'training-module-form');
        $this->dispatch('toast', message: 'Training module added.', type: 'success');
    }

    public function openLogForm(string $moduleId): void
    {
        $this->loggingModuleId = $moduleId;
        $this->logStatus = 'completed';
        $this->logScore = '';
        $this->reset(['logCertificate', 'logExpiresAt']);
        $this->dispatch('open-drawer', 'training-log-form');
    }

    public function logProgress(): void
    {
        $this->validate([
            'logStatus' => 'required|in:started,completed',
            'logScore' => 'nullable|numeric|min:0|max:100',
            'logExpiresAt' => 'nullable|date',
            'logCertificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $module = TrainingModule::find($this->loggingModuleId);

        $expiresAt = $this->logExpiresAt !== ''
            ? $this->logExpiresAt
            : ($this->logStatus === 'completed' && $module ? $module->defaultExpiryFrom(now()) : null);

        $progress = TrainingProgress::updateOrCreate(
            ['user_id' => Auth::id(), 'module_id' => $this->loggingModuleId],
            [
                'status' => $this->logStatus,
                'score' => $this->logScore !== '' ? $this->logScore : null,
                'completed_at' => $this->logStatus === 'completed' ? now() : null,
                'expires_at' => $expiresAt,
                // Batch 36: logging fresh progress means any prior renewal
                // chase is stale — reset the clock so TrainingMatrixComponent
                // will reconsider this row next time it checks for reminders.
                'last_reminded_at' => null,
            ]
        );

        // Batch 36: optional certificate upload — same two-step MediaFile
        // pattern as task photos (App\Livewire\Task\TaskListComponent::
        // completeTask()): create the MediaFile row, store its id on the
        // owning record, then point the MediaFile's own morph columns back.
        if ($this->logCertificate) {
            $path = $this->logCertificate->store('training-certificates', 'public');

            $media = MediaFile::create([
                'file_name' => $this->logCertificate->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $this->logCertificate->getMimeType(),
                'file_size' => $this->logCertificate->getSize(),
                'uploaded_by' => Auth::id(),
            ]);

            $progress->update(['certificate_media_id' => $media->id]);

            MediaFile::whereKey($media->id)->update([
                'related_type' => TrainingProgress::class,
                'related_id' => $progress->id,
            ]);
        }

        $this->reset(['logCertificate', 'logExpiresAt']);
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
