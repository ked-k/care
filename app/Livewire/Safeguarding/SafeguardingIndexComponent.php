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
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $this->reset(['formServiceUserId', 'formType', 'formDescription', 'formPhoto']);
        $this->dispatch('close-drawer', 'safeguarding-report-form');
        $this->dispatch('toast', message: 'Safeguarding concern reported.', type: 'success');
        $this->redirectRoute('safeguarding.show', $report->id);
    }

    /** Batch 19: shared by render() and exportCsv() so export always matches the on-screen filters. */
    protected function reportsQuery()
    {
        $agencyId = Auth::user()->agency_id;

        return SafeguardingReport::with(['serviceUser', 'reportedBy', 'escalatedTo'])
            ->where(function ($q) use ($agencyId) {
                $q->whereHas('serviceUser', fn ($sq) => $sq->where('agency_id', $agencyId))
                  ->orWhere(function ($q2) use ($agencyId) {
                      $q2->whereNull('service_user_id')
                         ->whereHas('reportedBy', fn ($rq) => $rq->where('agency_id', $agencyId));
                  });
            })
            // A carer only sees reports they filed or about people they have shifts with.
            ->when(Auth::user()->isCarerOnly(), fn ($q) => $q->where(fn ($q2) => $q2
                ->where('reported_by', Auth::id())
                ->orWhereIn('service_user_id', Auth::user()->shifts()->select('service_user_id'))))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, fn ($q) => $q->where('description', 'like', "%{$this->search}%"))
            ->orderByDesc('created_at');
    }

    /**
     * Batch 19: manager-only — the raw description text is sensitive
     * enough that bulk-exporting it should be a deliberate manager
     * action, same as the Audit Log / Compliance Dashboard exports.
     */
    public function exportCsv(): StreamedResponse
    {
        abort_unless($this->canManage(), 403, __('Only a manager can export safeguarding records.'));

        $reports = $this->reportsQuery()->get();

        return response()->streamDownload(function () use ($reports) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Service User', 'Type', 'Reported By', 'Reported At', 'Status', 'Description']);

            foreach ($reports as $report) {
                fputcsv($out, [
                    $report->serviceUser->name ?? 'Not person-specific',
                    ucfirst(str_replace('_', ' ', $report->type)),
                    $report->reportedBy->name ?? '—',
                    $report->created_at->format('Y-m-d H:i'),
                    ucfirst($report->status),
                    $report->description,
                ]);
            }

            fclose($out);
        }, 'safeguarding-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $reports = $this->reportsQuery()->paginate(12);

        return view('livewire.safeguarding.safeguarding-index', [
            'reports' => $reports,
            'canManage' => $this->canManage(),
        ]);
    }
}
