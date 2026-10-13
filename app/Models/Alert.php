<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Batch 16: an alert raised by App\Support\AlertGenerator — a late call,
 * missed call, missing record/evidence, or medication concern — assigned
 * to a named person (the service user's care coordinator, or any
 * manager/admin at the agency if none is set) who's expected to respond
 * and record the outcome. See that class for how/when these get created.
 *
 * Batch 17 added TYPE_URGENT_CONCERN and the static raiseFor() factory
 * below, so a carer's own "flag as urgent" action (App\Livewire\Handover\
 * HandoverComponent) and a significant wellbeing observation (App\Models\
 * WellbeingObservation) can raise an alert through the exact same
 * coordinator-fallback-and-notify path AlertGenerator's auto-detected
 * alerts already use, instead of duplicating that logic.
 */
class Alert extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    public const TYPE_LATE_CALL = 'late_call';
    public const TYPE_MISSED_CALL = 'missed_call';
    public const TYPE_MISSING_RECORD = 'missing_record';
    public const TYPE_MISSING_EVIDENCE = 'missing_evidence';
    public const TYPE_MEDICATION_CONCERN = 'medication_concern';
    public const TYPE_URGENT_CONCERN = 'urgent_concern';

    public const STATUS_OPEN = 'open';
    public const STATUS_ACKNOWLEDGED = 'acknowledged';
    public const STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'agency_id', 'service_user_id', 'type', 'alertable_type', 'alertable_id',
        'summary', 'assigned_to', 'status',
        'acknowledged_by', 'acknowledged_at', 'resolved_by', 'resolved_at', 'resolution_notes',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_OPEN,
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function serviceUser(): BelongsTo
    {
        return $this->belongsTo(ServiceUser::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function alertable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function acknowledge(User $user): void
    {
        if ($this->status !== self::STATUS_OPEN) {
            return;
        }

        $this->update([
            'status' => self::STATUS_ACKNOWLEDGED,
            'acknowledged_by' => $user->id,
            'acknowledged_at' => now(),
        ]);
    }

    public function resolve(User $user, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolved_by' => $user->id,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }

    /** Lets a manager reopen an alert that turns out not to have been handled after all. */
    public function reopen(): void
    {
        $this->update([
            'status' => self::STATUS_OPEN,
            'acknowledged_by' => null,
            'acknowledged_at' => null,
            'resolved_by' => null,
            'resolved_at' => null,
            'resolution_notes' => null,
        ]);
    }

    public function reassign(User $user): void
    {
        $this->update(['assigned_to' => $user->id]);
    }

    /**
     * Batch 17: the shared path for raising an alert outside of
     * App\Support\AlertGenerator's own scans — a carer's "flag as urgent"
     * action, or a wellbeing observation marked "significant". Same
     * named-responder logic AlertGenerator::createAlert() uses (the
     * service user's care coordinator, falling back to any manager/admin
     * at the agency), and the same in-app notification to whoever gets
     * assigned, so every alert — auto-detected or carer-raised — behaves
     * identically from here on.
     *
     * $agencyId is a string: Agency uses HasUuids, not an auto-increment
     * id. This was originally declared ?int, which threw a TypeError the
     * first time AlertGenerator::createAlert() actually called this with
     * a real agency (its two other callers, HandoverComponent and
     * WellbeingObservation, never pass this argument, so they never hit
     * it — only the auto-detected scan path explicitly passes
     * $agency->id). Fixed alongside Batch 21.
     */
    public static function raiseFor(
        ServiceUser $serviceUser,
        string $type,
        string $summary,
        ?Model $alertable = null,
        ?string $agencyId = null,
    ): self {
        $agencyId = $agencyId ?: $serviceUser->agency_id;
        $assignedTo = $serviceUser->care_coordinator_id ?: self::fallbackResponder($agencyId);

        $alert = self::create([
            'agency_id' => $agencyId,
            'service_user_id' => $serviceUser->id,
            'type' => $type,
            'alertable_type' => $alertable ? $alertable::class : null,
            'alertable_id' => $alertable?->id,
            'summary' => $summary,
            'assigned_to' => $assignedTo,
        ]);

        if ($assignedTo) {
            Notification::create([
                'user_id' => $assignedTo,
                'type' => 'alert_raised',
                'title' => $alert->label(),
                'message' => $summary,
                'priority' => in_array($type, [self::TYPE_MISSED_CALL, self::TYPE_MEDICATION_CONCERN, self::TYPE_URGENT_CONCERN], true)
                    ? Notification::PRIORITY_HIGH
                    : Notification::PRIORITY_NORMAL,
                'data' => ['alert_id' => $alert->id],
            ]);
        }

        return $alert;
    }

    /**
     * Any Manager/Admin/Super Admin at the agency, used when a service
     * user has no named care coordinator yet — so an alert is never
     * simply unassigned. Not load-balanced; it's deliberately the
     * simplest rule that still always names someone. Moved here from
     * App\Support\AlertGenerator in Batch 17 so raiseFor() can share it.
     * $agencyId is a string (see raiseFor()'s docblock); the return
     * value is a real bigint user id, so that stays ?int.
     */
    protected static function fallbackResponder(?string $agencyId): ?int
    {
        if (! $agencyId) {
            return null;
        }

        return User::where('agency_id', $agencyId)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Manager', 'Admin', 'Super Admin']))
            ->orderBy('id')
            ->value('id');
    }

    public function label(): string
    {
        return match ($this->type) {
            self::TYPE_LATE_CALL => __('Late call'),
            self::TYPE_MISSED_CALL => __('Missed call'),
            self::TYPE_MISSING_RECORD => __('Missing record'),
            self::TYPE_MISSING_EVIDENCE => __('Missing evidence'),
            self::TYPE_MEDICATION_CONCERN => __('Medication concern'),
            self::TYPE_URGENT_CONCERN => __('Urgent concern'),
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    public function icon(): string
    {
        return match ($this->type) {
            self::TYPE_LATE_CALL => 'ik ik-clock',
            self::TYPE_MISSED_CALL => 'ik ik-x-circle',
            self::TYPE_MISSING_RECORD, self::TYPE_MISSING_EVIDENCE => 'ik ik-file-minus',
            self::TYPE_MEDICATION_CONCERN => 'ik ik-alert-triangle',
            self::TYPE_URGENT_CONCERN => 'ik ik-flag',
            default => 'ik ik-bell',
        };
    }

    /** Maps to resources/views/components/badge.blade.php's `color` prop. */
    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'danger',
            self::STATUS_ACKNOWLEDGED => 'warning',
            self::STATUS_RESOLVED => 'success',
            default => 'gray',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => __('Open'),
            self::STATUS_ACKNOWLEDGED => __('Acknowledged'),
            self::STATUS_RESOLVED => __('Resolved'),
            default => ucfirst($this->status),
        };
    }
}
