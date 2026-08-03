<?php

namespace App\Models\care;

use App\Models\care\Agency;
use App\Models\care\MedicationAdministration;
use App\Models\care\ServiceUser;
use App\Models\care\Task;
use App\Models\care\VisitCheckin;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shift extends Model
{
    use SoftDeletes;

    protected $table = 'shifts';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'agency_id', 'service_user_id', 'assigned_to',
        'scheduled_start', 'scheduled_end', 'actual_start', 'actual_end',
        'status', 'notes', 'created_by', 'updated_by'
    ];

    protected $casts = [
        'id' => 'string',
        'agency_id' => 'string',
        'service_user_id' => 'string',
        'assigned_to' => 'integer',
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Status constants
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_MISSED = 'missed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_LATE = 'late';

    const STATUS_COLORS = [
        'scheduled' => 'secondary',
        'in_progress' => 'primary',
        'completed' => 'success',
        'missed' => 'danger',
        'cancelled' => 'dark',
        'late' => 'warning',
    ];

    const STATUS_ICONS = [
        'scheduled' => 'bi-calendar-check',
        'in_progress' => 'bi-play-circle',
        'completed' => 'bi-check-circle',
        'missed' => 'bi-x-circle',
        'cancelled' => 'bi-ban',
        'late' => 'bi-clock-history',
    ];

    // Relationships
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    public function serviceUser(): BelongsTo
    {
        return $this->belongsTo(ServiceUser::class, 'service_user_id');
    }

    public function assignedCarer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'shift_id');
    }

    public function checkIn(): HasOne
    {
        return $this->hasOne(VisitCheckin::class, 'shift_id');
    }

    public function medicationAdministrations(): HasMany
    {
        return $this->hasMany(MedicationAdministration::class, 'shift_id');
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }

    public function getStatusIconAttribute(): string
    {
        return self::STATUS_ICONS[$this->status] ?? 'bi-question-circle';
    }

    public function getDurationAttribute(): ?float
    {
        if ($this->actual_start && $this->actual_end) {
            return $this->actual_end->diffInMinutes($this->actual_start) / 60;
        }
        return $this->scheduled_end->diffInMinutes($this->scheduled_start) / 60;
    }

    public function getIsLateAttribute(): bool
    {
        if ($this->actual_start && $this->scheduled_start) {
            return $this->actual_start->gt($this->scheduled_start->addMinutes(15));
        }
        return false;
    }

    public function getIsOverdueAttribute(): bool
    {
        if ($this->status === self::STATUS_SCHEDULED && $this->scheduled_start->lt(now())) {
            return true;
        }
        return false;
    }

    // Scopes
    public function scopeForAgency($query, $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeForCarer($query, $carerId)
    {
        return $query->where('assigned_to', $carerId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('scheduled_start', $date);
    }

    public function scopeForWeek($query, $startDate, $endDate)
    {
        return $query->whereBetween('scheduled_start', [$startDate, $endDate]);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_SCHEDULED, self::STATUS_IN_PROGRESS]);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_start', today());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_start', '>', now())
                    ->where('status', self::STATUS_SCHEDULED);
    }

    // Helper Methods
    public function canCheckIn(): bool
    {
        return $this->status === self::STATUS_SCHEDULED
            && !$this->checkIn
            && $this->scheduled_start->subMinutes(30) <= now();
    }

    public function canCheckOut(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS
            && $this->checkIn
            && !$this->checkIn->checkout_time;
    }

    public function markAsInProgress(): void
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'actual_start' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'actual_end' => now(),
        ]);
    }

    public function markAsMissed(): void
    {
        $this->update(['status' => self::STATUS_MISSED]);
    }

    public function getTasksSummary(): array
    {
        $tasks = $this->tasks;
        return [
            'total' => $tasks->count(),
            'completed' => $tasks->filter(function($task) {
                return $task->logs()->where('status', 'completed')->exists();
            })->count(),
            'pending' => $tasks->filter(function($task) {
                return !$task->logs()->where('status', 'completed')->exists();
            })->count(),
        ];
    }
}
