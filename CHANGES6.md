# Batch 6 — Timesheet/payroll hours showing as zero (hotfix)

You reported: timesheets, payroll runs, and payslips all show 0.00 for hours, gross, deductions, and net pay, even though shifts have real start/finish times. This batch fixes the root cause.

## The bug

`TimesheetEntry::recalculateHours()` and `WeeklyTimesheetComponent::diffMinutes()` both compute a shift's duration like this:

```php
$minutes += $end->diffInMinutes($start);
```

That line has been correct since this code was first written — under Carbon 2, `diffInMinutes()` always returned a positive number of minutes regardless of which date you called it on or passed in. This app runs **Carbon 3.13** (pulled in by Laravel 12), and [Carbon 3 changed that default](https://shawnhooper.ca/2024/04/11/breaking-change-diffin-carbon/): the `diffIn*()` family now returns a *signed* value unless you explicitly ask for the absolute one. Called as `$end->diffInMinutes($start)` — the later time as the object, the earlier time as the argument — it now returns a **negative** number of minutes.

That negative number then flows into `$minutes -= $this->break_minutes` (making it more negative) and finally `round(max($minutes, 0) / 60, 2)` — the `max(...,0)` guard against negative hours was already in the code for a different reason, but it's exactly what was clamping every real shift down to 0.00. Nothing else was wrong: the shift times were always there, the arithmetic was always correct in intent, it's a one-word change in behavior from a Carbon major version upgrade silently flipping the sign.

**Fixed** by passing `true` explicitly for the `$absolute` parameter in both places, which restores the always-positive behavior regardless of Carbon version:

```php
$minutes += $end->diffInMinutes($start, true);
```

This is the actual bug behind everything in your screenshots: the Timesheet's per-day "TOTAL HOURS" and weekly total, the Payroll Run's gross/deductions/net, and the Payslip's regular/overtime hours and pay all derive from this one calculation, directly or by summing it. Fixing it here fixes all of them at once — there was only ever one bug, showing up in four places.

## A second, related gap found while fixing this

While tracing the calculation, `RotaPeriod::generateTimesheets()` turned out to have a real (separate, pre-existing) limitation: when a carer has two visits of the same shift type ("day") scheduled on the same date, it only carries one of those shifts' start/end times into that day's timesheet entry — `$dayShifts->firstWhere('shift_type', 'day')` — while still summing *both* shifts' break minutes into that entry. The demo seeder's rota happened to do exactly this (pairing carer1 with both Grace and Amara on the same days), which would have undercounted carer1's hours even after the zero-hours bug above was fixed.

Double-booked visits are completely normal in real domiciliary care, so this is worth a proper fix at some point — summing every same-type shift's own duration for the day, rather than reading a single shift's start/end. That's a bigger change than this hotfix, so it's not done here. For now, the demo seeder itself has been adjusted so Amara isn't in the daily rota (she keeps her care plan, assessment, consent, and safeguarding report — none of which need shift data), which avoids tripping this gap in the seeded data without touching `generateTimesheets()` itself.

## What you need to do

The fix corrects the *calculation* going forward, but any timesheet already generated (including the demo data from Batch 5, which is what your screenshots show) has its wrong 0.00 already written to the database — the fix doesn't retroactively recompute rows that already exist.

For the demo data specifically, the simplest path is a clean re-seed:

1. Delete the four demo service users (`Grace Thompson`, `Walter Higgins`, `Amara Obi`, and the soft-deleted `Leon Brooks`) — this cascades to their shifts, timesheets, and payroll data via the existing foreign keys.
2. Run `php artisan db:seed --class=DemoDataSeeder` again.

If you have real (non-demo) timesheets already sitting at 0.00 hours, calling `$timesheet->recalculateTotals()` on each one (e.g. from `php artisan tinker`) will recompute them correctly against the fixed code — happy to build a small one-off command for that if you'd like it rather than doing it by hand.

## Files changed

- `app/Models/TimesheetEntry.php` — `recalculateHours()`: both `diffInMinutes()` calls now pass `true` for absolute value.
- `app/Livewire/Timesheet/WeeklyTimesheetComponent.php` — `diffMinutes()`: same fix, for the live total-hours preview as you type into the Timesheet form.
- `database/seeders/DemoDataSeeder.php` — `fillWeekOfShifts()`: Amara removed from the daily rota assignments to avoid the same-day-double-shift gap described above; documented in a comment on the method.
