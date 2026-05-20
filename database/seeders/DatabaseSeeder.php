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
        User::create([
            'person_id' => $adminPerson->id,
            'username' => 'admin',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
    }
}
