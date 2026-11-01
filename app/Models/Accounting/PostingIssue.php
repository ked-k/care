<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** Batch 43: an automatic posting that failed and is waiting to be retried. */
class PostingIssue extends Model
{
    use HasUuids;

    protected $table = 'acc_posting_issues';

    protected $fillable = ['agency_id', 'source_type', 'source_id', 'label', 'message', 'attempts', 'resolved_at'];

    protected $casts = ['resolved_at' => 'datetime'];

    public const SOURCE_LABELS = [
        'care_invoice' => 'Care invoice',
        'payroll_run' => 'Payroll run',
        'shop_order' => 'Shop order',
        'shop_stock_movement' => 'Stock movement',
        'shop_grn' => 'Shop delivery (GRN)',
    ];

    public function sourceLabel(): string
    {
        return __(self::SOURCE_LABELS[$this->source_type] ?? $this->source_type);
    }
}
