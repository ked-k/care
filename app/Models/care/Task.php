<?php

namespace App\Models\care;

use App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Task extends Model
{
    use HasUuids, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['care_plan_id','title','description','type','scheduled_at','due_at','assigned_to','meta'];
    protected $casts = [
        'scheduled_at' => 'datetime',
        'due_at' => 'datetime',
        'meta' => 'array',
    ];

    public function carePlan() { return $this->belongsTo(CarePlan::class); }
    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function logs() { return $this->hasMany(TaskLog::class); }
}
