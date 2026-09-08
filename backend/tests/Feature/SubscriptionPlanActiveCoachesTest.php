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

class SubscriptionPlanActiveCoachesTest extends TestCase
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

    public function test_coaches_per_page_all_excludes_inactive_and_suspended_coaches_by_default(): void
    {
        $person1 = Person::create(['full_name' => 'Active Coach', 'gender' => 'male', 'type' => 'staff']);
        $activeCoach = Staff::create([
            'person_id' => $person1->id,
            'role' => 'coach',
            'is_active' => true,
            'work_status' => 'active',
        ]);
        $activeCoach->branches()->attach($this->branch->id);

        $person2 = Person::create(['full_name' => 'Suspended Coach', 'gender' => 'male', 'type' => 'staff']);
        $suspendedCoach = Staff::create([
            'person_id' => $person2->id,
            'role' => 'coach',
            'is_active' => true,
            'work_status' => 'suspended',
        ]);
        $suspendedCoach->branches()->attach($this->branch->id);

        $person3 = Person::create(['full_name' => 'Inactive Coach', 'gender' => 'male', 'type' => 'staff']);
        $inactiveCoach = Staff::create([
            'person_id' => $person3->id,
            'role' => 'coach',
            'is_active' => false,
            'work_status' => 'active',
        ]);
        $inactiveCoach->branches()->attach($this->branch->id);

        // 1. Default per_page=all should return only active coaches
        $response = $this->getJson("/api/v1/coaches?per_page=all");
        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($activeCoach->id, $ids);
        $this->assertNotContains($suspendedCoach->id, $ids, 'Suspended coach must not appear in per_page=all');
        $this->assertNotContains($inactiveCoach->id, $ids, 'Inactive coach must not appear in per_page=all');

        // 2. With all_statuses=1, all should be present
        $responseAll = $this->getJson("/api/v1/coaches?per_page=all&all_statuses=1");
        $responseAll->assertStatus(200);
        $idsAll = collect($responseAll->json('data'))->pluck('id')->toArray();
        $this->assertContains($activeCoach->id, $idsAll);
        $this->assertContains($suspendedCoach->id, $idsAll);
        $this->assertContains($inactiveCoach->id, $idsAll);

        // 3. Explicit work_status=suspended
        $responseSuspended = $this->getJson("/api/v1/coaches?work_status=suspended");
        $responseSuspended->assertStatus(200);
        $idsSuspended = collect($responseSuspended->json('data'))->pluck('id')->toArray();
        $this->assertContains($suspendedCoach->id, $idsSuspended);
        $this->assertNotContains($activeCoach->id, $idsSuspended);

        // 4. Explicit is_active=0
        $responseInactive = $this->getJson("/api/v1/coaches?is_active=0");
        $responseInactive->assertStatus(200);
        $idsInactive = collect($responseInactive->json('data'))->pluck('id')->toArray();
        $this->assertContains($inactiveCoach->id, $idsInactive);
        $this->assertNotContains($activeCoach->id, $idsInactive);
    }

    public function test_creating_subscription_plan_with_inactive_or_suspended_coach_fails_validation(): void
    {
        $activity = Activity::create([
            'branch_id' => $this->branch->id,
            'name' => 'Pilates',
            'is_active' => true,
        ]);

        $personSuspended = Person::create(['full_name' => 'Suspended Coach 2', 'gender' => 'male', 'type' => 'staff']);
        $suspendedCoach = Staff::create([
            'person_id' => $personSuspended->id,
            'role' => 'coach',
            'is_active' => true,
            'work_status' => 'suspended',
        ]);

        $payload = [
            'branch_id' => $this->branch->id,
            'name' => 'Pilates Plan',
            'base_price' => 150,
            'activities' => [
                [
                    'activity_id' => $activity->id,
                    'coach_id' => $suspendedCoach->id,
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/subscription-plans', $payload);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['activities.0.coach_id']);
    }

    public function test_creating_subscription_plan_with_active_coach_succeeds(): void
    {
        $activity = Activity::create([
            'branch_id' => $this->branch->id,
            'name' => 'CrossFit',
            'is_active' => true,
        ]);

        $personActive = Person::create(['full_name' => 'Active Coach 2', 'gender' => 'male', 'type' => 'staff']);
        $activeCoach = Staff::create([
            'person_id' => $personActive->id,
            'role' => 'coach',
            'is_active' => true,
            'work_status' => 'active',
        ]);

        $payload = [
            'branch_id' => $this->branch->id,
            'name' => 'CrossFit Plan',
            'base_price' => 250,
            'activities' => [
                [
                    'activity_id' => $activity->id,
                    'coach_id' => $activeCoach->id,
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/subscription-plans', $payload);
        $response->assertStatus(201);
        $this->assertDatabaseHas('subscription_plans', [
            'name' => 'CrossFit Plan',
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_plan_with_inactive_or_suspended_coach_is_excluded_from_per_page_all_requests(): void
    {
        $activity = Activity::create([
            'branch_id' => $this->branch->id,
            'name' => 'Yoga',
            'is_active' => true,
        ]);

        $personActive = Person::create(['full_name' => 'Yoga Coach Active', 'gender' => 'male', 'type' => 'staff']);
        $activeCoach = Staff::create([
            'person_id' => $personActive->id,
            'role' => 'coach',
            'is_active' => true,
            'work_status' => 'active',
        ]);
        $activeStaffAct = StaffActivity::create(['staff_id' => $activeCoach->id, 'activity_id' => $activity->id]);

        $personSuspended = Person::create(['full_name' => 'Yoga Coach Suspended', 'gender' => 'male', 'type' => 'staff']);
        $suspendedCoach = Staff::create([
            'person_id' => $personSuspended->id,
            'role' => 'coach',
            'is_active' => true,
            'work_status' => 'suspended',
        ]);
        $suspendedStaffAct = StaffActivity::create(['staff_id' => $suspendedCoach->id, 'activity_id' => $activity->id]);

        // Plan 1: with active coach
        $planActive = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Active Yoga Plan',
            'base_price' => 100,
            'status' => 'active',
        ]);
        SubscriptionPlanActivity::create(['plan_id' => $planActive->id, 'staff_activity_id' => $activeStaffAct->id]);

        // Plan 2: with suspended coach
        $planSuspended = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Suspended Yoga Plan',
            'base_price' => 100,
            'status' => 'active',
        ]);
        SubscriptionPlanActivity::create(['plan_id' => $planSuspended->id, 'staff_activity_id' => $suspendedStaffAct->id]);

        // GET /subscription-plans?branch_id=X&per_page=all (used in add subscription)
        $response = $this->getJson("/api/v1/subscription-plans?branch_id={$this->branch->id}&per_page=all");
        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($planActive->id, $ids);
        $this->assertNotContains($planSuspended->id, $ids, 'Plan with suspended coach must NOT appear in per_page=all');

        // GET with status=active
        $responseActive = $this->getJson("/api/v1/subscription-plans?branch_id={$this->branch->id}&status=active");
        $responseActive->assertStatus(200);
        $idsActive = collect($responseActive->json('data'))->pluck('id')->toArray();
        $this->assertContains($planActive->id, $idsActive);
        $this->assertNotContains($planSuspended->id, $idsActive);

        // GET with status=inactive
        $responseInactive = $this->getJson("/api/v1/subscription-plans?branch_id={$this->branch->id}&status=inactive");
        $responseInactive->assertStatus(200);
        $idsInactive = collect($responseInactive->json('data'))->pluck('id')->toArray();
        $this->assertContains($planSuspended->id, $idsInactive);
        $this->assertNotContains($planActive->id, $idsInactive);
    }

    public function test_subscribing_member_to_plan_with_inactive_or_suspended_coach_fails(): void
    {
        $activity = Activity::create([
            'branch_id' => $this->branch->id,
            'name' => 'MMA',
            'is_active' => true,
        ]);

        $coachPerson = Person::create(['full_name' => 'MMA Coach Suspended', 'gender' => 'male', 'type' => 'staff']);
        $coach = Staff::create([
            'person_id' => $coachPerson->id,
            'role' => 'coach',
            'is_active' => true,
            'work_status' => 'suspended',
        ]);

        $staffAct = StaffActivity::create(['staff_id' => $coach->id, 'activity_id' => $activity->id]);

        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'MMA Plan',
            'base_price' => 300,
            'status' => 'active',
        ]);
        SubscriptionPlanActivity::create(['plan_id' => $plan->id, 'staff_activity_id' => $staffAct->id]);

        $memberPerson = Person::create(['full_name' => 'Player Fighter', 'gender' => 'male', 'type' => 'player']);
        $member = Member::create([
            'person_id' => $memberPerson->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'join_date' => now()->toDateString(),
        ]);

        $payload = [
            'member_id' => $member->id,
            'plan_id' => $plan->id,
            'paid_amount' => 300,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ];

        $response = $this->postJson('/api/v1/player-subscriptions', $payload);
        $response->assertStatus(400);
        $this->assertStringContainsString('inactive', strtolower($response->json('message')));
    }
}
