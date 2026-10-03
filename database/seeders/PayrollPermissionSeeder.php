<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Run once with: php artisan db:seed --class=PayrollPermissionSeeder
 *
 * Idempotent (firstOrCreate / givePermissionTo), so it's safe to run again.
 * The timesheet and payroll screens were already gated on
 * "approve_timesheets" and "approve_payroll", but nothing ever created
 * those permissions — so only "Super Admin" (via the Gate::before in
 * AuthServiceProvider) could approve anything. Creates both and grants
 * them to "Admin" and "Manager", who run timesheet/payroll approval.
 */
class PayrollPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $created = [];
        foreach (['approve_timesheets', 'approve_payroll'] as $name) {
            $created[] = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (['Admin', 'Manager'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'web')->first()?->givePermissionTo($created);
        }
    }
}
