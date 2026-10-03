# Batch 12 — Consistent Carer Experience (fixing Batch 11's gap)

## What was reported

Screenshots from a Carer-role login showed two inconsistent things:
1. "Service Users" (and, implicitly, the Dashboard) still showed the full
   desktop admin sidebar — Care Management, Safeguarding, Compliance &
   Governance, User Management, everything — to a plain carer account.
2. "My Rota" (Batch 11's new mobile layout) had no navigation at all — no
   way to get anywhere else from it.

> "if we are maintaining the sidebar for carer portal let it be that, if
> we're using mobile layout let it be that too, please look into it and see"

## Root cause

Every staff-facing Livewire page in this app falls back to
`layouts::admin-layout` (the full sidebar shell) unless it explicitly says
otherwise — that's `config('livewire.component_layout')`. Batch 11 gave
`MyRotaComponent` and the new `ShiftVisitComponent` their own
`layouts.carer`, but nothing else a carer uses (Dashboard, Timesheets,
Notifications, Profile) was touched, so they kept falling back to the full
admin shell. On top of that, `config/menu.php` (the one sidebar everyone
shares) has almost nothing gated by permission — "Service Users",
"Safeguarding", "Compliance & Governance" etc. have no `'can' => ...`
at all — so any logged-in user, carer included, saw the same navigation as
an Admin.

## Decision: commit fully to the mobile layout for carers

Rather than a half-sidebar/half-mobile mix, a plain Carer account (see
below) now never sees the admin sidebar at all. Their whole experience —
Dashboard, My Rota, the shift visit screen, Timesheets, Notifications,
Profile — renders through `layouts.carer`. Admin/Super Admin/Manager
accounts are completely unaffected; they keep the existing desktop layout
and sidebar exactly as before.

**New: `App\Models\User::isCarerOnly()`** — the single source of truth:
`hasRole('Carer') && ! hasAnyRole(['Admin', 'Super Admin', 'Manager'])`.
Every layout decision below reads this one method.

## Fixed: My Rota (and everything else) had no way to navigate

`layouts/carer.blade.php` gained real navigation: a hamburger button in the
header opens a small dropdown with Dashboard / My Rota / Timesheets /
Notifications / Profile (current page highlighted), plus the existing
Log out link. This is a dropdown, not a second bottom tab bar, on purpose —
`ShiftVisitComponent`'s own bottom tab bar (Overview/Tasks/Medications/
Notes) is scoped to one shift and would otherwise collide with a
layout-level bottom bar on that same page.

## Fixed: Dashboard showed agency-wide data to a carer

`AnalyticsDashboardComponent` now branches on `isCarerOnly()`:
- **Carer** gets a new, small "what's next for me" home screen (new view
  `livewire.dashboard.carer-home`): next published shift with an "Open
  visit" button straight into `ShiftVisitComponent`, plus four quick-link
  tiles (My Rota, Timesheets, Notifications with an unread badge, Profile).
- **Everyone else** gets the exact same analytics dashboard as before
  (service user counts, shift completion trend, medication adherence,
  safeguarding case counts, timesheet/payroll status, care plan reviews) —
  unchanged, just now with an explicit `->layout('layouts.admin-layout')`
  instead of relying on the config fallback.

This also closes a real "what they should see" gap: a carer was previously
shown agency-wide safeguarding case counts and payroll run status on their
own dashboard, which has nothing to do with their job.

## Fixed: Profile, Notifications, Timesheets now match too

`ProfileComponent`, `NotificationCenterComponent`, and
`TimesheetIndexComponent` all got the same one-line branch in `render()` —
`->layout($user->isCarerOnly() ? 'layouts.carer' : 'layouts.admin-layout')`
— with their actual content untouched, since all three were already scoped
to the logged-in user's own data.

## Bug fix found while in `WeeklyTimesheetComponent`

`WeeklyTimesheetComponent::mount()`/`loadTimesheet()` had **no ownership
check at all** — any authenticated user who knew or guessed a timesheet's
id could open it, carer or not. Added:

```php
abort_unless($timesheet->user_id === Auth::id() || $this->canApprove, 403, ...);
```

Not something the screenshots showed directly, but squarely a "what they
should see" issue, and cheap to close while this file was already open for
the layout change.

## Bug fix in `ShiftVisitComponent`'s back arrow (Batch 11 regression)

The back arrow on the shift visit screen always linked to "My Rota" —
wrong for a manager/admin who arrived there via "Cover shift" from the Rota
Builder; they'd land on their own (probably empty) carer rota instead of
back where they came from. It now checks `$isOwnShift`: the carer's own
visit still goes to My Rota, a covering admin goes back to the Rota
Builder for that period.

## What this does *not* change (flagging, not fixing, for now)

Typing a staff-only URL directly (e.g. `/safeguarding`, `/service-users`)
still works for a carer if they know or guess it — those pages aren't
route-gated by role/permission, only hidden from the sidebar a carer no
longer sees. Locking that down properly means auditing every admin route
against `config('menu.php')`'s `'can' => ...` gates (most of which don't
exist yet) and deciding what a Manager should additionally be restricted
from — a bigger piece of work than today's navigation fix. Worth doing as
its own batch if you want carers (and possibly Managers) properly
access-controlled, not just UI-hidden.

Carers also still have no route to their own payslip (`payroll.payslip`)
from the new carer nav — that flow was never wired up for carer
self-service in the first place, so it's left out of scope here too.

## Files touched

- `app/Models/User.php` — `isCarerOnly()`.
- `resources/views/layouts/carer.blade.php` — nav dropdown added.
- `app/Livewire/Dashboard/AnalyticsDashboardComponent.php` — carer/non-carer branch.
- `resources/views/livewire/dashboard/carer-home.blade.php` — new.
- `app/Livewire/Profile/ProfileComponent.php`,
  `app/Livewire/Notification/NotificationCenterComponent.php`,
  `app/Livewire/Timesheet/TimesheetIndexComponent.php` — layout branch.
- `app/Livewire/Timesheet/WeeklyTimesheetComponent.php` — layout branch +
  ownership guard.
- `resources/views/livewire/rota/shift-visit.blade.php` — back-link fix.

No migrations in this batch — nothing to run.
