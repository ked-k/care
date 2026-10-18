# Batch 32 — Task Chart, caseload-wide

The follow-up you asked to proceed with: Batch 31 gave each client their
own Week chart tab; this gives you one page with every active client's
week side by side.

## What's new

New sidebar item: **Care Delivery › Task Chart**.

- One row per active client, one column per day (Mon–Sun), today's
  column highlighted.
- Each cell lists that day's tasks for that client — title, recurrence
  badge when it repeats, time, and the same status-colored badge as
  everywhere else in Tasks.
- Client name column stays pinned while you scroll the week sideways, so
  you don't lose track of which row you're looking at.
- Same Prev / This week / Next controls as the per-client version.
- Manager/Admin only (`manage_tasks`) — this spans the whole caseload, so
  it's gated the same way the rest of Tasks' manager-only features are.

This is the view for spotting a gap across everyone at once — if a
client's recurring task stopped generating for some other reason down
the line, it'll show as an empty cell in their row here rather than
needing to check each client individually.

## Files touched

- `app/Livewire/Task/TaskChartComponent.php` (new)
- `resources/views/livewire/task/task-chart.blade.php` (new)
- `routes/task-management.php` — added the `/tasks/chart` route
- `config/menu.php` — added the sidebar item under Care Delivery

No migration.

## One deliberate choice worth knowing about

This page's week-navigation code (`weekStart`, prevWeek/nextWeek/
goToCurrentWeek) is the same shape as Batch 31's `CarePlanShowComponent`
but not literally shared — didn't want to refactor code that's already
shipped and in use just to avoid a small, contained duplication. Worth
revisiting if a third place ever needs the same pattern.

## Same reminder as every batch

WAMP only so far. Needs your usual deploy step — and the cron line from
Batch 30/31 if it isn't live yet — before this shows up on
`care.affinityhealthcareltd.co.uk`.

## Verification

`php -l` clean on both PHP files. All four files committed with zero
rejections. Not clicked through in a live browser from here — please
open Care Delivery › Task Chart and confirm the grid renders and week
navigation works before relying on it with staff.
