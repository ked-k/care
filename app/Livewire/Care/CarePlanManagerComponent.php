<?php
namespace App\Livewire\Care;

// use App\Services\CarePlanReportService;
use App\Models\care\CarePlan;
use App\Models\care\ServiceUser;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class CarePlanManagerComponent extends Component
{
    use WithFileUploads;

    public $carePlans       = [];
    public $serviceUsers    = [];
    public $showForm        = false;
    public $editingCarePlan = null;
    public $viewingCarePlan = null;
    public $showReport      = false;
    public $reportData      = null;
    public $reportFormat    = 'html'; // html or pdf

    // Form fields
    public $care_plan_id;
    public $service_user_id;
    public $title;
    public $summary;
    public $review_date;
    public $is_active = true;

    // JSON structured data
    public $care_needs           = [];
    public $risk_assessments     = [];
    public $daily_routine        = [];
    public $emergency_contacts   = [];
    public $equipment            = [];
    public $dietary_requirements = [];
    public $communication_needs  = [];
    public $goals                = [];

    // Temporary inputs
    public $new_care_need         = '';
    public $new_care_need_support = 'Standard';
    public $new_risk_type         = '';
    public $new_risk_level        = 'medium';
    public $new_risk_mitigation   = '';
    public $new_routine_time      = '';
    public $new_routine_task      = '';
    public $new_contact_name      = '';
    public $new_contact_phone     = '';
    public $new_contact_relation  = '';
    public $new_equipment         = '';
    public $new_dietary           = '';
    public $new_communication     = '';
    public $new_goal              = '';
    public $new_goal_target       = '';

    protected $rules = [
        'service_user_id' => 'required|exists:service_users,id',
        'title'           => 'required|string|max:255',
        'summary'         => 'nullable|string',
        'review_date'     => 'required|date|after:today',
        'is_active'       => 'boolean',
    ];

    public function mount()
    {
        // $this->loadCarePlans();
        // $this->loadServiceUsers();
    }

    public function loadCarePlans()
    {
        $agencyId        = auth()->user()->agency_id;
        $this->carePlans = CarePlan::with(['serviceUser', 'creator'])
            ->whereHas('serviceUser', function ($q) use ($agencyId) {
                $q->where('agency_id', $agencyId);
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function loadServiceUsers()
    {
        $agencyId           = auth()->user()->agency_id;
        $this->serviceUsers = ServiceUser::where('agency_id', $agencyId)
            ->orderBy('name')
            ->get();
    }

    public function createNew()
    {
        $this->resetForm();
        $this->showForm        = true;
        $this->editingCarePlan = null;
        $this->showReport      = false;
    }

    public function edit($id)
    {
        $carePlan              = CarePlan::findOrFail($id);
        $this->editingCarePlan = $carePlan;
        $this->care_plan_id    = $carePlan->id;
        $this->service_user_id = $carePlan->service_user_id;
        $this->title           = $carePlan->title;
        $this->summary         = $carePlan->summary;
        $this->review_date     = $carePlan->review_date?->format('Y-m-d');
        $this->is_active       = $carePlan->is_active;

        // Load JSON data
        $planData                   = $carePlan->plan_data ?? [];
        $this->care_needs           = $planData['care_needs'] ?? [];
        $this->risk_assessments     = $planData['risk_assessments'] ?? [];
        $this->daily_routine        = $planData['daily_routine'] ?? [];
        $this->emergency_contacts   = $planData['emergency_contacts'] ?? [];
        $this->equipment            = $planData['equipment'] ?? [];
        $this->dietary_requirements = $planData['dietary_requirements'] ?? [];
        $this->communication_needs  = $planData['communication_needs'] ?? [];
        $this->goals                = $planData['goals'] ?? [];

        $this->showForm   = true;
        $this->showReport = false;
    }

    public function viewReport($id)
    {
        $this->viewingCarePlan = CarePlan::with(['serviceUser', 'creator', 'tasks'])
            ->findOrFail($id);

        $reportService    = new CarePlanReportService($this->viewingCarePlan);
        $this->reportData = $reportService->generateReportData();
        $this->showReport = true;
        $this->showForm   = false;
    }

    public function downloadPDF($id)
    {
        $carePlan      = CarePlan::findOrFail($id);
        $reportService = new CarePlanReportService($carePlan);
        $pdf           = $reportService->generatePDF();

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'care-plan-' . $carePlan->id . '.pdf');
    }

    public function save()
    {
        $this->validate();

        $planData = [
            'care_needs'           => $this->care_needs,
            'risk_assessments'     => $this->risk_assessments,
            'daily_routine'        => $this->daily_routine,
            'emergency_contacts'   => $this->emergency_contacts,
            'equipment'            => $this->equipment,
            'dietary_requirements' => $this->dietary_requirements,
            'communication_needs'  => $this->communication_needs,
            'goals'                => $this->goals,
        ];

        if ($this->editingCarePlan) {
            $this->editingCarePlan->update([
                'service_user_id' => $this->service_user_id,
                'title'           => $this->title,
                'summary'         => $this->summary,
                'review_date'     => $this->review_date,
                'is_active'       => $this->is_active,
                'plan_data'       => $planData,
                'updated_by'      => auth()->id(),
            ]);
            session()->flash('message', 'Care Plan updated successfully!');
        } else {
            CarePlan::create([
                'id'              => Str::uuid(),
                'service_user_id' => $this->service_user_id,
                'title'           => $this->title,
                'summary'         => $this->summary,
                'review_date'     => $this->review_date,
                'is_active'       => $this->is_active,
                'plan_data'       => $planData,
                'created_by'      => auth()->id(),
                'updated_by'      => auth()->id(),
            ]);
            session()->flash('message', 'Care Plan created successfully!');
        }

        $this->showForm = false;
        $this->loadCarePlans();
        $this->resetForm();
    }

    public function delete($id)
    {
        $carePlan = CarePlan::findOrFail($id);
        $carePlan->delete();
        $this->loadCarePlans();
        session()->flash('message', 'Care Plan deleted successfully!');
    }

    // Array management methods
    public function addCareNeed()
    {
        if (! empty($this->new_care_need)) {
            $this->care_needs[] = [
                'id'            => Str::uuid(),
                'need'          => $this->new_care_need,
                'support_level' => $this->new_care_need_support,
                'frequency'     => 'Daily',
                'notes'         => '',
                'created_at'    => now()->toISOString(),
            ];
            $this->new_care_need         = '';
            $this->new_care_need_support = 'Standard';
        }
    }

    public function removeCareNeed($index)
    {
        unset($this->care_needs[$index]);
        $this->care_needs = array_values($this->care_needs);
    }

    public function addRiskAssessment()
    {
        if (! empty($this->new_risk_type)) {
            $this->risk_assessments[] = [
                'id'          => Str::uuid(),
                'type'        => $this->new_risk_type,
                'level'       => $this->new_risk_level,
                'mitigation'  => $this->new_risk_mitigation,
                'review_date' => now()->addMonths(3)->format('Y-m-d'),
                'created_at'  => now()->toISOString(),
            ];
            $this->new_risk_type       = '';
            $this->new_risk_level      = 'medium';
            $this->new_risk_mitigation = '';
        }
    }

    public function removeRiskAssessment($index)
    {
        unset($this->risk_assessments[$index]);
        $this->risk_assessments = array_values($this->risk_assessments);
    }

    public function addDailyRoutine()
    {
        if (! empty($this->new_routine_time) && ! empty($this->new_routine_task)) {
            $this->daily_routine[] = [
                'id'         => Str::uuid(),
                'time'       => $this->new_routine_time,
                'task'       => $this->new_routine_task,
                'notes'      => '',
                'created_at' => now()->toISOString(),
            ];
            $this->new_routine_time = '';
            $this->new_routine_task = '';
        }
    }

    public function removeDailyRoutine($index)
    {
        unset($this->daily_routine[$index]);
        $this->daily_routine = array_values($this->daily_routine);
    }

    public function addEmergencyContact()
    {
        if (! empty($this->new_contact_name) && ! empty($this->new_contact_phone)) {
            $this->emergency_contacts[] = [
                'id'         => Str::uuid(),
                'name'       => $this->new_contact_name,
                'phone'      => $this->new_contact_phone,
                'relation'   => $this->new_contact_relation,
                'is_primary' => count($this->emergency_contacts) === 0,
                'created_at' => now()->toISOString(),
            ];
            $this->new_contact_name     = '';
            $this->new_contact_phone    = '';
            $this->new_contact_relation = '';
        }
    }

    public function removeEmergencyContact($index)
    {
        unset($this->emergency_contacts[$index]);
        $this->emergency_contacts = array_values($this->emergency_contacts);
    }

    public function addEquipment()
    {
        if (! empty($this->new_equipment)) {
            $this->equipment[] = [
                'id'         => Str::uuid(),
                'item'       => $this->new_equipment,
                'notes'      => '',
                'created_at' => now()->toISOString(),
            ];
            $this->new_equipment = '';
        }
    }

    public function removeEquipment($index)
    {
        unset($this->equipment[$index]);
        $this->equipment = array_values($this->equipment);
    }

    public function addDietaryRequirement()
    {
        if (! empty($this->new_dietary)) {
            $this->dietary_requirements[] = [
                'id'          => Str::uuid(),
                'requirement' => $this->new_dietary,
                'severity'    => 'moderate',
                'created_at'  => now()->toISOString(),
            ];
            $this->new_dietary = '';
        }
    }

    public function removeDietaryRequirement($index)
    {
        unset($this->dietary_requirements[$index]);
        $this->dietary_requirements = array_values($this->dietary_requirements);
    }

    public function addCommunicationNeed()
    {
        if (! empty($this->new_communication)) {
            $this->communication_needs[] = [
                'id'               => Str::uuid(),
                'need'             => $this->new_communication,
                'support_required' => '',
                'created_at'       => now()->toISOString(),
            ];
            $this->new_communication = '';
        }
    }

    public function removeCommunicationNeed($index)
    {
        unset($this->communication_needs[$index]);
        $this->communication_needs = array_values($this->communication_needs);
    }

    public function addGoal()
    {
        if (! empty($this->new_goal)) {
            $this->goals[] = [
                'id'          => Str::uuid(),
                'description' => $this->new_goal,
                'target_date' => $this->new_goal_target,
                'status'      => 'In Progress',
                'progress'    => 0,
                'created_at'  => now()->toISOString(),
            ];
            $this->new_goal        = '';
            $this->new_goal_target = '';
        }
    }

    public function removeGoal($index)
    {
        unset($this->goals[$index]);
        $this->goals = array_values($this->goals);
    }

    public function resetForm()
    {
        $this->reset([
            'care_plan_id', 'service_user_id', 'title', 'summary',
            'review_date', 'is_active', 'care_needs', 'risk_assessments',
            'daily_routine', 'emergency_contacts', 'equipment',
            'dietary_requirements', 'communication_needs', 'goals',
        ]);
        $this->resetErrorBag();
    }

    public function cancel()
    {
        $this->showForm   = false;
        $this->showReport = false;
        $this->resetForm();
    }

    public function render()
    {
        return view('livewire.care.care-plan-manager-component');
    }
}
