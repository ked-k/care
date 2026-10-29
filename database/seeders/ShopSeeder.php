<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permission for Batch 40 (Shop workspace). Idempotent — safe to re-run.
 * Same pattern as InvoicingSeeder: create the permission and give it to
 * Admin (Super Admin already passes every check via Gate::before). Grant it
 * to Manager or anyone else from the Roles screen if they'll run the shop.
 */
class ShopSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'manage_shop',
            'guard_name' => 'web',
        ]);

        $adminRole = Role::where('name', 'Admin')->first();

        if ($adminRole && ! $adminRole->hasPermissionTo($permission)) {
            $adminRole->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
