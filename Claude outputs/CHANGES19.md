# Batch 19 — Reporting and dashboard

Scope set up front via three scoping questions:
- CSV export → Tasks, Medications, Timesheets, Safeguarding, **plus a new agency-wide Visit History page** (visits had no agency-wide list to export from before this batch).
- Missing documentation view → care-plan review overdue, assessment overdue, open safeguarding case, **plus staff-side training/policy gaps for carers assigned to that service user**.
- Missing documentation view → new standalone page under Compliance.

## 1. CSV export broadened beyond Audit Log / Compliance Dashboard

Same pattern as the existing exports (no PDF/Excel library in this app, so "export" means a CSV that opens straight in Excel/Sheets): a `response()->streamDownload()` action on the Livewire component, re-running the exact same filtered query that's on screen rather than a separate route, so what you download always matches what you're looking at.

- **Tasks** (`/tasks`) — manager-only "Export CSV" button next to the date/status filters. Exports the one day currently selected (this page has always been a single-day view — it's a carer's "my tasks today" list when no `manage_tasks` permission, otherwise a manager's view of everyone's tasks that one day). Not shown on the embedded per-shift task list.
- **Medications** (`/medications/{serviceUser}`) — exports that service user's medication list exactly as shown (respects "show discontinued").
- **Timesheets** (`/timesheets`) — exports whatever's in view: a carer's own timesheets, or every carer's if you can approve timesheets, filtered by the status dropdown. Includes the Batch 18 live-in days/pay columns.
- **Safeguarding** (`/safeguarding`) — manager-only (the raw incident description is sensitive enough that bulk export should be a deliberate manager action, not a carer-visible button). Respects the status filter and search box, and the same carer-visibility scoping the page already applies.

**Also found and fixed along the way:** the Audit Log and Compliance Dashboard already had working `exportCsv()` methods from an earlier batch, but neither page actually had a button wired to them — they were dead code. Both now have a visible "Export CSV" button.

**Visits were a gap, not a wire-up:** there was no existing agency-wide visit list to attach CSV export to — only My Rota (one carer's own week) and the Care Call Timeline (one service user's one day). Per your answer, this batch adds one:

### New: Visit History (`/rota/visit-history`, Rota Management → Visit History)

An agency-wide `Shift` list across a date range (defaults to the current week), filterable by service user, carer, visit type (day/night/live-in), and status (scheduled/completed/missed/cancelled), with the same CSV export pattern. Shows the Batch 18 double-handed badge where relevant.

Scoped to `Shift` rows specifically — "visits" here means the same thing the Compliance Dashboard's existing "Missed Visits" tile already means. `CareCall` welfare checks stay a separate, non-payroll record per the Batch 14/18 design and aren't included; they're reached from each service user's own Care Call Timeline, same as before.

## 2. New: Missing Documentation (`/compliance/missing-documentation`, Compliance → Missing Documentation)

One worklist instead of checking care-plan reviews, assessments, and safeguarding separately. One row per service user; a "Show fully compliant too" toggle (off by default, since this is meant as a worklist of what needs attention) and its own CSV export.

Per service user, flags:
- **Care plan** — no active care plan on file, or the active one's review date has passed.
- **Assessment** — no assessment on file at all, or the most recent one's review date has passed.
- **Safeguarding** — count of open/investigating safeguarding reports for that person.
- **Staff compliance** (the scope you asked to add beyond the roadmap's original three) — for every carer assigned to that service user (the union of the explicit carer-assignment pivot and whoever actually has a `Shift` with them, since neither list is reliably complete on its own in this data): any mandatory, active policy they haven't acknowledged, and any training module they've started but not completed.

The staff-compliance check reuses the exact same "mandatory policy" and "training completion" concepts the Compliance Dashboard's tiles already use — see that component's own long-standing caveat: `TrainingModule` has no "mandatory" flag in the schema, so "training compliance" has only ever been a completion ratio, never a true mandatory-training figure. This view inherits that same limitation rather than inventing a new one — a carer who simply never started a module isn't flagged, only one who started it and didn't finish.

The Compliance Dashboard's header now links to this page ("View missing documentation") so a manager can go straight from an aggregate count to exactly who and what.

## Known scope cuts

- Consent status (expired/pending) was **not** added to the missing-documentation categories — you chose the staff-compliance option over the consent option when asked, so it's left exactly as it already was (checked on each service user's own profile, same as before this batch).
- The staff-compliance check is computed fresh on every page load / export (no caching) — fine at the scale this app runs at, but worth knowing if the carer or policy count grows a lot, since it's an agency-wide nested loop (every mandatory policy × every assigned carer, per service user).
- Visit History and Missing Documentation are both read-only report pages — neither lets you fix anything from there; you still go to the Rota Builder, Care Plans, Assessments, or Safeguarding pages themselves to act.

## Files touched

**New:**
- `app/Livewire/Rota/VisitHistoryComponent.php` + `resources/views/livewire/rota/visit-history.blade.php`
- `app/Livewire/Compliance/MissingDocumentationComponent.php` + `resources/views/livewire/compliance/missing-documentation.blade.php`

**Modified:**
- `app/Livewire/Task/TaskListComponent.php` + blade — `exportCsv()`, query extracted to `tasksQuery()`
- `app/Livewire/Medication/MedicationManagerComponent.php` + blade — `exportCsv()`, query extracted to `medicationsQuery()`
- `app/Livewire/Timesheet/TimesheetIndexComponent.php` + blade — `exportCsv()`, query extracted to `timesheetsQuery()`
- `app/Livewire/Safeguarding/SafeguardingIndexComponent.php` + blade — `exportCsv()`, query extracted to `reportsQuery()`
- `resources/views/livewire/audit/audit-index.blade.php` — wired up the pre-existing (dead) export button
- `resources/views/livewire/compliance/compliance-dashboard.blade.php` — wired up the pre-existing (dead) export button, added the "View missing documentation" link
- `routes/rota-payroll.php` — new `rota.visit-history` route
- `routes/compliance-governance.php` — new `compliance.missing-documentation` route
- `config/menu.php` — two new sidebar entries (Visit History under Rota Management, Missing Documentation under Compliance & Governance)

No new migrations this batch — everything here is built on tables that already existed.
