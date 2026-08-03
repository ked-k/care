<?php

namespace App\Livewire\Task;

use App\Models\MediaFile;
use App\Models\Task;
use App\Models\TaskLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class TaskListComponent extends Component
{
    use WithFileUploads;

    public ?string $shiftId = null;

    public string $dateFilter;

    public string $statusFilter = '';

    // Completion drawer state
    public ?string $completingTaskId = null;
    public string $completeStatus = 'completed';
    public string $completeNotes = '';
    public $completePhoto = null;
    public string $completeSignatureData = ''; // base64 PNG, set by the signature-pad Alpine component

    public function mount(?string $shiftId = null): void
    {
        $this->shiftId = $shiftId;
        $this->dateFilter = now()->toDateString();
    }

    #[Computed]
    public function tasks()
    {
        $user = Auth::user();

        $query = Task::with(['carePlan.serviceUser', 'assignee', 'latestLog'])
            ->whereHas('carePlan.serviceUser', fn ($q) => $q->where('agency_id', $user->agency_id));

        if ($this->shiftId) {
            $query->where('shift_id', $this->shiftId);
        } else {
            if (! ($user->can('manage_tasks') ?? false)) {
                $query->where('assigned_to', $user->id);
            }

            $date = $this->dateFilter;
            $query->where(function ($q) use ($date) {
                $q->whereDate('scheduled_at', $date)
                  ->orWhere(function ($q2) use ($date) {
                      $q2->whereNull('scheduled_at')->whereDate('due_at', $date);
                  });
            });
        }

        $tasks = $query->orderByDesc('priority')->orderBy('due_at')->get();

        if ($this->statusFilter) {
            $tasks = $tasks->filter(fn (Task $t) => $t->status() === $this->statusFilter)->values();
        }

        return $tasks;
    }

    public function openCompleteForm(string $taskId): void
    {
        $this->completingTaskId = $taskId;
        $this->reset(['completeNotes', 'completePhoto', 'completeSignatureData']);
        $this->completeStatus = 'completed';
        $this->resetErrorBag();
        $this->dispatch('open-drawer', 'task-complete-form');
        $this->dispatch('reset-signature-pad');
    }

    public function completeTask(): void
    {
        $task = Task::findOrFail($this->completingTaskId);

        $this->validate([
            'completeStatus' => 'required|in:completed,refused,skipped',
            'completeNotes' => 'nullable|string|max:2000',
            'completePhoto' => ($task->requires_photo && $this->completeStatus === 'completed')
                ? 'required|image|max:5120'
                : 'nullable|image|max:5120',
        ]);

        if ($task->requires_signature && $this->completeStatus === 'completed' && ! $this->completeSignatureData) {
            $this->addError('completeSignatureData', __('A signature is required to complete this task.'));
            return;
        }

        $photoId = null;
        if ($this->completePhoto) {
            $path = $this->completePhoto->store('task-photos', 'public');
            $photoId = MediaFile::create([
                'file_name' => $this->completePhoto->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $this->completePhoto->getMimeType(),
                'file_size' => $this->completePhoto->getSize(),
                'uploaded_by' => Auth::id(),
            ])->id;
        }

        $log = $task->complete(Auth::id(), $this->completeStatus, $this->completeNotes ?: null, $photoId);

        if ($photoId) {
            MediaFile::whereKey($photoId)->update(['related_type' => TaskLog::class, 'related_id' => $log->id]);
        }

        if ($this->completeSignatureData) {
            $this->storeSignature($log->id);
        }

        $this->completingTaskId = null;
        $this->dispatch('close-drawer', 'task-complete-form');
        $this->dispatch('toast', message: 'Task updated.', type: 'success');
    }

    protected function storeSignature(string $taskLogId): void
    {
        [, $encoded] = explode(';base64,', $this->completeSignatureData);
        $bytes = base64_decode($encoded);
        $path = 'task-signatures/'.Str::uuid().'.png';

        Storage::disk('public')->put($path, $bytes);

        MediaFile::create([
            'file_name' => basename($path),
            'file_path' => $path,
            'file_type' => 'image/png',
            'file_size' => strlen($bytes),
            'uploaded_by' => Auth::id(),
            'related_type' => TaskLog::class,
            'related_id' => $taskLogId,
            'meta' => ['kind' => 'signature'],
        ]);
    }

    public function render()
    {
        return view('livewire.task.task-list', ['tasks' => $this->tasks]);
    }
}
