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
use Modules\Sports\Models\SportSessionTemplate;
use Modules\Sports\Models\StaffActivity;
use Modules\StaffManager\Models\Staff;
use Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus;
use Modules\SubscriptionManager\Enums\SubscriptionPlanStatus;
use Modules\SubscriptionManager\Models\PlayerSubscription;
use Modules\SubscriptionManager\Models\PlayerSubscriptionItem;
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Modules\SubscriptionManager\Models\SubscriptionPlanActivity;
use Tests\TestCase;

class OffScheduleAttendanceOverrideTest extends TestCase
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
            'full_name' => 'Admin Test User',
            'gender' => 'female',
            'type' => 'staff',
        ]);

        $this->user = User::create([
            'person_id' => $person->id,
            'username' => 'admin_override_' . uniqid(),
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
            'full_name' => 'Coach Noor',
            'gender' => 'female',
            'type' => 'coach',
        ]);

        $this->coach = Staff::create([
            'person_id' => $coachPerson->id,
            'role' => 'coach',
        ]);

        $memberPerson = Person::create([
            'full_name' => 'Player Sarah',
            'gender' => 'female',
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
            'name' => 'Gymnastics & Fitness',
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

    /**
     * السيناريو المطلوب:
     * اليوم في اشتراك للاعبة الساعة 5 (17:00) عندها جلسة، وأتت لتلعب الساعة 1 (13:00).
     * بدون إدخال سبب -> يفشل وتظهر رسالة تحذيرية تطلب إدخال السبب وتوضح الموعد المجدول.
     */
    public function test_off_schedule_checkin_fails_without_reason(): void
    {
        // 2026-08-16 is a Sunday (day_of_week = 0)
        $plan = $this->createPlanWithActivity('Pilates 5PM Plan');
        SportSessionTemplate::create([
            'plan_id' => $plan->id,
            'day_of_week' => 0, // Sunday
            'start_time' => '17:00',
            'end_time' => '18:30',
            'is_active' => true,
        ]);

        $sub = $this->createPlayerSubscription($plan, '2026-08-01', '2026-08-31', 12, 0);

        // Member attempts check-in on Sunday at 13:00 (1:00 PM) instead of 17:00 (5:00 PM)
        $response = $this->postJson('/api/v1/attendances/check-in', [
            'attendable_type'         => 'member',
            'attendable_id'           => $this->member->id,
            'branch_id'               => $this->branch->id,
            'check_in_at'             => '2026-08-16 13:00:00',
            'player_subscription_ids' => [$sub->id],
        ]);

        $response->assertStatus(400);
        $this->assertStringContainsString('هذا ليس موعد فعاليتك المجدول', $response->json('message'));
        $this->assertStringContainsString('سبب تسجيل الحضور', $response->json('message'));

        // Ensure no attendance was recorded
        $this->assertDatabaseMissing('attendances', [
            'attendable_id' => $this->member->id,
        ]);
    }

    /**
     * السيناريو المطلوب:
     * اللاعبة أتت الساعة 1 بدلاً من الساعة 5، وتم إدخال سبب تسجيل الحضور ->
     * يمر تسجيل الحضور بنجاح، يُخزن السبب في سجل الحضور، وتُخصم الجلسة من رصيد الاشتراك.
     */
    public function test_off_schedule_checkin_succeeds_with_reason_and_deducts_session(): void
    {
        // Sunday (day_of_week = 0)
        $plan = $this->createPlanWithActivity('Pilates 5PM Plan');
        SportSessionTemplate::create([
            'plan_id' => $plan->id,
            'day_of_week' => 0,
            'start_time' => '17:00',
            'end_time' => '18:30',
            'is_active' => true,
        ]);

        $sub = $this->createPlayerSubscription($plan, '2026-08-01', '2026-08-31', 12, 0);

        // Member attempts check-in at 13:00 with override reason
        $reason = 'اللاعبة طلبت تقديم التمرين لظرف عائلي طارئ';
        $response = $this->postJson('/api/v1/attendances/check-in', [
            'attendable_type'         => 'member',
            'attendable_id'           => $this->member->id,
            'branch_id'               => $this->branch->id,
            'check_in_at'             => '2026-08-16 13:00:00',
            'player_subscription_ids' => [$sub->id],
            'notes'                   => $reason,
        ]);

        $response->assertStatus(200);
        $this->assertEquals($reason, $response->json('data.notes'));

        // Ensure attendance is saved with notes
        $this->assertDatabaseHas('attendances', [
            'attendable_id' => $this->member->id,
            'status'        => 'checked_in',
            'notes'         => $reason,
        ]);

        // Ensure session consumption is recorded
        $this->assertDatabaseHas('attendance_consumptions', [
            'player_subscription_id' => $sub->id,
            'subscription_plan_id'   => $plan->id,
        ]);

        // Ensure sessions_consumed was incremented from 0 to 1
        $this->assertDatabaseHas('player_subscription_items', [
            'player_subscription_id' => $sub->id,
            'sessions_consumed'      => 1,
        ]);
    }

    /**
     * اختبار الحضور في نفس موعد الجلسة المجدولة (الساعة 17:15) -> يمر بنجاح مباشرة بدون الحاجة لإدخال سبب.
     */
    public function test_on_schedule_checkin_succeeds_without_reason(): void
    {
        $plan = $this->createPlanWithActivity('Evening Aerobics');
        SportSessionTemplate::create([
            'plan_id' => $plan->id,
            'day_of_week' => 0,
            'start_time' => '17:00',
            'end_time' => '18:30',
            'is_active' => true,
        ]);

        $sub = $this->createPlayerSubscription($plan, '2026-08-01', '2026-08-31', 12, 0);

        // Check-in at 17:15 (during scheduled session 17:00-18:30)
        $response = $this->postJson('/api/v1/attendances/check-in', [
            'attendable_type'         => 'member',
            'attendable_id'           => $this->member->id,
            'branch_id'               => $this->branch->id,
            'check_in_at'             => '2026-08-16 17:15:00',
            'player_subscription_ids' => [$sub->id],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('attendances', [
            'attendable_id' => $this->member->id,
            'status'        => 'checked_in',
        ]);
        $this->assertDatabaseHas('player_subscription_items', [
            'player_subscription_id' => $sub->id,
            'sessions_consumed'      => 1,
        ]);
    }

    /**
     * اختبار مسار الاستقبال ذي الخطوتين:
     * خطوة 1: تسجيل الحضور
     * خطوة 2: الخصم اللاحق من الاشتراك عبر `/deduct` خارج وقت الجلسة.
     * يفشل عند عدم إرسال سبب، وينجح عند إرسال السبب.
     */
    public function test_reception_two_step_deduct_enforces_reason_when_off_schedule(): void
    {
        $plan = $this->createPlanWithActivity('Zumba Plan');
        SportSessionTemplate::create([
            'plan_id' => $plan->id,
            'day_of_week' => 0,
            'start_time' => '17:00',
            'end_time' => '18:00',
            'is_active' => true,
        ]);

        $sub = $this->createPlayerSubscription($plan, '2026-08-01', '2026-08-31', 10, 0);

        // Step 1: Check-in created at 13:00 without subscriptions
        $attendance = Attendance::create([
            'attendable_type' => 'member',
            'attendable_id'   => $this->member->id,
            'branch_id'       => $this->branch->id,
            'check_in_at'     => '2026-08-16 13:00:00',
            'status'          => 'checked_in',
        ]);

        // Step 2a: Deduct without reason -> fails
        $deductFail = $this->postJson("/api/v1/reception/attendances/{$attendance->id}/deduct", [
            'player_subscription_ids' => [$sub->id],
        ]);
        $deductFail->assertStatus(400);
        $this->assertStringContainsString('هذا ليس موعد فعاليتك المجدول', $deductFail->json('message'));

        // Step 2b: Deduct with reason -> succeeds
        $deductSuccess = $this->postJson("/api/v1/reception/attendances/{$attendance->id}/deduct", [
            'player_subscription_ids' => [$sub->id],
            'notes'                   => 'تم التنسيق مع المدربة لتقديم الموعد',
        ]);
        $deductSuccess->assertStatus(200);

        $attendance = $attendance->fresh();
        $this->assertEquals('تم التنسيق مع المدربة لتقديم الموعد', $attendance->notes);
        $this->assertDatabaseHas('player_subscription_items', [
            'player_subscription_id' => $sub->id,
            'sessions_consumed'      => 1,
        ]);
    }

    /**
     * اختبار الاشتراكات المفتوحة (Open Gym) بدون قوالب جلسات محددة:
     * تتاح في أي وقت من اليوم دون اشتراط سبب.
     */
    public function test_open_gym_plan_without_templates_works_at_any_time_without_reason(): void
    {
        $openPlan = $this->createPlanWithActivity('Open Gym All Day', null);
        $sub = $this->createPlayerSubscription($openPlan, '2026-08-01', '2026-08-31', 0, 0, true);

        // Check-in at 13:00
        $response = $this->postJson('/api/v1/attendances/check-in', [
            'attendable_type'         => 'member',
            'attendable_id'           => $this->member->id,
            'branch_id'               => $this->branch->id,
            'check_in_at'             => '2026-08-16 13:00:00',
            'player_subscription_ids' => [$sub->id],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('attendances', [
            'attendable_id' => $this->member->id,
            'status'        => 'checked_in',
        ]);
    }

    /**
     * اختبار واجهة الاستقبال لجلب اشتراكات اليوم:
     * التأكد من إرجاع الحقول is_on_schedule و requires_override_reason بشكل سليم.
     */
    public function test_reception_subscriptions_endpoint_returns_schedule_status_indicators(): void
    {
        $plan = $this->createPlanWithActivity('Evening Kickboxing');
        SportSessionTemplate::create([
            'plan_id' => $plan->id,
            'day_of_week' => 0,
            'start_time' => '17:00',
            'end_time' => '18:30',
            'is_active' => true,
        ]);

        $sub = $this->createPlayerSubscription($plan, '2026-08-01', '2026-08-31', 12, 0);

        $response = $this->getJson("/api/v1/reception/members/{$this->member->id}/subscriptions?date=2026-08-16");
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('is_on_schedule', $data[0]);
        $this->assertArrayHasKey('requires_override_reason', $data[0]);
        $this->assertArrayHasKey('today_sessions', $data[0]);
    }
}
