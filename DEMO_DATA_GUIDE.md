# CareTrust demo data — a tour of the system, one stage at a time

`DemoDataSeeder` populates the demo agency ("Affinity Healthcare Services Ltd") with data for every module this app has actually built, and deliberately spreads it across every stage each workflow can be in — a draft rota next to a published one, a timesheet at each approval step, a safeguarding report at each point in its lifecycle — so logging in shows a working system rather than a wall of empty states.

Run it once, after the other seeders:

```
php artisan db:seed            # if you haven't already run the base seeders
php artisan db:seed --class=DemoDataSeeder
```

It's safe to run alongside everything from Batches 1–4 — it looks up the demo agency and its three original accounts by e-mail rather than assuming IDs, and it checks for its own data before doing anything, so re-running it is a no-op rather than a duplicate.

## Who to log in as

All passwords are `Password123!` unless noted.

| Role | E-mail | What you'll see |
|---|---|---|
| Super Admin | `superadmin@affinityhealthcare.test` | Everything, unrestricted — the system administrator view. |
| Admin | `admin@affinityhealthcare.test` | Full agency administration — policies, compliance, data protection, staff. |
| Manager | `manager@affinityhealthcare.test` (new — "Ada Manager") | Day-to-day agency management: rota, tasks, safeguarding, staff, timesheets/payroll approval. |
| Carer | `carer@affinityhealthcare.test` ("Demo Carer") | The main carer account — has shifts, tasks, a timesheet, training progress, messages, and notifications. |
| Carer | `carer2@affinityhealthcare.test` (new — "Ben Carer") | A second carer, for comparing one carer's view against another's. |
| Family | `family1@affinityhealthcare.test` (new — "Nadia Family") | The family portal for Grace Thompson (her daughter). |
| Family | `family2@affinityhealthcare.test` (new — "Tom Family") | The family portal for Walter Higgins (his son). |

## Service users

Four, covering the range the dashboard tracks:

- **Grace Thompson** — active, consented, the most fully-populated record (care plan, tasks in every state, two safeguarding reports, a linked family member).
- **Walter Higgins** — active, consented, an overdue care-plan review and a high-risk assessment overdue for review.
- **Amara Obi** — active, consent still pending (her one consent record has expired) — the "consent_pending" stat on the dashboard is her.
- **Leon Brooks** — soft-deleted (off-boarded), so the dashboard's inactive-service-user count has something in it. He won't appear in any list — that's correct.

## Dashboard (`/dashboard`)

The landing page after login. Every stat tile now has real data behind it: service user counts (active/inactive/consented/pending), shift completion (scheduled/completed/missed/upcoming) with an 8-week trend, medication adherence (given/prompted/refused/missed), safeguarding by status including a genuinely escalated case, timesheet status counts, the latest paid payroll run, and care-plan review status (on-track/due-soon/overdue).

**Found and fixed while building this**: the dashboard was importing the stale stub model from the leftover `app/Models/care` directory instead of the real `SafeguardingReport` model, and its "escalated" count was filtering on a status value that has never existed on the real model (escalation is tracked separately from status) — so that tile has always silently shown zero. Both are fixed; see `CHANGES5.md`.

## Care Management → Care Plans / Tasks

- Grace's **Personal Care & Mobility Plan** (on track, review in 2 months) has one of each task outcome: a **completed** morning care task, a **refused** medication prompt, a still-**pending** lunch task due later today, and an **overdue** evening check-in that can't be completed without a photo.
- Walter's **Mobility & Falls Prevention Plan** has a review date already in the past (overdue) and a **skipped** task.
- Amara's **Nutrition & Hydration Plan** is due for review in 10 days (the "due soon" bucket).

Each completed/refused/skipped task also produced a **Care Timeline** entry — visit `Service Users → Timeline` for Grace or Walter to see them, plus two manually-added entries (a GP visit note, and one internal note deliberately marked "not shared with family").

## Assessments (`Service Users → Assessments`)

One per active service user: Grace (falls risk, medium, due in 4 months), Walter (moving & handling, **high risk, review already overdue**), Amara (initial assessment, low risk).

## Consents (`Service Users → Consents`)

Grace has one **active** and one **declined**; Walter's was granted and later **revoked**; Amara's was granted but has since **expired** — which is why her overall consent status still reads as pending on her profile and the dashboard.

## Family (`Service Users → Family`, and the family portal itself)

