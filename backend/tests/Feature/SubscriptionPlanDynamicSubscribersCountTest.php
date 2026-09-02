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

class SubscriptionPlanDynamicSubscribersCountTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $branch;
    protected $plan;

    protected function setUp(): void
    {
        parent::setUp();

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

        $club = Club::create(['name' => 'Dynamic Test Club', 'is_active' => true]);
        $this->branch = Branch::create(['club_id' => $club->id, 'name' => 'Dynamic Test Branch', 'is_active' => true]);

        $this->plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'خطة اختبار ديناميكية',
            'base_price' => 100.00,
            'max_subscribers' => 20,
            'current_subscribers' => 0,
            'status' => 'active',
        ]);
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

    public function test_json_response_calculates_current_subscribers_dynamically(): void
    {
        // 1. Create 3 active player subscriptions
        $sub1 = PlayerSubscription::create([
            'member_id' => $this->createMember()->id,
            'plan_id' => $this->plan->id,
            'months_count' => 1,
            'total_amount' => 100.00,
            'paid_amount' => 100.00,
            'remaining_amount' => 0,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
        ]);

        $sub2 = PlayerSubscription::create([
            'member_id' => $this->createMember()->id,
            'plan_id' => $this->plan->id,
            'months_count' => 1,
            'total_amount' => 100.00,
            'paid_amount' => 100.00,
            'remaining_amount' => 0,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
        ]);

        $sub3 = PlayerSubscription::create([
            'member_id' => $this->createMember()->id,
            'plan_id' => $this->plan->id,
            'months_count' => 1,
            'total_amount' => 100.00,
            'paid_amount' => 100.00,
            'remaining_amount' => 0,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
        ]);

        // Verify index response shows 3 subscribers
        $response = $this->getJson("/api/v1/subscription-plans?branch_id={$this->branch->id}");
        $response->assertStatus(200);
        $found = collect($response->json('data'))->firstWhere('id', $this->plan->id);
        $this->assertEquals(3, $found['current_subscribers']);

        // Verify registration endpoint also shows 3 subscribers
        $regResponse = $this->getJson("/api/v1/subscription-plans/registration?branch_id={$this->branch->id}");
        $regResponse->assertStatus(200);
        $regFound = collect($regResponse->json('data'))->firstWhere('id', $this->plan->id);
        $this->assertEquals(3, $regFound['current_subscribers']);

        // 2. Soft-delete sub1
        $sub1->delete();

        // Verify response immediately reflects 2 subscribers
        $response2 = $this->getJson("/api/v1/subscription-plans?branch_id={$this->branch->id}");
        $response2->assertStatus(200);
        $found2 = collect($response2->json('data'))->firstWhere('id', $this->plan->id);
        $this->assertEquals(2, $found2['current_subscribers']);

        // 3. Restore sub1
        $sub1->restore();

        // Verify response immediately reflects 3 subscribers again
        $response3 = $this->getJson("/api/v1/subscription-plans?branch_id={$this->branch->id}");
        $response3->assertStatus(200);
        $found3 = collect($response3->json('data'))->firstWhere('id', $this->plan->id);
        $this->assertEquals(3, $found3['current_subscribers']);
    }

    public function test_inactive_or_terminated_subscriptions_are_not_counted_in_current_subscribers(): void
    {
        // Active subscription
        PlayerSubscription::create([
            'member_id' => $this->createMember()->id,
            'plan_id' => $this->plan->id,
            'months_count' => 1,
            'total_amount' => 100.00,
            'paid_amount' => 100.00,
            'remaining_amount' => 0,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
        ]);

        // Terminated subscription
        PlayerSubscription::create([
            'member_id' => $this->createMember()->id,
            'plan_id' => $this->plan->id,
            'months_count' => 1,
            'total_amount' => 100.00,
            'paid_amount' => 100.00,
            'remaining_amount' => 0,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => PlayerSubscriptionStatus::TERMINATED->value,
        ]);

        // Finished subscription
        PlayerSubscription::create([
            'member_id' => $this->createMember()->id,
            'plan_id' => $this->plan->id,
            'months_count' => 1,
            'total_amount' => 100.00,
            'paid_amount' => 100.00,
            'remaining_amount' => 0,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => PlayerSubscriptionStatus::FINISHED->value,
        ]);

        $response = $this->getJson("/api/v1/subscription-plans?branch_id={$this->branch->id}");
        $response->assertStatus(200);
        $found = collect($response->json('data'))->firstWhere('id', $this->plan->id);
        
        // Only the 1 active subscription should be counted
        $this->assertEquals(1, $found['current_subscribers']);
    }
}
