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
            'تدريب عام',
            'تدريب خاص',
            'حصة جماعية',
            'دخول يومي',
        ];

        foreach ($types as $type) {
            ActivityType::firstOrCreate([
                'name' => $type
            ], [
                'is_active' => true
            ]);
        }
    }
}
