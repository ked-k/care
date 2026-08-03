<?php

namespace App\Models\care;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ComplianceCheck extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['agency_id','category','status','notes','next_due_at'];
    protected $casts = ['next_due_at' => 'datetime'];

    public function agency() { return $this->belongsTo(Agency::class); }
}
