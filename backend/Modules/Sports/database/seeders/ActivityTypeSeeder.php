<?php

namespace Modules\Sports\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Sports\Models\ActivityType;

class ActivityTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'تدريب عام',
                'is_active' => true,
                'is_session_based' => false,
                'has_unlimited_subscribers' => true,
                'has_shifts' => true,
            ],
            [
                'name' => 'تدريب خاص',
                'is_active' => true,
                'is_session_based' => false,
                'has_unlimited_subscribers' => true,
                'has_shifts' => false,
            ],
            [
                'name' => 'حصة جماعية',
                'is_active' => true,
                'is_session_based' => true,
                'has_unlimited_subscribers' => false,
                'has_shifts' => false,
            ],
            [
                'name' => 'دخول يومي',
                'is_active' => true,
                'is_session_based' => false,
                'has_unlimited_subscribers' => true,
                'has_shifts' => false,
            ],
        ];

        foreach ($types as $typeData) {
            ActivityType::updateOrCreate(
                ['name' => $typeData['name']],
                $typeData
            );
        }
    }
}
