<?php

namespace App\Models\care;

use App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Audit extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['user_id','entity_type','entity_id','action','metadata','ip_address','device_info'];
    protected $casts = ['metadata' => 'array'];

    public function user() { return $this->belongsTo(User::class); }
}