Nadia Family is linked to Grace as primary contact; Tom Family is linked to Walter. Log in as either to see the family portal (`/family/...`) — their linked service user's care plan and family-visible timeline entries.

## Rota Management

Three rota periods, each a different stage:

- **Two weeks ago** — published, fully worked (every shift has a real check-in/check-out) — this is the one that was run all the way through payroll (see below).
- **Last week** — published, mostly worked but Friday's shifts were never checked in — those show up as **missed** on the dashboard and in `Rota Plans`.
- **Next week** — still a **draft** (`Rota Plans`), not visible to carers yet. Log in as a carer and check `My Rota` — you'll only ever see published weeks, never this one, which is the point of that screen.

Publishing the two past periods also sent a real "your rota has been published" notification to whichever carer had shifts that week — check the notification bell as `carer@affinityhealthcare.test` or `carer2@affinityhealthcare.test`.

## Timesheets & Payroll

This is the module with the most stages, deliberately:

- **Two weeks ago's timesheets**: Demo Carer's was submitted, approved, picked up into a payroll run, and that run was carried all the way to **paid** — with a bonus and a tax deduction added to the payslip, so `Payroll → [that run] → Payslip` shows real line items. Ben Carer's timesheet for the same week was submitted then **rejected** ("hours don't match the rota").
- **Last week's timesheets**: Demo Carer's was submitted and approved, and sits in a second payroll run that's still in **draft** — ready for you to approve and mark paid yourself if you want to see that transition happen. Ben Carer's is still just **submitted**, waiting on a manager.

Log in as Manager or Admin and check `Time sheets` and `Payroll` to see all of these side by side.

## Safeguarding (`/safeguarding`)

Four reports, one at each stage:

1. **Open** — Amara, reported by Demo Carer, nothing actioned yet.
2. **Escalated & investigating** — Walter, reported by Ben Carer, escalated to the Manager (who got a real notification for it), with an investigation note logged.
3. **Resolved** — Grace, investigated and resolved.
4. **Closed** — Grace (a second, older report), taken through the full open → investigating → resolved → closed lifecycle.

Every action on every one of these went through the real model methods (`reportOpened`/`escalate`/`addInvestigationNote`/`resolve`/`close`), so the audit log (`Audit Log`, below) has real entries for all of them, not synthetic ones.

## Compliance & Governance

- **Policies**: three (Safeguarding, Medication, Data Protection), all mandatory reading. The Manager has acknowledged all three; Demo Carer has acknowledged two of three (Data Protection is outstanding); Ben Carer hasn't acknowledged any yet — useful for testing whatever "who hasn't read this" view you build next.
- **Training**: four modules (Safeguarding Awareness, Medication Administration, Moving & Handling, Fire Safety). The Manager has completed all four; Demo Carer has completed two, started a third, and never started the fourth; Ben Carer has completed one and started a second.
- **Compliance Dashboard**: four checklist items — one complete, one in progress (due in 3 weeks), one **not started and already overdue** (DBS Checks Renewal), one complete.
- **Audit Log**: populated organically by the safeguarding lifecycle above (real `SAFEGUARDING_REPORTED`/`SAFEGUARDING_ESCALATED`/`SAFEGUARDING_RESOLVED`/`SAFEGUARDING_CLOSED` entries) plus one `DEMO_DATA_SEEDED` marker entry.
- **Subject Access Requests**: one pending (Grace, requested by her family), one in progress (Walter, a rectification request), one fulfilled (Grace, a portability request already exported).
- **Data Breaches**: one open/low-severity (a rota sheet left visible in a car), one high-severity and already actioned, reported to the ICO.

## Notifications & Messaging

Log in as `carer@affinityhealthcare.test` to see the fullest picture: rota-published notifications, a safeguarding-escalation notification (if you're the Manager instead), a new-message notification, and a short real conversation with the Manager in the chat drawer (two read messages, one still unread). Ben Carer has a single "welcome" notification, already marked read, to show what a quiet inbox looks like by contrast.

## What isn't seeded

`AgencyMetric`, `Subscription`, and `ServiceUserQrCode` have database tables but no model or UI built against them yet (see `claude/CareTrust_Current_State_Overview.md`), so there's nothing meaningful to seed there. The legacy `/users`, `/roles`, `/permission` screens work off the same `users`/`roles`/`permissions` tables as everything else, so the accounts above are visible there too.
