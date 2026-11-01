<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Batch 43: the 'manage_accounts' permission for the Finance & Accounts
 * workspace (ledger, invoices, bills, bank, reports, financial years),
 * granted to Admin. Super Admin already has everything. Safe to re-run.
 */
class AccountingSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'manage_accounts', 'guard_name' => 'web']);

        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole && ! $adminRole->hasPermissionTo($permission)) {
            $adminRole->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
