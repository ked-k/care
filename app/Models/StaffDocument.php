<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Batch 39 (Staff Profile & Document Management): one row per document in
 * a staff member's digital personnel file — CVs, DBS certificates,
 * right-to-work checks, references, training certificates, signed
 * contracts, and anything else filed under CATEGORIES below. See the
 * migration's doc comment for why one table covers all eight of the
 * client's requested categories.
 *
 * LogsActivity gives "uploaded + changed" audit trail coverage for free,
 * the same way Policy/CarePlan/ServiceUser already get it — every create,
 * update (including an approve/reject status change) and delete lands in
 * the activity_log table with who did it and when. "Who viewed" is
 * explicitly out of scope for this batch (see CHANGES39.md) — nothing in
 * this app tracks views of anything yet, and adding that is a bigger,
 * separate piece of infrastructure.
 */
class StaffDocument extends Model
{
    use HasUuids, SoftDeletes, LogsActivity;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /**
     * Matches the client's own eight requested categories. "Personal
     * details" (full name, address, job title, start date) isn't here —
     * those live on the User/EmployeePayProfile records, not as a
     * document.
     */
    public const CATEGORIES = [
        'recruitment' => 'Recruitment documents',
        'right_to_work' => 'Right to Work',
        'dbs' => 'DBS checks',
        'references' => 'References',
        'qualifications_training' => 'Qualifications & Training',
        'employment_documents' => 'Employment documents',
        'additional_records' => 'Additional records',
    ];

    protected $fillable = [
        'agency_id', 'user_id', 'category', 'title', 'media_id',
        'issued_at', 'expires_at', 'status',
        'uploaded_by', 'reviewed_by', 'reviewed_at', 'notes',
        'retention_until', 'last_reminded_at',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'expires_at' => 'date',
        'reviewed_at' => 'datetime',
        'retention_until' => 'date',
        'last_reminded_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_APPROVED,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('Staff Document')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * media_id is a plain uuid column, not a DB foreign key — same
     * convention as training_progress.certificate_media_id.
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'media_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isExpired(): bool
    {
        return (bool) ($this->expires_at && $this->expires_at->isPast());
    }

    public function isExpiringSoon(int $withinDays = 30): bool
    {
        return (bool) ($this->expires_at && ! $this->isExpired() && now()->diffInDays($this->expires_at, false) <= $withinDays);
    }

    public static function categoryLabel(string $category): string
    {
        return self::CATEGORIES[$category] ?? $category;
    }

    /**
     * One row per category, each with the single most relevant document
     * (the current approved one if there is one, otherwise whatever's
     * pending or most recent) and a status: 'ok', 'expiring_soon',
     * 'expired', 'pending_review', or 'missing'. Shared by
     * StaffProfileComponent (the full checklist) and StaffManagerComponent
     * (the one-word badge on the staff list) so both ever only disagree if
     * one of them has a bug — not because the rule was written twice.
     */
    public static function complianceSummaryFor(int $userId): array
    {
        $documents = static::where('user_id', $userId)->get();

        $rows = [];
        foreach (self::CATEGORIES as $key => $label) {
            $categoryDocs = $documents->where('category', $key);
            $approved = $categoryDocs->where('status', self::STATUS_APPROVED);
            $currentApproved = $approved->reject(fn (self $d) => $d->isExpired())
                ->sortByDesc(fn (self $d) => $d->expires_at ?? $d->created_at)
                ->first();
            $expiredApproved = $approved->first(fn (self $d) => $d->isExpired());
            $pending = $categoryDocs->where('status', self::STATUS_PENDING)->sortByDesc('created_at')->first();

            $status = match (true) {
                $currentApproved && $currentApproved->isExpiringSoon() => 'expiring_soon',
                (bool) $currentApproved => 'ok',
                (bool) $expiredApproved => 'expired',
                (bool) $pending => 'pending_review',
                default => 'missing',
            };

            $rows[$key] = [
                'label' => $label,
                'status' => $status,
                'document' => $currentApproved ?: ($expiredApproved ?: $pending),
            ];
        }

        $hasGap = collect($rows)->contains(fn ($r) => in_array($r['status'], ['missing', 'expired', 'expiring_soon'], true));
        $pendingReviewCount = collect($rows)->where('status', 'pending_review')->count();

        return ['rows' => $rows, 'hasGap' => $hasGap, 'pendingReviewCount' => $pendingReviewCount];
    }
}
