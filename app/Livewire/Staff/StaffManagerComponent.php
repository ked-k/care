<?php
namespace App\Livewire\Staff;

use App\Mail\AccountAccessMail;
use App\Models\EmployeePayProfile;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
    public string $formRole     = 'Carer';
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
        // Batch 4: this was commented out — any authenticated staff member,
        // including a Carer, could open Staff Management and create/edit
        // any staff member, including assigning themselves an Admin role.
        abort_unless(Auth::user()->can('manage_user') || Auth::user()->hasRole(['Admin', 'Super Admin']), 403);

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
        $this->formRole      = $user->roles->first()?->name ?? 'Carer';
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
        $this->formRole                         = 'Carer';
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

        $isNewStaff = ! $this->editingUserId;
        // Typing a new password into the Edit drawer is, functionally, an
        // admin-initiated password reset — treat it the same as the
        // dedicated "Reset password" action below and email the person
        // their new details, rather than silently changing their password
        // with no way for them to know it happened.
        $passwordChangedOnEdit = ! $isNewStaff && (bool) $this->formPassword;

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

        $emailedOk = true;
        if ($isNewStaff || $passwordChangedOnEdit) {
            $emailedOk = $this->sendAccessEmail($user, $this->formPassword);
        }

        $this->resetForm();
        $this->dispatch('close-drawer', 'staff-form');

        $message = match (true) {
            $isNewStaff && $emailedOk => 'Staff member created. Login details emailed.',
            $isNewStaff => 'Staff member created. (Could not send the notification email — check mail settings.)',
            $passwordChangedOnEdit && $emailedOk => 'Staff member saved. New password emailed.',
            $passwordChangedOnEdit => 'Staff member saved. (Could not email the new password — check mail settings.)',
            default => 'Staff member saved.',
        };
        $this->dispatch('toast', message: $message, type: $emailedOk ? 'success' : 'warning');
    }

    /**
     * One-click alternative to the Edit drawer's password field: generates a
     * brand-new password for this staff member and emails it to them,
     * without the admin having to think one up. Covers "they never got (or
     * lost) their original login email" without the admin needing to know
     * or guess a new password themselves.
     */
    public function resetPassword(int $userId): void
    {
        abort_unless(Auth::user()->can('manage_user') || Auth::user()->hasRole(['Admin', 'Super Admin']), 403);

        $user = User::findOrFail($userId);

        // Staff ids are sequential integers, not UUIDs — unlike this
        // component's other actions (toggleActive has the same gap
        // pre-existing this batch), a password reset is sensitive enough
        // that it's worth explicitly confirming the target account is in
        // the acting admin's own agency rather than just any account.
        abort_unless($user->agency_id === Auth::user()->agency_id, 403);

        $newPassword = Str::password(12);
        $user->update(['password' => Hash::make($newPassword)]);

        $emailedOk = $this->sendAccessEmail($user, $newPassword);

        $this->dispatch('toast', message: $emailedOk
            ? 'Password reset. New login details emailed to '.$user->email.'.'
            : 'Password reset, but the email could not be sent — check mail settings.',
            type: $emailedOk ? 'success' : 'warning');
    }

    protected function sendAccessEmail(User $user, string $plainPassword): bool
    {
        try {
            Mail::to($user->email)->send(new AccountAccessMail(user: $user, plainPassword: $plainPassword));

            return true;
        } catch (\Throwable $e) {
            Log::warning('Failed to send staff access email: '.$e->getMessage());

            return false;
        }
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
