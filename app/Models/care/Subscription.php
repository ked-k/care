<?php

namespace App\Models\care;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Subscription extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['agency_id','plan_name','staff_limit','amount','start_date','end_date','is_active','meta'];
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function agency() { return $this->belongsTo(Agency::class); }
}
