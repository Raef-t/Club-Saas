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
use Modules\SubscriptionManager\Models\SubscriptionPlanSuspension;

class SubscriptionPlanInactiveFilterTest extends TestCase
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

    public function test_inactive_filter_returns_only_inactive_and_suspended_plans(): void
    {
        $coachPerson = Person::create(['full_name' => 'Coach 1', 'gender' => 'male', 'type' => 'staff']);
        $coach = Staff::create([
            'person_id' => $coachPerson->id,
            'branch_id' => $this->branch->id,
            'is_coach' => true,
            'is_active' => true,
            'work_status' => 'active',
        ]);

        $activeActivity = Activity::create([
            'branch_id' => $this->branch->id,
            'name' => 'Swimming',
            'is_active' => true,
        ]);

        $inactiveActivity = Activity::create([
            'branch_id' => $this->branch->id,
            'name' => 'Boxing',
            'is_active' => false,
        ]);

        $saActive = StaffActivity::create([
            'staff_id' => $coach->id,
            'activity_id' => $activeActivity->id,
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $saInactive = StaffActivity::create([
            'staff_id' => $coach->id,
            'activity_id' => $inactiveActivity->id,
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        // 1. Fully active plan
        $planActive = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Active Plan',
            'status' => 'active',
            'base_price' => 100,
            'sessions_per_week' => 3,
        ]);
        SubscriptionPlanActivity::create([
            'plan_id' => $planActive->id,
            'staff_activity_id' => $saActive->id,
        ]);

        // 2. Plan marked inactive directly
        $planInactiveStatus = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Inactive Status Plan',
            'status' => 'inactive',
            'base_price' => 120,
            'sessions_per_week' => 2,
        ]);
        SubscriptionPlanActivity::create([
            'plan_id' => $planInactiveStatus->id,
            'staff_activity_id' => $saActive->id,
        ]);

        // 3. Plan with inactive activity
        $planWithInactiveActivity = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Plan with Inactive Activity',
            'status' => 'active',
            'base_price' => 150,
            'sessions_per_week' => 4,
        ]);
        SubscriptionPlanActivity::create([
            'plan_id' => $planWithInactiveActivity->id,
            'staff_activity_id' => $saInactive->id,
        ]);

        // 4. Plan with active suspension
        $planSuspended = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Suspended Plan',
            'status' => 'active',
            'base_price' => 200,
            'sessions_per_week' => 5,
        ]);
        SubscriptionPlanActivity::create([
            'plan_id' => $planSuspended->id,
            'staff_activity_id' => $saActive->id,
        ]);
        SubscriptionPlanSuspension::create([
            'plan_id' => $planSuspended->id,
            'status' => 'active',
            'suspend_start_date' => now()->subDay()->toDateString(),
            'suspend_end_date' => now()->addMonth()->toDateString(),
            'suspension_days' => 30,
            'reason' => 'Maintenance',
        ]);

        // Request with status=inactive
        $responseInactive = $this->getJson("/api/v1/subscription-plans?branch_id={$this->branch->id}&status=inactive");
        $responseInactive->assertStatus(200);

        $inactiveIds = collect($responseInactive->json('data'))->pluck('id')->toArray();
        $this->assertNotContains($planActive->id, $inactiveIds);
        $this->assertContains($planInactiveStatus->id, $inactiveIds);
        $this->assertContains($planWithInactiveActivity->id, $inactiveIds);
        $this->assertContains($planSuspended->id, $inactiveIds);

        // Verify stats in response
        $stats = $responseInactive->json('stats');
        $this->assertNotNull($stats);
        $this->assertEquals(4, $stats['total_plans']);
        $this->assertEquals(1, $stats['active_plans']);
        $this->assertEquals(3, $stats['inactive_plans']);
        $this->assertEquals(5, $stats['max_sessions_per_week']);

        // Request with status=active
        $responseActive = $this->getJson("/api/v1/subscription-plans?branch_id={$this->branch->id}&status=active");
        $responseActive->assertStatus(200);
        $activeIds = collect($responseActive->json('data'))->pluck('id')->toArray();
        $this->assertContains($planActive->id, $activeIds);
        $this->assertNotContains($planInactiveStatus->id, $activeIds);
        $this->assertNotContains($planWithInactiveActivity->id, $activeIds);
        $this->assertNotContains($planSuspended->id, $activeIds);
    }
}
