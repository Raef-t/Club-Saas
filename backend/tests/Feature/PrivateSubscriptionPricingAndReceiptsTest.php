<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Branch;
use Modules\Sports\Models\Activity;
use Modules\Sports\Models\ActivityType;
use Modules\Sports\Models\StaffActivity;
use Modules\StaffManager\Models\Staff;
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Modules\SubscriptionManager\Models\SubscriptionPlanActivity;
use Modules\SubscriptionManager\Models\SubscriptionRevenueSplit;
use Modules\MemberManager\Models\Member;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Laravel\Sanctum\Sanctum;

class PrivateSubscriptionPricingAndReceiptsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $branch;
    protected $coach;
    protected $member;
    protected $privateActivity;

    protected function setUp(): void
    {
        parent::setUp();

        $person = Person::create([
            'full_name' => 'Admin Test User',
            'gender' => 'male',
            'type' => 'staff',
        ]);

        $this->user = User::create([
            'person_id' => $person->id,
            'username' => 'admin_pricing_' . uniqid(),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $role = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'sanctum',
        ]);
        $this->user->assignRole($role);
        Sanctum::actingAs($this->user, ['*']);

        $club = Club::create(['name' => 'Gold Gym', 'is_active' => true]);
        $this->branch = Branch::create(['club_id' => $club->id, 'name' => 'Damascus Branch', 'is_active' => true]);

        // Create Safe
        $accountId = \Illuminate\Support\Facades\DB::table('acc_accounts')->insertGetId([
            'code' => '101' . rand(1000, 9999),
            'name' => 'Safe Account ' . uniqid(),
            'type' => 'asset',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('acc_safes')->insert([
            'branch_id' => $this->branch->id,
            'name' => 'Main Safe',
            'account_id' => $accountId,
            'currency' => 'SYP',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Coach
        $coachPerson = Person::create(['full_name' => 'Captain Omar', 'gender' => 'male', 'type' => 'staff']);
        $this->coach = Staff::create([
            'person_id' => $coachPerson->id,
            'role' => 'coach',
            'work_status' => 'active',
            'is_active' => true,
        ]);

        // Member
        $memberPerson = Person::create(['full_name' => 'Tariq Player', 'gender' => 'male', 'type' => 'player']);
        $this->member = Member::create([
            'person_id' => $memberPerson->id,
            'branch_id' => $this->branch->id,
            'member_number' => 'M-' . uniqid(),
            'status' => 'active',
        ]);

        // Private activity (أجهزة خاص)
        $privateType = ActivityType::create([
            'name' => 'تدريب خاص',
            'is_active' => true,
            'is_session_based' => false,
            'has_unlimited_subscribers' => true,
        ]);

        $this->privateActivity = Activity::create([
            'branch_id' => $this->branch->id,
            'activity_type_id' => $privateType->id,
            'name' => 'أجهزة خاص',
            'is_private_equipment' => true,
            'is_active' => true,
        ]);
    }

    public function test_can_create_plan_with_coach_price_and_branch_price()
    {
        $payload = [
            'branch_id' => $this->branch->id,
            'name' => 'اشتراك خاص 3 أيام',
            'coach_price' => 200.00,
            'branch_price' => 100.00,
            'sessions_per_week' => 3,
            'session_count' => 12,
            'activities' => [
                [
                    'activity_id' => $this->privateActivity->id,
                    'coach_id' => $this->coach->id,
                ]
            ],
        ];

        $response = $this->postJson('/api/v1/subscription-plans', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'اشتراك خاص 3 أيام')
            ->assertJsonPath('data.coach_price', '200.00')
            ->assertJsonPath('data.branch_price', '100.00')
            ->assertJsonPath('data.base_price', '300.00');

        $this->assertDatabaseHas('subscription_plans', [
            'name' => 'اشتراك خاص 3 أيام',
            'coach_price' => 200.00,
            'branch_price' => 100.00,
            'base_price' => 300.00,
        ]);
    }

    public function test_subscribing_to_private_plan_creates_two_payments_with_separate_receipts()
    {
        // 1. Create plan with coach_price = 200 and branch_price = 100 (Total = 300)
        $staffActivity = StaffActivity::create([
            'staff_id' => $this->coach->id,
            'activity_id' => $this->privateActivity->id,
        ]);

        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'اشتراك خاص 3 أيام',
            'base_price' => 300.00,
            'coach_price' => 200.00,
            'branch_price' => 100.00,
            'sessions_per_week' => 3,
            'session_count' => 12,
            'max_subscribers' => 0,
            'status' => 'active',
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $plan->id,
            'staff_activity_id' => $staffActivity->id,
        ]);

        // 2. Subscribe member with coach_receipt_number and branch_receipt_number
        $payload = [
            'member_id' => $this->member->id,
            'plan_id' => $plan->id,
            'months_count' => 1,
            'start_date' => now()->toDateString(),
            'paid_amount' => 300.00,
            'payment_method' => 'cash',
            'coach_receipt_number' => 'REC-COACH-888',
            'branch_receipt_number' => 'REC-CLUB-999',
        ];

        $response = $this->postJson('/api/v1/player-subscriptions', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.coach_receipt_number', 'REC-COACH-888')
            ->assertJsonPath('data.branch_receipt_number', 'REC-CLUB-999')
            ->assertJsonPath('data.paid_amount', '300.00')
            ->assertJsonCount(2, 'data.payments');

        $subscriptionId = $response->json('data.id');

        // 3. Verify in Database
        $this->assertDatabaseHas('player_subscriptions', [
            'id' => $subscriptionId,
            'coach_receipt_number' => 'REC-COACH-888',
            'branch_receipt_number' => 'REC-CLUB-999',
            'total_amount' => 300.00,
            'paid_amount' => 300.00,
            'remaining_amount' => 0.00,
        ]);

        // Verify two separate Payment records created
        $this->assertDatabaseHas('payments', [
            'receipt_number' => 'REC-COACH-888',
            'amount' => 200.00,
            'reason' => 'دفعة اشتراك المدرب',
        ]);

        $this->assertDatabaseHas('payments', [
            'receipt_number' => 'REC-CLUB-999',
            'amount' => 100.00,
            'reason' => 'دفعة اشتراك النادي',
        ]);

        // Verify SubscriptionRevenueSplit snapshot
        $this->assertDatabaseHas('subscription_revenue_splits', [
            'player_subscription_id' => $subscriptionId,
            'coach_id' => $this->coach->id,
            'total_amount' => 300.00,
            'coach_amount' => 200.00,
            'club_amount' => 100.00,
            'coach_receipt_number' => 'REC-COACH-888',
            'branch_receipt_number' => 'REC-CLUB-999',
        ]);

        // 4. Verify show endpoint
        $showResponse = $this->getJson("/api/v1/player-subscriptions/{$subscriptionId}");
        $showResponse->assertStatus(200)
            ->assertJsonPath('data.coach_receipt_number', 'REC-COACH-888')
            ->assertJsonPath('data.branch_receipt_number', 'REC-CLUB-999')
            ->assertJsonPath('data.revenue_split.coach_receipt_number', 'REC-COACH-888')
            ->assertJsonPath('data.revenue_split.branch_receipt_number', 'REC-CLUB-999')
            ->assertJsonPath('data.revenue_split.coach_amount', '200.00')
            ->assertJsonPath('data.revenue_split.club_amount', '100.00');
    }

    public function test_subscribing_with_custom_coach_and_branch_paid_amounts()
    {
        $staffActivity = StaffActivity::create([
            'staff_id' => $this->coach->id,
            'activity_id' => $this->privateActivity->id,
        ]);

        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'اشتراك تدريب خاص مخصص',
            'base_price' => 500.00,
            'coach_price' => 300.00,
            'branch_price' => 200.00,
            'status' => 'active',
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $plan->id,
            'staff_activity_id' => $staffActivity->id,
        ]);

        $payload = [
            'member_id' => $this->member->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'paid_amount' => 300.00,
            'coach_paid_amount' => 200.00,
            'branch_paid_amount' => 100.00,
            'coach_receipt_number' => 'COACH-PARTIAL-1',
            'branch_receipt_number' => 'CLUB-PARTIAL-2',
        ];

        $response = $this->postJson('/api/v1/player-subscriptions', $payload);

        $response->assertStatus(201)
            ->assertJsonCount(2, 'data.payments');

        $this->assertDatabaseHas('payments', [
            'receipt_number' => 'COACH-PARTIAL-1',
            'amount' => 200.00,
        ]);

        $this->assertDatabaseHas('payments', [
            'receipt_number' => 'CLUB-PARTIAL-2',
            'amount' => 100.00,
        ]);
    }

    public function test_revenue_split_takes_commission_from_coach_price_and_adds_branch_price_to_club()
    {
        // Coach has 70% commission on private subscriptions
        \Modules\StaffManager\Models\StaffContract::create([
            'staff_id' => $this->coach->id,
            'employment_type' => 'hybrid',
            'commission_type' => 'percentage',
            'private_commission_rate' => 70.00,
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $staffActivity = StaffActivity::create([
            'staff_id' => $this->coach->id,
            'activity_id' => $this->privateActivity->id,
        ]);

        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'اشتراك خاص نسبة من سعر الكوتش',
            'base_price' => 300.00,
            'coach_price' => 200.00,
            'branch_price' => 100.00,
            'status' => 'active',
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $plan->id,
            'staff_activity_id' => $staffActivity->id,
        ]);

        $payload = [
            'member_id' => $this->member->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'paid_amount' => 300.00,
            'coach_receipt_number' => 'REC-COACH-70',
            'branch_receipt_number' => 'REC-CLUB-30',
        ];

        $response = $this->postJson('/api/v1/player-subscriptions', $payload);

        $response->assertStatus(201);
        $subscriptionId = $response->json('data.id');

        // Coach gets 70% of 200 = 140.00
        // Club gets 100.00 (branch_price) + 30% of 200 (60.00) = 160.00
        // Percentages: Coach = 70.00%, Club = 30.00%
        $this->assertDatabaseHas('subscription_revenue_splits', [
            'player_subscription_id' => $subscriptionId,
            'total_amount' => 300.00,
            'coach_amount' => 140.00,
            'club_amount' => 160.00,
            'coach_percentage' => 70.00,
            'club_percentage' => 30.00,
            'coach_receipt_number' => 'REC-COACH-70',
            'branch_receipt_number' => 'REC-CLUB-30',
        ]);

        $showResponse = $this->getJson("/api/v1/player-subscriptions/{$subscriptionId}");
        $showResponse->assertStatus(200)
            ->assertJsonPath('data.revenue_split.coach_amount', '140.00')
            ->assertJsonPath('data.revenue_split.club_amount', '160.00')
            ->assertJsonPath('data.revenue_split.coach_percentage', '70.00')
            ->assertJsonPath('data.revenue_split.club_percentage', '30.00');
    }

    public function test_revenue_split_falls_back_to_branch_default_percentages_when_contract_rate_is_zero()
    {
        // Coach has 0% in contract, Branch has default 40% coach / 60% club
        \Modules\StaffManager\Models\StaffContract::create([
            'staff_id' => $this->coach->id,
            'employment_type' => 'commission_based',
            'commission_rate' => 0.00,
            'private_commission_rate' => 0.00,
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        \Modules\ClubManager\Models\BranchSetting::updateOrCreate(
            ['branch_id' => $this->branch->id],
            [
                'default_coach_commission_percentage' => 40.00,
                'default_club_commission_percentage' => 60.00,
                'private_subscription_commission' => 0.00,
            ]
        );

        $staffActivity = StaffActivity::create([
            'staff_id' => $this->coach->id,
            'activity_id' => $this->privateActivity->id,
        ]);

        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'خطة اختبار الفولباك للفرع',
            'base_price' => 350.00,
            'coach_price' => 200.00,
            'branch_price' => 150.00,
            'status' => 'active',
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $plan->id,
            'staff_activity_id' => $staffActivity->id,
        ]);

        $payload = [
            'member_id' => $this->member->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'paid_amount' => 350.00,
            'coach_receipt_number' => 'REC-COACH-FB',
            'branch_receipt_number' => 'REC-CLUB-FB',
        ];

        $response = $this->postJson('/api/v1/player-subscriptions', $payload);
        $response->assertStatus(201);
        $subscriptionId = $response->json('data.id');

        // From 200 coach_price:
        // Coach gets 40% = 80.00
        // Club gets 150 + 60% of 200 (120.00) = 270.00
        // Percentages: Coach = 40.00%, Club = 60.00%
        $this->assertDatabaseHas('subscription_revenue_splits', [
            'player_subscription_id' => $subscriptionId,
            'total_amount' => 350.00,
            'coach_amount' => 80.00,
            'club_amount' => 270.00,
            'coach_percentage' => 40.00,
            'club_percentage' => 60.00,
            'coach_receipt_number' => 'REC-COACH-FB',
            'branch_receipt_number' => 'REC-CLUB-FB',
        ]);
    }
}

