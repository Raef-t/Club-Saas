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
use Modules\MemberManager\Models\Member;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Laravel\Sanctum\Sanctum;

class UpdateSubscriptionPricingAndReceiptsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $branch;
    protected $coach;
    protected $member;
    protected $privateActivity;
    protected $plan;

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
            'username' => 'admin_upd_' . uniqid(),
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

        $staffActivity = StaffActivity::create([
            'staff_id' => $this->coach->id,
            'activity_id' => $this->privateActivity->id,
        ]);

        $this->plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'الاشتراك الذهبي',
            'base_price' => 350.00,
            'coach_price' => 200.00,
            'branch_price' => 150.00,
            'sessions_per_week' => 3,
            'session_count' => 12,
            'max_subscribers' => 0,
            'status' => 'active',
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $this->plan->id,
            'staff_activity_id' => $staffActivity->id,
        ]);
    }

    public function test_can_update_subscription_receipt_numbers()
    {
        // 1. Create a subscription initially
        $createPayload = [
            'member_id' => $this->member->id,
            'plan_id' => $this->plan->id,
            'months_count' => 1,
            'start_date' => now()->toDateString(),
            'paid_amount' => 350.00,
            'payment_method' => 'cash',
            'coach_receipt_number' => 'REC-COACH-001',
            'branch_receipt_number' => 'REC-CLUB-001',
        ];

        $createRes = $this->postJson('/api/v1/player-subscriptions', $createPayload);
        $createRes->assertStatus(201);
        $subscriptionId = $createRes->json('data.id');

        // Verify initial state
        $this->assertDatabaseHas('player_subscriptions', [
            'id' => $subscriptionId,
            'coach_receipt_number' => 'REC-COACH-001',
            'branch_receipt_number' => 'REC-CLUB-001',
        ]);

        // 2. Now update with user's exact payload
        $updatePayload = [
            'reason' => 'تعديل تاريخ بداية ونهاية الاشتراك',
            'receipt_number' => 'REC-CLUB-004',
            'coach_receipt_number' => 'REC-COACH-005',
            'branch_receipt_number' => 'REC-CLUB-006',
        ];

        $updateRes = $this->putJson("/api/v1/player-subscriptions/{$subscriptionId}", $updatePayload);

        $updateRes->assertStatus(200)
            ->assertJsonPath('data.coach_receipt_number', 'REC-COACH-005')
            ->assertJsonPath('data.branch_receipt_number', 'REC-CLUB-006')
            ->assertJsonPath('data.revenue_split.coach_receipt_number', 'REC-COACH-005')
            ->assertJsonPath('data.revenue_split.branch_receipt_number', 'REC-CLUB-006');

        // Verify in database
        $this->assertDatabaseHas('player_subscriptions', [
            'id' => $subscriptionId,
            'coach_receipt_number' => 'REC-COACH-005',
            'branch_receipt_number' => 'REC-CLUB-006',
            'reason' => 'تعديل تاريخ بداية ونهاية الاشتراك',
        ]);

        $this->assertDatabaseHas('subscription_revenue_splits', [
            'player_subscription_id' => $subscriptionId,
            'coach_receipt_number' => 'REC-COACH-005',
            'branch_receipt_number' => 'REC-CLUB-006',
        ]);

        $this->assertDatabaseHas('payments', [
            'receipt_number' => 'REC-COACH-005',
            'reason' => 'دفعة اشتراك المدرب',
        ]);

        $this->assertDatabaseHas('payments', [
            'receipt_number' => 'REC-CLUB-006',
            'reason' => 'دفعة اشتراك النادي',
        ]);
    }

    public function test_can_update_subscription_coach_and_branch_prices()
    {
        // 1. Create a subscription initially
        $createPayload = [
            'member_id' => $this->member->id,
            'plan_id' => $this->plan->id,
            'months_count' => 1,
            'start_date' => now()->toDateString(),
            'paid_amount' => 350.00,
            'payment_method' => 'cash',
            'coach_paid_amount' => 200.00,
            'branch_paid_amount' => 150.00,
            'coach_receipt_number' => 'REC-COACH-001',
            'branch_receipt_number' => 'REC-CLUB-001',
        ];

        $createRes = $this->postJson('/api/v1/player-subscriptions', $createPayload);
        $createRes->assertStatus(201);
        $subscriptionId = $createRes->json('data.id');

        // 2. Update with new coach and branch paid amounts
        $updatePayload = [
            'reason' => 'تعديل أسعار الكوتش والنادي',
            'coach_paid_amount' => 220.00,
            'branch_paid_amount' => 130.00,
            'coach_receipt_number' => 'REC-COACH-NEW',
            'branch_receipt_number' => 'REC-CLUB-NEW',
        ];

        $updateRes = $this->putJson("/api/v1/player-subscriptions/{$subscriptionId}", $updatePayload);

        $updateRes->assertStatus(200)
            ->assertJsonPath('data.paid_amount', '350.00')
            ->assertJsonPath('data.coach_receipt_number', 'REC-COACH-NEW')
            ->assertJsonPath('data.branch_receipt_number', 'REC-CLUB-NEW')
            ->assertJsonPath('data.revenue_split.coach_amount', '220.00')
            ->assertJsonPath('data.revenue_split.club_amount', '130.00');

        $this->assertDatabaseHas('payments', [
            'receipt_number' => 'REC-COACH-NEW',
            'amount' => 220.00,
            'reason' => 'دفعة اشتراك المدرب',
        ]);

        $this->assertDatabaseHas('payments', [
            'receipt_number' => 'REC-CLUB-NEW',
            'amount' => 130.00,
            'reason' => 'دفعة اشتراك النادي',
        ]);
    }

    public function test_can_update_using_coach_price_and_branch_price_aliases()
    {
        // 1. Create a subscription initially
        $createPayload = [
            'member_id' => $this->member->id,
            'plan_id' => $this->plan->id,
            'months_count' => 1,
            'start_date' => now()->toDateString(),
            'paid_amount' => 350.00,
            'payment_method' => 'cash',
            'coach_price' => 200.00,
            'branch_price' => 150.00,
            'coach_receipt_number' => 'REC-COACH-001',
            'branch_receipt_number' => 'REC-CLUB-001',
        ];

        $createRes = $this->postJson('/api/v1/player-subscriptions', $createPayload);
        $createRes->assertStatus(201);
        $subscriptionId = $createRes->json('data.id');

        // 2. Update with coach_price and branch_price aliases
        $updatePayload = [
            'reason' => 'تعديل بالأسماء البديلة',
            'coach_price' => 250.00,
            'branch_price' => 100.00,
        ];

        $updateRes = $this->putJson("/api/v1/player-subscriptions/{$subscriptionId}", $updatePayload);

        $updateRes->assertStatus(200)
            ->assertJsonPath('data.paid_amount', '350.00')
            ->assertJsonPath('data.revenue_split.coach_amount', '250.00')
            ->assertJsonPath('data.revenue_split.club_amount', '100.00');

        $this->assertDatabaseHas('payments', [
            'amount' => 250.00,
            'reason' => 'دفعة اشتراك المدرب',
        ]);

        $this->assertDatabaseHas('payments', [
            'amount' => 100.00,
            'reason' => 'دفعة اشتراك النادي',
        ]);
    }

    public function test_returns_404_when_subscription_not_found()
    {
        $response = $this->getJson('/api/v1/player-subscriptions/999999');
        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Record not found.',
            ]);
    }
}
