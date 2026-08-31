<?php

namespace Database\Seeders;

use App\Models\Agency;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Batch 4 (Role & Data Cleanup) — one-time repair for a database that was
 * already seeded by the OLD RoleSeeder/PermissionSeeder/UserSeeder (i.e.
 * this project's actual dev database) before this batch rewrote them.
 * RoleSeeder/PermissionSeeder/RolePermissionSeeder only ever *add* rows, so
 * they won't touch what's already there — this seeder cleans up what's
 * already there. It is intentionally non-destructive: existing rows are
 * renamed rather than deleted, since deleting a role or permission with
 * existing model_has_roles / role_has_permissions rows in an environment
 * this session can't query directly could silently strip access from a
 * real account. Safe to re-run — every step is a guarded, idempotent no-op
 * once applied (and a total no-op on a database that never had the old
 * seed data at all).
 */
class RoleCleanupSeeder extends Seeder
{
    public function run(): void
    {
        // Retire the generic CRM roles/permissions the app never references
        // (confirmed by searching the codebase — see CHANGES4.md). Renamed
        // with a "(unused)" prefix rather than removed, so they're obviously
        // safe to delete by hand later without guessing.
        foreach (['Project Manager', 'Sales Manager', 'Member'] as $staleRole) {
            Role::where('name', $staleRole)->where('guard_name', 'web')
                ->update(['name' => "(unused) {$staleRole}"]);
        }

        foreach (['manage_sales', 'manage_projects'] as $stalePermission) {
            Permission::where('name', $stalePermission)->where('guard_name', 'web')
                ->update(['name' => "(unused) {$stalePermission}"]);
        }

        // Grant the two permissions this batch introduces (manage_rota,
        // manage_tasks — see PermissionSeeder) to whatever Admin/Manager
        // roles already exist in this database, in case RoleSeeder/
        // PermissionSeeder/RolePermissionSeeder above ran against a fresh
        // install rather than this one.
        $operational = ['manage_rota', 'manage_tasks'];
        foreach (['Admin', 'Manager'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            $role?->givePermissionTo($operational);
        }

        // One-time data fix: the only agency seeded so far has a typo'd
        // name from the original build ("Anfinity HC") that matches
        // neither the user manual's "Affinity Healthcare Services Ltd" nor
        // its own contact e-mail ("afinity@gmail.com").
        Agency::where('name', 'Anfinity HC')->update([
            'name' => 'Affinity Healthcare Services Ltd',
            'contact_email' => 'affinity@gmail.com',
        ]);
    }
}
