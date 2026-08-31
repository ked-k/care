<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Links a family member's own User login (role "Family") to one service
 * user. The previous version of this model was an empty stub — this is the
 * first real implementation.
 */
class FamilyMember extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'service_user_id', 'user_id', 'relationship',
        'is_primary_contact', 'can_receive_updates', 'notification_preferences',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_primary_contact' => 'boolean',
        'can_receive_updates' => 'boolean',
        'notification_preferences' => 'array',
    ];

    public function serviceUser(): BelongsTo
    {
        return $this->belongsTo(ServiceUser::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
