<?php

namespace App\Models;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePayProfile extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'agency_id', 'user_id', 'manager_id', 'employee_no', 'job_title', 'employment_type',
        'hourly_rate', 'overtime_multiplier', 'weekly_overtime_threshold_hours', 'pay_frequency',
        'bank_name', 'bank_account_no', 'mobile_money_number', 'status', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'overtime_multiplier' => 'decimal:2',
        'weekly_overtime_threshold_hours' => 'decimal:2',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function overtimeRate(): float
    {
        return (float) $this->hourly_rate * (float) $this->overtime_multiplier;
    }
}
