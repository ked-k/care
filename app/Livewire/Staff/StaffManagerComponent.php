<?php
namespace App\Livewire\Staff;

use App\Models\EmployeePayProfile;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

// #[Layout('layouts.main', 'content')]
class StaffManagerComponent extends Component
{
    use WithPagination;

    public string $search = '';

    // User fields
    public ?int $editingUserId  = null;
    public string $formName     = '';
    public string $formEmail    = '';
    public string $formPassword = '';
    public string $formRole     = 'carer';
    public bool $formIsActive   = true;

    // Pay profile fields
    public string $formEmployeeNo                  = '';
    public string $formJobTitle                    = '';
    public string $formEmploymentType              = 'full_time';
    public ?int $formManagerId                     = null;
    public float $formHourlyRate                   = 0;
    public float $formOvertimeMultiplier           = 1.5;
    public float $formWeeklyOvertimeThresholdHours = 40;
    public string $formPayFrequency                = 'weekly';
    public string $formBankName                    = '';
    public string $formBankAccountNo               = '';
    public string $formMobileMoneyNumber           = '';

    public array $roleOptions    = [];
    public array $managerOptions = [];

    public function mount(): void
    {
        // abort_unless(Auth::user()->hasAnyRole(['admin', 'manager']), 403);

        $this->roleOptions    = Role::pluck('name', 'name')->toArray();
        $this->managerOptions = User::where('agency_id', Auth::user()->agency_id)
            ->orderBy('name')->pluck('name', 'id')->toArray();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->dispatch('open-drawer', 'staff-form');
    }

    public function openEditForm(int $userId): void
    {
        $user    = User::findOrFail($userId);
        $profile = EmployeePayProfile::where('user_id', $user->id)->first();

        $this->editingUserId = $user->id;
        $this->formName      = $user->name;
        $this->formEmail     = $user->email;
        $this->formPassword  = '';
        $this->formRole      = $user->roles->first()?->name ?? 'carer';
        $this->formIsActive  = (bool) $user->is_active;

        $this->formEmployeeNo                   = $profile?->employee_no ?? '';
        $this->formJobTitle                     = $profile?->job_title ?? '';
        $this->formEmploymentType               = $profile?->employment_type ?? 'full_time';
        $this->formManagerId                    = $profile?->manager_id;
        $this->formHourlyRate                   = (float) ($profile?->hourly_rate ?? 0);
        $this->formOvertimeMultiplier           = (float) ($profile?->overtime_multiplier ?? 1.5);
        $this->formWeeklyOvertimeThresholdHours = (float) ($profile?->weekly_overtime_threshold_hours ?? 40);
        $this->formPayFrequency                 = $profile?->pay_frequency ?? 'weekly';
        $this->formBankName                     = $profile?->bank_name ?? '';
        $this->formBankAccountNo                = $profile?->bank_account_no ?? '';
        $this->formMobileMoneyNumber            = $profile?->mobile_money_number ?? '';

        $this->dispatch('open-drawer', 'staff-form');
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingUserId', 'formName', 'formEmail', 'formPassword',
            'formEmployeeNo', 'formJobTitle', 'formManagerId', 'formBankName',
            'formBankAccountNo', 'formMobileMoneyNumber',
        ]);
        $this->formRole                         = 'carer';
        $this->formIsActive                     = true;
        $this->formEmploymentType               = 'full_time';
        $this->formHourlyRate                   = 0;
        $this->formOvertimeMultiplier           = 1.5;
        $this->formWeeklyOvertimeThresholdHours = 40;
        $this->formPayFrequency                 = 'weekly';
    }

    public function saveStaff(): void
    {
        $this->validate([
            'formName'                         => 'required|string|max:100',
            'formEmail'                        => 'required|email|max:100|unique:users,email,' . ($this->editingUserId ?? 'NULL') . ',id',
            'formPassword'                     => $this->editingUserId ? 'nullable|min:8' : 'required|min:8',
            'formRole'                         => 'required|string|exists:roles,name',
            'formEmployeeNo'                   => 'required|string|max:255',
            'formHourlyRate'                   => 'required|numeric|min:0',
            'formOvertimeMultiplier'           => 'required|numeric|min:1',
            'formWeeklyOvertimeThresholdHours' => 'required|numeric|min:1',
        ]);

        $agencyId = Auth::user()->agency_id;

        $userData = [
            'name'      => $this->formName,
            'email'     => $this->formEmail,
            'agency_id' => $agencyId,
            'is_active' => $this->formIsActive,
        ];
        if ($this->formPassword) {
            $userData['password'] = Hash::make($this->formPassword);
        }

        if ($this->editingUserId) {
            $user = User::findOrFail($this->editingUserId);
            $user->update($userData);
        } else {
            $userData['uid']      = (string) Str::uuid();
            $userData['is_admin'] = false;
            $user                 = User::create($userData);
        }

        $user->syncRoles([$this->formRole]);

        EmployeePayProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'agency_id'                       => $agencyId,
                'manager_id'                      => $this->formManagerId ?: null,
                'employee_no'                     => $this->formEmployeeNo,
                'job_title'                       => $this->formJobTitle ?: null,
                'employment_type'                 => $this->formEmploymentType,
                'hourly_rate'                     => $this->formHourlyRate,
                'overtime_multiplier'             => $this->formOvertimeMultiplier,
                'weekly_overtime_threshold_hours' => $this->formWeeklyOvertimeThresholdHours,
                'pay_frequency'                   => $this->formPayFrequency,
                'bank_name'                       => $this->formBankName ?: null,
                'bank_account_no'                 => $this->formBankAccountNo ?: null,
                'mobile_money_number'             => $this->formMobileMoneyNumber ?: null,
                'status'                          => $this->formIsActive ? 'active' : 'inactive',
            ]
        );

        $this->resetForm();
        $this->dispatch('close-drawer', 'staff-form');
        $this->dispatch('toast', message: 'Staff member saved.', type: 'success');
    }

    public function toggleActive(int $userId): void
    {
        $user = User::findOrFail($userId);
        $user->update(['is_active' => ! $user->is_active]);

        EmployeePayProfile::where('user_id', $user->id)
            ->update(['status' => $user->is_active ? 'active' : 'inactive']);

        $this->dispatch('toast', message: $user->is_active ? 'Staff member reactivated.' : 'Staff member deactivated.', type: 'success');
    }

    public function render()
    {
        $agencyId = Auth::user()->agency_id;

        $staff = User::where('agency_id', $agencyId)
            ->with(['roles', 'payProfile'])
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(12);

        return view('livewire.staff.staff-manager', compact('staff'));
    }
}
