<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Batch 4 (Role & Data Cleanup): rewritten from the original starter
 * template's raw, hardcoded-ID DB::table() inserts (Super Admin, Admin,
 * Project Manager, Sales Manager, Member — generic CRM roles never used
 * anywhere in CareTrust) to idempotent Spatie Role::firstOrCreate() calls
 * for the roles the app actually checks by name:
 *   - "Super Admin" and "Admin" — relied on throughout (AuthServiceProvider's
 *     Gate::before, SafeguardingReport, ServiceUserManagerComponent, etc.)
 *   - "Manager" and "Carer" — new; close the gap where StaffManagerComponent
 *     already offered a role dropdown and defaulted new staff to "carer"
 *     with nothing seeding a matching role.
 * ("Family" is intentionally not created here — SafeguardingConsentFamilySeeder
 * already does that idempotently, so it's left alone to avoid duplication.)
 *
 * Safe to re-run: firstOrCreate is a no-op for roles that already exist.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Super Admin', 'Admin', 'Manager', 'Carer'] as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}
