<?php

namespace App\Models\care;

use App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BreachReport extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['reported_by','agency_id','description','severity','action_taken','reported_to_ico','evidence'];
    protected $casts = ['reported_to_ico' => 'boolean','evidence' => 'array'];

    public function reporter() { return $this->belongsTo(User::class, 'reported_by'); }
    public function agency() { return $this->belongsTo(Agency::class); }
}
