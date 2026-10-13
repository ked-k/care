<?php
namespace App\Livewire\Rota;

use App\Models\RecurringVisitTemplate;
use App\Models\ServiceUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Batch 18: "set a repeating pattern once" for a service user's visits —
 * see App\Models\RecurringVisitTemplate. Manages both template_type
 * values (day/night Shift templates that feed payroll, and CareCall
 * welfare-check templates that don't) and runs generate() for a chosen
 * date range — there's no cron in this app, so nothing is created until
 * a manager explicitly asks for it here (or, for shift templates only,
 * from the "Generate from templates" button on the Rota Builder itself).
 */
class RecurringVisitTemplateComponent extends Component
{
    use WithPagination;

    public ?string $editingId = null;

    public string $formServiceUserId = '';
    public string $formCarerId       = '';
    public string $formTemplateType  = RecurringVisitTemplate::TYPE_SHIFT;
    public string $formShiftType     = 'day';
    public string $formCallLabel     = '';
    public array $formDaysOfWeek     = [];
    public string $formStartTime     = '';
    public string $formEndTime       = '';
    public int $formBreakMinutes     = 0;
    public string $formStartsOn      = '';
    public string $formEndsOn        = '';
    public bool $formIsActive        = true;
    public string $formNotes         = '';

    // "Generate" drawer — separate from the edit form. Null templateId
    // means "every active template for this agency", not just one.
    public ?string $generateTemplateId = null;
    public string $generateFrom        = '';
    public string $generateTo          = '';

    public array $serviceUsers = [];
    public array $carers       = [];

    public const DAY_LABELS = [0 => 'Sun', 1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat'];

    public function mount(): void
    {
        abort_unless(Auth::user()->can('manage_rota') || Auth::user()->hasRole(['Admin', 'Super Admin']), 403);

        $agencyId = Auth::user()->agency_id;

        $this->serviceUsers = ServiceUser::where('agency_id', $agencyId)->orderBy('name')->pluck('name', 'id')->toArray();
        $this->carers       = User::where('agency_id', $agencyId)->orderBy('name')->pluck('name', 'id')->toArray();
    }

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->formStartsOn = now()->toDateString();
        $this->dispatch('open-drawer', 'template-form');
    }

    public function openEditForm(string $id): void
    {
        $template = RecurringVisitTemplate::findOrFail($id);

        $this->editingId          = $template->id;
        $this->formServiceUserId  = $template->service_user_id;
        $this->formCarerId        = (string) $template->assigned_to;
        $this->formTemplateType   = $template->template_type;
        $this->formShiftType      = $template->shift_type ?? 'day';
        $this->formCallLabel      = $template->call_label ?? '';
        $this->formDaysOfWeek     = $template->days_of_week ?? [];
        $this->formStartTime      = $template->start_time ? substr($template->start_time, 0, 5) : '';
        $this->formEndTime        = $template->end_time ? substr($template->end_time, 0, 5) : '';
        $this->formBreakMinutes   = $template->break_minutes ?? 0;
        $this->formStartsOn       = $template->starts_on->toDateString();
        $this->formEndsOn         = $template->ends_on?->toDateString() ?? '';
        $this->formIsActive       = (bool) $template->is_active;
        $this->formNotes          = $template->notes ?? '';
        $this->dispatch('open-drawer', 'template-form');
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingId', 'formServiceUserId', 'formCarerId', 'formCallLabel',
            'formDaysOfWeek', 'formStartTime', 'formEndTime', 'formStartsOn', 'formEndsOn', 'formNotes',
        ]);
        $this->formTemplateType = RecurringVisitTemplate::TYPE_SHIFT;
        $this->formShiftType    = 'day';
        $this->formBreakMinutes = 0;
        $this->formIsActive     = true;
    }

    public function save(): void
    {
        $this->validate([
            'formServiceUserId' => 'required',
            'formTemplateType'  => 'required|in:shift,care_call',
            'formShiftType'     => 'required_if:formTemplateType,shift|in:day,night',
            'formCallLabel'     => 'required_if:formTemplateType,care_call|nullable|string|max:255',
            'formDaysOfWeek'    => 'required|array|min:1',
            'formStartTime'     => 'required',
            'formEndTime'       => 'required',
            'formStartsOn'      => 'required|date',
            'formEndsOn'        => 'nullable|date|after_or_equal:formStartsOn',
        ]);

        $isShift = $this->formTemplateType === RecurringVisitTemplate::TYPE_SHIFT;

        $data = [
            'agency_id'       => Auth::user()->agency_id,
            'service_user_id' => $this->formServiceUserId,
            'assigned_to'     => $this->formCarerId ?: null,
            'template_type'   => $this->formTemplateType,
            'shift_type'      => $isShift ? $this->formShiftType : null,
            'call_label'      => $isShift ? null : $this->formCallLabel,
            'days_of_week'    => array_values(array_map('intval', $this->formDaysOfWeek)),
            'start_time'      => $this->formStartTime,
            'end_time'        => $this->formEndTime,
            'break_minutes'   => $isShift ? $this->formBreakMinutes : 0,
            'starts_on'       => $this->formStartsOn,
            'ends_on'         => $this->formEndsOn ?: null,
            'is_active'       => $this->formIsActive,
            'notes'           => $this->formNotes ?: null,
            'updated_by'      => Auth::id(),
        ];

        if ($this->editingId) {
            RecurringVisitTemplate::whereKey($this->editingId)->update($data);
        } else {
            $data['created_by'] = Auth::id();
            RecurringVisitTemplate::create($data);
        }

        $this->resetForm();
        $this->dispatch('close-drawer', 'template-form');
        $this->dispatch('toast', message: 'Recurring template saved.', type: 'success');
    }

    public function toggleActive(string $id): void
    {
        $template = RecurringVisitTemplate::findOrFail($id);
        $template->update(['is_active' => ! $template->is_active]);

        $this->dispatch('toast', message: $template->is_active ? 'Template activated.' : 'Template deactivated.', type: 'success');
    }

    public function delete(string $id): void
    {
        RecurringVisitTemplate::whereKey($id)->delete();
        $this->dispatch('toast', message: 'Template removed.', type: 'warning');
    }

    public function openGenerateForm(?string $id = null): void
    {
        $this->generateTemplateId = $id;
        $this->generateFrom       = now()->toDateString();
        $this->generateTo         = now()->addDays(6)->toDateString();
        $this->dispatch('open-drawer', 'generate-form');
    }

    public function runGenerate(): void
    {
        $this->validate([
            'generateFrom' => 'required|date',
            'generateTo'   => 'required|date|after_or_equal:generateFrom',
        ]);

        $from = Carbon::parse($this->generateFrom);
        $to   = Carbon::parse($this->generateTo);

        $templates = $this->generateTemplateId
            ? RecurringVisitTemplate::whereKey($this->generateTemplateId)->get()
            : RecurringVisitTemplate::where('agency_id', Auth::user()->agency_id)->where('is_active', true)->get();

        $created = 0;
        foreach ($templates as $template) {
            $created += $template->generate($from, $to);
        }

        $this->generateTemplateId = null;
        $this->dispatch('close-drawer', 'generate-form');
        $this->dispatch('toast', message: $created > 0
            ? __(':n visit(s) generated.', ['n' => $created])
            : __('No new visits to generate — everything in that range is already covered.'), type: 'success');
    }

    public function render()
    {
        $templates = RecurringVisitTemplate::where('agency_id', Auth::user()->agency_id)
            ->with(['serviceUser', 'assignee'])
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('livewire.rota.recurring-visit-template', compact('templates'));
    }
}
