<?php

namespace Modules\TenantManager\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\TenantManager\Models\Tenant;

class InitialTenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Tenant::updateOrCreate(
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
        
        $this->command->info('Initial test tenant [Gold Gym Club] created!');
    }
}
