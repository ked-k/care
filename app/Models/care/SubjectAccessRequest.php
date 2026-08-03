<?php

namespace App\Models\care;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SubjectAccessRequest extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['requested_by','service_user_id','type','status','fulfilled_by','notes'];

    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }
    public function serviceUser() { return $this->belongsTo(ServiceUser::class); }
    public function fulfiller() { return $this->belongsTo(User::class, 'fulfilled_by'); }
}
