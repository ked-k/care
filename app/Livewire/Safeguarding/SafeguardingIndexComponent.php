<?php

namespace App\Livewire\Safeguarding;

use App\Models\MediaFile;
use App\Models\SafeguardingReport;
use App\Models\ServiceUser;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class SafeguardingIndexComponent extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $statusFilter = '';

    public string $search = '';

    // "Report a concern" form (drawer) state
    public string $formServiceUserId = '';
    public string $formType = 'safeguarding_concern';
    public string $formDescription = '';
    public $formPhoto = null;

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openReportForm(): void
    {
        $this->reset(['formServiceUserId', 'formDescription', 'formPhoto']);
        $this->formType = 'safeguarding_concern';
        $this->resetErrorBag();
        $this->dispatch('open-drawer', 'safeguarding-report-form');
    }

    #[Computed]
    public function serviceUserOptions(): array
    {
        return ServiceUser::where('agency_id', Auth::user()->agency_id)
            ->orderBy('name')->pluck('name', 'id')->toArray();
    }

    public function canManage(): bool
    {
        $user = Auth::user();
        return $user->can('safeguarding.manage') || $user->hasRole(['Admin', 'Super Admin']);
    }

    public function submitReport(): void
    {
        $this->validate([
            'formType' => 'required|string|max:255',
            'formDescription' => 'required|string|max:4000',
            'formServiceUserId' => 'nullable|string',
            'formPhoto' => 'nullable|image|max:5120',
        ]);

        $photoId = null;
        if ($this->formPhoto) {
            $path = $this->formPhoto->store('safeguarding-photos', 'public');
            $photoId = MediaFile::create([
                'file_name' => $this->formPhoto->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $this->formPhoto->getMimeType(),
                'file_size' => $this->formPhoto->getSize(),
                'uploaded_by' => Auth::id(),
            ])->id;
        }

        $report = SafeguardingReport::create([
            'service_user_id' => $this->formServiceUserId ?: null,
            'reported_by' => Auth::id(),
            'type' => $this->formType,
            'description' => $this->formDescription,
            'photo_id' => $photoId,
            'created_by' => Auth::id(),
        ]);

        $report->reportOpened(Auth::user());

        if ($photoId) {
            MediaFile::whereKey($photoId)->update([
                'related_type' => SafeguardingReport::class,
                'related_id' => $report->id,
            ]);
        }

        $this->dispatch('close-drawer', 'safeguarding-report-form');
        $this->dispatch('toast', message: 'Safeguarding concern reported.', type: 'success');
        $this->redirectRoute('safeguarding.show', $report->id);
    }

    public function render()
    {
        $agencyId = Auth::user()->agency_id;

        $reports = SafeguardingReport::with(['serviceUser', 'reportedBy', 'escalatedTo'])
            ->where(function ($q) use ($agencyId) {
                $q->whereHas('serviceUser', fn ($sq) => $sq->where('agency_id', $agencyId))
                  ->orWhere(function ($q2) use ($agencyId) {
                      $q2->whereNull('service_user_id')
                         ->whereHas('reportedBy', fn ($rq) => $rq->where('agency_id', $agencyId));
                  });
            })
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, fn ($q) => $q->where('description', 'like', "%{$this->search}%"))
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('livewire.safeguarding.safeguarding-index', [
            'reports' => $reports,
            'canManage' => $this->canManage(),
        ]);
    }
}
