<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\AttendanceManager\Models\Attendance;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Modules\ClubManager\Models\Branch;
use Modules\ClubManager\Models\Club;
use Modules\MemberManager\Models\Member;
use Modules\Sports\Models\Activity;
use Modules\Sports\Models\ActivityType;
use Modules\Sports\Models\SessionException;
use Modules\Sports\Models\SportSessionTemplate;
use Modules\Sports\Models\StaffActivity;
use Modules\StaffManager\Models\Staff;
use Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus;
use Modules\SubscriptionManager\Enums\SubscriptionPlanStatus;
use Modules\SubscriptionManager\Models\PlayerSubscription;
use Modules\SubscriptionManager\Models\PlayerSubscriptionItem;
use Modules\SubscriptionManager\Models\SubscriptionFreeze;
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Modules\SubscriptionManager\Models\SubscriptionPlanActivity;
use Modules\SubscriptionManager\Models\SubscriptionPlanSuspension;
use Tests\TestCase;

class AttendanceSessionDayFilterTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $branch;
    protected $member;
    protected $coach;
    protected $activityType;

    protected function setUp(): void
    {
        parent::setUp();

        $person = Person::create([
            'full_name' => 'Admin User',
            'gender' => 'male',
            'type' => 'staff',
        ]);

        $this->user = User::create([
            'person_id' => $person->id,
            'username' => 'admin_test_' . uniqid(),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $role = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'sanctum',
        ]);
        $this->user->assignRole($role);
        Sanctum::actingAs($this->user, ['*']);

        $club = Club::create(['name' => 'Test Club']);
        $this->branch = Branch::create(['club_id' => $club->id, 'name' => 'Main Branch']);

        $coachPerson = Person::create([
            'full_name' => 'Coach Ahmad',
            'gender' => 'male',
            'type' => 'coach',
        ]);

        $this->coach = Staff::create([
            'person_id' => $coachPerson->id,
            'role' => 'coach',
        ]);

        $memberPerson = Person::create([
            'full_name' => 'Player Samer',
            'gender' => 'male',
            'type' => 'player',
        ]);

        $this->member = Member::create([
            'branch_id' => $this->branch->id,
            'person_id' => $memberPerson->id,
            'member_number' => 'M-' . uniqid(),
            'join_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $this->activityType = ActivityType::create([
            'name' => 'Fitness',
            'is_active' => true,
        ]);
    }

    private function createPlanWithActivity(string $name, ?int $sessionCount = 12): SubscriptionPlan
    {
        $activity = Activity::create([
            'branch_id' => $this->branch->id,
            'activity_type_id' => $this->activityType->id,
            'name' => $name . ' Activity',
            'is_active' => true,
        ]);

        $staffActivity = StaffActivity::create([
            'activity_id' => $activity->id,
            'staff_id' => $this->coach->id,
        ]);

        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => $name,
            'session_count' => $sessionCount,
            'base_price' => 100,
            'status' => SubscriptionPlanStatus::ACTIVE->value,
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $plan->id,
            'staff_activity_id' => $staffActivity->id,
        ]);

        return $plan;
    }

    private function createPlayerSubscription(SubscriptionPlan $plan, string $startDate = '2026-08-01', string $endDate = '2026-08-31', int $allocated = 12, int $consumed = 0, bool $unlimited = false): PlayerSubscription
    {
        $sub = PlayerSubscription::create([
            'member_id' => $this->member->id,
            'plan_id' => $plan->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
            'total_amount' => 100,
            'paid_amount' => 100,
            'remaining_amount' => 0,
        ]);

        PlayerSubscriptionItem::create([
            'player_subscription_id' => $sub->id,
            'sessions_allocated' => $allocated,
            'sessions_consumed' => $consumed,
            'is_unlimited' => $unlimited,
        ]);

        return $sub;
    }

    public function test_reception_subscriptions_only_returns_subscriptions_with_sessions_on_the_given_day(): void
    {
        // Sunday is day_of_week = 0, Monday is day_of_week = 1
        $sundayPlan = $this->createPlanWithActivity('Football Sunday Plan');
        SportSessionTemplate::create([
            'plan_id' => $sundayPlan->id,
            'day_of_week' => 0, // Sunday
            'start_time' => '16:00',
            'end_time' => '17:30',
            'is_active' => true,
        ]);

        $mondayPlan = $this->createPlanWithActivity('Swimming Monday Plan');
        SportSessionTemplate::create([
            'plan_id' => $mondayPlan->id,
            'day_of_week' => 1, // Monday
            'start_time' => '18:00',
            'end_time' => '19:30',
            'is_active' => true,
        ]);

        $subSunday = $this->createPlayerSubscription($sundayPlan);
        $subMonday = $this->createPlayerSubscription($mondayPlan);

        // 2026-08-16 is a Sunday (dayOfWeek = 0)
        $sundayResponse = $this->getJson("/api/v1/reception/members/{$this->member->id}/subscriptions?date=2026-08-16");
        $sundayResponse->assertStatus(200);
        $sundayData = $sundayResponse->json('data');

        $this->assertCount(1, $sundayData);
        $this->assertEquals($subSunday->id, $sundayData[0]['player_subscription_id']);
        $this->assertNotEmpty($sundayData[0]['today_sessions']);
        $this->assertEquals('16:00', $sundayData[0]['today_sessions'][0]['start_time']);

        // 2026-08-17 is a Monday (dayOfWeek = 1)
        $mondayResponse = $this->getJson("/api/v1/reception/members/{$this->member->id}/subscriptions?date=2026-08-17");
        $mondayResponse->assertStatus(200);
        $mondayData = $mondayResponse->json('data');

        $this->assertCount(1, $mondayData);
        $this->assertEquals($subMonday->id, $mondayData[0]['player_subscription_id']);
        $this->assertNotEmpty($mondayData[0]['today_sessions']);
        $this->assertEquals('18:00', $mondayData[0]['today_sessions'][0]['start_time']);

        // 2026-08-18 is a Tuesday (dayOfWeek = 2) -> neither plan has sessions on Tuesday
        $tuesdayResponse = $this->getJson("/api/v1/reception/members/{$this->member->id}/subscriptions?date=2026-08-18");
        $tuesdayResponse->assertStatus(404);
    }

    public function test_open_gym_plan_without_session_templates_is_returned_on_any_day(): void
    {
        // Open gym plan with no session templates
        $openGymPlan = $this->createPlanWithActivity('Open Gym Plan', null);
        $subOpen = $this->createPlayerSubscription($openGymPlan, '2026-08-01', '2026-08-31', 0, 0, true);

        // Check on Sunday
        $resSunday = $this->getJson("/api/v1/reception/members/{$this->member->id}/subscriptions?date=2026-08-16");
        $resSunday->assertStatus(200);
        $this->assertEquals($subOpen->id, $resSunday->json('data.0.player_subscription_id'));

        // Check on Wednesday
        $resWednesday = $this->getJson("/api/v1/reception/members/{$this->member->id}/subscriptions?date=2026-08-19");
        $resWednesday->assertStatus(200);
        $this->assertEquals($subOpen->id, $resWednesday->json('data.0.player_subscription_id'));
    }

    public function test_soft_deleted_subscriptions_and_plans_are_excluded(): void
    {
        $plan = $this->createPlanWithActivity('Karate Plan');
        SportSessionTemplate::create([
            'plan_id' => $plan->id,
            'day_of_week' => 0,
            'start_time' => '15:00',
            'end_time' => '16:00',
            'is_active' => true,
        ]);

        $sub = $this->createPlayerSubscription($plan);
        $sub->delete(); // Soft delete the subscription

        $response = $this->getJson("/api/v1/reception/members/{$this->member->id}/subscriptions?date=2026-08-16");
        $response->assertStatus(404);

        // Now restore subscription but soft-delete plan
        $sub->restore();
        $plan->delete(); // Soft delete plan

        $response2 = $this->getJson("/api/v1/reception/members/{$this->member->id}/subscriptions?date=2026-08-16");
        $response2->assertStatus(404);
    }

    public function test_subscriptions_with_zero_remaining_sessions_are_excluded(): void
    {
        $plan = $this->createPlanWithActivity('Boxing Plan');
        SportSessionTemplate::create([
            'plan_id' => $plan->id,
            'day_of_week' => 0,
            'start_time' => '17:00',
            'end_time' => '18:00',
            'is_active' => true,
        ]);

        // Allocated 10, consumed 10 (0 remaining)
        $sub = $this->createPlayerSubscription($plan, '2026-08-01', '2026-08-31', 10, 10, false);

        $response = $this->getJson("/api/v1/reception/members/{$this->member->id}/subscriptions?date=2026-08-16");
        $response->assertStatus(404);
    }

    public function test_future_subscriptions_that_have_not_started_are_excluded_and_cannot_be_deducted(): void
    {
        $plan = $this->createPlanWithActivity('Future Basketball Plan');
        SportSessionTemplate::create([
            'plan_id' => $plan->id,
            'day_of_week' => 0, // Sunday
            'start_time' => '10:00',
            'end_time' => '11:30',
            'is_active' => true,
        ]);

        // Starts on 2026-09-01 (future)
        $sub = $this->createPlayerSubscription($plan, '2026-09-01', '2026-09-30', 12, 0, false);

        // Checking on Sunday 2026-08-16 should NOT show the subscription
        $response = $this->getJson("/api/v1/reception/members/{$this->member->id}/subscriptions?date=2026-08-16");
        $response->assertStatus(404);

        // Create an attendance record and try to deduct session directly -> should fail with error
        $attendance = Attendance::create([
            'attendable_type' => 'member',
            'attendable_id' => $this->member->id,
            'branch_id' => $this->branch->id,
            'check_in_at' => '2026-08-16 10:00:00',
            'status' => 'checked_in',
        ]);

        $deductResponse = $this->postJson("/api/v1/reception/attendances/{$attendance->id}/deduct", [
            'player_subscription_ids' => [$sub->id],
        ]);

        $deductResponse->assertStatus(400);
        $this->assertStringContainsString('لم تبدأ بعد', $deductResponse->json('message'));
    }

    public function test_cancelled_session_exceptions_exclude_subscription_from_today(): void
    {
        $plan = $this->createPlanWithActivity('Gymnastics Plan');
        $template = SportSessionTemplate::create([
            'plan_id' => $plan->id,
            'day_of_week' => 0, // Sunday
            'start_time' => '14:00',
            'end_time' => '15:00',
            'is_active' => true,
        ]);

        $sub = $this->createPlayerSubscription($plan);

        // Add a cancellation exception for Sunday 2026-08-16
        SessionException::create([
            'sport_session_template_id' => $template->id,
            'date' => '2026-08-16',
            'status' => 'cancelled',
            'reason' => 'Coach apology',
        ]);

        $response = $this->getJson("/api/v1/reception/members/{$this->member->id}/subscriptions?date=2026-08-16");
        $response->assertStatus(404);

        // But next Sunday 2026-08-23 is not cancelled, so it should be returned!
        $responseNextSunday = $this->getJson("/api/v1/reception/members/{$this->member->id}/subscriptions?date=2026-08-23");
        $responseNextSunday->assertStatus(200);
        $this->assertEquals($sub->id, $responseNextSunday->json('data.0.player_subscription_id'));
    }

    public function test_frozen_subscription_and_suspended_plan_are_excluded(): void
    {
        $plan = $this->createPlanWithActivity('Tennis Plan');
        SportSessionTemplate::create([
            'plan_id' => $plan->id,
            'day_of_week' => 0, // Sunday
            'start_time' => '09:00',
            'end_time' => '10:30',
            'is_active' => true,
        ]);

        $sub = $this->createPlayerSubscription($plan);

        // Freeze subscription from 2026-08-10 to 2026-08-20
        SubscriptionFreeze::create([
            'player_subscription_id' => $sub->id,
            'freeze_start_date' => '2026-08-10',
            'freeze_end_date' => '2026-08-20',
            'reason' => 'Medical',
        ]);

        $response = $this->getJson("/api/v1/reception/members/{$this->member->id}/subscriptions?date=2026-08-16");
        $response->assertStatus(404);
    }
}
