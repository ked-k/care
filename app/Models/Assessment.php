<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A structured assessment record for a service user (e.g. moving & handling,
 * falls risk, mental capacity). `questions_and_answers` is a free-form json
 * array of {question, answer} pairs rather than a fixed schema per type —
 * the vision doc describes many assessment types and this avoids needing a
 * separate table/migration per type.
 */
class Assessment extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    public const RISK_LOW = 'low';
    public const RISK_MEDIUM = 'medium';
    public const RISK_HIGH = 'high';

    public const TYPES = [
        'initial' => 'Initial Assessment',
        'moving_handling' => 'Moving & Handling',
        'nutrition' => 'Nutrition & Hydration',
        'mental_capacity' => 'Mental Capacity',
        'falls_risk' => 'Falls Risk',
        'environmental' => 'Environmental / Home Safety',
        'other' => 'Other',
    ];

    protected $fillable = [
        'service_user_id', 'conducted_by', 'assessment_type', 'questions_and_answers',
        'score', 'risk_level', 'recommendations', 'review_date', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'questions_and_answers' => 'array',
        'score' => 'decimal:2',
        'review_date' => 'date',
    ];

    public function serviceUser(): BelongsTo
    {
        return $this->belongsTo(ServiceUser::class);
    }

    public function conductedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'conducted_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->assessment_type] ?? $this->assessment_type;
    }

    public function riskColor(): string
    {
        return match ($this->risk_level) {
            'high' => 'danger',
            'medium' => 'amber',
            'low' => 'success',
            default => 'secondary',
        };
    }

    public function isDue(): bool
    {
        return $this->review_date !== null && $this->review_date->isPast();
    }
}
