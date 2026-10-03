<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One instance of a manager/admin covering a carer's shift — "take over any
 * carer's session and fill in records in case the carer did not". Created
 * the moment an admin gives a reason and starts covering a shift
 * (App\Livewire\Rota\ShiftVisitComponent::startTakeover()), closed when
 * they stop (endTakeover()) or implicitly still "active" (ended_at null)
 * if they navigate away without explicitly ending it.
 *
 * carer_id snapshots Shift::assigned_to at the moment the takeover started,
 * since a shift's assignment could in principle change later and the
 * takeover record should keep saying who it originally covered for.
 *
 * Every task log, medication administration, visit check-in, and
 * care-timeline note created while this takeover is active gets this
 * row's id stamped on it (shift_takeover_id) — see those models' takeover()
 * relation — so a later view of any of those records can show plainly that
 * an admin entered it on the carer's behalf, and the reason given.
 */
class ShiftTakeover extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'shift_id', 'carer_id', 'admin_id', 'reason', 'started_at', 'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function carer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'carer_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function taskLogs(): HasMany
    {
        return $this->hasMany(TaskLog::class);
    }

    public function medicationAdministrations(): HasMany
    {
        return $this->hasMany(MedicationAdministration::class);
    }

    public function visitCheckins(): HasMany
    {
        return $this->hasMany(VisitCheckin::class);
    }

    public function isActive(): bool
    {
        return $this->ended_at === null;
    }

    public function end(): void
    {
        if ($this->isActive()) {
            $this->update(['ended_at' => now()]);
        }
    }

    /**
     * A short label for wherever a record needs to say, in one line, who
     * actually entered it and on whose behalf — e.g. "Covered by Jane A.
     * for John K.".
     */
    public function summaryLabel(): string
    {
        return __('Covered by :admin for :carer', [
            'admin' => $this->admin->name ?? __('a manager'),
            'carer' => $this->carer->name ?? __('the assigned carer'),
        ]);
    }
}
