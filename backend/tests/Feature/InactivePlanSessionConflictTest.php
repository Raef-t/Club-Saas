<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Branch;
use Modules\ClubManager\Models\Facility;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Modules\Sports\Models\ActivityType;
use Modules\Sports\Models\Activity;
use Modules\Sports\Models\StaffActivity;
use Modules\Sports\Models\SportSessionTemplate;
use Modules\StaffManager\Models\Staff;
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Modules\SubscriptionManager\Models\SubscriptionPlanActivity;

class InactivePlanSessionConflictTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Branch $branch;
    protected Facility $facility1;
    protected ActivityType $groupSessionType;
    protected Activity $crossfitActivity;
    protected Activity $yogaActivity;
    protected Staff $coach1;
    protected Staff $coach2;

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
        $this->facility1 = Facility::create(['branch_id' => $this->branch->id, 'name' => 'Main Studio', 'is_active' => true]);

        $this->groupSessionType = ActivityType::create([
            'name' => 'حصة جماعية',
            'is_active' => true,
            'is_session_based' => true,
        ]);

        $this->crossfitActivity = Activity::create([
            'branch_id' => $this->branch->id,
            'activity_type_id' => $this->groupSessionType->id,
            'name' => 'كروسفيت',
            'is_active' => true,
        ]);

        $this->yogaActivity = Activity::create([
            'branch_id' => $this->branch->id,
            'activity_type_id' => $this->groupSessionType->id,
            'name' => 'يوغا',
            'is_active' => true,
        ]);

        $coachPerson1 = Person::create(['full_name' => 'Coach Hiba', 'gender' => 'female', 'type' => 'staff']);
        $this->coach1 = Staff::create([
            'person_id' => $coachPerson1->id,
            'branch_id' => $this->branch->id,
            'role' => 'coach',
            'is_active' => true,
            'work_status' => 'active',
        ]);

        $coachPerson2 = Person::create(['full_name' => 'Coach Rania', 'gender' => 'female', 'type' => 'staff']);
        $this->coach2 = Staff::create([
            'person_id' => $coachPerson2->id,
            'branch_id' => $this->branch->id,
            'role' => 'coach',
            'is_active' => true,
            'work_status' => 'active',
        ]);
    }

    /**
     * Test deactivating an active plan that has the same schedule as another active plan succeeds.
     */
    public function test_can_deactivate_plan_even_when_conflicting_with_another_active_plan(): void
    {
        // 1. Create active Plan 23 (Yoga) on Sunday (0) 12:00-13:00
        $sa2 = StaffActivity::create(['activity_id' => $this->yogaActivity->id, 'staff_id' => $this->coach2->id]);
        $plan23 = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'يوغا - رانية التنجي',
            'base_price' => 300,
            'status' => 'active',
            'sessions_per_week' => 1,
            'session_count' => 4,
        ]);
        SubscriptionPlanActivity::create(['plan_id' => $plan23->id, 'staff_activity_id' => $sa2->id]);
        SportSessionTemplate::create([
            'plan_id' => $plan23->id,
            'day_of_week' => 0,
            'start_time' => '12:00',
            'end_time' => '13:00',
            'is_active' => true,
        ]);

        // 2. Create active Plan 6 (Crossfit) also on Sunday (0) 12:00-13:00
        $sa1 = StaffActivity::create(['activity_id' => $this->crossfitActivity->id, 'staff_id' => $this->coach1->id]);
        $plan6 = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'كروسفيت هبة احد 12',
            'base_price' => 300,
            'status' => 'active',
            'sessions_per_week' => 1,
            'session_count' => 4,
        ]);
        SubscriptionPlanActivity::create(['plan_id' => $plan6->id, 'staff_activity_id' => $sa1->id]);
        SportSessionTemplate::create([
            'plan_id' => $plan6->id,
            'day_of_week' => 0,
            'start_time' => '12:00',
            'end_time' => '13:00',
            'is_active' => true,
        ]);

        // 3. User deactivates Plan 6: changing status to 'inactive'
        $response = $this->putJson("/api/v1/subscription-plans/{$plan6->id}", [
            'reason' => 'Deactivating plan',
            'status' => 'inactive',
            'sessions_per_week' => 1,
            'session_count' => 4,
            'session_templates' => [
                ['day_of_week' => 0, 'start_time' => '12:00', 'end_time' => '13:00'],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertEquals('inactive', $plan6->fresh()->status->value);
    }

    /**
     * Test deactivating an active plan via is_active = false succeeds.
     */
    public function test_can_deactivate_plan_via_is_active_flag(): void
    {
        $sa2 = StaffActivity::create(['activity_id' => $this->yogaActivity->id, 'staff_id' => $this->coach2->id]);
        $plan23 = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'يوغا - رانية التنجي',
            'base_price' => 300,
            'status' => 'active',
            'sessions_per_week' => 1,
            'session_count' => 4,
        ]);
        SubscriptionPlanActivity::create(['plan_id' => $plan23->id, 'staff_activity_id' => $sa2->id]);
        SportSessionTemplate::create([
            'plan_id' => $plan23->id,
            'day_of_week' => 0,
            'start_time' => '12:00',
            'end_time' => '13:00',
            'is_active' => true,
        ]);

        $sa1 = StaffActivity::create(['activity_id' => $this->crossfitActivity->id, 'staff_id' => $this->coach1->id]);
        $plan6 = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'كروسفيت هبة احد 12',
            'base_price' => 300,
            'status' => 'active',
            'sessions_per_week' => 1,
            'session_count' => 4,
        ]);
        SubscriptionPlanActivity::create(['plan_id' => $plan6->id, 'staff_activity_id' => $sa1->id]);
        SportSessionTemplate::create([
            'plan_id' => $plan6->id,
            'day_of_week' => 0,
            'start_time' => '12:00',
            'end_time' => '13:00',
            'is_active' => true,
        ]);

        $response = $this->putJson("/api/v1/subscription-plans/{$plan6->id}", [
            'reason' => 'Deactivating plan via is_active boolean',
            'is_active' => false,
            'sessions_per_week' => 1,
            'session_count' => 4,
            'session_templates' => [
                ['day_of_week' => 0, 'start_time' => '12:00', 'end_time' => '13:00'],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertEquals('inactive', $plan6->fresh()->status->value);
    }

    /**
     * Test activating an inactive plan that conflicts with an active plan fails with 422.
     */
    public function test_cannot_activate_plan_that_conflicts_with_active_plan(): void
    {
        $sa2 = StaffActivity::create(['activity_id' => $this->yogaActivity->id, 'staff_id' => $this->coach2->id]);
        $plan23 = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'يوغا - رانية التنجي',
            'base_price' => 300,
            'status' => 'active',
            'sessions_per_week' => 1,
            'session_count' => 4,
        ]);
        SubscriptionPlanActivity::create(['plan_id' => $plan23->id, 'staff_activity_id' => $sa2->id]);
        SportSessionTemplate::create([
            'plan_id' => $plan23->id,
            'day_of_week' => 0,
            'start_time' => '12:00',
            'end_time' => '13:00',
            'is_active' => true,
        ]);

        $sa1 = StaffActivity::create(['activity_id' => $this->crossfitActivity->id, 'staff_id' => $this->coach1->id]);
        $plan6 = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'كروسفيت هبة احد 12',
            'base_price' => 300,
            'status' => 'inactive',
            'sessions_per_week' => 1,
            'session_count' => 4,
        ]);
        SubscriptionPlanActivity::create(['plan_id' => $plan6->id, 'staff_activity_id' => $sa1->id]);

        $response = $this->putJson("/api/v1/subscription-plans/{$plan6->id}", [
            'reason' => 'Trying to activate conflicting plan',
            'status' => 'active',
            'sessions_per_week' => 1,
            'session_count' => 4,
            'session_templates' => [
                ['day_of_week' => 0, 'start_time' => '12:00', 'end_time' => '13:00'],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['session_templates']);
    }

    /**
     * Test internal overlap within an inactive plan still fails with 422.
     */
    public function test_internal_overlap_still_fails_even_for_inactive_plan(): void
    {
        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'خطة مسودة',
            'base_price' => 200,
            'status' => 'inactive',
            'sessions_per_week' => 2,
            'session_count' => 8,
        ]);

        $response = $this->putJson("/api/v1/subscription-plans/{$plan->id}", [
            'reason' => 'Updating inactive plan with internal overlap',
            'status' => 'inactive',
            'sessions_per_week' => 2,
            'session_count' => 8,
            'session_templates' => [
                ['day_of_week' => 1, 'start_time' => '10:00', 'end_time' => '11:00'],
                ['day_of_week' => 1, 'start_time' => '10:30', 'end_time' => '11:30'],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['session_templates']);
    }

    /**
     * Test that an inactive plan does not block an active plan from booking the same facility.
     */
    public function test_inactive_plan_does_not_block_facility_for_active_plan(): void
    {
        $inactivePlan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'خطة غير نشطة',
            'base_price' => 200,
            'status' => 'inactive',
        ]);
        SportSessionTemplate::create([
            'plan_id' => $inactivePlan->id,
            'facility_id' => $this->facility1->id,
            'day_of_week' => 2,
            'start_time' => '14:00',
            'end_time' => '15:00',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/v1/subscription-plans', [
            'branch_id' => $this->branch->id,
            'name' => 'خطة جديدة نشطة',
            'base_price' => 300,
            'status' => 'active',
            'sessions_per_week' => 1,
            'session_count' => 4,
            'session_templates' => [
                [
                    'facility_id' => $this->facility1->id,
                    'day_of_week' => 2,
                    'start_time' => '14:00',
                    'end_time' => '15:00',
                ],
            ],
        ]);

        $response->assertStatus(201);
    }
}
