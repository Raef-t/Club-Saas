<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Admin Person Profile
        $adminPerson = Person::create([
            'full_name' => 'Club Administrator',
            'gender' => 'male',
            'type' => 'staff',
            'mobile_1' => '0500000000',
            'email' => 'admin@clubsaas.com',
        ]);

        // 2. Create Admin Staff Profile
        $adminPerson->staffProfile()->create([
            'job_title' => 'System Administrator',
        ]);

        // 3. Create Admin User Account
        $adminUser = User::create([
            'person_id' => $adminPerson->id,
            'username' => 'admin',
            'password' => Hash::make('password'),
            'is_active' => true,
            'role' => 'super_admin',
        ]);

        // 4. Seed Roles and Permissions
        $this->call(RolesAndPermissionsSeeder::class);

        // 5. Assign Super Admin Role via Spatie
        $adminUser->assignRole('super_admin');
    }
}
