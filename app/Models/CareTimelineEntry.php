<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A chronological, family-visible-or-not feed of care interactions for one
 * service user. Rows are currently created automatically when a carer
 * completes a task (see TaskListComponent::completeTask()); nothing wrote to
 * this table before this batch, so it existed as schema only.
 */
class CareTimelineEntry extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'service_user_id', 'entry_type', 'content', 'media_id',
        'visible_to_family', 'metadata', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'visible_to_family' => 'boolean',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'visible_to_family' => true,
    ];

    public function serviceUser(): BelongsTo
    {
        return $this->belongsTo(ServiceUser::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'media_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeVisibleToFamily(Builder $query): Builder
    {
        return $query->where('visible_to_family', true);
    }
}
