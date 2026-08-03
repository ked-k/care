<?php

namespace App\Models\care;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TrainingProgress extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['user_id','module_id','status','score','completed_at'];
    protected $casts = ['completed_at' => 'datetime','score' => 'decimal:2'];

    public function user() { return $this->belongsTo(User::class); }
    public function module() { return $this->belongsTo(TrainingModule::class, 'module_id'); }
}
