<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Batch 14: the one place that turns scheduled/actual times into the five
 * day-to-day states the Care Call Timeline shows (Upcoming, In Progress,
 * Completed, Late, Missed — plus Cancelled for a call a manager called
 * off). Used identically for a real Shift (the one scheduled app visit a
 * service user already has) and for the new, separate CareCall rows, so
 * the timeline can merge both into one consistent sequence without either
 * model needing its own copy of this logic.
 *
 * Deliberately never stored: status here is always a function of "what
 * time is it right now" plus whatever actual_start/actual_end already say,
 * so it can never drift out of sync with the clock the way a cached column
 * would.
 */
class CareCallStatus
{
    public const UPCOMING = 'upcoming';
    public const IN_PROGRESS = 'in_progress';
    public const COMPLETED = 'completed';
    public const LATE = 'late';
    public const MISSED = 'missed';
    public const CANCELLED = 'cancelled';

    /**
     * @param  bool  $cancelled  True when the underlying record has an
     *                           explicit cancelled state (only CareCall has
     *                           this today — a Shift is never cancelled by
     *                           this helper's caller).
     */
    public static function compute(
        Carbon $scheduledStart,
        Carbon $scheduledEnd,
        ?Carbon $actualStart,
        ?Carbon $actualEnd,
        bool $cancelled = false,
    ): string {
        if ($cancelled) {
            return self::CANCELLED;
        }

        if ($actualEnd) {
            return self::COMPLETED;
        }

        if ($actualStart) {
            return self::IN_PROGRESS;
        }

        $now = Carbon::now();

        if ($now->lt($scheduledStart)) {
            return self::UPCOMING;
        }

        if ($now->lte($scheduledEnd)) {
            return self::LATE;
        }

        return self::MISSED;
    }

    public static function label(string $status): string
    {
        return match ($status) {
            self::UPCOMING => __('Upcoming'),
            self::IN_PROGRESS => __('In Progress'),
            self::COMPLETED => __('Completed'),
            self::LATE => __('Late'),
            self::MISSED => __('Missed'),
            self::CANCELLED => __('Cancelled'),
            default => ucfirst($status),
        };
    }

    /**
     * Maps to resources/views/components/badge.blade.php's `color` prop —
     * 'warning' is new there as of this batch (amber), everything else
     * already existed.
     */
    public static function badgeColor(string $status): string
    {
        return match ($status) {
            self::UPCOMING => 'gray',
            self::IN_PROGRESS => 'primary',
            self::COMPLETED => 'success',
            self::LATE => 'warning',
            self::MISSED => 'danger',
            self::CANCELLED => 'dark',
            default => 'gray',
        };
    }

    /**
     * The ✓ / ● / ○ style glyph for the timeline step itself (separate from
     * the text badge, which carries the full label).
     */
    public static function glyph(string $status): string
    {
        return match ($status) {
            self::UPCOMING => '○',
            self::IN_PROGRESS => '●',
            self::COMPLETED => '✓',
            self::LATE => '!',
            self::MISSED => '✕',
            self::CANCELLED => '–',
            default => '○',
        };
    }
}
