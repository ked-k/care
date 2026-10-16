# Batch 25 — Recurring Tasks (fix + Monthly)

You asked for help getting tasks to actually repeat — specifically "monthly"
wasn't available no matter what you tried, and you were still having to
re-create weekly tasks by hand. Dug into the attached database and the live
code to find out why.

## What was actually wrong

Recurrence logic did exist in the app already (`Task::generateNextOccurrence()`),
but two things made it useless for your workflow:

1. **There was no "Monthly" option at all.** The "Repeats" dropdown on a
   task only ever offered Does not repeat / Daily / Weekly / Every 3 days.
   No setting you picked could save as monthly, because the option simply
   wasn't there — that's why nothing you tried worked.
2. **A repeating task only generated its next copy when marked
   "Completed."** If a carer instead logged it as Refused, Skipped, or
   Partly completed — or it just sat there overdue and unactioned — the
   chain silently stopped forever and never generated again. This matches
   exactly what the attached database showed: several tasks with
   `recurring_pattern` set to `daily` but with hand-duplicated copies (same
   task, title manually re-dated) instead of real generated children,
   because the chain had broken at some point and someone worked around it
   by copy-pasting.

## What changed

- **"Repeats" dropdown now offers Daily / Weekly / Monthly / "Every N
  days..."** — the last one reveals a plain number field, so any interval
  works, not just a hard-coded 3 days.
- **Monthly repeats by calendar month**, not a fixed day count (e.g. the
  30th repeats to the 30th of next month; if a month is shorter it lands on
  its last day instead of rolling into the following month).
- **A repeating task's next occurrence is now generated no matter how the
  current one is logged** — Completed, Partly completed, Refused, or
  Skipped all now create the next one. The chain can no longer silently die
  on an awkward visit.
- Cleaned up the "Repeats: ..." label shown under a task's title on the
  care plan page — it was printing the raw stored value (e.g. "Every n
  days:3"); it now reads properly ("Every 3 days", "Monthly", etc).

## What I deliberately left alone

Per your call: the existing hand-duplicated task rows already in the
database are untouched. This fix only changes how *new* recurrence behaves
going forward — nothing was deleted or merged.

## Where this lives

- `app/Models/Task.php` — `complete()` and `generateNextOccurrence()`
- `app/Livewire/CarePlan/CarePlanShowComponent.php` — task create/edit form
  state (`formRecurringPattern`, new `formRecurringCustomDays`)
- `resources/views/livewire/care-plan/care-plan-show.blade.php` — the
  "Repeats" field on the task drawer, and the task list's recurrence label

No migration needed — this uses the `recurring_pattern`/`parent_task_id`
columns that were already on the `tasks` table, just actually wiring them
up properly. Existing tasks with `daily`/`weekly`/`every_n_days:N` already
set will keep working as before, now with the broken-chain bug fixed too.

## Worth checking on your end

Edit one of the real recurring tasks (or create a test one), set it to
Monthly, mark it Skipped or Refused instead of Completed, and confirm a
next occurrence appears a month later. That exercises both fixes at once.
I wasn't able to click through this live myself this session (same browser
connectivity gap as last time), so a quick manual check is worth doing
before relying on it for real care tasks.
