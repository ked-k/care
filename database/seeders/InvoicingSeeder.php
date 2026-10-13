<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Permission for Batch 21 (Invoicing). Idempotent — safe to re-run.
 * Mirrors ComplianceGovernanceSeeder's pattern: firstOrCreate the
 * permission, then attach it to Admin (Gate::before already grants
 * Super Admin everything, so it's left out here as in that seeder).
 */
class InvoicingSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'manage_invoicing',
            'guard_name' => 'web',
        ]);

        $adminRole = Role::where('name', 'Admin')->first();

        if ($adminRole && ! $adminRole->hasPermissionTo($permission)) {
            $adminRole->givePermissionTo($permission);
        }
    }
}
