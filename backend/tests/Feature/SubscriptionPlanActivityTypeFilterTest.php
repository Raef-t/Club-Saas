<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Branch;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Modules\Sports\Models\ActivityType;
use Modules\Sports\Models\Activity;
use Modules\Sports\Models\StaffActivity;
use Modules\StaffManager\Models\Staff;
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Modules\SubscriptionManager\Models\SubscriptionPlanActivity;

class SubscriptionPlanActivityTypeFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Branch $branch;
    protected ActivityType $fitnessType;
    protected ActivityType $swimmingType;
    protected Activity $gymActivity;
    protected Activity $poolActivity;
    protected Staff $coach;
    protected SubscriptionPlan $fitnessPlan;
    protected SubscriptionPlan $swimmingPlan;

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

        // Create Activity Types
        $this->fitnessType = ActivityType::create([
            'name' => 'Fitness & Bodybuilding',
            'is_active' => true,
        ]);
        $this->swimmingType = ActivityType::create([
            'name' => 'Swimming Sports',
            'is_active' => true,
        ]);

        // Create Activities
        $this->gymActivity = Activity::create([
            'branch_id' => $this->branch->id,
            'activity_type_id' => $this->fitnessType->id,
            'name' => 'Gym Workout',
            'is_active' => true,
        ]);
        $this->poolActivity = Activity::create([
            'branch_id' => $this->branch->id,
            'activity_type_id' => $this->swimmingType->id,
            'name' => 'Olympic Pool',
            'is_active' => true,
        ]);

        // Create Coach
        $coachPerson = Person::create(['full_name' => 'Captain Ahmed', 'gender' => 'male', 'type' => 'staff']);
        $this->coach = Staff::create([
            'person_id' => $coachPerson->id,
            'branch_id' => $this->branch->id,
            'employment_type' => 'trainer',
            'job_title' => 'Coach',
            'role' => 'coach',
            'work_status' => 'active',
            'is_active' => true,
        ]);

        $staffGym = StaffActivity::create(['staff_id' => $this->coach->id, 'activity_id' => $this->gymActivity->id]);
        $staffPool = StaffActivity::create(['staff_id' => $this->coach->id, 'activity_id' => $this->poolActivity->id]);

        // Create Subscription Plans
        $this->fitnessPlan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Gold Fitness Plan',
            'base_price' => 150,
            'status' => 'active',
            'max_subscribers' => 50,
        ]);
        SubscriptionPlanActivity::create(['plan_id' => $this->fitnessPlan->id, 'staff_activity_id' => $staffGym->id]);

        $this->swimmingPlan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Silver Swimming Plan',
            'base_price' => 200,
            'status' => 'active',
            'max_subscribers' => 30,
        ]);
        SubscriptionPlanActivity::create(['plan_id' => $this->swimmingPlan->id, 'staff_activity_id' => $staffPool->id]);
    }

    public function test_filter_subscription_plans_by_activity_type_id(): void
    {
        // Request with activity_type_id for Fitness (matching user's query format)
        $url = "/api/v1/subscription-plans?branch_id={$this->branch->id}&available=true&activity_type_id={$this->fitnessType->id}&per_page=15&page=1";
        $response = $this->getJson($url);

        $response->assertStatus(200);
        $data = $response->json('data');
        $ids = collect($data)->pluck('id')->toArray();

        $this->assertContains($this->fitnessPlan->id, $ids);
        $this->assertNotContains($this->swimmingPlan->id, $ids);

        // Request with activity_type_id for Swimming
        $urlSwimming = "/api/v1/subscription-plans?branch_id={$this->branch->id}&available=true&activity_type_id={$this->swimmingType->id}&per_page=15&page=1";
        $responseSwimming = $this->getJson($urlSwimming);

        $responseSwimming->assertStatus(200);
        $dataSwimming = $responseSwimming->json('data');
        $idsSwimming = collect($dataSwimming)->pluck('id')->toArray();

        $this->assertContains($this->swimmingPlan->id, $idsSwimming);
        $this->assertNotContains($this->fitnessPlan->id, $idsSwimming);
    }

    public function test_filter_subscription_plans_by_activity_type_name(): void
    {
        // Request using activity_type string name
        $url = "/api/v1/subscription-plans?branch_id={$this->branch->id}&activity_type=Swimming";
        $response = $this->getJson($url);

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id')->toArray();

        $this->assertContains($this->swimmingPlan->id, $ids);
        $this->assertNotContains($this->fitnessPlan->id, $ids);
    }

    public function test_filter_subscription_plans_by_activity_id(): void
    {
        // Request filtering directly by activity_id
        $url = "/api/v1/subscription-plans?branch_id={$this->branch->id}&activity_id={$this->gymActivity->id}";
        $response = $this->getJson($url);

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id')->toArray();

        $this->assertContains($this->fitnessPlan->id, $ids);
        $this->assertNotContains($this->swimmingPlan->id, $ids);
    }

    public function test_filter_registration_plans_by_activity_type_id(): void
    {
        $url = "/api/v1/subscription-plans/registration?branch_id={$this->branch->id}&activity_type_id={$this->fitnessType->id}";
        $response = $this->getJson($url);

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id')->toArray();

        $this->assertContains($this->fitnessPlan->id, $ids);
        $this->assertNotContains($this->swimmingPlan->id, $ids);
    }

    public function test_response_includes_enriched_activity_and_activity_types_metadata(): void
    {
        $url = "/api/v1/subscription-plans?branch_id={$this->branch->id}&activity_type_id={$this->fitnessType->id}";
        $response = $this->getJson($url);

        $response->assertStatus(200);
        $firstPlan = $response->json('data.0');

        $this->assertEquals($this->fitnessPlan->id, $firstPlan['id']);

        // Assert activities details
        $this->assertNotEmpty($firstPlan['activities']);
        $activityItem = $firstPlan['activities'][0];
        $this->assertEquals($this->gymActivity->id, $activityItem['activity_id']);
        $this->assertEquals('Gym Workout', $activityItem['activity_name']);
        $this->assertEquals($this->fitnessType->id, $activityItem['activity_type_id']);
        $this->assertEquals('Fitness & Bodybuilding', $activityItem['activity_type_name']);
        $this->assertEquals('Captain Ahmed', $activityItem['coach_name']);

        // Assert activity_types array
        $this->assertNotEmpty($firstPlan['activity_types']);
        $this->assertEquals($this->fitnessType->id, $firstPlan['activity_types'][0]['id']);
        $this->assertEquals('Fitness & Bodybuilding', $firstPlan['activity_types'][0]['name']);
    }

    public function test_filtering_with_unmatched_activity_type_returns_empty(): void
    {
        $url = "/api/v1/subscription-plans?branch_id={$this->branch->id}&activity_type_id=999999";
        $response = $this->getJson($url);

        $response->assertStatus(200);
        $this->assertEmpty($response->json('data'));
    }
}
