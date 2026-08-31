<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Run once with: php artisan db:seed --class=SafeguardingConsentFamilySeeder
 *
 * Idempotent (firstOrCreate throughout), so it's safe to run again. Adds:
 *   - a "Family" role (for family-portal logins)
 *   - three new permissions: safeguarding.manage, consent.manage, family.manage
 *   - grants all three to the existing "Admin" role ("Super Admin" already
 *     gets every permission implicitly via the Gate::before in
 *     AuthServiceProvider, so it's left out here on purpose)
 */
class SafeguardingConsentFamilySeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'Family', 'guard_name' => 'web']);

        $permissions = [
            'safeguarding.manage',
            'consent.manage',
            'family.manage',
        ];

        $created = [];
        foreach ($permissions as $name) {
            $created[] = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $admin = Role::where('name', 'Admin')->where('guard_name', 'web')->first();
        if ($admin) {
            $admin->givePermissionTo($created);
        }
    }
}
