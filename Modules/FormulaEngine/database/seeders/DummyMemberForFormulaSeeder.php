<?php

namespace Modules\FormulaEngine\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Models\Person;
use Modules\MemberManager\Models\Member;
use Modules\MemberManager\Models\MemberMeasurement;
use Modules\ClubManager\Models\Branch;

class DummyMemberForFormulaSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure we have a branch (Create dummy if none exists)
        $branchId = DB::table('branches')->first()->id ?? DB::table('branches')->insertGetId([
            'name' => 'الفرع الرئيسي',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Create a Person (Male, born in 1996 -> ~30 years old)
        $person = Person::create([
            'full_name' => 'لاعب تجريبي للمعادلات',
            'gender' => 'male',
            'type' => 'player',
            'dob' => '1996-05-15',
            'mobile_1' => '0500000000',
        ]);

        // 3. Create a Member profile for this person
        $member = Member::create([
            'branch_id' => $branchId,
            'person_id' => $person->id,
            'member_number' => 'MEM-9999',
            'membership_status' => 'active',
            'join_date' => now(),
        ]);

        // 4. Add Measurements for this member
        // Weight: 85kg, Height: 180cm, Waist: 90cm, Neck: 40cm
        MemberMeasurement::create([
            'member_id' => $member->id,
            'measurement_date' => now(),
            'weight' => 85.0,
            'height' => 180.0,
            'waist_circumference' => 90.0,
            'neck_circumference' => 40.0,
        ]);

        $this->command->info("✅ Dummy Member Created successfully!");
        $this->command->info("🧑 Name: {$person->full_name}");
        $this->command->info("🆔 Member ID: {$member->id}");
        $this->command->info("⚖️ Measurements: Weight 85kg, Height 180cm, Waist 90cm, Neck 40cm");
        $this->command->info("➡️ Use Member ID [{$member->id}] in Postman for testing!");
    }
}
