# Batch 15: Key Care Info, "Partly Completed" Tasks, Medication Safeguards

Part of the post-gap-assessment roadmap (`claude/Roadmap_Post_Gap_Assessment.md`),
closing three of the smaller gaps first: client profile key-care-information
fields, a "partly completed" task outcome, and two medication-recording
safeguards (duplicate-recording guard + concern flagging). Written directly
into your working tree at `C:\wamp64\www\ked\care`.

## 1. Key care information on the client profile

Every service user profile now has five free-text fields: **allergies**,
**medical conditions**, **communication needs**, **preferences**, and
**important information**. These live directly on the service user's own
record (not inside a care plan), so they're visible even before a care plan
exists — which is what your gap-assessment notes flagged as missing.

- Manager side: editable in the "Key care information" section of the
  service user drawer form (Service Users page). A small amber warning icon
  appears next to a client's name in the list when any of these fields are
  filled in, so managers can spot at a glance who has something on file.
- Carer side: a new "Key care information" card appears at the top of the
  carer's client profile page (the page added in Batch 14), shown only when
  there's something to show.

## 2. "Partly completed" task outcome

Tasks (personal care, meals, etc.) could previously only be marked
Completed, Refused, or Skipped — with no way to record that something was
started but not finished (e.g. the service user tired halfway through).
There's now a fourth outcome, **Partly completed**, available everywhere a
task can be completed:

- The general Tasks screen (`TaskListComponent`)
- The shift visit screen's Tasks tab (`ShiftVisitComponent` — this screen
  has its own separate, parallel implementation of task completion, so the
  change had to be made in both places)

When "Partly completed" is selected, the notes field becomes required (the
label changes to "What was and wasn't done (required)") so there's always
an explanation on record for why the task wasn't finished. The status badge
shows as amber, and the care timeline entry reads "...was partly completed
for...".

## 3. Medication safeguards

Two changes, both driven from two new shared helper methods on
`MedicationAdministration` (`alreadyRecordedOn()` and
`recentConcernCounts()`) so the MAR chart and the shift visit medications
tab — which, like the tasks tab, duplicates its own recording logic — stay
consistent with each other:

- **Duplicate-recording guard**: recording a dose for a scheduled (non-PRN)
  medication that's already been recorded for that day is now blocked with
  an error message, instead of silently creating a second row. PRN
  medications are exempt, since more than one dose a day is normal for
  those.
- **Concern flagging**: a small amber warning icon now appears next to a
  medication's name — on the MAR chart and the shift visit medications tab
  — whenever that medication has had 2 or more refused/missed doses in the
  trailing 7 days. Hovering shows the count. Nothing is stored for this; it's
  computed fresh each time from the existing administration records.

## Files touched

New: `database/migrations/2026_10_16_100000_add_care_info_to_service_users_table.php`

Changed: `app/Models/ServiceUser.php`, `app/Models/MedicationAdministration.php`,
`app/Livewire/ServiceUser/ServiceUserManagerComponent.php`,
`resources/views/livewire/service-user/service-user-manager.blade.php`,
`resources/views/livewire/care-timeline/client-profile.blade.php`,
`app/Livewire/Task/TaskListComponent.php`,
`resources/views/livewire/task/task-list.blade.php`,
`app/Livewire/Rota/ShiftVisitComponent.php`,
`resources/views/livewire/rota/shift-visit.blade.php`,
`app/Livewire/Medication/MarChartComponent.php`,
`resources/views/livewire/medication/mar-chart.blade.php`

## Something worth knowing

- The concern-flag window (trailing 7 days) is independent of whichever week
  is currently displayed on the MAR chart, so it stays meaningful even when
  browsing back to an earlier week.
- `ShiftVisitComponent`/`shift-visit.blade.php` keeps duplicating
  `TaskListComponent` and `MarChartComponent` rather than reusing them — this
  is pre-existing structure, not something this batch changed. Every change
  above had to be made in both places for that reason.
- The "required notes" behaviour for Partly completed is enforced
  server-side only (Livewire validation), not via an HTML `required`
  attribute — consistent with how Refused's reason field already works in
  this codebase.
- Still outstanding from earlier batches, untouched this time: route-level
  permission gating for staff-only URLs, and carer payslip self-service.

## Next step on your end

Run the new migration:

```
php artisan migrate
```

No other manual steps. After that, open a service user's profile to add
some key care information, mark a task as "Partly completed" to see the new
required-notes behaviour, and try recording the same scheduled medication
dose twice in one day to see the duplicate guard.
