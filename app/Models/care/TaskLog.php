<?php

namespace App\Models\care;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TaskLog extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['task_id','completed_by','status','notes','photo_id','completed_at'];

    protected $casts = ['completed_at' => 'datetime'];

    public function task() { return $this->belongsTo(Task::class); }
    public function completer() { return $this->belongsTo(User::class, 'completed_by'); }
    public function photo() { return $this->belongsTo(MediaFile::class, 'photo_id'); }
}
