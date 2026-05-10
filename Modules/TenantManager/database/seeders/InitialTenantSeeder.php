<?php

namespace Modules\TenantManager\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\TenantManager\Models\Tenant;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Illuminate\Support\Facades\Hash;

class InitialTenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Tenant
        $tenant = Tenant::updateOrCreate(
            ['domain_prefix' => 'gold_gym'],
            [
                'name' => 'Gold Gym Club',
                'branding_config' => [
                    'primary_color' => '#FFD700',
                    'logo_url' => 'https://example.com/logo.png'
                ],
                'supported_languages' => ['ar', 'en'],
                'default_language' => 'ar',
                'status' => 'active'
            ]
        );

        // 2. Create Person
        $person = Person::updateOrCreate(
            ['email' => 'admin@goldgym.com', 'tenant_id' => $tenant->id],
            [
                'full_name' => 'Admin User',
                'mobile_1' => '0500000000',
                'gender' => 'male',
                'type' => 'admin',
            ]
        );

        // 3. Create User
        User::updateOrCreate(
            ['username' => 'admin', 'tenant_id' => $tenant->id],
            [
                'person_id' => $person->id,
                'password' => Hash::make('password123'),
                'is_active' => true,
            ]
        );
        
        $this->command->info('Initial test tenant [Gold Gym Club] and User [admin/password123] created!');
    }
}
