<?php

namespace App\Models;

use App\Models\Payslip;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayslipLine extends Model
{
    use HasUuids;

    protected $fillable = ['payslip_id', 'line_type', 'category', 'description', 'amount'];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class);
    }
}
