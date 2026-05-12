<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\TenantManager\Database\Seeders\InitialTenantSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            InitialTenantSeeder::class,
        ]);
    }
}
