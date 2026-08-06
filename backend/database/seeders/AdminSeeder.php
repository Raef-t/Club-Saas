<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Illuminate\Support\Facades\Hash;
use Modules\StaffManager\Models\Staff;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Admin Person Profile
        $adminPerson = Person::create([
            'full_name' => 'Yaman Admin',
            'gender' => 'male',
            'type' => 'staff',
            'email' => 'yaman@clubsaas.com',
        ]);

        $adminPerson->contacts()->create([
            'name' => 'Personal',
            'relation' => 'self',
            'phone_number' => '0555555555',
        ]);

        // 2. Create Admin Staff Profile
        Staff::create([
            'person_id' => $adminPerson->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        // 3. Create Admin User Account
        $adminUser = User::create([
            'person_id' => $adminPerson->id,
            'username' => 'admin2',
            'password' => Hash::make('password123'),
            'is_active' => true,
            'role' => 'admin',
        ]);

        // 4. Assign Admin Role via Spatie
        $adminUser->assignRole('admin');
    }
}
