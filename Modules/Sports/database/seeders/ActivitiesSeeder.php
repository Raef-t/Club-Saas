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
            ['name' => ['ar' => 'أجهزة عام', 'en' => 'General Equipment'], 'gender_allowed' => 'mixed', 'is_private_equipment' => false],
            ['name' => ['ar' => 'أجهزة خاص', 'en' => 'Private Equipment'], 'gender_allowed' => 'mixed', 'is_private_equipment' => true],
            ['name' => ['ar' => 'ايروبيك', 'en' => 'Aerobics'], 'gender_allowed' => 'mixed', 'is_private_equipment' => false],
            ['name' => ['ar' => 'كروسفيت', 'en' => 'CrossFit'], 'gender_allowed' => 'mixed', 'is_private_equipment' => false],
            ['name' => ['ar' => 'X55', 'en' => 'X55'], 'gender_allowed' => 'mixed', 'is_private_equipment' => false],
            ['name' => ['ar' => 'جمباز', 'en' => 'Gymnastics'], 'gender_allowed' => 'mixed', 'is_private_equipment' => false],
            ['name' => ['ar' => 'يوغا', 'en' => 'Yoga'], 'gender_allowed' => 'mixed', 'is_private_equipment' => false],
            ['name' => ['ar' => 'رقص شرقي', 'en' => 'Belly Dance'], 'gender_allowed' => 'female', 'is_private_equipment' => false],
            ['name' => ['ar' => 'زومبا', 'en' => 'Zumba'], 'gender_allowed' => 'mixed', 'is_private_equipment' => false],
            ['name' => ['ar' => 'مكس', 'en' => 'Mix'], 'gender_allowed' => 'mixed', 'is_private_equipment' => false],
            ['name' => ['ar' => 'بيلاتس', 'en' => 'Pilates'], 'gender_allowed' => 'mixed', 'is_private_equipment' => false],
            ['name' => ['ar' => 'كيك بوكسينغ', 'en' => 'Kickboxing'], 'gender_allowed' => 'mixed', 'is_private_equipment' => false],
            ['name' => ['ar' => 'زومبا أطفال', 'en' => 'Kids Zumba'], 'gender_allowed' => 'mixed', 'is_private_equipment' => false],
            ['name' => ['ar' => 'سباحة', 'en' => 'Swimming'], 'gender_allowed' => 'mixed', 'is_private_equipment' => false],
            ['name' => ['ar' => 'دفاع عن النفس', 'en' => 'Self Defense'], 'gender_allowed' => 'mixed', 'is_private_equipment' => false],
        ];

        foreach ($activities as $activity) {
            Activity::updateOrCreate(
                ['name->ar' => $activity['name']['ar']],
                $activity
            );
        }
    }
}
