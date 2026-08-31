<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A safeguarding concern report and its permanent action timeline.
 *
 * The table has no dedicated "timeline" table of its own — every action taken
 * against a report (escalate, investigation note, resolve, close) is appended
 * to the `escalation_log` json column as an immutable entry. Nothing already
 * written to that array is ever edited or removed, matching the manual's
 * promise that "nothing here can be edited or deleted afterward".
 */
class SafeguardingReport extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    public const STATUS_OPEN = 'open';
    public const STATUS_INVESTIGATING = 'investigating';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'service_user_id', 'reported_by', 'type', 'description', 'photo_id',
        'location_lat', 'location_long', 'escalated_to', 'status', 'escalation_log',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'escalation_log' => 'array',
        'location_lat' => 'decimal:7',
        'location_long' => 'decimal:7',
    ];

    protected $attributes = [
        'status' => self::STATUS_OPEN,
    ];

    public function serviceUser(): BelongsTo
    {
        return $this->belongsTo(ServiceUser::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function escalatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_to');
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'photo_id');
    }

    /**
     * Timeline entries, oldest first. Each entry: action, by_id, by_name,
     * to_id/to_name (escalations only), note, at (ISO 8601).
     */
    public function timeline(): array
    {
        return $this->escalation_log ?? [];
    }

    protected function appendLog(string $action, User $by, ?string $note = null, ?User $to = null): void
    {
        $log = $this->escalation_log ?? [];

        $log[] = array_filter([
            'action' => $action,
            'by_id' => $by->id,
            'by_name' => $by->name,
            'to_id' => $to?->id,
            'to_name' => $to?->name,
            'note' => $note,
            'at' => now()->toIso8601String(),
        ], fn ($v) => $v !== null);

        $this->escalation_log = $log;
    }

    public function reportOpened(User $by, ?string $note = null): void
    {
        $this->appendLog('reported', $by, $note);
        $this->save();
    }

    public function escalate(User $by, User $to, ?string $note = null): void
    {
        $this->escalated_to = $to->id;
        $this->appendLog('escalated', $by, $note, $to);
        $this->save();
    }

    /**
     * Logs an investigation note. The report automatically moves to
     * "Investigating" the first time this is called, matching the manual.
     */
    public function addInvestigationNote(User $by, string $note): void
    {
        if ($this->status === self::STATUS_OPEN) {
            $this->status = self::STATUS_INVESTIGATING;
        }
        $this->appendLog('investigation_note', $by, $note);
        $this->save();
    }

    public function resolve(User $by, string $resolutionNote): void
    {
        $this->status = self::STATUS_RESOLVED;
        $this->appendLog('resolved', $by, $resolutionNote);
        $this->save();
    }

    public function canClose(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function close(User $by, ?string $note = null): void
    {
        $this->status = self::STATUS_CLOSED;
        $this->appendLog('closed', $by, $note);
        $this->save();
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'amber',
            self::STATUS_INVESTIGATING => 'primary',
            self::STATUS_RESOLVED => 'success',
            self::STATUS_CLOSED => 'secondary',
            default => 'secondary',
        };
    }
}
