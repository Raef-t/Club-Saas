<?php

namespace Modules\Sports\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Sports\Models\Activity;

class ActivitiesSeeder extends Seeder
{
    /**
     * Seed default gym activities based on PDF requirements.
     */
    public function run(): void
    {
        $activities = [
            ['name' => 'أجهزة عام', 'gender_allowed' => 'mixed', 'is_private_equipment' => false],
            ['name' => 'أجهزة خاص', 'gender_allowed' => 'mixed', 'is_private_equipment' => true],
            ['name' => 'ايروبيك', 'gender_allowed' => 'mixed', 'is_private_equipment' => false],
            ['name' => 'كروسفيت', 'gender_allowed' => 'mixed', 'is_private_equipment' => false],
            ['name' => 'X55', 'gender_allowed' => 'mixed', 'is_private_equipment' => false],
            ['name' => 'جمباز', 'gender_allowed' => 'mixed', 'is_private_equipment' => false],
            ['name' => 'يوغا', 'gender_allowed' => 'mixed', 'is_private_equipment' => false],
            ['name' => 'رقص شرقي', 'gender_allowed' => 'female', 'is_private_equipment' => false],
            ['name' => 'زومبا', 'gender_allowed' => 'mixed', 'is_private_equipment' => false],
            ['name' => 'مكس', 'gender_allowed' => 'mixed', 'is_private_equipment' => false],
            ['name' => 'بيلاتس', 'gender_allowed' => 'mixed', 'is_private_equipment' => false],
            ['name' => 'كيك بوكسينغ', 'gender_allowed' => 'mixed', 'is_private_equipment' => false],
            ['name' => 'زومبا أطفال', 'gender_allowed' => 'mixed', 'is_private_equipment' => false],
            ['name' => 'سباحة', 'gender_allowed' => 'mixed', 'is_private_equipment' => false],
            ['name' => 'دفاع عن النفس', 'gender_allowed' => 'mixed', 'is_private_equipment' => false],
        ];

        foreach ($activities as $activity) {
            Activity::updateOrCreate(
                ['name' => $activity['name']],
                $activity
            );
        }
    }
}
