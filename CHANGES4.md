# Batch 4 — Role & Data Cleanup

This batch cleans up what the reconciliation notes flagged as leftovers from the original admin/CRM starter template, fixes the agency name typo, and closes three authorization gaps found while going through this material — two of them new discoveries, one already flagged in `CHANGES3.md`.

As with every batch so far: written directly into your working tree, no shell access on this machine so no git branch — `git status`/`git diff` first, `git stash` to back out if needed.

**Run this after pulling the files in:** `php artisan db:seed` (or, if you'd rather not re-run everything, `php artisan db:seed --class=RoleCleanupSeeder` on its own — see "Seeders" below for why both work).

## Roles & permissions

The original `RoleSeeder`/`PermissionSeeder`/`RolePermissionSeeder`/`UserSeeder` were untouched from the starter template: hardcoded-ID `DB::table()->insert()` calls creating roles `Super Admin`, `Admin`, `Project Manager`, `Sales Manager`, `Member`, permissions `manage_role`, `manage_permission`, `manage_user`, `manage_sales`, `manage_projects`, and four demo users (`admin@test.com`, `pm@test.com`, `sm@test.com`, `hr@test.com`, password `1234`) with **no `agency_id` at all** — meaning logging in as any of them would break on the first screen that scopes by `Auth::user()->agency_id` (nearly all of them).

All four are rewritten to use Spatie's `Role`/`Permission` models with `firstOrCreate`/`givePermissionTo` (idempotent, matching the pattern already used by `SafeguardingConsentFamilySeeder` and `ComplianceGovernanceSeeder` from earlier batches) instead of raw hardcoded-ID inserts:

- **Roles**: `Super Admin`, `Admin` (unchanged — both relied on throughout the app), plus two new ones — `Manager` and `Carer`. `Project Manager`, `Sales Manager`, `Member` are dropped from the seeder (nothing in this codebase ever checks for them).
- **Permissions**: `manage_role`, `manage_permission`, `manage_user` (unchanged), plus two new ones — `manage_rota` and `manage_tasks` (see "Authorization gaps closed" below for why). `manage_sales` and `manage_projects` are dropped.
- **Grants**: `Admin` and `Manager` both get `manage_user`, `manage_rota`, `manage_tasks`. Same as the original design, `manage_role`/`manage_permission` stay Super-Admin-only (Super Admin already gets everything via the `Gate::before` in `AuthServiceProvider` — it never needed an explicit grant).
- **Demo users**: one demo agency ("Affinity Healthcare Services Ltd" — see the typo fix below) and three usable demo accounts, each with `agency_id` set and the matching role: `superadmin@affinityhealthcare.test`, `admin@affinityhealthcare.test`, `carer@affinityhealthcare.test`, password `Password123!` for all three. **Change these before using this anywhere real.**

Because your database was already seeded by the *old* versions of these files before this batch, a fresh `php artisan db:seed` run won't touch what's already there (`firstOrCreate` only adds missing rows) — so this batch also adds **`RoleCleanupSeeder`**, a one-time, non-destructive repair for exactly that: it renames (not deletes — deleting could silently strip a role from a real account in a database this session can't inspect) `Project Manager`/`Sales Manager`/`Member` and `manage_sales`/`manage_projects` to `(unused) ...`, grants the two new permissions to whatever `Admin`/`Manager` roles already exist, and fixes the agency typo below. It's now the last step in `DatabaseSeeder`'s call list, and it's safe to leave there permanently — every step is a guarded no-op once applied.

## Agency name typo

The one seeded agency in your database is named "Anfinity HC" with contact e-mail "afinity@gmail.com" — doesn't match either its own manual (which is for "Affinity Healthcare Services Ltd") or itself consistently. `RoleCleanupSeeder` corrects both fields in place.

## Authorization gaps closed

Three screens had either no permission check or a check that was commented out, meaning any authenticated staff member — including a Carer account — could reach them:

- **Rota Builder** (`RotaBuilder`, `RotaPeriodIndex`) — flagged as a known gap in `CHANGES3.md`; not fixed then because the permission it needed (`manage_rota`) didn't exist yet. Both now `abort_unless` on `manage_rota` (or `Admin`/`Super Admin`). `config/menu.php`'s "Rota Plans" link now has `'can' => 'manage_rota'` to match — the half-fix I deliberately avoided in Batch 3 is now a real fix.
- **Staff Management** (`StaffManagerComponent`) — its guard was written but commented out (`// abort_unless(...)`); any authenticated user could create or edit staff, including granting themselves an Admin role. Now gated on `manage_user`.
- **Agency Settings** (`AgencySettingsComponent`) — same commented-out guard, same fix, same `manage_user` gate.

While fixing Staff Management I also found a real functional bug sitting next to that commented-out line: the form's default and reset value for a new staff member's role was the lowercase string `'carer'`, but no role by that exact name ever existed (roles were `Super Admin`/`Admin`/title case) — the role dropdown itself was populated correctly from whichever roles exist, but if you didn't touch the dropdown before saving, `formRole => 'required|string|exists:roles,name'` would fail validation. Now defaults to `'Carer'`, matching the roles this batch actually seeds.

## Dead generic-CRM routes removed

`routes/web.php` still `include`d five route files under `routes/modules/` — `demo.php` (themekit/UI demo pages, calendar, invoice, taskboard, etc.), `inventory.php` (products, POS, sales, purchases, customers, suppliers), `accounting.php` (banking, budget planner, presale, invoices, bills), `reports.php`, and `settings.php` (general/company/localization/appearance/security) — entirely unrelated to a care platform, left over from the admin/CRM starter template. None were linked from anywhere (their sidebar entries were already commented out in `config/menu.php`), but the routes themselves were still live and reachable by any authenticated non-family user who knew or guessed the URL. The five `include()` lines are removed from `routes/web.php`; the underlying controllers and Blade views under `routes/modules/`, `resources/views/inventory/`, `resources/views/accounting/`, `resources/views/reports/`, `resources/views/settings/`, and `resources/views/pages/` are left in place (this session can't delete files on your machine) but are now fully unreachable — safe to delete by hand whenever convenient.

## Stale duplicate model directory

`app/Models/care/` — 28 files in an `App\Models\care` namespace that duplicate the real models in `app/Models/` (e.g. `app/Models/care/Assessment.php` alongside the real `app/Models/Assessment.php`). Checked: nothing in the codebase references the `App\Models\care\*` namespace anywhere — these are leftovers from an earlier, abandoned refactor. Not touched in this batch (28 files' worth of changes for a directory nobody references isn't worth the noise), but confirmed dead and safe to delete by hand.

## Known gotchas / things worth knowing

- **The legacy Users/Roles/Permissions screens don't scope by agency.** `UserController`/`RolesController`/`PermissionController` (routes `/users`, `/roles`, `/permission`) are a separate, older admin CRUD system from this batch's `StaffManagerComponent` — already gated behind `manage_user`/`manage_role`/`manage_permission` at the route level (that part's fine), but their queries (`User::with('roles','permissions')->paginate()`, etc.) list every user across every agency, not just the current one. In a single-agency deployment this is invisible; in a genuinely multi-agency one, an `Admin` (not `Super Admin`) with `manage_user` would see every agency's staff. Whether that's intentional depends on whether these are meant as a true System-Administrator-only console (per the vision doc's distinction between "System Administrator" and "Agency Administrator") — worth a decision before relying on them in a multi-agency setup. Not changed here; flagging it the same way the Rota gap was flagged in Batch 3.
- **Demo account passwords** (`Password123!`) are exactly that — demo. Change them (or delete the seeded demo users) before this is used for anything beyond local development.
- **`RoleCleanupSeeder` renames rather than deletes.** If you're confident nothing depends on the `(unused) Project Manager` / `(unused) Sales Manager` / `(unused) Member` roles or `(unused) manage_sales` / `(unused) manage_projects` permissions after checking your own `model_has_roles`/`role_has_permissions` tables, they can be deleted by hand (e.g. via the Roles/Permissions admin screens this app already has).

## Files changed or added

**New seeders:** `RoleCleanupSeeder`.

**Rewritten seeders:** `RoleSeeder`, `PermissionSeeder`, `RolePermissionSeeder`, `UserSeeder` — same responsibilities, rebuilt on Spatie's idempotent model methods instead of raw hardcoded-ID inserts, with care-specific roles/permissions/demo data instead of generic CRM ones.

**Modified seeder:** `DatabaseSeeder` (added `RoleCleanupSeeder` to the call list).

**Modified Livewire components:** `Rota\RotaBuilder` and `Rota\RotaPeriodIndex` (new `manage_rota` authorization guard — `RotaPeriodIndex` gained a `mount()` method it didn't have before, purely to hold the guard), `Staff\StaffManagerComponent` (uncommented and implemented its `manage_user` guard; fixed the `'carer'`/`'Carer'` casing bug in three places), `Agency\AgencySettingsComponent` (uncommented and implemented its `manage_user` guard).

**Modified config:** `config/menu.php` (`'can' => 'manage_rota'` on Rota Plans, `'can' => 'manage_user'` on Staffs and Agencies).

**Modified routes:** `routes/web.php` (removed the five dead `modules/*.php` includes and their unused `NotificationCenterComponent` import; tidied two other pieces of dead/commented-out code encountered along the way — a duplicate `/notifications` route already replaced by Batch 3's real one, and the `tasks.by-shift` closure comment already superseded by Batch 3's actual fix).

**Not changed, documented only:** `app/Models/care/` (28 stale duplicate model files, safe to delete by hand); `routes/modules/*.php` and their controllers/views (now unreachable, safe to delete by hand).
