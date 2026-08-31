<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Permissions for Batch 2 (Compliance & Governance). Idempotent — safe to
 * re-run. Mirrors SafeguardingConsentFamilySeeder's pattern: firstOrCreate
 * the permission, then attach it to Admin (Gate::before already grants
 * Super Admin everything, so it's left out here as in the earlier seeder).
 */
class ComplianceGovernanceSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'policy.manage',
            'training.manage',
            'compliance.manage',
            'data-protection.manage',
        ];

        $adminRole = Role::where('name', 'Admin')->first();

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);

            if ($adminRole && ! $adminRole->hasPermissionTo($permission)) {
                $adminRole->givePermissionTo($permission);
            }
        }
    }
}
