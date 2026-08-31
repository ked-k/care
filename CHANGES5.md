# Batch 5 — Demo data seeder + system walkthrough

What you asked for: "generate seeder demo data to test and give a general overview of the system with data at each stage." This batch delivers both.

## What was built

**`database/seeders/DemoDataSeeder.php`** — a new, standalone seeder that fills the demo agency (Affinity Healthcare Services Ltd, created by `UserSeeder`) with realistic data across every module the app actually has screens for: service users, family links, consents, care plans, tasks, care timeline entries, assessments, medications and administrations, three rota periods, shifts and visit check-ins, timesheets, payroll runs and payslips, four safeguarding reports (one at each stage of its lifecycle), policies and acknowledgments, training modules and progress, compliance checks, subject access requests, breach reports, messages, and notifications.

It's deliberately spread across stages rather than all in one state — a draft rota next to two published ones, a timesheet at each approval step, safeguarding reports open/escalated/resolved/closed, tasks completed/refused/skipped/pending/overdue — so every list and filter in the app has something in each bucket to show, instead of one state repeated everywhere.

Wherever the app has a real business-logic method, the seeder calls it instead of writing final-state rows directly: `Task::complete()`, `SafeguardingReport::reportOpened()/escalate()/addInvestigationNote()/resolve()/close()`, `RotaPeriod::publish()/generateTimesheets()`, `Timesheet::submit()/approve()`, `PayrollRun::generateFromApprovedTimesheets()/approve()/markPaid()`, `Payslip::addEarning()/addDeduction()`. That means the audit log and notifications this data produces are the same ones a real user's actions would produce — not synthetic copies.

It's not added to `DatabaseSeeder`'s default run — it's test/demo data, so it's run by hand:

```
php artisan db:seed --class=DemoDataSeeder
```

It's safe to run more than once: it looks up the demo agency and accounts by the identifiers `UserSeeder` already gives them, and checks for its own data (a service user named "Grace Thompson") before doing anything, so a second run is a no-op rather than a duplicate.

**`DEMO_DATA_GUIDE.md`** — the "general overview of the system with data at each stage" you asked for: login credentials for all 7 demo accounts, what each of the 4 seeded service users represents, and a module-by-module tour naming exactly which seeded record shows which stage of which workflow and where to find it on screen.

## Two bugs found and fixed along the way

Building a seeder that exercises the whole system meant reading every model and screen it touches, which turned up two pre-existing bugs — neither introduced by this batch, both now fixed:

**1. `app/Livewire/Dashboard/AnalyticsDashboardComponent.php` — wrong model import.** It imported `App\Models\care\SafeguardingReport` (the stale stub in the leftover `app/Models/care` directory) instead of the real `App\Models\SafeguardingReport`. This happened to share the same underlying table, so it never crashed — but it also filtered the "escalated" count on `where('status', 'escalated')`, a value that has never existed on the real model (escalation is tracked separately via the `escalated_to` column, set by `SafeguardingReport::escalate()`). That tile has been silently showing zero. Fixed: the import now points at the real model, and "escalated" is now reports with `escalated_to` set that haven't yet been resolved or closed.

Worth flagging: Batch 4's changelog stated "nothing in the codebase references the `App\Models\care\*` namespace anywhere." That check missed this file — it's now corrected. The `app/Models/care` directory still isn't used by anything else and remains safe to delete once you've confirmed nothing else references it, but it wasn't as fully dead as reported.

**2. `app/Models/ServiceUser.php` — six missing relations.** Earlier in this session (Batch 3/4), this model had `safeguardingReports()`, `consents()`, `familyMembers()`, `careTimelineEntries()`, `carePlans()`, and `assessments()` relations. At one point while researching this batch, the on-device copy of this file was missing all six — which would have broken Family Management, the family portal, Consents, Safeguarding, Care Timeline, Assessments and Care Plans, since each of those screens calls one of those relations directly (confirmed via `FamilyMemberManagerComponent::render()` and `FamilyServiceUserComponent`, among others).

By the time this batch was ready to deliver, the six relations were already back in the file on your machine — so it seems you (or an editor/tool on your end) caught and fixed this yourself in the meantime. Nothing further was needed here; it's confirmed correct as it stands now. Mentioning it here only so you know why it briefly would have been broken, in case you saw errors from it.

## Known gotchas

- `AgencyMetric`, `Subscription`, and `ServiceUserQrCode` aren't seeded — none of the three has a real Eloquent model backing it (only migrations and/or stale `app/Models/care` stubs), so there's no application code that would ever read demo rows in those tables anyway. See `claude/CareTrust_Current_State_Overview.md`.
- The seeder deliberately does not use `ServiceUser::carers()` (the `service_user_carer` pivot) — there's no migration for that table, so using it would throw "table not found." The relation itself is untouched.
- If you want a clean re-seed rather than an idempotent no-op, delete the demo service users first (`Grace Thompson`, `Walter Higgins`, `Amara Obi`, and the soft-deleted `Leon Brooks`) — most of what the seeder creates cascades from those via foreign keys.

## Files changed or added

- `database/seeders/DemoDataSeeder.php` — new.
- `DEMO_DATA_GUIDE.md` — new.
- `app/Livewire/Dashboard/AnalyticsDashboardComponent.php` — fixed the `SafeguardingReport` import and the escalated-count logic.
- `app/Models/ServiceUser.php` — checked, no change needed (see above).
