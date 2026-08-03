<?php
namespace App\Models;

use App\Models\care\Agency;
use App\Models\care\CarePlan;
use App\Models\care\ServiceUser;
use App\Models\care\Shift;
use App\Models\care\Task;
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
        'phone_number', 'agency_id', 'is_active', 'avatar', 'signature',
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
