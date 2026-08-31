<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

/**
 * Batch 4 (Role & Data Cleanup): rewritten from raw, hardcoded-ID
 * DB::table() inserts that included two permissions never referenced
 * anywhere in this codebase ("manage_sales", "manage_projects" — leftovers
 * from the CRM starter template) to idempotent Permission::firstOrCreate()
 * calls, and adds two permissions the app already checks in code but that
 * no seeder ever created:
 *   - "manage_rota" — new; used by RotaBuilder/RotaPeriodIndex's new
 *     mount() guard (see CHANGES4.md — previously either component had no
 *     authorization check at all).
 *   - "manage_tasks" — TaskListComponent has checked $user->can('manage_tasks')
 *     since Batch 1, but until now nothing ever granted it, so every
 *     non-Super-Admin user only ever saw their own tasks even if they
 *     should have seen everyone's.
 * "manage_role"/"manage_permission"/"manage_user" are kept — real,
 * route-gated (routes/web.php) admin functionality.
 *
 * Safe to re-run: firstOrCreate is a no-op for permissions that already exist.
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['manage_role', 'manage_permission', 'manage_user', 'manage_rota', 'manage_tasks'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}
