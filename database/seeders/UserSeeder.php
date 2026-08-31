<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Batch 4 (Role & Data Cleanup): rewritten from raw, hardcoded-ID
 * DB::table() inserts that created four generic CRM demo accounts
 * (admin@test.com / pm@test.com / sm@test.com / hr@test.com, password
 * "1234", named "Project Manager" / "Sales Manager" / "HR") with no
 * agency_id at all — meaning every screen that scopes by
 * Auth::user()->agency_id (which is almost all of them) would break for
 * these users the moment they logged in.
 *
 * This creates one demo agency and three demo staff accounts that are
 * actually usable: a Super Admin, an agency Admin, and a Carer, each tied
 * to the demo agency and given the matching role. **Change these passwords
 * before using this anywhere real** — see CHANGES4.md.
 *
 * Safe to re-run: firstOrCreate keyed on slug/email, so it never duplicates.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $agency = Agency::firstOrCreate(
            ['slug' => 'affinity-hcsl'],
            [
                'name' => 'Affinity Healthcare Services Ltd',
                'address' => 'London',
                'contact_email' => 'affinity@gmail.com',
                'phone' => '07563139084',
                'settings' => ['week_starts_on' => 'monday'],
            ]
        );

        $accounts = [
            ['name' => 'Super Admin', 'email' => 'superadmin@affinityhealthcare.test', 'role' => 'Super Admin'],
            ['name' => 'Agency Admin', 'email' => 'admin@affinityhealthcare.test', 'role' => 'Admin'],
            ['name' => 'Demo Carer', 'email' => 'carer@affinityhealthcare.test', 'role' => 'Carer'],
        ];

        foreach ($accounts as $account) {
            $user = User::firstOrCreate(
                ['email' => $account['email']],
                [
                    'uid' => (string) Str::uuid(),
                    'name' => $account['name'],
                    'agency_id' => $agency->id,
                    'password' => Hash::make('Password123!'),
                    'is_active' => true,
                ]
            );

            $user->syncRoles([$account['role']]);
        }
    }
}
