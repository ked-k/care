# Batch 16: Alerts With a Named Responsible Person

Part of the post-gap-assessment roadmap (`claude/Roadmap_Post_Gap_Assessment.md`).
Closes the "Call monitoring and alerts" gap: a late call, missed call,
missing record, missing evidence, or medication concern now creates a
real, actionable Alert — assigned to a named person — instead of only
being a number on a dashboard someone has to notice. Written directly
into your working tree at `C:\wamp64\www\ked\care`.

Two scoping questions were settled with you before building this:

- **Who an alert is assigned to**: every service user can now have a
  named **Care coordinator** (a Manager/Admin/Super Admin, set in the
  Service Users drawer form). Alerts for that person's calls, tasks and
  medications are assigned to their coordinator, falling back to any
  manager/admin at the agency if none is set yet. Per your answer, every
  alert stays visible to **all** managers/admins on the Alerts page, not
  just the assigned person — the "assigned to" is about accountability,
  not restricted visibility, and any manager can act on any alert.
- **What counts as a "missing record" alert**: a completed shift/call
  with nothing logged against it at all (**missing record**), and
  separately, a task marked Completed or Partly completed whose task
  requires a photo or signature but doesn't have one attached
  (**missing evidence**) — your answer asked for both.

## What's new

**A new Alerts page** (`/alerts`, "Alerts" in the sidebar, Manager/Admin
only) listing every alert for the agency: type, the service user, a
plain-English summary, who it's assigned to, and its status (Open /
Acknowledged / Resolved). Any manager/admin can Acknowledge, Resolve
(with optional notes), Reopen, or Reassign any alert to a different
named person. Filterable by type and status.

**Five alert types, generated automatically:**
- **Late call** / **Missed call** — a Shift or care call (the Batch 14
  Care Call Timeline) past its scheduled window with no check-in, using
  the same live status logic (`App\Support\CareCallStatus`) the
  timeline already shows.
- **Missing record** — a shift/call that's been checked out with no
  task or medication logged anywhere during its visit window.
- **Missing evidence** — a task logged Completed or Partly completed
  whose task requires a photo or signature, but one wasn't attached.
  Possible because "Partly completed" (Batch 15) doesn't force the
  photo/signature the way a full "Completed" does.
- **Medication concern** — the same 2+ refused/missed-doses-in-7-days
  signal Batch 15 added as a warning icon on the MAR chart, now also
  raised as an alert.

**A new "Care coordinator" field** on each service user (Service Users
→ drawer form), the named person their alerts go to.

**The manager dashboard** gets a new "Open alerts" tile — amber when
there are any, green when there are none, showing how many (if any) are
assigned to you specifically — linking straight to the Alerts page.

## Files touched

New: `database/migrations/2026_10_23_100000_add_care_coordinator_id_to_service_users_table.php`,
`database/migrations/2026_10_23_100001_create_alerts_table.php`,
`app/Models/Alert.php`, `app/Support/AlertGenerator.php`,
`app/Livewire/Alert/AlertsManagerComponent.php`,
`resources/views/livewire/alert/alerts-manager.blade.php`

Changed: `app/Models/ServiceUser.php`,
`app/Livewire/ServiceUser/ServiceUserManagerComponent.php`,
`resources/views/livewire/service-user/service-user-manager.blade.php`,
`app/Livewire/Dashboard/AnalyticsDashboardComponent.php`,
`resources/views/livewire/dashboard/analytics-dashboard.blade.php`,
`routes/operational.php`, `config/menu.php`

## Something worth knowing

- **No cron/queue job runs this.** `app/Console/Kernel.php` has no
  active schedule, so rather than add a dependency on Windows Task
  Scheduler running `php artisan schedule:run` every minute,
  `AlertGenerator::run()` is called synchronously wherever a manager
  would see the results — opening the Alerts page, or loading the
  dashboard (which also feeds the "Open alerts" tile). Same approach
  already used for `CareCallStatus`. A busy agency hitting this on
  every 30-second dashboard poll is the kind of thing a real queue/cron
  job should eventually take over — out of scope for this batch, but
  worth flagging if usage grows.
- **Late vs. Missed doesn't escalate.** If a call is first alerted while
  Late and nobody's acted by the time it tips over to Missed, the stored
  alert keeps its original "Late call" label — it's still open and
  actionable, just not relabelled. A deliberate simplification to avoid
  juggling alert history across a status change.
- **Each one-time event only ever alerts once.** Late/missed
  calls, missing records, and missing evidence are deduplicated against
  *any* alert ever raised for that exact shift/call/task-log, resolved
  or not — they can't recur for the same occurrence. Medication concerns
  are the exception: they dedupe only against a still-open/acknowledged
  alert, so a concern that was resolved and later reappears (fresh
  refusals) raises a fresh one.
- The "Assigned to" dropdown on each alert row uses `$event.target.value`
  in its `wire:change` — this is documented Livewire behaviour, but
  it's new to this codebase, so give it a quick try after migrating.
- Still outstanding from earlier batches: route-level permission gating
  for staff-only URLs, carer payslip self-service.

## Next step on your end

Run the new migrations:

```
php artisan migrate
```

No other manual steps. After that, set a Care coordinator on a service
user, then visit `/alerts` (or the dashboard's new tile) — if you have
any shifts/calls currently late or missed in your demo data, you should
see alerts appear immediately.

## Roadmap status

Batch 16 of 7 (see `claude/Roadmap_Post_Gap_Assessment.md`) — complete.
Next up: Batch 17 (wellbeing observations + handover + a general
urgent-escalation route).
