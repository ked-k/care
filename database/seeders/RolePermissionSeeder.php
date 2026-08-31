<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Batch 4 (Role & Data Cleanup): rewritten from raw, hardcoded role_id/
 * permission_id pivot-table inserts (which only made sense against the old
 * CRM roles/permissions and would silently grant nothing once those IDs
 * changed) to name-based Spatie grants, run after RoleSeeder/PermissionSeeder.
 *
 * Deliberately mirrors the original design: "Super Admin" gets nothing
 * explicit (AuthServiceProvider's Gate::before already grants it every
 * permission), and role/permission management itself ("manage_role",
 * "manage_permission") stays Super-Admin-only, same as the original seeder
 * — Admin never had those two either. Admin and Manager both get the
 * day-to-day operational permissions an agency's own administrators need.
 *
 * Safe to re-run: givePermissionTo() is idempotent (Spatie skips permissions
 * a role already has).
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $operational = ['manage_user', 'manage_rota', 'manage_tasks'];

        $admin = Role::where('name', 'Admin')->where('guard_name', 'web')->first();
        $admin?->givePermissionTo($operational);

        $manager = Role::where('name', 'Manager')->where('guard_name', 'web')->first();
        $manager?->givePermissionTo($operational);
    }
}
