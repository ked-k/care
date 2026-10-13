# Sidebar Menu Reorganization

You asked to reorganize the sidebar menu (`config/menu.php`) and chose "full redesign from scratch" when asked how deep to go. This covers what changed and why, plus the audit you also asked for — whether any existing page has no menu entry at all.

## The audit: nothing is actually missing

Every named route in the app was cross-checked against `config/menu.php`. The routes that don't have a sidebar entry fall into exactly two buckets, and both are by design:

1. **Carer-only accounts never see this sidebar at all.** `User::isCarerOnly()` routes them to a completely separate layout (`layouts/carer.blade.php`) with its own 5-tab bottom nav (Home, Rota, Notes, Messages, More) from Batches 11-13. Routes like `notes.quick`, `messages.carer`, and `clients.show` live there, not in `config/menu.php`.
2. **Per-record drill-ins reached by navigating into a list, not from the sidebar directly.** Example: there's no standalone "Medications" sidebar item because medications are always viewed via a specific service user (Service Users → pick someone → their Medications/MAR Chart). Same pattern for Assessments, Timeline (this is also where Batch 17's wellbeing observations/handover notes live — there's no separate "Handover" page), Consents, Family, Messages, Calls, and the Visit/shift-takeover screen.

So no page was orphaned — the redesign below is purely about how the admin/manager sidebar is organized, not about surfacing anything that was hidden.

## What changed

**Fixed two bugs in the old file:**
- "User Management" group had `'active' => 'rota*'` — a copy-paste mistake that didn't match any of its own routes (Service Users/Staffs/Agencies), so the group never visually highlighted when you were actually inside it.
- The old "Care Management" group had `'active' => 'care-management/manage*'`, which doesn't match either of its real routes (`/care-plans`, `/tasks`) — same class of bug, now fixed.

**Split the two groups that had grown too large:**
- "Rota Management" (8 items, mixing scheduling with payroll) → **Rota & Scheduling** (6 scheduling items) + **Timesheets & Payroll** (Time sheets, Payroll).
- "Compliance & Governance" (7 items, mixing day-to-day compliance with GDPR requests) → **Compliance** (Policies, Training, Compliance Dashboard, Missing Documentation, Audit Log) + **Data Protection** (Subject Access Requests, Data Breaches).

**Renamed and re-grouped for clarity:**
- "User Management" → **People** (Service Users, Staffs, Agencies) — the name now reflects that it's about who's being cared for *and* who's doing the caring, not just account admin.
- "Care Management" (the children group) → **Care Delivery** (Care Plans, Tasks) — avoids the name clash with the top-bar "Care Management" module switcher, which is a different thing (that one jumps to the care-plan workspace itself).

**Left as standalone, single-click items** (not worth burying in a group): Dashboard, Notifications, Alerts, Safeguarding. Safeguarding in particular stays one click away rather than nested, since incidents are high-priority.

**Left unchanged:** the top-bar module switcher (`'top'` array) — it has one live entry (Care Management) and three commented-out entries from the original starter template (Inventory/Accounting/POS) that were already ruled out of scope back in Batch 4 as dead starter-template modules.

## New structure at a glance

- Dashboard
- Notifications
- Alerts
- Safeguarding
- **People** — Service Users, Staffs, Agencies
- **Care Delivery** — Care Plans, Tasks
- **Rota & Scheduling** — My Rota, Rota Plans, Live-in Placements, Carer Availability, Recurring Templates, Visit History
- **Timesheets & Payroll** — Time sheets, Payroll
- **Compliance** — Policies, Training, Compliance Dashboard, Missing Documentation, Audit Log
- **Data Protection** — Subject Access Requests, Data Breaches

## Files touched

- `config/menu.php` (rewritten)

No routes, components, or migrations changed — this is sidebar configuration only. Nothing to run.
