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

        // 2. Seed All System Permissions (188 permissions)
        $this->call(AllSystemPermissionsSeeder::class);

        // 3. Seed Club and Branch
        $this->call(TechnogymSeeder::class);

        // 4. Seed Super Admin (with Staff profile)
        $this->call(NewSuperAdminSeeder::class);

        // 5. Seed default plans and activities
        $this->call(RealPlansAndActivitiesSeeder::class);
    }
}
