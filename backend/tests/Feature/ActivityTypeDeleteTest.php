<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Branch;
use Modules\MemberManager\Models\Member;
use Modules\Sports\Models\ActivityType;
use Modules\Sports\Models\Activity;
use Modules\Sports\Models\StaffActivity;
use Modules\StaffManager\Models\Staff;
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Modules\SubscriptionManager\Models\SubscriptionPlanActivity;
use Modules\SubscriptionManager\Models\PlayerSubscription;
use Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus;

class ActivityTypeDeleteTest extends TestCase
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

    public function test_delete_activity_type_without_confirm_returns_422(): void
    {
        $type = ActivityType::create([
            'name' => 'Test Type',
            'is_active' => true,
        ]);

        $response = $this->deleteJson("/api/v1/activity-types/{$type->id}");
        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
            ]);

        $this->assertDatabaseHas('activity_types', [
            'id' => $type->id,
            'deleted_at' => null,
        ]);
    }

    public function test_delete_activity_type_with_active_subscriptions_and_no_confirm_warns_with_count(): void
    {
        $type = ActivityType::create([
            'name' => 'Fitness Type',
            'is_active' => true,
        ]);

        $activity = Activity::create([
            'name' => 'Gym Workout',
            'branch_id' => $this->branch->id,
            'activity_type_id' => $type->id,
            'is_active' => true,
            'session_price' => 50.00,
        ]);

        $coachPerson = Person::create(['full_name' => 'Coach One', 'gender' => 'male', 'type' => 'staff']);
        $coach = Staff::create([
            'person_id' => $coachPerson->id,
            'staff_number' => 'STF-' . uniqid(),
            'role' => 'coach',
            'is_active' => true,
        ]);

        $staffActivity = StaffActivity::create([
            'staff_id' => $coach->id,
            'activity_id' => $activity->id,
        ]);

        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Fitness Plan',
            'base_price' => 200.00,
            'status' => 'active',
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $plan->id,
            'staff_activity_id' => $staffActivity->id,
        ]);

        $memberPerson = Person::create(['full_name' => 'Player One', 'gender' => 'male', 'type' => 'player']);
        $member = Member::create([
            'branch_id' => $this->branch->id,
            'person_id' => $memberPerson->id,
            'member_number' => 'MEM-7001',
            'membership_status' => 'active',
            'join_date' => now(),
        ]);

        PlayerSubscription::create([
            'member_id' => $member->id,
            'plan_id' => $plan->id,
            'months_count' => 1,
            'total_amount' => 200.00,
            'paid_amount' => 200.00,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
        ]);

        $response = $this->deleteJson("/api/v1/activity-types/{$type->id}");
        $response->assertStatus(422);

        $data = $response->json();
        $this->assertStringContainsString('1', $data['message'] ?? '');
    }

    public function test_delete_activity_type_with_confirm_delete_succeeds_and_soft_deletes_child_activities(): void
    {
        $type = ActivityType::create([
            'name' => 'Swimming Type',
            'is_active' => true,
        ]);

        $activity = Activity::create([
            'name' => 'Pool Session',
            'branch_id' => $this->branch->id,
            'activity_type_id' => $type->id,
            'is_active' => true,
            'session_price' => 60.00,
        ]);

        $coachPerson = Person::create(['full_name' => 'Coach Two', 'gender' => 'male', 'type' => 'staff']);
        $coach = Staff::create([
            'person_id' => $coachPerson->id,
            'staff_number' => 'STF-' . uniqid(),
            'role' => 'coach',
            'is_active' => true,
        ]);

        $staffActivity = StaffActivity::create([
            'staff_id' => $coach->id,
            'activity_id' => $activity->id,
        ]);

        // Delete with ?confirm=delete query param
        $response = $this->deleteJson("/api/v1/activity-types/{$type->id}?confirm=delete");
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertSoftDeleted('activity_types', ['id' => $type->id]);
        $this->assertSoftDeleted('activities', ['id' => $activity->id]);
        $this->assertSoftDeleted('staff_activities', ['id' => $staffActivity->id]);
    }
}
