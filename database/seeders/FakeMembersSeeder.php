<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Modules\Authentication\Models\CoachProfile;
use Modules\MemberManager\Models\Member;
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Modules\SubscriptionManager\Models\PlayerSubscription;
use Faker\Factory as Faker;

class FakeMembersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('ar_SA');

        DB::transaction(function () use ($faker) {
            // 1. Ensure there is a Branch
            $branchId = DB::table('branches')->value('id');
            if (!$branchId) {
                $clubId = DB::table('clubs')->insertGetId([
                    'name' => json_encode(['en' => 'Main Club', 'ar' => 'النادي الرئيسي']),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $branchId = DB::table('branches')->insertGetId([
                    'club_id' => $clubId,
                    'name' => json_encode(['en' => 'Main Branch', 'ar' => 'الفرع الرئيسي']),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 2. Ensure there is a Subscription Plan
            $plan = SubscriptionPlan::first();
            if (!$plan) {
                $plan = SubscriptionPlan::create([
                    'name' => ['en' => 'Pro Monthly Plan', 'ar' => 'الاشتراك الشهري الاحترافي'],
                    'type' => 'monthly',
                    'duration_days' => 30,
                    'session_count' => 12,
                    'max_freeze_count' => 1,
                    'max_freeze_days' => 7,
                    'base_price' => 500,
                    'is_active' => true,
                ]);
            }

            // 3. Ensure there is a Coach
            $coachPerson = Person::where('type', 'coach')->first();
            if (!$coachPerson) {
                $coachPerson = Person::create([
                    'full_name' => 'الكابتن محمد ' . $faker->lastName,
                    'gender' => 'male',
                    'type' => 'coach',
                    'mobile_1' => $faker->unique()->phoneNumber,
                    'email' => $faker->unique()->safeEmail,
                ]);

                CoachProfile::create([
                    'person_id' => $coachPerson->id,
                    'branch_id' => $branchId,
                    'specialization' => 'كمال أجسام',
                    'experience_years' => 5,
                    'work_type' => 'full_time',
                    'start_date' => now(),
                ]);

                User::create([
                    'person_id' => $coachPerson->id,
                    'username' => 'coach_' . $faker->unique()->randomNumber(4),
                    'password' => Hash::make('password123'),
                    'is_active' => true,
                ]);
            }

            // 4. Create Fake Players
            $numberOfPlayers = 5; // You can change this number
            $this->command->info("Creating {$numberOfPlayers} fake players with coach and subscription...");

            for ($i = 0; $i < $numberOfPlayers; $i++) {
                // a. Create Person for Player
                $playerPerson = Person::create([
                    'full_name' => $faker->firstName . ' ' . $faker->lastName,
                    'gender' => $faker->randomElement(['male', 'female']),
                    'type' => 'player',
                    'dob' => $faker->date('Y-m-d', '2005-01-01'),
                    'mobile_1' => $faker->unique()->phoneNumber,
                    'email' => $faker->unique()->safeEmail,
                ]);

                // b. Create Player Profile
                $playerPerson->playerProfile()->create([
                    'qr_code' => Str::uuid()->toString(),
                    'blood_type' => $faker->randomElement(['O+', 'A+', 'B+', 'AB+']),
                ]);

                // c. Create Member
                $member = Member::create([
                    'branch_id' => $branchId,
                    'person_id' => $playerPerson->id,
                    'member_number' => 'MEM-' . $faker->unique()->randomNumber(5, true),
                    'membership_status' => 'active',
                    'join_date' => now(),
                ]);

                // d. Create User Account
                User::create([
                    'person_id' => $playerPerson->id,
                    'username' => 'player_' . $faker->unique()->randomNumber(5, true),
                    'password' => Hash::make('password123'),
                    'is_active' => true,
                ]);

                // e. Assign Subscription & Coach
                PlayerSubscription::create([
                    'member_id' => $member->id,
                    'coach_id' => $coachPerson->coachProfile->id ?? null, // Assuming coach profile ID or Person ID? Let's check PlayerSubscription. 
                    'plan_id' => $plan->id,
                    'total_amount' => $plan->base_price,
                    'paid_amount' => $plan->base_price,
                    'remaining_amount' => 0,
                    'start_date' => now(),
                    'end_date' => now()->addDays($plan->duration_days),
                    'status' => 'active',
                    'remaining_sessions' => $plan->session_count,
                    'notes' => 'Generated by FakeMembersSeeder',
                ]);
            }

            $this->command->info("✅ Successfully created {$numberOfPlayers} players, assigned to coach and subscribed to plan.");
        });
    }
}
