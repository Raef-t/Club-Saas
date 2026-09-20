<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Branch;
use Modules\Authentication\Models\Person;
use Modules\MemberManager\Models\Member;
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Modules\SubscriptionManager\Models\PlayerSubscription;
use Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus;
use Modules\Authentication\Models\User;
use Modules\Sports\Models\Activity;
use Modules\Sports\Models\ActivityType;
use Carbon\Carbon;

class SubscriptionPeriodCapacityTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $branch;
    protected $plan;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-08-15'));

        $person = Person::create(['full_name' => 'Admin User', 'gender' => 'male', 'type' => 'staff']);
        $this->user = User::create([
            'person_id' => $person->id,
            'username' => 'admin_' . uniqid(),
            'password' => 'password123',
            'is_active' => true,
        ]);
        $role = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'sanctum',
        ]);
        $this->user->assignRole($role);
        $this->actingAs($this->user, 'sanctum');

        $club = Club::create(['name' => 'Club Test', 'is_active' => true]);
        $this->branch = Branch::create(['club_id' => $club->id, 'name' => 'Main Branch', 'is_active' => true]);

        $activityType = ActivityType::create(['name' => 'Aerobics Type', 'is_active' => true]);
        $activity = Activity::create([
            'activity_type_id' => $activityType->id,
            'name' => 'Aerobics',
            'is_active' => true,
        ]);

        $this->plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'فعالية ايروبيك',
            'base_price' => 100.00,
            'max_subscribers' => 10,
            'current_subscribers' => 0,
            'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createMember(): Member
    {
        $person = Person::create([
            'full_name' => 'عضو ' . uniqid(),
            'gender' => 'male',
            'type' => 'player',
        ]);

        return Member::create([
            'person_id' => $person->id,
            'branch_id' => $this->branch->id,
            'member_number' => (string) random_int(100000, 999999),
            'status' => 'active',
            'join_date' => now()->toDateString(),
        ]);
    }

    /**
     * Test the user's scenario:
     * 8 subscribers in Month 8 (August).
     * A player subscribes in Month 8 for Month 9 (September).
     * The count for Month 8 must remain 8/10 (available slots = 2).
     * The count for Month 9 must be 1/10 (available slots = 9).
     */
    public function test_future_month_subscription_does_not_occupy_current_month_capacity(): void
    {
        // 1. Create 8 active subscriptions for Month 8 (2026-08-01 to 2026-08-31)
        for ($i = 1; $i <= 8; $i++) {
            $member = $this->createMember();
            $this->postJson('/api/v1/player-subscriptions', [
                'member_id' => $member->id,
                'plan_id' => $this->plan->id,
                'start_date' => '2026-08-01',
                'end_date' => '2026-08-31',
                'months_count' => 1,
                'paid_amount' => 100.00,
            ])->assertStatus(201);
        }

        // Check August capacity via API (date=2026-08-15)
        $augustResponse = $this->getJson("/api/v1/subscription-plans?branch_id={$this->branch->id}&date=2026-08-15");
        $augustResponse->assertStatus(200);
        $augustPlan = collect($augustResponse->json('data'))->firstWhere('id', $this->plan->id);
        $this->assertEquals(8, $augustPlan['current_subscribers']);
        $this->assertEquals(2, $augustPlan['available_slots']);
        $this->assertTrue($augustPlan['is_available']);

        // 2. Player 9 registers in Month 8 for Month 9 (2026-09-01 to 2026-09-30)
        $septemberMember = $this->createMember();
        $regResponse = $this->postJson('/api/v1/player-subscriptions', [
            'member_id' => $septemberMember->id,
            'plan_id' => $this->plan->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'months_count' => 1,
            'paid_amount' => 100.00,
        ]);
        $regResponse->assertStatus(201);

        // 3. Verify August capacity is STILL 8/10 (2 available slots)
        $augustAfter = $this->getJson("/api/v1/subscription-plans?branch_id={$this->branch->id}&date=2026-08-15");
        $augustAfter->assertStatus(200);
        $augustPlanAfter = collect($augustAfter->json('data'))->firstWhere('id', $this->plan->id);
        $this->assertEquals(8, $augustPlanAfter['current_subscribers']);
        $this->assertEquals(2, $augustPlanAfter['available_slots']);
        $this->assertTrue($augustPlanAfter['is_available']);

        // Also check registration endpoint for August
        $regAugust = $this->getJson("/api/v1/subscription-plans/registration?branch_id={$this->branch->id}&date=2026-08-15");
        $regAugust->assertStatus(200);
        $regAugustPlan = collect($regAugust->json('data'))->firstWhere('id', $this->plan->id);
        $this->assertEquals(8, $regAugustPlan['current_subscribers']);
        $this->assertEquals(2, $regAugustPlan['available_slots']);
        $this->assertTrue($regAugustPlan['is_available']);

        // 4. Verify September capacity is 1/10 (9 available slots)
        $septemberResponse = $this->getJson("/api/v1/subscription-plans?branch_id={$this->branch->id}&date=2026-09-15");
        $septemberResponse->assertStatus(200);
        $septPlan = collect($septemberResponse->json('data'))->firstWhere('id', $this->plan->id);
        $this->assertEquals(1, $septPlan['current_subscribers']);
        $this->assertEquals(9, $septPlan['available_slots']);
        $this->assertTrue($septPlan['is_available']);
    }

    /**
     * Test capacity enforcement per period:
     * If September fills up (10/10), attempting an 11th registration in September fails,
     * but August still has 2 slots available and allows registration.
     */
    public function test_capacity_limit_is_enforced_for_specific_period(): void
    {
        // Fill Month 8 with 8 subscribers
        for ($i = 1; $i <= 8; $i++) {
            $member = $this->createMember();
            $this->postJson('/api/v1/player-subscriptions', [
                'member_id' => $member->id,
                'plan_id' => $this->plan->id,
                'start_date' => '2026-08-01',
                'end_date' => '2026-08-31',
                'months_count' => 1,
                'paid_amount' => 100.00,
            ])->assertStatus(201);
        }

        // Fill Month 9 with 10 subscribers (maximum capacity)
        for ($i = 1; $i <= 10; $i++) {
            $member = $this->createMember();
            $this->postJson('/api/v1/player-subscriptions', [
                'member_id' => $member->id,
                'plan_id' => $this->plan->id,
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-30',
                'months_count' => 1,
                'paid_amount' => 100.00,
            ])->assertStatus(201);
        }

        // Attempting an 11th subscriber in September MUST fail
        $extraMemberSept = $this->createMember();
        $failResponse = $this->postJson('/api/v1/player-subscriptions', [
            'member_id' => $extraMemberSept->id,
            'plan_id' => $this->plan->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'months_count' => 1,
            'paid_amount' => 100.00,
        ]);
        $failResponse->assertStatus(400);

        // However, August still has 2 slots open, so a 9th subscriber in August MUST SUCCEED
        $extraMemberAug = $this->createMember();
        $augSuccessResponse = $this->postJson('/api/v1/player-subscriptions', [
            'member_id' => $extraMemberAug->id,
            'plan_id' => $this->plan->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'months_count' => 1,
            'paid_amount' => 100.00,
        ]);
        $augSuccessResponse->assertStatus(201);

        // August now has 9 subscribers
        $augCheck = $this->getJson("/api/v1/subscription-plans?branch_id={$this->branch->id}&date=2026-08-15");
        $augPlan = collect($augCheck->json('data'))->firstWhere('id', $this->plan->id);
        $this->assertEquals(9, $augPlan['current_subscribers']);
        $this->assertEquals(1, $augPlan['available_slots']);
    }

    /**
     * Test multi-month subscription occupying both months.
     */
    public function test_multi_month_subscription_occupies_both_months(): void
    {
        // 1 player subscribes for 2 months (August + September)
        $member = $this->createMember();
        $this->postJson('/api/v1/player-subscriptions', [
            'member_id' => $member->id,
            'plan_id' => $this->plan->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-09-30',
            'months_count' => 2,
            'paid_amount' => 200.00,
        ])->assertStatus(201);

        // In August, count is 1
        $augResponse = $this->getJson("/api/v1/subscription-plans?branch_id={$this->branch->id}&date=2026-08-15");
        $augPlan = collect($augResponse->json('data'))->firstWhere('id', $this->plan->id);
        $this->assertEquals(1, $augPlan['current_subscribers']);

        // In September, count is also 1
        $septResponse = $this->getJson("/api/v1/subscription-plans?branch_id={$this->branch->id}&date=2026-09-15");
        $septPlan = collect($septResponse->json('data'))->firstWhere('id', $this->plan->id);
        $this->assertEquals(1, $septPlan['current_subscribers']);

        // In October (after subscription ends), count is 0
        $octResponse = $this->getJson("/api/v1/subscription-plans?branch_id={$this->branch->id}&date=2026-10-15");
        $octPlan = collect($octResponse->json('data'))->firstWhere('id', $this->plan->id);
        $this->assertEquals(0, $octPlan['current_subscribers']);
        $this->assertEquals(10, $octPlan['available_slots']);
    }
}
