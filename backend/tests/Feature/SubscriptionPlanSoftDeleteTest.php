<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Branch;
use Modules\Authentication\Models\Person;
use Modules\MemberManager\Models\Member;
use Modules\StaffManager\Models\Staff;
use Modules\Sports\Models\Activity;
use Modules\Sports\Models\StaffActivity;
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Modules\SubscriptionManager\Models\SubscriptionPlanActivity;
use Modules\SubscriptionManager\Models\PlayerSubscription;
use Modules\Authentication\Models\User;

class SubscriptionPlanSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_delete_check_endpoint_returns_accurate_summary(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $club = Club::create(['name' => 'Club A', 'is_active' => true]);
        $branch = Branch::create(['club_id' => $club->id, 'name' => 'Branch A', 'is_active' => true]);

        $plan = SubscriptionPlan::create([
            'branch_id' => $branch->id,
            'name' => 'Gold Plan',
            'base_price' => 100.00,
            'status' => 'active',
        ]);

        $personMember = Person::create(['full_name' => 'Member One', 'gender' => 'male', 'type' => 'player']);
        $member = Member::create([
            'branch_id' => $branch->id,
            'person_id' => $personMember->id,
            'member_number' => 'MEM-101',
            'membership_status' => 'active',
            'join_date' => now(),
        ]);

        // Create 1 active subscription and 1 expired subscription
        PlayerSubscription::create([
            'member_id' => $member->id,
            'plan_id' => $plan->id,
            'months_count' => 1,
            'total_amount' => 100.00,
            'paid_amount' => 100.00,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'status' => 'active',
        ]);

        PlayerSubscription::create([
            'member_id' => $member->id,
            'plan_id' => $plan->id,
            'months_count' => 1,
            'total_amount' => 100.00,
            'paid_amount' => 100.00,
            'start_date' => now()->subMonths(2),
            'end_date' => now()->subMonth(),
            'status' => 'expired',
        ]);

        // Coach and activity
        $personCoach = Person::create(['full_name' => 'Captain Coach', 'gender' => 'male', 'type' => 'staff']);
        $staff = Staff::create(['person_id' => $personCoach->id, 'role' => 'coach', 'is_active' => true]);
        $activity = Activity::create(['branch_id' => $branch->id, 'name' => 'Swimming', 'is_active' => true]);
        $staffActivity = StaffActivity::create(['staff_id' => $staff->id, 'activity_id' => $activity->id]);
        SubscriptionPlanActivity::create(['plan_id' => $plan->id, 'staff_activity_id' => $staffActivity->id]);

        $response = $this->getJson("/api/v1/subscription-plans/{$plan->id}/delete-check");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.has_active_subscriptions', true)
            ->assertJsonPath('data.active_subscriptions_count', 1)
            ->assertJsonPath('data.inactive_subscriptions_count', 1)
            ->assertJsonPath('data.associated_coaches.0.staff_id', $staff->id);
    }

    public function test_subscription_plan_soft_delete_preserves_active_subscriptions_if_not_forced(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $club = Club::create(['name' => 'Club B', 'is_active' => true]);
        $branch = Branch::create(['club_id' => $club->id, 'name' => 'Branch B', 'is_active' => true]);

        $plan = SubscriptionPlan::create([
            'branch_id' => $branch->id,
            'name' => 'Silver Plan',
            'base_price' => 50.00,
            'status' => 'active',
        ]);

        $personMember = Person::create(['full_name' => 'Member Two', 'gender' => 'female', 'type' => 'player']);
        $member = Member::create([
            'branch_id' => $branch->id,
            'person_id' => $personMember->id,
            'member_number' => 'MEM-102',
            'membership_status' => 'active',
            'join_date' => now(),
        ]);

        $activeSub = PlayerSubscription::create([
            'member_id' => $member->id,
            'plan_id' => $plan->id,
            'months_count' => 1,
            'total_amount' => 50.00,
            'paid_amount' => 50.00,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'status' => 'active',
        ]);

        $expiredSub = PlayerSubscription::create([
            'member_id' => $member->id,
            'plan_id' => $plan->id,
            'months_count' => 1,
            'total_amount' => 50.00,
            'paid_amount' => 50.00,
            'start_date' => now()->subMonths(3),
            'end_date' => now()->subMonths(2),
            'status' => 'expired',
        ]);

        $response = $this->deleteJson("/api/v1/subscription-plans/{$plan->id}", [
            'force_delete_active_subscriptions' => false,
        ]);

        $response->assertStatus(200);

        $this->assertSoftDeleted('subscription_plans', ['id' => $plan->id]);
        $this->assertSoftDeleted('player_subscriptions', ['id' => $expiredSub->id]);
        $this->assertNotSoftDeleted('player_subscriptions', ['id' => $activeSub->id]);

        // Verify active sub can still access soft-deleted plan
        $reloadedSub = PlayerSubscription::find($activeSub->id);
        $this->assertNotNull($reloadedSub->plan);
        $this->assertEquals('Silver Plan', $reloadedSub->plan->name);
    }

    public function test_subscription_plan_soft_delete_forces_active_subscriptions_and_staff_deletion(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $club = Club::create(['name' => 'Club C', 'is_active' => true]);
        $branch = Branch::create(['club_id' => $club->id, 'name' => 'Branch C', 'is_active' => true]);

        $plan = SubscriptionPlan::create([
            'branch_id' => $branch->id,
            'name' => 'Bronze Plan',
            'base_price' => 30.00,
            'status' => 'active',
        ]);

        $personCoach = Person::create(['full_name' => 'Coach Bob', 'gender' => 'male', 'type' => 'staff']);
        $staff = Staff::create(['person_id' => $personCoach->id, 'role' => 'coach', 'is_active' => true]);

        $personMember = Person::create(['full_name' => 'Member Three', 'gender' => 'male', 'type' => 'player']);
        $member = Member::create([
            'branch_id' => $branch->id,
            'person_id' => $personMember->id,
            'member_number' => 'MEM-103',
            'membership_status' => 'active',
            'join_date' => now(),
        ]);

        $activeSub = PlayerSubscription::create([
            'member_id' => $member->id,
            'plan_id' => $plan->id,
            'months_count' => 1,
            'total_amount' => 30.00,
            'paid_amount' => 30.00,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'status' => 'active',
        ]);

        $response = $this->deleteJson("/api/v1/subscription-plans/{$plan->id}", [
            'force_delete_active_subscriptions' => true,
            'detach_and_delete_staff_ids' => [$staff->id],
        ]);

        $response->assertStatus(200);

        $this->assertSoftDeleted('subscription_plans', ['id' => $plan->id]);
        $this->assertSoftDeleted('player_subscriptions', ['id' => $activeSub->id]);
    }

    public function test_delete_subscription_plan_fails_when_unassociated_staff_id_passed(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $club = Club::create(['name' => 'Club D', 'is_active' => true]);
        $branch = Branch::create(['club_id' => $club->id, 'name' => 'Branch D', 'is_active' => true]);

        $plan = SubscriptionPlan::create([
            'branch_id' => $branch->id,
            'name' => 'Plan D',
            'base_price' => 20.00,
            'status' => 'active',
        ]);

        $unassociatedStaff = Staff::create(['person_id' => Person::create(['full_name' => 'Unassociated Coach', 'gender' => 'male', 'type' => 'staff'])->id, 'role' => 'coach', 'is_active' => true]);

        $response = $this->deleteJson("/api/v1/subscription-plans/{$plan->id}", [
            'detach_and_delete_staff_ids' => [$unassociatedStaff->id],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['detach_and_delete_staff_ids']);
    }
}
