<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** Batch 44b: one written note to a year's financial statements. */
class ReportNote extends Model
{
    use HasUuids;

    protected $table = 'acc_report_notes';

    protected $fillable = ['agency_id', 'financial_year_id', 'title', 'body', 'sort_order', 'updated_by'];
}
