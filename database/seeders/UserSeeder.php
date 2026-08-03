<?php
namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            [
                'id'       => 1,
                'name'     => 'Super Admin',
                'uid'      => Str::uuid(),
                'email'    => 'admin@test.com',
                'password' => Hash::make(1234),
            ],
            [
                'id'       => 2,
                'uid'      => Str::uuid(),
                'name'     => 'Project Manager',
                'email'    => 'pm@test.com',
                'password' => Hash::make(1234),
            ],
            [
                'id'       => 3,
                'uid'      => Str::uuid(),
                'name'     => 'Sales Manager',
                'email'    => 'sm@test.com',
                'password' => Hash::make(1234),
            ],
            [
                'id'       => 4,
                'name'     => 'HR',
                'uid'      => Str::uuid(),
                'email'    => 'hr@test.com',
                'password' => Hash::make(1234),
            ],
        ]);
    }
}
