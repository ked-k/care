<?php

namespace App\Models\Accounting\Concerns;

use Illuminate\Support\Carbon;

/**
 * Batch 43: store calendar dates as plain "Y-m-d" (not "Y-m-d 00:00:00"),
 * so date comparisons in report queries behave the same on every database.
 * List the columns in $plainDates.
 */
trait PlainDates
{
    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->plainDates ?? [], true)) {
            $this->attributes[$key] = ($value === null || $value === '') ? null : Carbon::parse($value)->toDateString();

            return $this;
        }

        return parent::setAttribute($key, $value);
    }
}
