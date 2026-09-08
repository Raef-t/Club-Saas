<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Branch;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Modules\Sports\Models\Activity;
use Modules\Sports\Models\StaffActivity;
use Modules\StaffManager\Models\Staff;
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Modules\SubscriptionManager\Models\SubscriptionPlanActivity;
use Modules\MemberManager\Models\Member;

class SubscriptionPlanActiveActivitiesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Branch $branch;

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

        $club = Club::create(['name' => 'Test Club', 'is_active' => true]);
        $this->branch = Branch::create(['club_id' => $club->id, 'name' => 'Main Branch', 'is_active' => true]);
    }

    public function test_plan_with_inactive_activity_is_excluded_from_per_page_all_requests(): void
    {
        // 1. Create active activity
        $activeActivity = Activity::create([
            'branch_id' => $this->branch->id,
            'name' => 'Swimming',
            'is_active' => true,
        ]);

        // 2. Create inactive activity
        $inactiveActivity = Activity::create([
            'branch_id' => $this->branch->id,
            'name' => 'Boxing',
            'is_active' => false,
        ]);

        $coachPerson = Person::create(['full_name' => 'Coach One', 'gender' => 'male', 'type' => 'staff']);
        $coach = Staff::create([
            'person_id' => $coachPerson->id,
            'branch_id' => $this->branch->id,
            'employment_type' => 'trainer',
            'job_title' => 'Coach',
        ]);

        $activeStaffAct = StaffActivity::create(['staff_id' => $coach->id, 'activity_id' => $activeActivity->id]);
        $inactiveStaffAct = StaffActivity::create(['staff_id' => $coach->id, 'activity_id' => $inactiveActivity->id]);

        // Plan 1: with active activity
        $planActive = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Active Swimming Plan',
            'base_price' => 100,
            'status' => 'active',
        ]);
        SubscriptionPlanActivity::create(['plan_id' => $planActive->id, 'staff_activity_id' => $activeStaffAct->id]);

        // Plan 2: with inactive activity
        $planInactive = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Inactive Boxing Plan',
            'base_price' => 120,
            'status' => 'active',
        ]);
        SubscriptionPlanActivity::create(['plan_id' => $planInactive->id, 'staff_activity_id' => $inactiveStaffAct->id]);

        // When requesting /subscription-plans?branch_id=X&per_page=all (as in add subscription)
        $response = $this->getJson("/api/v1/subscription-plans?branch_id={$this->branch->id}&per_page=all");
        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($planActive->id, $ids);
        $this->assertNotContains($planInactive->id, $ids, 'Plan with inactive activity must NOT appear in per_page=all');

        // When requesting with status=active
        $responseActive = $this->getJson("/api/v1/subscription-plans?branch_id={$this->branch->id}&status=active");
        $responseActive->assertStatus(200);
        $idsActive = collect($responseActive->json('data'))->pluck('id')->toArray();
        $this->assertContains($planActive->id, $idsActive);
        $this->assertNotContains($planInactive->id, $idsActive);

        // When requesting registration endpoint
        $responseReg = $this->getJson("/api/v1/subscription-plans/registration?branch_id={$this->branch->id}");
        $responseReg->assertStatus(200);
        $idsReg = collect($responseReg->json('data'))->pluck('id')->toArray();
        $this->assertContains($planActive->id, $idsReg);
        $this->assertNotContains($planInactive->id, $idsReg);

        // When requesting status=inactive
        $responseInactive = $this->getJson("/api/v1/subscription-plans?branch_id={$this->branch->id}&status=inactive");
        $responseInactive->assertStatus(200);
        $idsInactive = collect($responseInactive->json('data'))->pluck('id')->toArray();
        $this->assertContains($planInactive->id, $idsInactive);
        $this->assertNotContains($planActive->id, $idsInactive);
    }

    public function test_subscribing_member_to_plan_with_inactive_activity_fails(): void
    {
        $inactiveActivity = Activity::create([
            'branch_id' => $this->branch->id,
            'name' => 'Tennis',
            'is_active' => false,
        ]);

        $coachPerson = Person::create(['full_name' => 'Coach Two', 'gender' => 'male', 'type' => 'staff']);
        $coach = Staff::create([
            'person_id' => $coachPerson->id,
            'branch_id' => $this->branch->id,
            'employment_type' => 'trainer',
            'job_title' => 'Coach',
        ]);

        $staffAct = StaffActivity::create(['staff_id' => $coach->id, 'activity_id' => $inactiveActivity->id]);

        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Tennis Plan',
            'base_price' => 150,
            'status' => 'active',
        ]);
        SubscriptionPlanActivity::create(['plan_id' => $plan->id, 'staff_activity_id' => $staffAct->id]);

        $memberPerson = Person::create(['full_name' => 'Player Test', 'gender' => 'male', 'type' => 'player']);
        $member = Member::create([
            'person_id' => $memberPerson->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'join_date' => now()->toDateString(),
        ]);

        $payload = [
            'member_id' => $member->id,
            'plan_id' => $plan->id,
            'paid_amount' => 150,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ];

        $response = $this->postJson('/api/v1/player-subscriptions', $payload);
        $response->assertStatus(400);
        $this->assertStringContainsString('inactive', strtolower($response->json('message')));
    }

    public function test_activities_per_page_all_excludes_inactive_activities_by_default(): void
    {
        $activeActivity = Activity::create([
            'branch_id' => $this->branch->id,
            'name' => 'Active Yoga',
            'is_active' => true,
        ]);

        $inactiveActivity = Activity::create([
            'branch_id' => $this->branch->id,
            'name' => 'Inactive Karate',
            'is_active' => false,
        ]);

        // Default per_page=all should exclude inactive
        $response = $this->getJson("/api/v1/activities?branch_id={$this->branch->id}&per_page=all");
        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($activeActivity->id, $ids);
        $this->assertNotContains($inactiveActivity->id, $ids);

        // With all_statuses=true, both should be present
        $responseAll = $this->getJson("/api/v1/activities?branch_id={$this->branch->id}&per_page=all&all_statuses=1");
        $responseAll->assertStatus(200);
        $idsAll = collect($responseAll->json('data'))->pluck('id')->toArray();
        $this->assertContains($activeActivity->id, $idsAll);
        $this->assertContains($inactiveActivity->id, $idsAll);

        // With explicit is_active=0, only inactive should be present
        $responseInactive = $this->getJson("/api/v1/activities?branch_id={$this->branch->id}&is_active=0");
        $responseInactive->assertStatus(200);
        $idsInactive = collect($responseInactive->json('data'))->pluck('id')->toArray();
        $this->assertContains($inactiveActivity->id, $idsInactive);
        $this->assertNotContains($activeActivity->id, $idsInactive);
    }

    public function test_creating_subscription_plan_with_inactive_activity_fails_validation(): void
    {
        $inactiveActivity = Activity::create([
            'branch_id' => $this->branch->id,
            'name' => 'Inactive Gymnastics',
            'is_active' => false,
        ]);

        $payload = [
            'branch_id' => $this->branch->id,
            'name' => 'Gymnastics Plan',
            'base_price' => 200,
            'activities' => [
                ['activity_id' => $inactiveActivity->id],
            ],
        ];

        $response = $this->postJson('/api/v1/subscription-plans', $payload);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['activities.0.activity_id']);
    }

    public function test_creating_subscription_plan_with_active_activity_succeeds(): void
    {
        $activeActivity = Activity::create([
            'branch_id' => $this->branch->id,
            'name' => 'Active Gymnastics',
            'is_active' => true,
        ]);

        $payload = [
            'branch_id' => $this->branch->id,
            'name' => 'Active Gymnastics Plan',
            'base_price' => 200,
            'activities' => [
                ['activity_id' => $activeActivity->id],
            ],
        ];

        $response = $this->postJson('/api/v1/subscription-plans', $payload);
        $response->assertStatus(201);
        $this->assertDatabaseHas('subscription_plans', [
            'name' => 'Active Gymnastics Plan',
            'branch_id' => $this->branch->id,
        ]);
    }
}
