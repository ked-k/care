# Batch 17 — Wellbeing observations + handover

Closes the gap assessment's #8 (wellbeing observations/incident reporting)
and #9 (handover and communication) items, following on from Batch 16's
alerting workflow — see `claude/Roadmap_Post_Gap_Assessment.md`.

## What was built

**Wellbeing observations** — a structured record distinct from a free-text
Timeline note and from a full safeguarding report: category (skin
integrity, mood/behaviour, nutrition/hydration, mobility, sleep,
continence, other), a severity (routine / concern / significant), a
description, optional action taken, who was informed, an optional
follow-up flag + notes, and an optional photo. Built as one reusable
component (`App\Livewire\Wellbeing\WellbeingObservationComponent`) nested
on both the carer's shift-visit screen (new "Handover" tab) and the
standalone client-profile page, so it's available whether a carer reaches
a person through a Shift or through a CareCall.

Every observation is cross-posted to the existing Care Timeline
(`entry_type = 'wellbeing_observation'`, not visible to family) so it
shows up for managers reviewing a person's history without a separate
screen. **A "significant" observation automatically raises an Alert** —
same named-responder path Batch 16 built — so a manager doesn't have to
separately notice it among routine entries. Per your answer, "routine"
and "concern" severities don't raise anything; they're just logged.

**Handover** — a second nested component
(`App\Livewire\Handover\HandoverComponent`), sharing the same "Handover"
tab/card slot: outstanding tasks for the person (not yet logged, due
today or overdue), a running list of handover notes (always internal —
`visible_to_family = false`, since this is for the next carer, not
family), and a **"Flag as urgent"** button that raises an Alert straight
to the person's named care coordinator (or a manager) without going
through a safeguarding report. Per your answer, there's no requirement to
leave a handover note before checking out — it's there if wanted, not
enforced.

**`Alert::raiseFor()`** — the coordinator-fallback-and-notify logic that
used to live only in `AlertGenerator::createAlert()` is now a public
static factory on the `Alert` model itself, so both AlertGenerator's
auto-detected alerts and these two new carer-initiated ones (a
significant observation, "flag as urgent") go through identical logic —
nothing was duplicated. Added `Alert::TYPE_URGENT_CONCERN` for both.

## Where it shows up

- **My Rota → open a shift → Handover tab** — both components, stacked.
  Any record created here is stamped with the active shift-takeover id,
  same as every other record on this screen, if a manager/admin is
  covering the shift.
- **Client profile page** (the carer's non-shift view of a person) — both
  components appear as cards below "Today's calls", above the existing
  Notes/Messages quick links. No shift-takeover id here (there's no shift
  context on this page).
- **Care Timeline (staff view)** — wellbeing observations and handover
  notes both appear automatically, since they're cross-posted like every
  other recorded event.
- **Alerts page / dashboard** — a significant observation or a "flag as
  urgent" shows up exactly like a late-call or medication-concern alert,
  assigned to the same named responder.

## Known scope cuts

- "Outstanding tasks" on the Handover tab means tasks for this person
  with no log yet, due today or already overdue — not their entire future
  task backlog. Keeps the list meaningful for a handover, not a full
  task-management view (that's what the Tasks tab is for).
- No new manager-facing "all wellbeing observations" list page — they're
  visible via the existing staff Care Timeline and, for significant ones,
  via the Alerts page. A dedicated list/filter view would be a reasonable
  follow-up if managers want to review patterns in bulk rather than
  per-person.
- The Alerts page's type filter dropdown wasn't touched in this pass — an
  "Urgent concern" alert still appears in the main list and uses the same
  label/icon/notification as every other type, but if that dropdown
  hardcodes its list of types it may not yet offer "Urgent concern" as a
  filter option. Worth a quick look.

## Next step for the user

Run `php artisan migrate` to create the `wellbeing_observations` table.

## Roadmap status

Batch 17 of 7 — complete. Next up: Batch 18 (Rota and scheduling), the
biggest remaining batch — needs its own design pass since Shift feeds
payroll directly.
