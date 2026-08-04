<?php
namespace App\Models\care;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SafeguardingReport extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'service_user_id', 'reported_by', 'type', 'description', 'photo_id',
        'location_lat', 'location_long', 'escalated_to', 'status', 'escalation_log',
    ];

    protected $casts = [
        'escalation_log' => 'array',
        'location_lat'   => 'decimal:7',
        'location_long'  => 'decimal:7',
    ];

    public function serviceUser()
    {return $this->belongsTo(ServiceUser::class);}
    public function reporter()
    {return $this->belongsTo(User::class, 'reported_by');}
    public function reportedBy()
    {return $this->belongsTo(User::class, 'reported_by');}
    public function escalatedTo()
    {return $this->belongsTo(User::class, 'escalated_to');}
    public function photo()
    {return $this->belongsTo(MediaFile::class, 'photo_id');}
}
