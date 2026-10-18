<?php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Batch 30.
 *
 * Before this command existed, a repeating task's next occurrence was
 * only ever created reactively — the instant the CURRENT occurrence got
 * a completion log (see Task::complete()). That meant:
 *
 *   1. If nobody actioned today's task, tomorrow's never got created —
 *      the recurrence chain stalled silently for every date after it.
 *   2. Even when it worked, only ONE occurrence ahead ever existed, so
 *      there was never a visible run of upcoming dates to look at —
 *      only ever "the next one", and only after the last one was done.
 *
 * This command walks every repeating task chain's tip (a Task with
 * recurring_pattern set and no childTasks yet — i.e. the most recent
 * occurrence that's been generated) and creates occurrences forward
 * until the window --days ahead is filled, independent of whether
 * anyone has completed anything. Run daily (see Kernel::schedule()) so
 * the window keeps rolling forward.
 *
 * Run it once by hand right after deploying this fix to backfill the
 * gap in whatever's already stalled:
 *
 *   php artisan tasks:generate-occurrences
 */
class GenerateRecurringTaskOccurrences extends Command
{
    protected $signature = 'tasks:generate-occurrences {--days=30 : How many days ahead to keep populated}';

    protected $description = 'Pre-generate upcoming occurrences of every repeating care-plan task so future dates are never empty while waiting on a completion.';

    public function handle(): int
    {
        $horizon = Carbon::now()->addDays((int) $this->option('days'));

        $tips = Task::whereNotNull('recurring_pattern')
            ->whereDoesntHave('childTasks')
            ->whereHas('carePlan', fn ($q) => $q->where('is_active', true))
            ->get();

        $totalCreated = 0;

        foreach ($tips as $tip) {
            $totalCreated += $tip->backfillOccurrencesUntil($horizon);
        }

        $this->info(
            "Checked {$tips->count()} repeating task chain(s), created {$totalCreated} new occurrence(s) up to "
            .$horizon->toDateString().'.'
        );

        return self::SUCCESS;
    }
}
