# Batch 20 — Audit Depth (spatie/laravel-activitylog)

## What changed

The custom, hand-rolled audit system from Batch 2 (`App\Services\AuditLogger`
writing to the `audits` table) has been replaced end to end with
[`spatie/laravel-activitylog`](https://github.com/spatie/laravel-activitylog),
per your request to use it instead of continuing to extend the bespoke
version. This was a **full migration**, not an addition: every model that
used to be logged by hand is now logged automatically, several models that
were never logged at all now are, and the Audit Log page has been
repointed at the new table while keeping the old history visible.

### The schema surprise

The `activity_log` table already existed in your schema — it's part of the
starter template's original migrations — but the package itself was never
installed, so nothing had ever written to it. One problem with that
pre-existing table: its `subject_id`/`causer_id` columns were created as
plain `unsignedBigInteger` (Laravel's default for `nullableMorphs()`), which
is wrong for this app — almost everything you'd want to audit
(`ServiceUser`, `CarePlan`, `Medication`, `Consent`, `Policy`,
`SafeguardingReport`, `BreachReport`, `SubjectAccessRequest`,
`ComplianceCheck`, `PolicyAcknowledgment`, `Invoice`, `Agency`, ...) uses
UUID string primary keys. A new migration widens both columns to
`string(36)` before anything else happens. The table had never been
populated, so this is a pure column-type fix — no data was at risk.

### Models now logged automatically (`LogsActivity` trait)

Create/update/delete events are captured without any explicit logging code,
via each model's `getActivitylogOptions()`:

- `ServiceUser` — replaces the old `SERVICE_USER_CREATED`/`_UPDATED`/
  `_DEACTIVATED`/`_REACTIVATED` actions. A deactivate/reactivate (soft
  delete/restore) now shows up as a generic "deleted"/"restored" event
  rather than a custom action name — the Entity/Description columns still
  make it unambiguous which service user and what changed.
- `Consent` — replaces `CONSENT_RECORDED`/`CONSENT_REVOKED`.
- `SafeguardingReport` — replaces `SAFEGUARDING_REPORTED`/`_ESCALATED`/
  `_RESOLVED`/`_CLOSED`. Deliberately logs only `status`/`escalated_to`
  (not the full `escalation_log` blob) to avoid duplicating the
  already-permanent timeline that lives on the model itself.
- `Policy` — replaces `POLICY_UPDATED`/`POLICY_PUBLISHED`.
- `PolicyAcknowledgment` — replaces `POLICY_ACKNOWLEDGED` (now logged
  against the acknowledgment row itself, which is more precise than
  logging against the policy, since many staff acknowledge the same
  policy).
- `SubjectAccessRequest` — replaces `SAR_SUBMITTED`/`SAR_<STATUS>`.
- `BreachReport` — replaces `DATA_BREACH_REPORTED`/`_ACTIONED`.
- `ComplianceCheck` — **new instrumentation**. `saveCheck()`/
  `advanceCheck()` in `ComplianceDashboardComponent` had no audit logging
  at all before this batch, despite the Batch 2 writeup describing
  otherwise.
- `CarePlan`, `Medication`, `MedicationAdministration` — **new
  instrumentation**. None of these were ever logged before — closing the
  "care plans, medication, consent" gap the roadmap called out by name.

### Logins

A login has no natural Eloquent "subject" to attach `LogsActivity` to, so
`LoginController::login()` now calls Spatie's own `activity()` helper
directly (`causedBy(...)->useLogName('Authentication')->event('login')
->log(...)`) instead of the old `AuditLogger::log('LOGIN_SUCCESS', ...)`.

### The Audit Log page

`AuditIndexComponent` (`/audits`) now has two tabs:

- **Activity Log** (default) — queries the new `activity_log` table via a
  thin `App\Models\ActivityLogEntry` subclass of Spatie's own `Activity`
  model (adds `actionLabel()`/`entityLabel()`/`causerLabel()` helpers,
  nothing else). Same filter UX as before (user, free-text search across
  description/log name/event, date range), same CSV export.
- **Legacy Audit Log** — the original `Audit`-table view, completely
  unchanged, so everything recorded before this batch stays visible rather
  than being orphaned when the live page repointed to the new table.

`App\Services\AuditLogger` and `App\Models\Audit` are left in place,
fully functional, just unused — nothing calls `AuditLogger::log()` anywhere
in the app any more. Safe to delete in a future cleanup pass; not deleted
now so the Legacy tab keeps working.

## ⚠️ Required steps before this works

This batch adds a Composer package and a migration. **Both must be done
before these files take effect** — until then, any page touching
`ServiceUser`, `Consent`, `SafeguardingReport`, `Policy`, `CarePlan`,
`Medication`, etc. will throw a `Class "Spatie\Activitylog\..." not found`
fatal error, because the trait is now referenced in those core models.

```bash
composer require spatie/laravel-activitylog
php artisan migrate
```

Do **not** run `php artisan vendor:publish --tag="activitylog-migrations"`
— that tag tries to create the `activity_log` table again, and it already
exists (that's the whole reason the widening migration uses
`Schema::table()`, not `Schema::create()`). The package's config has
sensible defaults and doesn't need publishing either; nothing in this
batch depends on `config/activitylog.php` existing.

Run `composer require` **first**, then `php artisan migrate` — the new
migration depends on the package's own earlier migration (already applied)
for the `activity_log` table to exist, but doesn't depend on the package's
service provider being registered, so order between the two artisan steps
doesn't matter as much as doing the composer step before loading any page.

## Known scope cuts / things to know

- `properties` logging uses each model's `logFillable()` (or a narrowed
  `logOnly()` for `SafeguardingReport`) combined with `logOnlyDirty()` —
  so an update only logs the fields that actually changed, not a full
  before/after snapshot of every column.
- `MedicationAdministration` and `PolicyAcknowledgment` don't use
  `logOnlyDirty()` since those rows are created once and essentially never
  updated — every field is logged on creation, which is what you want for
  a dose record.
- The Activity Log tab's "User" filter only matches activity caused by a
  staff member at your agency (`causer_type` = `User`); entries with no
  causer (none currently exist, but the package supports system-triggered
  events) show as "System" and are included regardless of the filter.
- No retention/pruning job has been added — `spatie/laravel-activitylog`
  ships a `CleanActivitylog` command you can schedule later if the table
  needs trimming; out of scope for this batch.
- The "Export CSV" button on the Audit Log page respects whichever tab
  (Activity vs Legacy) is currently open.

## Files touched

**New:**
- `database/migrations/2026_10_11_200000_widen_activity_log_morph_ids.php`
- `app/Models/ActivityLogEntry.php`

**Modified:**
- `app/Models/ServiceUser.php`, `Consent.php`, `SafeguardingReport.php`,
  `Policy.php`, `PolicyAcknowledgment.php`, `ComplianceCheck.php`,
  `SubjectAccessRequest.php`, `BreachReport.php`, `CarePlan.php`,
  `Medication.php`, `MedicationAdministration.php` — added `LogsActivity`.
- `app/Models/Audit.php`, `app/Services/AuditLogger.php` — doc comments
  only, marking them retired/read-only.
- `app/Livewire/Consent/ConsentManagerComponent.php`,
  `app/Livewire/Policy/PolicyIndexComponent.php`,
  `app/Livewire/DataProtection/SarIndexComponent.php`,
  `app/Livewire/DataProtection/BreachReportIndexComponent.php`,
  `app/Livewire/ServiceUser/ServiceUserManagerComponent.php` — removed
  `AuditLogger::log()` calls and the now-unused import.
- `app/Http/Controllers/Auth/LoginController.php` — swapped
  `AuditLogger::log('LOGIN_SUCCESS', ...)` for Spatie's `activity()` helper.
- `app/Livewire/Audit/AuditIndexComponent.php` — rewritten with the
  Activity/Legacy tab split.
- `resources/views/livewire/audit/audit-index.blade.php` — rewritten to
  match.

## Next up

With Batch 20 and 21 both delivered, every item from the gap assessment
except "Offline access" (explicitly deferred) is now addressed. Let me
know if you'd like a full re-check across the engagement, or if you want to
revisit Offline access next.
