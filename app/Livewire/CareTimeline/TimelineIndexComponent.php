<?php

namespace App\Livewire\CareTimeline;

use App\Models\CareTimelineEntry;
use App\Models\MediaFile;
use App\Models\ServiceUser;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * A staff-facing view of a service user's care timeline. Most entries here
 * arrive automatically (TaskListComponent::completeTask() logs one per
 * completed/refused/skipped task); this adds the missing piece — a manual
 * "add an update" action for anything that isn't a task completion (e.g. a
 * GP visit, a family call, a general wellbeing note).
 */
class TimelineIndexComponent extends Component
{
    use WithFileUploads;

    public string $serviceUserId;

    public string $formEntryType = 'note';
    public string $formContent = '';
    public bool $formVisibleToFamily = true;
    public $formPhoto = null;

    public function mount(string $serviceUserId): void
    {
        $this->serviceUserId = $serviceUserId;
    }

    protected function serviceUser(): ServiceUser
    {
        return ServiceUser::where('agency_id', Auth::user()->agency_id)->findOrFail($this->serviceUserId);
    }

    public function openCreateForm(): void
    {
        $this->reset(['formContent', 'formPhoto']);
        $this->formEntryType = 'note';
        $this->formVisibleToFamily = true;
        $this->dispatch('open-drawer', 'timeline-entry-form');
    }

    public function addEntry(): void
    {
        $this->validate([
            'formEntryType' => 'required|string|max:255',
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
            'entry_type' => $this->formEntryType,
            'content' => $this->formContent,
            'media_id' => $mediaId,
            'visible_to_family' => $this->formVisibleToFamily,
            'created_by' => Auth::id(),
        ]);

        $this->reset(['formContent', 'formPhoto']);
        $this->dispatch('close-drawer', 'timeline-entry-form');
        $this->dispatch('toast', message: 'Timeline updated.', type: 'success');
    }

    public function render()
    {
        $serviceUser = $this->serviceUser();

        $entries = $serviceUser->careTimelineEntries()
            ->with(['media', 'creator'])
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.care-timeline.timeline-index', compact('serviceUser', 'entries'));
    }
}
