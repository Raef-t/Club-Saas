<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Modules\MemberManager\Models\Member;

class PlayerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Ensure there is at least one branch
            $branchId = DB::table('branches')->value('id');
            if (!$branchId) {
                // Ensure there is at least one club first
                $clubId = DB::table('clubs')->value('id');
                if (!$clubId) {
                    $clubId = DB::table('clubs')->insertGetId([
                        'name' => json_encode(['en' => 'Main Club', 'ar' => 'النادي الرئيسي']),
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $branchId = DB::table('branches')->insertGetId([
                    'club_id' => $clubId,
                    'name' => json_encode(['en' => 'Main Branch', 'ar' => 'الفرع الرئيسي']),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 1. Create Player Person Profile
            $playerPerson = Person::create([
                'full_name' => 'Yaman Player',
                'gender' => 'male',
                'type' => 'player',
                'dob' => '2005-01-01',
                'email' => 'player@clubsaas.com',
            ]);

            $playerPerson->contacts()->create([
                'name' => 'Personal',
                'relation' => 'self',
                'phone_number' => '0555555555',
            ]);

            // 2. Create Player Profile
            $playerPerson->playerProfile()->create([
                'qr_code' => \Illuminate\Support\Str::uuid()->toString(),
                'blood_type' => 'O+',
                'medical_conditions' => ['None'],
                'emergency_contact' => [
                    'name' => 'Emergency Parent',
                    'phone' => '0555555556'
                ]
            ]);

            // 3. Create Member record for the player
            Member::create([
                'branch_id' => $branchId,
                'person_id' => $playerPerson->id,
                'member_number' => 'MEM-' . rand(1000, 9999),
                'membership_status' => 'active',
                'join_date' => now(),
            ]);

            // 4. Create Player User Account for Login
            User::create([
                'person_id' => $playerPerson->id,
                'username' => 'player',
                'password' => Hash::make('password123'),
                'is_active' => true,
            ]);
            
            $this->command->info('✅ Fake Player created successfully!');
            $this->command->info('👤 Username: player');
            $this->command->info('🔑 Password: password123');
        });
    }
}
