<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Policy extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'agency_id', 'title', 'category', 'description', 'version', 'document_id',
        'effective_date', 'review_date', 'is_mandatory_reading', 'is_active',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'review_date' => 'date',
        'is_mandatory_reading' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'document_id');
    }

    public function acknowledgments(): HasMany
    {
        return $this->hasMany(PolicyAcknowledgment::class);
    }

    public function isAcknowledgedBy(int $userId): bool
    {
        return $this->acknowledgments()->where('user_id', $userId)->exists();
    }
}
