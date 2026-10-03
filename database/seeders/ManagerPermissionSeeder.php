<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Run once with: php artisan db:seed --class=ManagerPermissionSeeder
 *
 * Idempotent (firstOrCreate / givePermissionTo), so it's safe to run again.
 *
 * - The timesheet and payroll screens were already gated on
 *   "approve_timesheets" and "approve_payroll", but nothing ever created
 *   those permissions — so only "Super Admin" (via the Gate::before in
 *   AuthServiceProvider) could approve anything. Creates both and grants
 *   them to "Admin" and "Manager", who run timesheet/payroll approval.
 * - "Manager" handles safeguarding day to day, but only "Admin" had
 *   "safeguarding.manage" (SafeguardingConsentFamilySeeder), so a Manager
 *   couldn't escalate, investigate, resolve or close a report.
 */
class ManagerPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $approvals = [];
        foreach (['approve_timesheets', 'approve_payroll'] as $name) {
            $approvals[] = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (['Admin', 'Manager'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'web')->first()?->givePermissionTo($approvals);
        }

        $safeguarding = Permission::firstOrCreate(['name' => 'safeguarding.manage', 'guard_name' => 'web']);
        Role::where('name', 'Manager')->where('guard_name', 'web')->first()?->givePermissionTo($safeguarding);
    }
}
