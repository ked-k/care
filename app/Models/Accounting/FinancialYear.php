<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** Batch 43: an agency's accounting year (e.g. 1 Apr 2026 – 31 Mar 2027). */
class FinancialYear extends Model
{
    use HasUuids, Concerns\PlainDates;

    protected array $plainDates = ['starts_on', 'ends_on'];

    protected $table = 'acc_financial_years';

    protected $fillable = ['agency_id', 'name', 'starts_on', 'ends_on', 'status', 'closed_at', 'closed_by', 'notes'];

    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date', 'closed_at' => 'datetime'];

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function contains($date): bool
    {
        $d = \Illuminate\Support\Carbon::parse($date)->toDateString();

        return $d >= $this->starts_on->toDateString() && $d <= $this->ends_on->toDateString();
    }

    /** The year before this one, if it has been set up. */
    public function previous(): ?self
    {
        return static::where('agency_id', $this->agency_id)
            ->where('ends_on', '<', $this->starts_on)->orderByDesc('ends_on')->first();
    }
}
