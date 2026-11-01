<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentLine extends Model
{
    use HasUuids;

    protected $table = 'acc_document_lines';

    protected $fillable = ['document_id', 'account_id', 'description', 'qty', 'unit_price', 'discount', 'vat_rate', 'net', 'vat', 'total', 'sort_order'];

    protected $casts = [
        'qty' => 'decimal:3', 'unit_price' => 'decimal:4', 'discount' => 'decimal:2', 'vat_rate' => 'decimal:2',
        'net' => 'decimal:2', 'vat' => 'decimal:2', 'total' => 'decimal:2',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
