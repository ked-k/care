<?php

namespace App\Models\care;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Agency extends Model
{
    use HasUuids, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['name','slug','address','contact_email','phone','subscription_id','logo_path','settings'];
    protected $casts = [
        'settings' => 'array',
    ];

    public function users() { return $this->hasMany(User::class); }
    public function serviceUsers() { return $this->hasMany(ServiceUser::class); }
    public function subscriptions() { return $this->hasMany(Subscription::class); }
    public function complianceChecks() { return $this->hasMany(ComplianceCheck::class); }
}
