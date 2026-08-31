<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            UserSeeder::class,
            RolePermissionSeeder::class,
            SafeguardingConsentFamilySeeder::class,
            ComplianceGovernanceSeeder::class,
            // Batch 4: one-time repair for a database seeded before this
            // batch rewrote the seeders above — see RoleCleanupSeeder's
            // own docblock. Safe to leave in the call list permanently,
            // it's a no-op once applied (or on a database that never had
            // the old CRM seed data).
            RoleCleanupSeeder::class,
        ]);
    }
}
