<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Modules\Authentication\Models\PersonContact;
use Modules\ClubManager\Models\Branch;
use Modules\ClubManager\Models\Club;
use Modules\StaffManager\Models\Staff;
use Spatie\Permission\Models\Role;

class NewSuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Get the existing branch
        $branch = Branch::first();

        if (!$branch) {
            $this->command->error('No branch found! Please run TechnogymSeeder first.');
            return;
        }

        // Cleanup existing data to allow re-running the seeder without duplicate errors
        $existingUser = User::where('username', 'technogym')->first();
        if ($existingUser) {
            $existingUser->delete();
        }
        $existingPerson = Person::where('email', 'superadmin2@clubsaas.com')->first();
        if ($existingPerson) {
            $existingPerson->contacts()->delete();
            Staff::where('person_id', $existingPerson->id)->delete();
            $existingPerson->delete();
        }

        // 2. Create Admin Person Profile
        $adminPerson = Person::create([
            'full_name' => 'Super Admin',
            'gender' => 'male',
            'type' => 'staff',
            'email' => 'superadmin2@clubsaas.com',
        ]);

        // Add mobile phone number through the person_contacts model (relation)
        PersonContact::create([
            'person_id' => $adminPerson->id,
            'name' => 'Personal',
            'relation' => 'self',
            'phone_number' => '0500000001',
        ]);

        // 3. Create Admin Staff Profile
        $staff = Staff::create([
            'person_id' => $adminPerson->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $staff->branches()->sync([$branch->id]);

        $staff->contracts()->create([
            'employment_type' => 'fixed_salary',
            'base_salary' => 0,
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        // 4. Create Admin User Account
        $adminUser = User::create([
            'person_id' => $adminPerson->id,
            'username' => 'technogym',
            'password' => Hash::make('password123'),
            'is_active' => true,
            'role' => 'super_admin',
        ]);

        // 5. Ensure the role exists for the sanctum guard
        Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'sanctum'
        ]);

        // 6. Assign Super Admin Role via Spatie
        $adminUser->assignRole('super_admin');
    }
}
