# Batch 2 — Compliance & Governance

This batch adds policy management, staff training tracking, a compliance
dashboard, a surfaced audit log, and GDPR data-protection workflows (Subject
Access Requests and data breach/incident reporting). It follows the same
conventions and delivery approach as Batch 1 (Safeguarding, Consent & Family
Portal) — see `Batch1_Safeguarding_Consent_Family_Delivery.md` for that
batch's own notes.

As with Batch 1, I have no shell access to your machine, so I could not
create a git branch as originally discussed. Everything below was written
directly into your working tree. Before running the one seeder command
below, it's worth doing a `git status` / `git diff` pass yourself so you can
see exactly what changed, and `git stash` is there if you want to back out.

## One thing you need to run

```
php artisan db:seed --class=Database\\Seeders\\ComplianceGovernanceSeeder
```

(or just `php artisan db:seed`, since `DatabaseSeeder` now calls it too).
This creates four new permissions — `policy.manage`, `training.manage`,
`compliance.manage`, `data-protection.manage` — and grants them to the
`Admin` role. `Super Admin` already gets everything via the existing
`Gate::before` rule, so it needs no permission rows.

## What was built

**Policies** (`/policies`) — Admins/managers publish policy documents
(optionally attaching a PDF/doc upload, stored via the existing `MediaFile`
model) with a category, version, effective date and review date, and can
mark a policy as mandatory reading. Any staff member can view active
policies and acknowledge one with a single click; each acknowledgment
records the timestamp and IP address. Editing a policy or acknowledging one
writes an audit entry.

**Training** (`/training`) — Admins/managers create training modules
(title, category, an optional external link, optional duration). Every
staff member sees the module list with their own progress and can log
their own status (started/completed, with an optional score). There's no
enforced completion workflow beyond self-reporting — the schema has no
"who must complete this" or "proof of completion" mechanism, so this is
intentionally a lightweight tracker rather than a full LMS.

**Compliance Dashboard** (`/compliance`) — a single page pulling together
eight indicators: care plans due for review (next 14 days) and overdue
reviews, training compliance, medication compliance (last 7 days'
administered-vs-scheduled ratio), open safeguarding cases, missed visits
this week, pending mandatory-policy acknowledgments, and open (unactioned)
data incidents. Below the tiles is a manual compliance checklist (e.g. "CQC
registration renewal") that managers can add to and cycle through
not-started → in-progress → complete.

Two of these numbers have real limitations, called out directly on the
page: **training compliance** is a simple completed-vs-started ratio
because training modules have no "mandatory" flag in the schema, so it
isn't a true regulatory mandatory-training figure. **Missed visits** counts
shifts explicitly marked `status = 'missed'`, but nothing in the app
currently sets that status automatically — it will read zero until missed
shifts are marked as such, either manually or by a scheduled job this
batch does not add.

**Audit Log** (`/audits`) — a manager-only, read-only, filterable view
(by user, action text, date range) over the previously-empty `audits`
table. This batch introduces `App\Services\AuditLogger`, a small
dependency-free logging service (the `activity_log` table exists in your
schema but the `spatie/laravel-activitylog` package that would populate it
isn't installed — see the Batch 1/overview notes). It's wired into the
specific actions that matter for a care record, not blanket-logged across
every model: policy publish/update/acknowledge, training... (self-reported,
not logged — see note below), compliance-check changes, SAR
submit/resolve, data breach report/action, safeguarding report/
escalate/resolve/close, consent record/revoke, service user create/
update/activate/deactivate, and successful logins.

**Subject Access Requests** (`/data-protection/sar`) — logs the six UK
GDPR data-subject rights (access, rectification, erasure, portability,
restriction, objection) against a service user, who requested it, and its
resolution status. Any staff member can log a request (e.g. one phoned in
by a family member); only a manager can move it to in-progress/fulfilled/
rejected.

**Data Breaches** (`/data-protection/breaches`) — a separate incident log
from safeguarding, specifically for data-protection incidents (a lost
device, a misdirected email, etc.), with severity, a free-text description,
and a manager-recorded action-taken note plus an "reported to the ICO"
flag. There's no automatic 72-hour countdown or reminder — the schema has
no due-date field for this, so it's a manual log, not a compliance-clock
tool.

## Known scope cuts

- Training has no assignment/enforcement model — it's self-reported
  completion, not a certification system.
- The Compliance Dashboard's training-compliance and missed-visits figures
  have the limitations described above; both are documented directly on
  the page, not just here.
- Subject Access Requests and breach reports have no automatic reminders or
  statutory deadline tracking (SARs: one month under UK GDPR; breaches: 72
  hours to notify the ICO where required) — these are logs, not tickable
  clocks.
- The audit log only covers the actions listed above, not every write in
  the app.

## Files changed or added

**New models:** `Policy`, `PolicyAcknowledgment`, `TrainingModule`,
`TrainingProgress`, `ComplianceCheck`, `Audit`, `BreachReport`,
`SubjectAccessRequest`.

**Modified model:** `Agency` (added `users()`, `policies()`,
`complianceChecks()`, `breachReports()` relations — all original relations
preserved), `SafeguardingReport` (wired in `AuditLogger` calls on
report/escalate/resolve/close).

**New service:** `app/Services/AuditLogger.php`.

**New Livewire components:** `Policy\PolicyIndexComponent`,
`Training\TrainingIndexComponent`, `Compliance\ComplianceDashboardComponent`,
`Audit\AuditIndexComponent`, `DataProtection\SarIndexComponent`,
`DataProtection\BreachReportIndexComponent`.

**Modified Livewire components:** `Consent\ConsentManagerComponent` (audit
logging on record/revoke), `ServiceUser\ServiceUserManagerComponent` (audit
logging on create/update/activate/deactivate).

**New views:** `livewire/policy/policy-index.blade.php`,
`livewire/training/training-index.blade.php`,
`livewire/compliance/compliance-dashboard.blade.php`,
`livewire/audit/audit-index.blade.php`,
`livewire/data-protection/sar-index.blade.php`,
`livewire/data-protection/breach-report-index.blade.php`.

**Routes:** new `routes/compliance-governance.php`, included from
`routes/web.php` inside the main staff auth group (same pattern as
`safeguarding-consent-family.php`).

**Modified controller:** `Http\Controllers\Auth\LoginController` (logs
`LOGIN_SUCCESS` on every successful login).

**Config:** `config/menu.php` — new "Compliance & Governance" sidebar
group with six children (Policies, Training, Compliance Dashboard, Audit
Log, Subject Access Requests, Data Breaches). The Audit Log entry is
hidden from non-managers via the menu's `can` key.

**Seeders:** new `database/seeders/ComplianceGovernanceSeeder.php`, called
from `database/seeders/DatabaseSeeder.php`.
