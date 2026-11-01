<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use HasRoles;

    public function get_roles()
    {
        $roles = [];
        foreach ($this->getRoleNames() as $key => $role) {
            $roles[$key] = $role;
        }

        return $roles;
    }

    protected $fillable = [
        'uid', 'name', 'first_name', 'last_name', 'email', 'password',
        // Batch 39: 'address' is a new column (see the migration's doc
        // comment — ProfileComponent has read/written it for a while with
        // no column to back it).
        'phone_number', 'address', 'agency_id', 'is_active', 'avatar', 'signature',
        'two_factor_auth_enabled', 'created_by', 'updated_by',
    ];

    protected $hidden = ['password', 'remember_token', 'two_factor_code'];

    protected $casts = [
        'email_verified_at'       => 'datetime',
        'is_active'               => 'boolean',
        'two_factor_auth_enabled' => 'boolean',
        'trusted_devices'         => 'array',
        'created_at'              => 'datetime',
        'updated_at'              => 'datetime',
        'password'                => 'hashed',
    ];

    // Relationships
    public function agency()
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    public function createdServiceUsers()
    {
        return $this->hasMany(ServiceUser::class, 'created_by');
    }

    public function updatedServiceUsers()
    {
        return $this->hasMany(ServiceUser::class, 'updated_by');
    }

    public function createdCarePlans()
    {
        return $this->hasMany(CarePlan::class, 'created_by');
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class, 'assigned_to');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function payProfile()
    {
        return $this->hasOne(EmployeePayProfile::class, 'user_id');
    }

    /**
     * Batch 39 (Staff Profile & Document Management): every document filed
     * in this staff member's digital personnel file — see StaffDocument.
     */
    public function staffDocuments()
    {
        return $this->hasMany(StaffDocument::class, 'user_id');
    }

    public function safeguardingReportsFiled()
    {
        return $this->hasMany(SafeguardingReport::class, 'reported_by');
    }

    public function safeguardingReportsEscalatedToMe()
    {
        return $this->hasMany(SafeguardingReport::class, 'escalated_to');
    }

    public function consentsGranted()
    {
        return $this->hasMany(Consent::class, 'granted_by');
    }

    /**
     * The service users this user is linked to as a family member (role
     * "Family"). Empty for staff accounts.
     */
    public function familyLinks()
    {
        return $this->hasMany(FamilyMember::class, 'user_id');
    }

    public function isFamilyMember(): bool
    {
        return $this->hasRole('Family');
    }

    /**
     * Batch 12: true for a plain carer account — the "Carer" role with none
     * of "Admin"/"Super Admin"/"Manager" — which gets the mobile-only carer
     * portal (layouts.carer, no admin sidebar) for Dashboard, My Rota,
     * Timesheets, Notifications and Profile, instead of the full desktop
     * admin layout every staff page rendered through by default. See
     * CHANGES12.md for why this was needed: the sidebar (config/menu.php)
     * has almost nothing gated by permission, so a carer logging in
     * previously saw the same full admin navigation (Service Users,
     * Safeguarding, Compliance, Rota Builder, Staff...) as an Admin.
     */
    public function isCarerOnly(): bool
    {
        return $this->hasRole('Carer') && ! $this->hasAnyRole(['Admin', 'Super Admin', 'Manager']);
    }

    /**
     * Whether this user may open a service user's record pages (medications,
     * MAR chart, timeline, consents, assessments, family, care plans). Staff
     * are limited to their own agency; a plain carer additionally only to the
     * people they have shifts with — the same "assigned" rule the carer
     * messaging and quick-note components already use.
     */
    public function canAccessServiceUser(ServiceUser $serviceUser): bool
    {
        if ($this->hasRole('Super Admin')) {
            return true;
        }

        if ($serviceUser->agency_id !== $this->agency_id) {
            return false;
        }

        return ! $this->isCarerOnly()
            || $this->shifts()->where('service_user_id', $serviceUser->id)->exists();
    }

    /** Payroll runs and other people's payslips — carers only ever see their own payslip. */
    public function canManagePayroll(): bool
    {
        return $this->can('approve_payroll');
    }

    /**
     * Batch 21: invoicing runs and invoices — a separate permission from
     * payroll since billing payers and paying carers are different
     * responsibilities that don't have to sit with the same person.
     */
    public function canManageInvoicing(): bool
    {
        return $this->can('manage_invoicing');
    }

    /**
     * Batch 40: the Shop workspace (products, orders, stock, payments) —
     * its own permission, since running a shop is a different job from
     * running care, and seeded to Admin only by ShopSeeder.
     */
    public function canManageShop(): bool
    {
        return $this->can('manage_shop');
    }

    /**
     * Batch 43: the Finance & Accounts workspace — ledger, sales invoices,
     * bills, bank, financial years and reports. Seeded to Admin only by
     * AccountingSeeder.
     */
    public function canManageAccounts(): bool
    {
        return $this->can('manage_accounts');
    }

    /**
     * Batch 23: who gets the floating "Start a conversation" button (see
     * App\Livewire\Messaging\NewConversationComponent) — Admin and Manager
     * were the starting ask, Carer was explicitly added, and Super Admin is
     * included as a superset of Admin everywhere else in this app. Family
     * is deliberately excluded: a family member can be *added* to a
     * conversation by one of these roles, but doesn't get the button to
     * start one themselves.
     */
    public function canStartConversations(): bool
    {
        return $this->hasAnyRole(['Super Admin', 'Admin', 'Manager', 'Carer']);
    }

    /**
     * Named `appNotifications`, not `notifications`, because Notifiable
     * (above) already defines a `notifications()` relation against
     * Laravel's own DatabaseNotification model — a different table shape
     * than App\Models\Notification. See that model's docblock.
     */
    public function appNotifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Batch 39: `avatar` has been a plain fillable column since this table
     * was created, but nothing ever wrote or read it until this batch
     * wired up a profile-photo upload on ProfileComponent/StaffManager. A
     * plain storage path, same convention as ServiceUser::photoUrl() —
     * no MediaFile row, no audit need for a decorative photo.
     */
    public function avatarUrl(): ?string
    {
        return $this->avatar ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->avatar) : null;
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return $this->first_name
            ? "{$this->first_name} {$this->last_name}"
            : $this->name;
    }

    public function getInitialsAttribute()
    {
        if ($this->first_name && $this->last_name) {
            return strtoupper(substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1));
        }
        return strtoupper(substr($this->name, 0, 2));
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByAgency($query, $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }
}
