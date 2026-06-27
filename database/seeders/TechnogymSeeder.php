<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\ClubManager\Models\Club;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Illuminate\Support\Facades\Hash;

class TechnogymSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Club
        $club = Club::create([
            'name' => 'technogym',
            'is_active' => true,
        ]);

        // 2. Create Branch
        $branch = $club->branches()->create([
            'name' => ['en' => 'Main Branch', 'ar' => 'الفرع الرئيسي'], // Branch name is translatable
            'gender_restriction' => 'mixed',
            'type' => 'main',
            'is_active' => true,
        ]);

        // 3. Create Admin Person
        $adminPerson = Person::create([
            'full_name' => 'Admin Technogym',
            'gender' => 'male',
            'type' => 'staff',
            'mobile_1' => '0500000000',
            'email' => 'admin@technogym.com',
        ]);

        // 4. Create Staff Profile
        $adminPerson->staffProfile()->create([
            'job_title' => 'System Admin',
        ]);

        // 5. Create Admin User
        $adminUser = User::create([
            'person_id' => $adminPerson->id,
            'username' => 'admin_technogym',
            'password' => Hash::make('password'),
            'is_active' => true,
            'role' => 'admin',
        ]);

        // 6. Assign Admin Role via Spatie
        $adminUser->assignRole('admin');
    }
}
