<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A chronological, family-visible-or-not feed of care interactions for one
 * service user. Rows are created automatically when a carer completes a
 * task (TaskListComponent::completeTask()) or logs a quick note during a
 * visit (App\Livewire\Rota\ShiftVisitComponent::addNote()).
 */
class CareTimelineEntry extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'service_user_id', 'shift_takeover_id', 'entry_type', 'content', 'media_id',
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

    /**
     * Set only when this entry was written by a manager/admin covering the
     * carer's shift. Deliberately not surfaced in the family portal's own
     * rendering of this entry (family sees the update itself, not internal
     * staffing detail) — only staff-side screens show it.
     */
    public function takeover(): BelongsTo
    {
        return $this->belongsTo(ShiftTakeover::class, 'shift_takeover_id');
    }

    public function scopeVisibleToFamily(Builder $query): Builder
    {
        return $query->where('visible_to_family', true);
    }
}
