<?php

namespace App\Livewire\CareTimeline;

use App\Models\CareTimelineEntry;
use App\Models\MediaFile;
use App\Models\ServiceUser;
use App\Models\Shift;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Batch 13: "a quick way to put notes on a service user's assigned" — a
 * carer-only, mobile-first counterpart to TimelineIndexComponent (the
 * staff desktop page reached from the Service Users list). Deliberately
 * not just TimelineIndexComponent reused as-is: that page renders through
 * the default admin layout (no #[Layout]/->layout() branch) and isn't
 * scoped to "service users this carer is actually assigned to" — reusing
 * it here would reopen exactly the "carer lands on a full desktop page"
 * inconsistency Batch 12 fixed.
 *
 * Two steps, no URL parameter: a picker of the carer's assigned service
 * users, then a short note form for whichever one they tap — mirrors
 * App\Livewire\Family\CarerMessagesComponent's picker-then-detail shape so
 * the two new carer bottom-tab destinations behave the same way. Writes
 * into the same care_timeline_entries table TimelineIndexComponent uses,
 * so a note added here shows up in the normal timeline (and to family,
 * when marked visible) exactly like a staff-added one — no separate
 * "carer notes" store.
 */
class CarerQuickNoteComponent extends Component
{
    use WithFileUploads;

    public ?string $serviceUserId = null;

    public string $formContent = '';
    public bool $formVisibleToFamily = true;
    public $formPhoto = null;

    public function mount(): void
    {
        abort_unless(Auth::user()->isCarerOnly(), 403, __('This page is for carers.'));
    }

    protected function assignedServiceUserIds()
    {
        return Shift::where('assigned_to', Auth::id())->distinct()->pluck('service_user_id');
    }

    protected function isAssigned(string $serviceUserId): bool
    {
        return Shift::where('assigned_to', Auth::id())->where('service_user_id', $serviceUserId)->exists();
    }

    public function selectServiceUser(string $serviceUserId): void
    {
        abort_unless($this->isAssigned($serviceUserId), 403, __("You're not assigned to this person."));

        $this->serviceUserId = $serviceUserId;
        $this->reset(['formContent', 'formPhoto']);
        $this->formVisibleToFamily = true;
        $this->resetErrorBag();
    }

    public function backToList(): void
    {
        $this->serviceUserId = null;
        $this->reset(['formContent', 'formPhoto']);
    }

    public function addNote(): void
    {
        abort_unless($this->serviceUserId && $this->isAssigned($this->serviceUserId), 403, __("You're not assigned to this person."));

        $this->validate([
            'formContent' => 'required|string|max:2000',
            'formPhoto' => 'nullable|image|max:5120',
        ]);

        $mediaId = null;
        if ($this->formPhoto) {
            $path = $this->formPhoto->store('timeline-photos', 'public');
            $mediaId = MediaFile::create([
                'file_name' => $this->formPhoto->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $this->formPhoto->getMimeType(),
                'file_size' => $this->formPhoto->getSize(),
                'uploaded_by' => Auth::id(),
            ])->id;
        }

        CareTimelineEntry::create([
            'service_user_id' => $this->serviceUserId,
            'entry_type' => 'note',
            'content' => $this->formContent,
            'media_id' => $mediaId,
            'visible_to_family' => $this->formVisibleToFamily,
            'created_by' => Auth::id(),
        ]);

        $this->reset(['formContent', 'formPhoto']);
        $this->dispatch('toast', message: __('Note added.'), type: 'success');
        $this->backToList();
    }

    public function render()
    {
        $serviceUsers = ServiceUser::whereIn('id', $this->assignedServiceUserIds())
            ->orderBy('name')
            ->get();

        $serviceUser = $this->serviceUserId
            ? $serviceUsers->firstWhere('id', $this->serviceUserId)
            : null;

        return view('livewire.care-timeline.carer-quick-note', [
            'serviceUsers' => $serviceUsers,
            'serviceUser' => $serviceUser,
        ])->layout('layouts.carer');
    }
}
