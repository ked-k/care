<?php

namespace App\Models\care;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Consent extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'service_user_id','consent_type','granted','granted_by','granted_at','expires_at','revoked_at','notes'
    ];

    protected $casts = [
        'granted' => 'boolean',
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function serviceUser() { return $this->belongsTo(ServiceUser::class); }
    public function granter() { return $this->belongsTo(User::class, 'granted_by'); }
}
