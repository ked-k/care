# Batch 31 — Week chart, so the Batch 30 fix is actually visible

Nice confirmation from production — 742 occurrences created across 37
chains means the generation fix is doing real work.

## What's new

Every care plan page (**Care Delivery › Care Plans › [client]**) now has
a second tab next to the existing task list: **Week chart**.

- A 7-day Mon–Sun grid, one column per day, today's column highlighted.
- Each day shows every task due or scheduled that day — title, a
  recurrence badge ("Weekly", "Every 10 days"...) when it repeats, the
  time, and a status badge in the same colors as the list view
  (pending/overdue/completed/refused/skipped).
- Prev week / This week / Next week buttons move the whole grid.
- Switching between List and Week chart is instant — no reload, both
  tabs are already on the page.

This is exactly where to go to confirm Batch 30 actually worked for a
given client: open their care plan, click Week chart, and the days after
today should now have rows in them instead of being empty.

## Scope, as agreed

You chose "per service user first, caseload-wide later" — this is the
per-client view, reached from each client's own care plan. A single page
showing every active client's week side by side is a reasonable follow-up
once this is in daily use, not built this round.

## Same visibility rule as the existing task list

A plain carer sees only their own assigned tasks on the chart, same as
everywhere else in Tasks — Manager and Admin see everyone's, since both
already carry `manage_tasks` (confirmed in Batch 30's investigation).

## Files touched

- `app/Models/Task.php` — added `statusBadgeColor()`, one shared mapping
  from a task's status to its badge color, used by both the list and the
  new chart (previously only inlined in the list view's Blade).
- `app/Livewire/CarePlan/CarePlanShowComponent.php` — added
  `chartWeekStart` + prev/next/this-week actions, and the chart's grouped
  query in `render()` (same visibility filter as `TaskListComponent`).
- `resources/views/livewire/care-plan/care-plan-show.blade.php` — added
  the List/Week chart tab switcher and the 7-column grid.

No migration, no new route — it's a tab on the page that already existed.

## Still on you: getting Batch 30 + this onto production, and the cron

Good that the server lets you run real cron — that's simpler than the
Windows Task Scheduler workaround I wrote up for WAMP. On
`care.affinityhealthcareltd.co.uk`, add a line like this to crontab
(`crontab -e`), adjusting the path to wherever the app actually lives on
that account and confirming the right PHP binary first (`which php`, or
check cPanel's PHP version if this is shared hosting):

```
* * * * * cd /home/affiwspg/care.affinityhealthcareltd.co.uk && php artisan schedule:run >> /dev/null 2>&1
```

That's Laravel's standard "run every minute, let Laravel decide what
actually fires" pattern — `Kernel.php` already has
`tasks:generate-occurrences` registered `->daily()`, so this one cron
line is all it needs; no separate line for the task command itself.

**This batch (the Week chart tab) is only on your WAMP box right now** —
same as every batch before it, it needs your usual deploy step before
Neyirah or anyone else sees it on `care.affinityhealthcareltd.co.uk`.

## Verification

`php -l` clean on both PHP files. All three files committed with zero
rejections. Not clicked through in a live browser from here — please open
a client with a recurring task, confirm the Week chart tab renders and
Prev/Next week move correctly, before relying on it with staff.
