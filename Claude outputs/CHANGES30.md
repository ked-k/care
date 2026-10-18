# Batch 30 — Why recurring tasks go empty on future dates

## What Neyirah ran into

Monday's tasks showed up because she typed them herself. Tuesday had
nothing — not because of her account (more on that below), but because
Tuesday's task row genuinely didn't exist in the database yet.

## Root cause

Checked the real logic on your WAMP box directly (`app/Models/Task.php`,
`app/Livewire/Task/TaskListComponent.php`). Two things, together:

1. **A future occurrence was only ever created reactively.** The only
   place that generated "tomorrow's task" was `Task::complete()` —
   fired the instant someone marks the *current* occurrence off
   (Completed/Partly completed/Refused/Skipped, since Batch 25). If
   nobody actions today's task, tomorrow's never gets created — and
   neither does the day after that, or the day after that. The chain
   doesn't just pause, it stays stalled indefinitely until someone goes
   back and manually marks the stuck one.
2. **Even when it worked, it only ever created ONE occurrence ahead.**
   So there was never a run of upcoming dates sitting there in advance —
   only ever "tomorrow's one", and only after today's was dealt with.

The task list screen (`TaskListComponent::tasksQuery()`) just filters
existing rows by exact date — `whereDate('scheduled_at', $date)`. No row
for that date, nothing shows. It was never a display bug; there was
simply nothing to display.

**On the account question** — checked `RolePermissionSeeder.php`
directly: `manage_tasks` is granted to both **Admin and Manager**. A
Manager account isn't missing anything here; switching to Admin
wouldn't have changed what she saw. (That's assuming the seeder has
actually run wherever she's logging in — worth a quick check if this
persists after the fix below.)

## What changed

- `app/Models/Task.php` — new `backfillOccurrencesUntil($horizon)`,
  which walks a recurring chain's tip forward creating occurrences
  until the next one would land past the horizon, instead of stopping
  after one.
- `app/Console/Commands/GenerateRecurringTaskOccurrences.php` (new) —
  `php artisan tasks:generate-occurrences --days=30` (30 is the
  default). Finds every repeating task with no child yet, on an active
  care plan, and tops each one up to 30 days ahead.
- `app/Console/Kernel.php` — registered it to run **daily**.

So now a repeating task's future dates get created on a rolling
30-day window on their own — nobody has to action today's task to make
tomorrow's appear.

## Two things you need to do on the server this runs on

**1. Run it once by hand right after deploying**, to backfill the gap
that's already there (this is what makes Tuesday/Wednesday/etc. appear
immediately instead of waiting a day):

```
cd C:\wamp64\www\ked\care
php artisan tasks:generate-occurrences
```

**2. Set up something to run it daily** — Windows has no cron, so
Laravel's own scheduler (what the `Kernel.php` entry assumes) needs
something external calling it. Simplest for one job: skip
`schedule:run` entirely and point Windows Task Scheduler straight at
the command itself:

- Task Scheduler → Create Basic Task → Trigger: **Daily**, pick a time
  (e.g. 2am)
- Action: **Start a program**
  - Program: your php.exe, e.g. `C:\wamp64\bin\php\php8.4.15\php.exe`
  - Arguments: `artisan tasks:generate-occurrences`
  - Start in: `C:\wamp64\www\ked\care`

Without this, the fix works the moment you run it by hand, but the
window won't keep rolling forward on its own.

## Same reminder as every batch

This is written to your WAMP box. `care.affinityhealthcareltd.co.uk`
and `e.affinityhealthcareltd.co.uk` are separate servers — this needs
deploying there the same way you've been pushing the other batches
before Neyirah (or anyone else on production) sees it fixed.

## One more thing, not code

The superadmin password came through in plain text over WhatsApp. Worth
rotating it once this is sorted — not urgent, just good hygiene for a
credential that's now sitting in a chat thread.

## Not done yet — flagged, not forgotten

You mentioned wanting a task chart (a calendar/timeline view of a
service user's upcoming and past tasks) once this is working properly.
Deliberately held off starting that until the generation fix itself is
confirmed live — no point designing a view on top of data that might
still have gaps. Happy to scope it next once you've confirmed Tuesday
onward actually show up now.

## Verification

`php -l` clean on all three files. All three committed to your WAMP box
with zero rejections. Not run against your live database from here —
please run step 1 above and confirm tasks appear on the following days
before trusting this in front of Neyirah again.
