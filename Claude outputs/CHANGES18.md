# Batch 18 — Rota & Scheduling: Double-handed calls, Live-in shifts, Recurring templates, Carer availability

Scope set by you up front:
- Double-handed calls → **link two separate shift rows** (not one row with two carers).
- Live-in shifts → **scheduling + payroll**, not scheduling-only.
- Recurring visit templates → **both** Recurring Shifts (payroll) and Recurring CareCalls (non-payroll).
- Carer availability → **date-range unavailability** log.

## 1. Double-handed visits (two carers, one visit)

A double-handed visit — e.g. a lift/transfer needing two people — is recorded as **two separate `Shift` rows**, one per carer, linked together via a new `linked_shift_id` column on `shifts` (self-referencing, set both ways).

Why two rows instead of one row with two carer columns: `RotaPeriod::generateTimesheets()` builds each carer's weekly timesheet by grouping **their own** shifts. Two independent rows means each carer's row is picked up under their own name exactly like any single-handed visit — zero changes needed to the payroll grouping logic, and each carer's hours land correctly in their own timesheet.

- Rota Builder: a slot with one shift now shows an **"Add 2nd carer"** button. It opens the same shift-form drawer, pre-filled with the service user/date/time, but blank on carer — saving creates the second row and links it.
- A double-handed pair shows a small "linked" icon on both shift cards and is **excluded** from the new conflict check below (it's deliberate overlap, not a mistake).

## 2. Live-in shifts (scheduling + payroll)

A live-in placement is a carer placed with one service user continuously for several days/weeks, paid a flat **daily rate** rather than hourly day/night slots.

- New `shift_type = 'live_in'` (added to the `shifts.shift_type` enum via a raw `ALTER TABLE` — this app doesn't have `doctrine/dbal` installed, same constraint already documented against the `shift_takeovers` migration).
- A live-in `Shift` spans multiple days (`scheduled_start`/`scheduled_end` days apart) and, like ad-hoc shifts, has **no `rota_period_id`** — it isn't tied to one week. `RotaPeriod::generateTimesheets()` sweeps live-in shifts by **date-range overlap** with each period's week instead.
- New **Live-in Placements** page (Rota Management → Live-in Placements) — a standalone CRUD list: service user, carer, start date, end date, notes. "End now" ends an open placement as of today; "Delete" removes it entirely.
- New `employee_pay_profiles.live_in_daily_rate` column — settable on the **Staff** page (Edit staff → Pay Profile → "Live-in daily rate (optional)"). Left blank for carers who never take live-in placements.
- Payroll: for every calendar day a live-in placement covers, `RotaPeriod::generateLiveInEntries()` creates a `TimesheetEntry` with `live_in_shift_id`/`live_in_rate` set (day/night columns left null — this is intentionally **not** hourly). `Timesheet::recalculateTotals()` sums these into new `total_live_in_days`/`total_live_in_pay` columns, kept **separate** from the hourly regular/overtime split (a live-in day never counts toward the weekly overtime threshold). `PayrollRun::generateFromApprovedTimesheets()` adds the live-in total to the payslip as a separate earning line via the **existing** `Payslip::addEarning('live_in_allowance', ...)` mechanism — no new Payslip schema needed.
- If a live-in day and a day/night shift ever land on the same date for the same carer (shouldn't normally happen), the **day/night entry wins** and the live-in day is skipped — left for a manager to sort out by hand rather than guessing which should count.
- The weekly Rota Builder grid shows a **"Live-in" badge** next to a service user's name when they're already covered by a live-in placement that week, so a manager building the day/night rota doesn't accidentally double-book them.

## 3. Recurring visit templates

One template now generates either real day/night `Shift` rows (payroll) or `CareCall` welfare-check rows (non-payroll) — your choice per template.

- New `recurring_visit_templates` table/model: service user, carer (optional), type (`shift` or `care_call`), days of week, start/end time, break minutes (shift only), call label (care_call only), start/end date window, active flag, notes.
- New **Recurring Visit Templates** page (Rota Management → Recurring Templates): create/edit/deactivate/delete templates, plus a **"Generate"** action — pick a date range and it creates the matching `Shift`/`CareCall` rows for every matching weekday in range. Generation is **idempotent**: re-running it over an already-covered range creates nothing new for slots that already have a row, whether that row came from the template or was entered by hand.
- The Rota Builder page also has a **"Generate from templates"** button that runs every active *shift* template against that week only (CareCall templates are generated from their own page, since they don't appear in the day/night grid).
- There's no cron/queue in this app (same reasoning as the existing Alert/CareCall-status generators), so nothing is created automatically — a manager always explicitly runs Generate.

## 4. Carer availability

- New `carer_unavailabilities` table/model: carer, start date, end date, reason. A simple date-range log — leave, sickness, or anything else.
- New **Carer Availability** page (Rota Management → Carer Availability) for managing these.
- Rota Builder **warns** (doesn't block) when a shift is saved for a carer who's marked unavailable on that date — shown as an amber badge on the shift card and a toast after saving. A manager sometimes knows better than the record (the carer agreed to come in anyway), so this is a flag, not a lock.

## 5. Conflict flagging (new, this batch)

The Rota Builder grid now flags, with a red "Conflict" badge, any two of the **same carer's** shifts this week that overlap in time and are **not** a deliberate double-handed pair.

**Scope cut, by design:** this check is within one rota period only — it does not look across rota periods, and it does not check a `Shift` against a `CareCall`. It also doesn't fix anything; it surfaces a case to the manager before it reaches payroll.

## Known pre-existing limitation — unchanged, now partially surfaced

`RotaPeriod::generateTimesheets()` still picks `firstWhere('shift_type', 'day'|'night')` per carer per date — if a carer somehow ends up with **two independent (non-linked) day-type shifts** on the same date (e.g. two separate single-handed visits for two different service users, entered by mistake), the second one's hours are silently dropped from their timesheet, same as before this batch. This is documented in the migration that originally created `CareCall` as the payroll-safe alternative for exactly this reason.

Double-handed visits don't trip this (each carer in a linked pair still has exactly one shift that date). The new conflict-flagging feature (section 5) at least warns a manager when this situation arises, but the underlying grouping logic itself is unchanged — fixing it properly would mean reworking how `timesheet_entries` stores a day's hours (currently one `day_shift_id`/`night_shift_id` pair per entry) and was out of scope for this batch.

## Housekeeping note (not fixed this batch)

`app/Livewire/Rota/RotaBuilderComponent.php` and `RotaPeriodIndexComponent.php` are **stale, unrouted leftover files** — the live, routed classes are `RotaBuilder.php` and `RotaPeriodIndex.php` (confirmed against `routes/rota-payroll.php`). They're dead code, not used anywhere, and are safe to delete whenever convenient — left alone this batch to avoid scope creep.

## Migration

Seven new migrations — run:

```
php artisan migrate
```

- `2026_11_06_100000_add_linked_shift_id_to_shifts_table`
- `2026_11_06_100001_add_live_in_to_shift_type_enum` (raw `ALTER TABLE`, MySQL only — no `doctrine/dbal` in this app)
- `2026_11_06_100002_add_live_in_daily_rate_to_employee_pay_profiles_table`
- `2026_11_06_100003_add_live_in_columns_to_timesheet_entries_table`
- `2026_11_06_100004_add_live_in_totals_to_timesheets_table`
- `2026_11_06_100005_create_carer_unavailabilities_table`
- `2026_11_06_100006_create_recurring_visit_templates_table`

## Files touched

**New:**
- `app/Models/CarerUnavailability.php`
- `app/Models/RecurringVisitTemplate.php`
- `app/Livewire/Rota/LiveInPlacementComponent.php` + `resources/views/livewire/rota/live-in-placement.blade.php`
- `app/Livewire/Rota/CarerAvailabilityComponent.php` + `resources/views/livewire/rota/carer-availability.blade.php`
- `app/Livewire/Rota/RecurringVisitTemplateComponent.php` + `resources/views/livewire/rota/recurring-visit-template.blade.php`
- 7 migrations (listed above)

**Modified:**
- `app/Models/Shift.php` — `linked_shift_id`, `TYPE_DAY`/`TYPE_NIGHT`/`TYPE_LIVE_IN`, `linkedShift()`/`isDoubleHanded()`/`isLiveIn()`/`linkWith()`
- `app/Models/EmployeePayProfile.php` — `live_in_daily_rate`
- `app/Models/TimesheetEntry.php` — `live_in_shift_id`/`live_in_rate`, `liveInShift()`/`isLiveInDay()`
- `app/Models/Timesheet.php` — `total_live_in_days`/`total_live_in_pay`, `recalculateTotals()` extended
- `app/Models/RotaPeriod.php` — `generateTimesheets()` extended, new `generateLiveInEntries()`
- `app/Models/PayrollRun.php` — `generateFromApprovedTimesheets()` adds the live-in earning line
- `app/Livewire/Rota/RotaBuilder.php` — array-of-shifts grid, double-handed/unavailable/conflict flags, live-in badges, "Generate from templates"
- `app/Livewire/Staff/StaffManagerComponent.php` + blade — live-in daily rate field
- `resources/views/livewire/rota/rota-builder.blade.php` — rewritten for the new grid shape
- `routes/rota-payroll.php` — 3 new routes
- `config/menu.php` — 3 new sidebar entries under Rota Management
