<?php

namespace App\Livewire\Wellbeing;

use App\Models\Alert;
use App\Models\CareTimelineEntry;
use App\Models\MediaFile;
use App\Models\ServiceUser;
use App\Models\WellbeingObservation;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Batch 17: a structured wellbeing-observation log — nested on both the
 * carer's shift-visit screen and the standalone client-profile page, the
 * same "build it once, nest it everywhere" pattern as App\Livewire\
 * CareTimeline\CareCallTimelineComponent. Trusts whichever parent screen
 * mounted it to have already checked the carer belongs on this service
 * user's page; this only re-scopes to the carer's own agency.
 *
 * A "significant" severity automatically raises an Alert via
 * Alert::raiseFor() — the same named-responder path AlertGenerator's own
 * auto-detected alerts use — so a manager doesn't have to separately spot
 * it among routine observations. "Routine" and "concern" observations
 * don't raise anything; they're just logged for a manager reviewing
 * patterns over time.
 */
class WellbeingObservationComponent extends Component
{
    use WithFileUploads;

    public string $serviceUserId;
    public ?string $shiftTakeoverId = null;

    public string $formCategory = 'other';
    public string $formSeverity = WellbeingObservation::SEVERITY_ROUTINE;
    public string $formDescription = '';
    public string $formActionTaken = '';
    public string $formInformedPerson = '';
    public bool $formFollowUpRequired = false;
    public string $formFollowUpNotes = '';
    public $formPhoto = null;

    public function mount(string $serviceUserId, ?string $shiftTakeoverId = null): void
    {
        $this->serviceUserId = $serviceUserId;
        $this->shiftTakeoverId = $shiftTakeoverId;
    }

    protected function serviceUser(): ServiceUser
    {
        return ServiceUser::where('agency_id', Auth::user()->agency_id)->findOrFail($this->serviceUserId);
    }

    #[Computed]
    public function observations()
    {
        return WellbeingObservation::where('service_user_id', $this->serviceUserId)
            ->with('creator')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }

    public function openForm(): void
    {
        $this->reset(['formDescription', 'formActionTaken', 'formInformedPerson', 'formFollowUpNotes', 'formPhoto']);
        $this->formCategory = 'other';
        $this->formSeverity = WellbeingObservation::SEVERITY_ROUTINE;
        $this->formFollowUpRequired = false;
        $this->resetErrorBag();
        $this->dispatch('open-drawer', 'wellbeing-observation-form');
    }

    public function save(): void
    {
        $this->validate([
            'formCategory' => 'required|string|max:255',
            'formSeverity' => 'required|in:'.implode(',', [
                WellbeingObservation::SEVERITY_ROUTINE,
                WellbeingObservation::SEVERITY_CONCERN,
                WellbeingObservation::SEVERITY_SIGNIFICANT,
            ]),
            'formDescription' => 'required|string|max:2000',
            'formActionTaken' => 'nullable|string|max:2000',
            'formInformedPerson' => 'nullable|string|max:255',
            'formFollowUpNotes' => $this->formFollowUpRequired ? 'required|string|max:2000' : 'nullable|string|max:2000',
            'formPhoto' => 'nullable|image|max:5120',
        ]);

        $serviceUser = $this->serviceUser();

        $mediaId = null;
        if ($this->formPhoto) {
            $path = $this->formPhoto->store('wellbeing-photos', 'public');
            $mediaId = MediaFile::create([
                'file_name' => $this->formPhoto->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $this->formPhoto->getMimeType(),
                'file_size' => $this->formPhoto->getSize(),
                'uploaded_by' => Auth::id(),
            ])->id;
        }

        $observation = WellbeingObservation::create([
            'agency_id' => $serviceUser->agency_id,
            'service_user_id' => $serviceUser->id,
            'shift_takeover_id' => $this->shiftTakeoverId,
            'category' => $this->formCategory,
            'severity' => $this->formSeverity,
            'description' => $this->formDescription,
            'action_taken' => $this->formActionTaken ?: null,
            'informed_person' => $this->formInformedPerson ?: null,
            'follow_up_required' => $this->formFollowUpRequired,
            'follow_up_notes' => $this->formFollowUpNotes ?: null,
            'media_id' => $mediaId,
            'created_by' => Auth::id(),
        ]);

        CareTimelineEntry::create([
            'service_user_id' => $serviceUser->id,
            'shift_takeover_id' => $this->shiftTakeoverId,
            'entry_type' => 'wellbeing_observation',
            'content' => trim($observation->categoryLabel().' — '.$observation->description),
            'media_id' => $mediaId,
            'visible_to_family' => false,
            'metadata' => ['wellbeing_observation_id' => $observation->id, 'severity' => $observation->severity],
            'created_by' => Auth::id(),
        ]);

        if ($observation->isSignificant()) {
            $alert = Alert::raiseFor(
                $serviceUser,
                Alert::TYPE_URGENT_CONCERN,
                sprintf('%s: %s observation for %s — %s', __('Significant wellbeing observation'), $observation->categoryLabel(), $serviceUser->name, $observation->description),
            );
            $observation->update(['alert_id' => $alert->id]);
        }

        $this->dispatch('close-drawer', 'wellbeing-observation-form');
        $this->dispatch('toast', message: $observation->isSignificant()
            ? 'Observation logged and an alert was raised.'
            : 'Observation logged.', type: 'success');
    }

    public function render()
    {
        return view('livewire.wellbeing.wellbeing-observation');
    }
}
