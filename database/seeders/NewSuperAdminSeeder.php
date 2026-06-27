<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;

class NewSuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Admin Person Profile
        $adminPerson = Person::create([
            'full_name' => 'Super Admin',
            'gender' => 'male',
            'type' => 'staff',
            'mobile_1' => '0500000001',
            'email' => 'superadmin2@clubsaas.com',
        ]);

        // 2. Create Admin Staff Profile
        $adminPerson->staffProfile()->create([
            'job_title' => 'System Administrator',
        ]);

        // 3. Create Admin User Account
        $adminUser = User::create([
            'person_id' => $adminPerson->id,
            'username' => 'super_admin',
            'password' => Hash::make('password123'),
            'is_active' => true,
            'role' => 'super_admin',
        ]);

        // 4. Assign Super Admin Role via Spatie
        $adminUser->assignRole('super_admin');
    }
}
