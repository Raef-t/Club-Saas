<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Roles and Permissions
        $this->call(RolesAndPermissionsSeeder::class);

        // 2. Seed Club and Branch
        $this->call(TechnogymSeeder::class);

        // 3. Seed Super Admin (with Staff profile)
        $this->call(NewSuperAdminSeeder::class);

        // 4. Seed default plans and activities
        $this->call(RealPlansAndActivitiesSeeder::class);
    }
}
