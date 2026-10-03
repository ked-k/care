<?php

namespace App\Livewire\Rota;

use App\Models\CareTimelineEntry;
use App\Models\MediaFile;
use App\Models\Medication;
use App\Models\MedicationAdministration;
use App\Models\Shift;
use App\Models\ShiftTakeover;
use App\Models\Task;
use App\Models\TaskLog;
use App\Models\VisitCheckin;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * The carer's single mobile screen for one shift — "My Rota" links here
 * rather than to the old separate Tasks/MAR-chart pages, since on a phone
 * a carer wants one place to check in, work through today's tasks and
 * medications for this one visit, and check out, not three different
 * full-page navigations.
 *
 * Also the admin side of "take up any carer's session and fill in records
 * in case the carer did not": a manager/admin who isn't the shift's own
 * carer lands on a short reason-gate screen instead of the visit itself
 * (see render()'s $activeTakeover / needsTakeoverReason split); once they
 * give a reason, App\Models\ShiftTakeover tracks that they're covering it,
 * and every record they then create here is stamped with that takeover's
 * id so later views of the same record (the task list, the MAR chart) can
 * show plainly who actually entered it and why.
 */
#[Layout('layouts.carer')]
class ShiftVisitComponent extends Component
{
    use WithFileUploads;

    public string $shiftId;
    public bool $isOwnShift = true;
    public bool $canTakeOver = false;
    public ?string $activeTakeoverId = null;

    public string $tab = 'overview';

    // Takeover reason-gate form
    public string $takeoverReason = '';

    // Task completion drawer state (mirrors TaskListComponent)
    public ?string $completingTaskId = null;
    public string $completeStatus = 'completed';
    public string $completeNotes = '';
    public $completePhoto = null;
    public string $completeSignatureData = '';

    // Medication recording drawer state (mirrors MarChartComponent, trimmed to "today")
    public ?string $recordingMedicationId = null;
    public string $recordStatus = 'given';
    public string $recordActualTime = '';
    public string $recordNotes = '';
    public string $recordRefusalReason = '';
    public string $recordWitness = '';
    public $recordPhoto = null;

    // Quick note form
    public string $noteContent = '';
    public bool $noteVisibleToFamily = true;

    public function mount(string $shiftId): void
    {
        $shift = Shift::findOrFail($shiftId);

        abort_unless($shift->agency_id === Auth::user()->agency_id, 403);

        $this->shiftId = $shiftId;
        $this->isOwnShift = $shift->assigned_to === Auth::id();
        $this->canTakeOver = Auth::user()->can('manage_rota') || Auth::user()->hasRole(['Admin', 'Super Admin']);

        abort_unless($this->isOwnShift || $this->canTakeOver, 403, "You don't have access to this shift.");

        $this->activeTakeoverId = $this->isOwnShift ? null : $shift->activeTakeover()?->id;
    }

    protected function shift(): Shift
    {
        return Shift::with(['serviceUser', 'carer'])->findOrFail($this->shiftId);
    }

    #[Computed]
    public function activeTakeover(): ?ShiftTakeover
    {
        return $this->activeTakeoverId ? ShiftTakeover::find($this->activeTakeoverId) : null;
    }

    /**
     * True once we need to show the reason-gate instead of the real visit:
     * an admin/manager who isn't the assigned carer, with no takeover
     * already running for this shift.
     */
    public function needsTakeoverReason(): bool
    {
        return ! $this->isOwnShift && ! $this->activeTakeoverId;
    }

    public function startTakeover(): void
    {
        abort_unless($this->canTakeOver, 403);

        $this->validate(['takeoverReason' => 'required|string|max:500']);

        $shift = $this->shift();

        $takeover = ShiftTakeover::create([
            'shift_id' => $shift->id,
            'carer_id' => $shift->assigned_to,
            'admin_id' => Auth::id(),
            'reason' => $this->takeoverReason,
            'started_at' => now(),
        ]);

        $this->activeTakeoverId = $takeover->id;
        $this->takeoverReason = '';
        $this->dispatch('toast', message: 'Covering this shift. Everything you fill in will be logged under your name, marked as entered on behalf of '.($shift->carer->name ?? 'the assigned carer').'.', type: 'success');
    }

    public function endTakeover(): void
    {
        $this->activeTakeover?->end();
        $this->activeTakeoverId = null;
        $this->dispatch('toast', message: 'Stopped covering this shift.', type: 'success');
        $this->redirect(route('rota.builder', $this->shift()->rota_period_id), navigate: true);
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    // ===================== CHECK-IN / CHECK-OUT =====================

    public function checkIn(): void
    {
        $shift = $this->shift();

        if ($shift->openCheckin()) {
            return;
        }

        VisitCheckin::create([
            'shift_id' => $shift->id,
            'shift_takeover_id' => $this->activeTakeoverId,
            'user_id' => Auth::id(),
            'checkin_method' => 'manual',
            'checkin_time' => now(),
            'created_by' => Auth::id(),
        ]);

        $this->dispatch('toast', message: 'Checked in.', type: 'success');
    }

    public function checkOut(): void
    {
        $checkin = $this->shift()->openCheckin();

        if (! $checkin) {
            return;
        }

        $checkin->update(['checkout_time' => now(), 'updated_by' => Auth::id()]);
        $this->dispatch('toast', message: 'Checked out.', type: 'success');
    }

    // ===================== TASKS =====================

    #[Computed]
    public function tasks()
    {
        return Task::with(['carePlan.serviceUser', 'latestLog.takeover.admin'])
            ->where('shift_id', $this->shiftId)
            ->orderByDesc('priority')
            ->orderBy('due_at')
            ->get();
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
        $task = Task::with('carePlan.serviceUser')->findOrFail($this->completingTaskId);

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

        $log = $task->logs()->create([
            'shift_takeover_id' => $this->activeTakeoverId,
            'completed_by' => Auth::id(),
            'status' => $this->completeStatus,
            'notes' => $this->completeNotes ?: null,
            'photo_id' => $photoId,
            'completed_at' => now(),
        ]);

        if ($this->completeStatus === 'completed' && $task->recurring_pattern) {
            $task->generateNextOccurrence();
        }

        if ($photoId) {
            MediaFile::whereKey($photoId)->update(['related_type' => TaskLog::class, 'related_id' => $log->id]);
        }

        if ($this->completeSignatureData) {
            $this->storeSignature($log->id);
        }

        $this->recordTimelineEntry($task, $log);

        $this->completingTaskId = null;
        $this->dispatch('close-drawer', 'task-complete-form');
        $this->dispatch('toast', message: 'Task updated.', type: 'success');
    }

    protected function recordTimelineEntry(Task $task, TaskLog $log): void
    {
        $serviceUser = $task->carePlan?->serviceUser;

        if (! $serviceUser) {
            return;
        }

        $verb = match ($log->status) {
            'completed' => 'completed',
            'refused' => 'was refused by the service user for',
            'skipped' => 'skipped',
            default => $log->status,
        };

        $content = trim("{$task->title} — {$verb}." . ($log->notes ? " {$log->notes}" : ''));

        CareTimelineEntry::create([
            'service_user_id' => $serviceUser->id,
            'shift_takeover_id' => $this->activeTakeoverId,
            'entry_type' => $task->type ?: 'task',
            'content' => $content,
            'media_id' => $log->photo_id,
            'visible_to_family' => true,
            'metadata' => ['task_id' => $task->id, 'task_log_id' => $log->id, 'status' => $log->status],
            'created_by' => Auth::id(),
        ]);
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

    // ===================== MEDICATIONS (today only) =====================

    #[Computed]
    public function medications()
    {
        $shift = $this->shift();
        $date = $shift->scheduled_start->toDateString();
        $serviceUser = $shift->serviceUser;

        $allMeds = $serviceUser->medications()->where('is_active', true)->orderBy('medication_name')->get()
            ->filter(fn (Medication $m) => $m->isActiveOn(Carbon::parse($date)));

        $administrations = MedicationAdministration::whereIn('medication_id', $allMeds->pluck('id'))
            ->whereDate('scheduled_time', $date)
            ->with('administeredBy', 'takeover.admin')
            ->get()
            ->groupBy('medication_id');

        return $allMeds->map(function (Medication $med) use ($administrations, $date) {
            $admin = $administrations->get($med->id)?->first();

            $state = match (true) {
                (bool) $admin => $admin->status,
                $med->is_prn => 'prn',
                Carbon::parse($date.' '.($med->scheduledTimeFormatted() ?? '23:59'))->isPast() => 'overdue',
                default => 'upcoming',
            };

            return ['medication' => $med, 'administration' => $admin, 'state' => $state];
        })->values();
    }

    public function openRecordForm(string $medicationId): void
    {
        $this->recordingMedicationId = $medicationId;
        $this->reset(['recordNotes', 'recordRefusalReason', 'recordWitness', 'recordPhoto']);
        $this->recordStatus = 'given';

        $med = Medication::findOrFail($medicationId);
        $this->recordActualTime = $med->is_prn
            ? now()->format('Y-m-d\TH:i')
            : $this->shift()->scheduled_start->toDateString().'T'.($med->scheduledTimeFormatted() ?? now()->format('H:i'));

        $this->dispatch('open-drawer', 'mar-record-form');
    }

    public function recordAdministration(): void
    {
        $this->validate([
            'recordStatus' => 'required|in:given,prompted,refused,missed',
            'recordActualTime' => 'required|date',
            'recordRefusalReason' => $this->recordStatus === 'refused' ? 'required|string' : 'nullable|string',
            'recordPhoto' => 'nullable|image|max:5120',
        ]);

        $med = Medication::findOrFail($this->recordingMedicationId);
        $shift = $this->shift();

        $scheduledTime = $med->is_prn
            ? Carbon::parse($this->recordActualTime)
            : Carbon::parse($shift->scheduled_start->toDateString().' '.($med->scheduledTimeFormatted() ?? '00:00'));

        $photoId = null;
        if ($this->recordPhoto) {
            $path = $this->recordPhoto->store('medication-photos', 'public');
            $photoId = MediaFile::create([
                'file_name' => $this->recordPhoto->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $this->recordPhoto->getMimeType(),
                'file_size' => $this->recordPhoto->getSize(),
                'uploaded_by' => Auth::id(),
            ])->id;
        }

        MedicationAdministration::create([
            'medication_id' => $med->id,
            'administered_by' => Auth::id(),
            'shift_id' => $shift->id,
            'shift_takeover_id' => $this->activeTakeoverId,
            'scheduled_time' => $scheduledTime,
            'actual_time' => $this->recordStatus === 'missed' ? null : Carbon::parse($this->recordActualTime),
            'status' => $this->recordStatus,
            'refusal_reason' => $this->recordRefusalReason ?: null,
            'notes' => $this->recordNotes ?: null,
            'witness_signature' => $this->recordWitness ?: null,
            'photo_id' => $photoId,
            'created_by' => Auth::id(),
        ]);

        $this->recordingMedicationId = null;
        $this->dispatch('close-drawer', 'mar-record-form');
        $this->dispatch('toast', message: 'Administration recorded.', type: 'success');
    }

    // ===================== NOTES =====================

    #[Computed]
    public function timelineEntries()
    {
        return CareTimelineEntry::where('service_user_id', $this->shift()->service_user_id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
    }

    public function addNote(): void
    {
        $this->validate(['noteContent' => 'required|string|max:2000']);

        CareTimelineEntry::create([
            'service_user_id' => $this->shift()->service_user_id,
            'shift_takeover_id' => $this->activeTakeoverId,
            'entry_type' => 'note',
            'content' => $this->noteContent,
            'visible_to_family' => $this->noteVisibleToFamily,
            'created_by' => Auth::id(),
        ]);

        $this->reset(['noteContent']);
        $this->noteVisibleToFamily = true;
        $this->dispatch('toast', message: 'Note added.', type: 'success');
    }

    public function render()
    {
        $shift = $this->shift();

        return view('livewire.rota.shift-visit', [
            'shift' => $shift,
            'openCheckin' => $shift->openCheckin(),
        ]);
    }
}
