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

class GroupActivityConflictTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Branch $branch;
    protected ActivityType $groupSessionType;
    protected ActivityType $generalType;
    protected Activity $aerobicActivity;
    protected Activity $yogaActivity;
    protected Activity $generalGymActivity;
    protected Activity $poolActivity;
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

        // 1. Group Session Activity Type (حصة جماعية)
        $this->groupSessionType = ActivityType::create([
            'name' => 'حصة جماعية',
            'is_active' => true,
            'is_session_based' => true,
            'has_unlimited_subscribers' => false,
            'has_shifts' => false,
        ]);

        // 2. General Training Activity Type (تدريب عام)
        $this->generalType = ActivityType::create([
            'name' => 'تدريب عام',
            'is_active' => true,
            'is_session_based' => false,
            'has_unlimited_subscribers' => true,
            'has_shifts' => true,
        ]);

        // 3. Group Activities: Aerobics & Yoga
        $this->aerobicActivity = Activity::create([
            'branch_id' => $this->branch->id,
            'activity_type_id' => $this->groupSessionType->id,
            'name' => 'ايروبيك',
            'is_active' => true,
        ]);

        $this->yogaActivity = Activity::create([
            'branch_id' => $this->branch->id,
            'activity_type_id' => $this->groupSessionType->id,
            'name' => 'يوغا',
            'is_active' => true,
        ]);

        // 4. General Activities
        $this->generalGymActivity = Activity::create([
            'branch_id' => $this->branch->id,
            'activity_type_id' => $this->generalType->id,
            'name' => 'أجهزة عام',
            'is_active' => true,
        ]);

        $this->poolActivity = Activity::create([
            'branch_id' => $this->branch->id,
            'activity_type_id' => $this->generalType->id,
            'name' => 'مسبح عام',
            'is_active' => true,
        ]);

        // 5. Coaches
        $p1 = Person::create(['full_name' => 'Coach Sara', 'gender' => 'female', 'type' => 'staff']);
        $this->coach1 = Staff::create([
            'person_id' => $p1->id,
            'branch_id' => $this->branch->id,
            'employment_type' => 'trainer',
            'job_title' => 'Coach',
            'role' => 'coach',
            'work_status' => 'active',
            'is_active' => true,
        ]);
        $this->coach1->branches()->attach($this->branch->id);

        $p2 = Person::create(['full_name' => 'Coach Maha', 'gender' => 'female', 'type' => 'staff']);
        $this->coach2 = Staff::create([
            'person_id' => $p2->id,
            'branch_id' => $this->branch->id,
            'employment_type' => 'trainer',
            'job_title' => 'Coach',
            'role' => 'coach',
            'work_status' => 'active',
            'is_active' => true,
        ]);
        $this->coach2->branches()->attach($this->branch->id);

        StaffActivity::create(['staff_id' => $this->coach1->id, 'activity_id' => $this->aerobicActivity->id]);
        StaffActivity::create(['staff_id' => $this->coach2->id, 'activity_id' => $this->yogaActivity->id]);
    }

    /**
     * Test creating a subscription plan with 2 group activities (Aerobics and Yoga) fails with 422.
     */
    public function test_cannot_create_subscription_plan_with_multiple_group_activities(): void
    {
        $payload = [
            'branch_id' => $this->branch->id,
            'name' => 'باقة ايروبيك ويوغا',
            'base_price' => 300,
            'activities' => [
                ['activity_id' => $this->aerobicActivity->id, 'coach_id' => $this->coach1->id],
                ['activity_id' => $this->yogaActivity->id, 'coach_id' => $this->coach2->id],
            ],
        ];

        $response = $this->postJson('/api/v1/subscription-plans', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['activities']);
    }

    /**
     * Test creating a subscription plan with 1 group activity succeeds.
     */
    public function test_can_create_subscription_plan_with_single_group_activity(): void
    {
        $payload = [
            'branch_id' => $this->branch->id,
            'name' => 'اشتراك ايروبيك فقط',
            'base_price' => 200,
            'activities' => [
                ['activity_id' => $this->aerobicActivity->id, 'coach_id' => $this->coach1->id],
            ],
            'session_templates' => [
                [
                    'day_of_week' => 0, // Sunday
                    'start_time' => '10:00',
                    'end_time' => '11:00',
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/subscription-plans', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('subscription_plans', ['name' => 'اشتراك ايروبيك فقط']);
    }

    /**
     * Test adding a second group activity to an existing plan via subscription-plan-activities fails with 422.
     */
    public function test_cannot_add_second_group_activity_to_existing_plan(): void
    {
        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'خطة ايروبيك',
            'base_price' => 200,
            'status' => 'active',
        ]);

        $saAerobic = StaffActivity::where('activity_id', $this->aerobicActivity->id)->first();
        SubscriptionPlanActivity::create([
            'plan_id' => $plan->id,
            'staff_activity_id' => $saAerobic->id,
        ]);

        // Attempt to add Yoga to the same plan
        $response = $this->postJson('/api/v1/subscription-plan-activities', [
            'plan_id' => $plan->id,
            'activity_id' => $this->yogaActivity->id,
            'coach_id' => $this->coach2->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['activity_id']);
    }

    /**
     * Test updating a plan with 2 group activities fails with 422.
     */
    public function test_cannot_update_plan_to_contain_multiple_group_activities(): void
    {
        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'خطة تجريبية',
            'base_price' => 200,
            'status' => 'active',
        ]);

        $response = $this->putJson("/api/v1/subscription-plans/{$plan->id}", [
            'reason' => 'Updating activities',
            'activities' => [
                ['activity_id' => $this->aerobicActivity->id, 'coach_id' => $this->coach1->id],
                ['activity_id' => $this->yogaActivity->id, 'coach_id' => $this->coach2->id],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['activities']);
    }

    /**
     * Test internal timing overlap within the same plan's session_templates fails with 422.
     */
    public function test_cannot_add_overlapping_session_templates_internally(): void
    {
        $payload = [
            'branch_id' => $this->branch->id,
            'name' => 'خطة يوغا صباحية',
            'base_price' => 250,
            'activities' => [
                ['activity_id' => $this->yogaActivity->id, 'coach_id' => $this->coach2->id],
            ],
            'session_templates' => [
                [
                    'day_of_week' => 1, // Monday
                    'start_time' => '10:00',
                    'end_time' => '11:30',
                ],
                [
                    'day_of_week' => 1, // Monday (Overlapping with 10:00 - 11:30)
                    'start_time' => '11:00',
                    'end_time' => '12:00',
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/subscription-plans', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['session_templates']);
    }

    /**
     * Test scheduling two group activities (Aerobics and Yoga) at the same time in the same branch fails with 422.
     */
    public function test_cannot_schedule_two_group_activities_at_same_time_in_branch(): void
    {
        // 1. Create first plan (Aerobics) on Sunday 16:00 - 17:30
        $planAerobic = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'اشتراك ايروبيك',
            'base_price' => 200,
            'status' => 'active',
        ]);
        $saAerobic = StaffActivity::where('activity_id', $this->aerobicActivity->id)->first();
        SubscriptionPlanActivity::create([
            'plan_id' => $planAerobic->id,
            'staff_activity_id' => $saAerobic->id,
        ]);
        SportSessionTemplate::create([
            'plan_id' => $planAerobic->id,
            'day_of_week' => 0, // Sunday
            'start_time' => '16:00',
            'end_time' => '17:30',
            'is_active' => true,
        ]);

        // 2. Attempt to create second plan (Yoga) with session overlapping at 17:00 - 18:00
        $payload = [
            'branch_id' => $this->branch->id,
            'name' => 'اشتراك يوغا مسائي',
            'base_price' => 250,
            'activities' => [
                ['activity_id' => $this->yogaActivity->id, 'coach_id' => $this->coach2->id],
            ],
            'session_templates' => [
                [
                    'day_of_week' => 0, // Sunday (Overlaps with 16:00 - 17:30)
                    'start_time' => '17:00',
                    'end_time' => '18:00',
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/subscription-plans', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['session_templates']);
    }

    /**
     * Test scheduling two session-based subscription plans (is_session_based = true) with DIFFERENT facilities
     * at the same time and day in the same branch fails with 422.
     */
    public function test_cannot_schedule_two_session_based_plans_at_same_time_even_with_different_facilities(): void
    {
        $facilityA = Facility::create(['branch_id' => $this->branch->id, 'name' => 'Studio A']);
        $facilityB = Facility::create(['branch_id' => $this->branch->id, 'name' => 'Studio B']);

        // 1. Create first plan (Aerobics, is_session_based=true) in Studio A on Sunday 10:00 - 11:00
        $planAerobic = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'اشتراك ايروبيك الصباحي',
            'base_price' => 200,
            'status' => 'active',
        ]);
        $saAerobic = StaffActivity::where('activity_id', $this->aerobicActivity->id)->first();
        SubscriptionPlanActivity::create([
            'plan_id' => $planAerobic->id,
            'staff_activity_id' => $saAerobic->id,
        ]);
        SportSessionTemplate::create([
            'plan_id' => $planAerobic->id,
            'facility_id' => $facilityA->id,
            'day_of_week' => 0, // Sunday
            'start_time' => '10:00',
            'end_time' => '11:00',
            'is_active' => true,
        ]);

        // 2. Attempt to create second plan (Yoga, is_session_based=true) in Studio B (different facility!) at 10:00 - 11:00
        $payload = [
            'branch_id' => $this->branch->id,
            'name' => 'اشتراك يوغا متزامن',
            'base_price' => 250,
            'activities' => [
                ['activity_id' => $this->yogaActivity->id, 'coach_id' => $this->coach2->id],
            ],
            'session_templates' => [
                [
                    'facility_id' => $facilityB->id, // Studio B (different facility)
                    'day_of_week' => 0, // Sunday
                    'start_time' => '10:00',
                    'end_time' => '11:00',
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/subscription-plans', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['session_templates']);
    }

    /**
     * Test creating a session template via SessionTemplateController rejects branch group session time conflict.
     */
    public function test_session_template_controller_rejects_group_session_time_conflict(): void
    {
        // 1. Existing Aerobic plan with session at Monday 10:00 - 11:00
        $planAerobic = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'اشتراك ايروبيك الصباحي',
            'base_price' => 200,
            'status' => 'active',
        ]);
        $saAerobic = StaffActivity::where('activity_id', $this->aerobicActivity->id)->first();
        SubscriptionPlanActivity::create([
            'plan_id' => $planAerobic->id,
            'staff_activity_id' => $saAerobic->id,
        ]);
        SportSessionTemplate::create([
            'plan_id' => $planAerobic->id,
            'day_of_week' => 1,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'is_active' => true,
        ]);

        // 2. Yoga Plan
        $planYoga = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'اشتراك يوغا',
            'base_price' => 220,
            'status' => 'active',
        ]);
        $saYoga = StaffActivity::where('activity_id', $this->yogaActivity->id)->first();
        SubscriptionPlanActivity::create([
            'plan_id' => $planYoga->id,
            'staff_activity_id' => $saYoga->id,
        ]);

        // Attempt to create session template for Yoga overlapping with Aerobic (10:30 - 11:30)
        $response = $this->postJson('/api/v1/session-templates', [
            'plan_id' => $planYoga->id,
            'day_of_week' => 1,
            'start_time' => '10:30',
            'end_time' => '11:30',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['start_time']);
    }

    /**
     * Test coach conflict: Same coach cannot teach two sessions at the same time.
     */
    public function test_same_coach_cannot_teach_two_sessions_at_same_time(): void
    {
        // Coach 1 assigned to Yoga as well
        StaffActivity::create(['staff_id' => $this->coach1->id, 'activity_id' => $this->yogaActivity->id]);

        $facility1 = Facility::create(['branch_id' => $this->branch->id, 'name' => 'Hall 1']);
        $facility2 = Facility::create(['branch_id' => $this->branch->id, 'name' => 'Hall 2']);

        // Plan 1 in Hall 1 with Coach 1 on Tuesday 14:00 - 15:00
        $plan1 = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Plan 1',
            'base_price' => 200,
            'status' => 'active',
        ]);
        $sa1 = StaffActivity::where('staff_id', $this->coach1->id)->where('activity_id', $this->aerobicActivity->id)->first();
        SubscriptionPlanActivity::create(['plan_id' => $plan1->id, 'staff_activity_id' => $sa1->id]);
        SportSessionTemplate::create([
            'plan_id' => $plan1->id,
            'facility_id' => $facility1->id,
            'day_of_week' => 2,
            'start_time' => '14:00',
            'end_time' => '15:00',
            'is_active' => true,
        ]);

        // Plan 2 in Hall 2 also assigning Coach 1 at overlapping time (14:30 - 15:30)
        $payload = [
            'branch_id' => $this->branch->id,
            'name' => 'Plan 2',
            'base_price' => 250,
            'activities' => [
                ['activity_id' => $this->yogaActivity->id, 'coach_id' => $this->coach1->id],
            ],
            'session_templates' => [
                [
                    'facility_id' => $facility2->id,
                    'day_of_week' => 2,
                    'start_time' => '14:30',
                    'end_time' => '15:30',
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/subscription-plans', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['session_templates']);
    }

    /**
     * Test non-group activities (General Training) can be combined in one plan.
     */
    public function test_can_combine_multiple_general_activities_in_plan(): void
    {
        $payload = [
            'branch_id' => $this->branch->id,
            'name' => 'باقة الحديد والمسبح العام',
            'base_price' => 350,
            'activities' => [
                ['activity_id' => $this->generalGymActivity->id],
                ['activity_id' => $this->poolActivity->id],
            ],
        ];

        $response = $this->postJson('/api/v1/subscription-plans', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('subscription_plans', ['name' => 'باقة الحديد والمسبح العام']);
    }
}
