<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\ClubManager\Models\Club;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Illuminate\Support\Facades\Hash;
use Modules\StaffManager\Models\Staff;

class TechnogymSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Club
        $club = Club::create([
            'name' => 'technogym',
            'is_active' => true,
        ]);

        // 2. Create Branch
        $branch = $club->branches()->create([
            'name' => 'الفرع الرئيسي',
            'gender_restriction' => 'mixed',
            'type' => 'main',
            'is_active' => true,
        ]);
    }
}
