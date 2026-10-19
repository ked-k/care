<?php

namespace App\Livewire\Rota;

use App\Mail\RemoteCheckinMail;
use App\Models\CareTimelineEntry;
use App\Models\MediaFile;
use App\Models\Medication;
use App\Models\MedicationAdministration;
use App\Models\Notification;
use App\Models\Shift;
use App\Models\ShiftTakeover;
use App\Models\Task;
use App\Models\TaskLog;
use App\Models\User;
use App\Models\VisitCheckin;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
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

    // Locked: set only on the server, so the browser can't flip itself past the takeover gate.
    #[Locked]
    public string $shiftId;
    #[Locked]
    public bool $isOwnShift = true;
    #[Locked]
    public bool $canTakeOver = false;
    #[Locked]
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

    /** Every action that writes a record: the gate above is enforced here, not just hidden in the view. */
    protected function authorizeRecording(): void
    {
        abort_if($this->needsTakeoverReason(), 403, __('Give a reason for covering this shift first.'));
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

    /**
     * Batch 35: $lat/$lng come from the browser's navigator.geolocation,
     * read and passed in by shift-visit.blade.php's Check in button before
     * this method ever runs server-side — see that file's comment. Either
     * can be null (no GPS, permission denied, an old browser), which is
     * handled the same as "too far away": allowed only if the agency's
     * "allow remote check-in" setting is on, and always logged/notified
     * when it happens.
     */
    public function checkIn(?float $lat = null, ?float $lng = null): void
    {
        $this->authorizeRecording();
        $shift = $this->shift();

        if ($shift->openCheckin()) {
            return;
        }

        $geo = $this->evaluateCheckinLocation($shift, $lat, $lng);

        if ($geo['blocked']) {
            $this->dispatch('toast', message: $geo['message'], type: 'danger');

            return;
        }

        $checkin = VisitCheckin::create([
            'shift_id' => $shift->id,
            'shift_takeover_id' => $this->activeTakeoverId,
            'user_id' => Auth::id(),
            'checkin_method' => $geo['method'],
            'checkin_time' => now(),
            'latitude' => $lat,
            'longitude' => $lng,
            'location_verified' => $geo['verified'],
            'distance_from_location' => $geo['distance'],
            'deviation_reason' => $geo['deviationReason'],
            'created_by' => Auth::id(),
        ]);

        if ($geo['notifyOverride']) {
            $this->notifyRemoteCheckin($shift, $checkin, $geo['deviationReason']);
        }

        $this->dispatch('toast', message: $geo['message'], type: $geo['verified'] ? 'success' : 'warning');
    }

    /**
     * Batch 35: the geofenced check-in restriction. Decides whether a
     * check-in at ($lat, $lng) is close enough to the service user's
     * stored location (App\Models\ServiceUser::distanceInMetersTo()), and
     * what to do when it isn't — block it, or let it through as a logged,
     * notified override, per Agency::allowsRemoteCheckin(). Returns
     * everything checkIn() needs: blocked, verified, distance, a
     * deviation reason for the audit trail, whether to notify Admin/
     * Manager, the checkin_method to record, and the toast message.
     *
     * A service user with no location on file yet (no address, address
     * not yet geocoded, no manual pin) is treated exactly as before this
     * feature existed — a plain, unverified manual check-in — rather than
     * blocking every check-in until every client has a pinned location.
     */
    protected function evaluateCheckinLocation(Shift $shift, ?float $lat, ?float $lng): array
    {
        $serviceUser = $shift->serviceUser;

        if (! $serviceUser || ! $serviceUser->hasLocation()) {
            return [
                'blocked' => false, 'verified' => false, 'distance' => null, 'deviationReason' => null,
                'notifyOverride' => false, 'method' => 'manual', 'message' => __('Checked in.'),
            ];
        }

        $agency = Auth::user()->agency;
        $allowRemote = $agency?->allowsRemoteCheckin() ?? false;
        $radius = $agency?->checkinRadiusMeters() ?? 150;

        if ($lat === null || $lng === null) {
            if (! $allowRemote) {
                return [
                    'blocked' => true, 'verified' => false, 'distance' => null, 'deviationReason' => null,
                    'notifyOverride' => false, 'method' => 'manual',
                    'message' => __("Check-in needs your location, and this agency doesn't allow remote check-in. Please enable location access on your device and try again at :name's address.", ['name' => $serviceUser->name]),
                ];
            }

            return [
                'blocked' => false, 'verified' => false, 'distance' => null,
                'deviationReason' => __('No location reached the server (denied, or unavailable on this device).'),
                'notifyOverride' => true, 'method' => 'remote_override',
                'message' => __('Checked in without a location — your manager has been notified.'),
            ];
        }

        $distance = $serviceUser->distanceInMetersTo($lat, $lng);

        if ($distance !== null && $distance <= $radius) {
            return [
                'blocked' => false, 'verified' => true, 'distance' => round($distance), 'deviationReason' => null,
                'notifyOverride' => false, 'method' => 'gps', 'message' => __('Checked in.'),
            ];
        }

        $roundedDistance = $distance !== null ? round($distance) : null;

        if (! $allowRemote) {
            return [
                'blocked' => true, 'verified' => false, 'distance' => $roundedDistance, 'deviationReason' => null,
                'notifyOverride' => false, 'method' => 'gps',
                'message' => __("You're too far from :name's address to check in (:distance m away). Get closer and try again, or ask a manager to turn on remote check-in if this address looks wrong.", [
                    'name' => $serviceUser->name,
                    'distance' => $roundedDistance ?? '?',
                ]),
            ];
        }

        return [
            'blocked' => false, 'verified' => false, 'distance' => $roundedDistance,
            'deviationReason' => __(':distance m from :name\'s recorded address.', ['distance' => $roundedDistance ?? '?', 'name' => $serviceUser->name]),
            'notifyOverride' => true, 'method' => 'remote_override',
            'message' => __("Checked in from outside :name's address — your manager has been notified.", ['name' => $serviceUser->name]),
        ];
    }

    /**
     * Notifies every Admin/Manager/Super Admin at this carer's agency —
     * "admin and manager are notified" was the explicit ask — both as an
     * in-app notification (App\Services\NotificationService, same as any
     * other alert in this app) and by email (App\Mail\RemoteCheckinMail),
     * so it's seen even by someone not currently logged in. A mail failure
     * (bad SMTP config, no connectivity) is logged and swallowed — it must
     * never undo or block a check-in that's already been recorded.
     */
    protected function notifyRemoteCheckin(Shift $shift, VisitCheckin $checkin, ?string $deviationReason): void
    {
        $carer = Auth::user();

        $recipients = User::where('agency_id', $carer->agency_id)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Manager', 'Admin', 'Super Admin']))
            ->get();

        $clientName = $shift->serviceUser->name ?? __('an unassigned visit');
        $title = __('Remote check-in: :carer', ['carer' => $carer->name]);
        $message = __(':carer checked in for :client outside the expected location.', [
            'carer' => $carer->name,
            'client' => $clientName,
        ]).($deviationReason ? ' '.$deviationReason : '');

        foreach ($recipients as $recipient) {
            NotificationService::send(
                $recipient->id,
                'remote_checkin',
                $title,
                $message,
                Notification::PRIORITY_HIGH,
                ['shift_id' => $shift->id, 'checkin_id' => $checkin->id, 'carer_id' => $carer->id]
            );

            if ($recipient->email) {
                try {
                    Mail::to($recipient->email)->send(new RemoteCheckinMail($recipient, $carer, $shift, $checkin, $deviationReason));
                } catch (\Throwable $e) {
                    Log::warning('RemoteCheckinMail failed to send: '.$e->getMessage(), ['recipient' => $recipient->id]);
                }
            }
        }
    }

    public function checkOut(): void
    {
        $this->authorizeRecording();
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
        $shift = $this->shift();

        // Tasks linked to this shift, plus every task on this client's
        // active care plan scheduled (or, if unscheduled, due) on the
        // shift's date.
        //
        // Batch 33: this used to also require assigned_to = $shift->assigned_to
        // — i.e. the task's own static carer column had to already match
        // today's rota carer, or the task silently never showed up here.
        // That's backwards: a care-plan task is permanent for the service
        // user — only WHO carries it out changes with the rota. Requiring
        // the two to already agree meant whoever covers a client via a
        // takeover, or on any day the rota assigned someone different from
        // whoever a recurring task happened to be created with, saw an
        // empty or short task list on a real visit. The rota (this shift)
        // is the source of truth for "who's covering this today" — it
        // doesn't also need the stored column's blessing.
        return Task::with(['carePlan.serviceUser', 'latestLog.takeover.admin'])
            ->where(fn ($q) => $q->where('shift_id', $this->shiftId)->orWhere(fn ($q2) => $q2
                ->whereHas('carePlan', fn ($cp) => $cp->where('service_user_id', $shift->service_user_id)->where('is_active', true))
                ->where(fn ($d) => $d->whereDate('scheduled_at', $shift->scheduled_start)
                    ->orWhere(fn ($d2) => $d2->whereNull('scheduled_at')->whereDate('due_at', $shift->scheduled_start)))))
            ->orderByDesc('priority')
            ->orderBy('due_at')
            ->get();
    }

    public function openCompleteForm(string $taskId): void
    {
        $this->authorizeRecording();
        abort_unless($this->tasks->contains('id', $taskId), 404);
        $this->completingTaskId = $taskId;
        $this->reset(['completeNotes', 'completePhoto', 'completeSignatureData']);
        $this->completeStatus = 'completed';
        $this->resetErrorBag();
        $this->dispatch('open-drawer', 'task-complete-form');
        $this->dispatch('reset-signature-pad');
    }

    public function completeTask(): void
    {
        $this->authorizeRecording();
        abort_unless($this->completingTaskId && $this->tasks->contains('id', $this->completingTaskId), 404);
        $task = Task::with('carePlan.serviceUser')->findOrFail($this->completingTaskId);

        $this->validate([
            // Batch 15: "partly_completed" added — see TaskListComponent's
            // version of this comment.
            'completeStatus' => 'required|in:completed,partly_completed,refused,skipped',
            'completeNotes' => $this->completeStatus === 'partly_completed'
                ? 'required|string|max:2000'
                : 'nullable|string|max:2000',
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
            'partly_completed' => 'was partly completed for',
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

        // Batch 15: same trailing-7-day refused/missed concern flag the MAR
        // chart shows, so a carer mid-visit sees it here too without having
        // to separately open the full chart.
        $concerns = MedicationAdministration::recentConcernCounts($allMeds->pluck('id'));

        return $allMeds->map(function (Medication $med) use ($administrations, $date, $concerns) {
            $admin = $administrations->get($med->id)?->first();

            $state = match (true) {
                (bool) $admin => $admin->status,
                $med->is_prn => 'prn',
                Carbon::parse($date.' '.($med->scheduledTimeFormatted() ?? '23:59'))->isPast() => 'overdue',
                default => 'upcoming',
            };

            return [
                'medication' => $med,
                'administration' => $admin,
                'state' => $state,
                'concern' => $concerns[$med->id] ?? null,
            ];
        })->values();
    }

    public function openRecordForm(string $medicationId): void
    {
        $this->authorizeRecording();
        abort_unless($this->medications->contains(fn ($m) => $m['medication']->id === $medicationId), 404);
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

        $this->authorizeRecording();
        abort_unless($this->recordingMedicationId && $this->medications->contains(fn ($m) => $m['medication']->id === $this->recordingMedicationId), 404);
        $med = Medication::findOrFail($this->recordingMedicationId);
        $shift = $this->shift();

        // Batch 15: a same-day duplicate-recording guard, mirroring
        // MarChartComponent::recordAdministration() — PRN doses are exempt
        // since more than one in a day is normal and expected for them.
        if (! $med->is_prn && MedicationAdministration::alreadyRecordedOn($med->id, $shift->scheduled_start->toDateString())) {
            $this->addError('recordStatus', __('This dose has already been recorded for today.'));
            $this->dispatch('toast', message: 'This dose has already been recorded for today.', type: 'error');
            return;
        }

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
        $this->authorizeRecording();
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
