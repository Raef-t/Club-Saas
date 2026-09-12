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

class ReceptionCheckInAndDeductTest extends TestCase
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
            'full_name' => 'Receptionist Staff',
            'gender' => 'female',
            'type' => 'staff',
        ]);

        $this->user = User::create([
            'person_id' => $person->id,
            'username' => 'reception_user_' . uniqid(),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $role = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'sanctum',
        ]);
        $this->user->assignRole($role);
        Sanctum::actingAs($this->user, ['*']);

        $club = Club::create(['name' => 'Al-Ahli Club']);
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
            'full_name' => 'Player Sami',
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
            'name' => 'Swimming',
            'is_active' => true,
        ]);
    }

    private function createPlan(string $name, ?int $sessionCount = 12): SubscriptionPlan
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
            'base_price' => 150,
            'status' => SubscriptionPlanStatus::ACTIVE->value,
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $plan->id,
            'staff_activity_id' => $staffActivity->id,
        ]);

        return $plan;
    }

    private function createSubscription(SubscriptionPlan $plan, int $allocated = 12, int $consumed = 0): PlayerSubscription
    {
        $sub = PlayerSubscription::create([
            'member_id' => $this->member->id,
            'plan_id' => $plan->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-10-31',
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
            'total_amount' => 150,
            'paid_amount' => 150,
            'remaining_amount' => 0,
        ]);

        PlayerSubscriptionItem::create([
            'player_subscription_id' => $sub->id,
            'sessions_allocated' => $allocated,
            'sessions_consumed' => $consumed,
            'is_unlimited' => false,
        ]);

        return $sub;
    }

    /**
     * 1. فحص تسجيل الدخول وخصم الجلسة بنجاح مع تمرير معرفات الاشتراكات بشكل صريح
     */
    public function test_reception_check_in_and_deduct_with_explicit_subscription_ids(): void
    {
        // 2026-08-16 is a Sunday (day 0)
        $plan = $this->createPlan('Swimming Sunday');
        SportSessionTemplate::create([
            'plan_id' => $plan->id,
            'day_of_week' => 0,
            'start_time' => '10:00',
            'end_time' => '11:30',
            'is_active' => true,
        ]);

        $sub = $this->createSubscription($plan, 10, 0);

        $response = $this->postJson('/api/v1/reception/check-in-and-deduct', [
            'member_id'               => $this->member->id,
            'branch_id'               => $this->branch->id,
            'check_in_at'             => '2026-08-16 10:15:00',
            'player_subscription_ids' => [$sub->id],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');

        $attendanceId = $response->json('data.id');
        $this->assertNotNull($attendanceId);

        // Verify Attendance record created
        $this->assertDatabaseHas('attendances', [
            'id'            => $attendanceId,
            'attendable_id' => $this->member->id,
            'status'        => 'checked_in',
        ]);

        // Verify session consumed atomically
        $this->assertDatabaseHas('attendance_consumptions', [
            'attendance_id'          => $attendanceId,
            'player_subscription_id' => $sub->id,
        ]);

        $this->assertDatabaseHas('player_subscription_items', [
            'player_subscription_id' => $sub->id,
            'sessions_consumed'      => 1,
        ]);
    }

    /**
     * 2. فحص الاكتشاف التلقائي لاشتراك اليوم في حال عدم تمرير معرف الاشتراك
     */
    public function test_reception_check_in_and_deduct_auto_detects_subscription(): void
    {
        // 2026-08-16 is a Sunday (day 0)
        $plan = $this->createPlan('Gym Sunday');
        SportSessionTemplate::create([
            'plan_id' => $plan->id,
            'day_of_week' => 0,
            'start_time' => '10:00',
            'end_time' => '11:30',
            'is_active' => true,
        ]);

        $sub = $this->createSubscription($plan, 10, 0);

        // Omit player_subscription_ids
        $response = $this->postJson('/api/v1/reception/check-in-and-deduct', [
            'member_id'   => $this->member->id,
            'branch_id'   => $this->branch->id,
            'check_in_at' => '2026-08-16 10:20:00',
        ]);

        $response->assertStatus(200);
        $attendanceId = $response->json('data.id');

        $this->assertDatabaseHas('attendance_consumptions', [
            'attendance_id'          => $attendanceId,
            'player_subscription_id' => $sub->id,
        ]);

        $this->assertDatabaseHas('player_subscription_items', [
            'player_subscription_id' => $sub->id,
            'sessions_consumed'      => 1,
        ]);
    }

    /**
     * 3. فحص تطبيع المفاتيح البديلة: attendable_id و subscription_id المفرد
     */
    public function test_reception_check_in_and_deduct_normalizes_alternative_keys(): void
    {
        $plan = $this->createPlan('Yoga Sunday');
        SportSessionTemplate::create([
            'plan_id' => $plan->id,
            'day_of_week' => 0,
            'start_time' => '09:00',
            'end_time' => '10:30',
            'is_active' => true,
        ]);

        $sub = $this->createSubscription($plan, 8, 2);

        // Pass attendable_id and single subscription_id
        $response = $this->postJson('/api/v1/reception/check-in-and-deduct', [
            'attendable_id'   => $this->member->id,
            'branch_id'       => $this->branch->id,
            'check_in_at'     => '2026-08-16 09:15:00',
            'subscription_id' => $sub->id,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('player_subscription_items', [
            'player_subscription_id' => $sub->id,
            'sessions_consumed'      => 3,
        ]);
    }

    /**
     * 4. فحص التراجع التام (Atomic Rollback): عدم تسجيل أي حضور عند نفاد الجلسات
     */
    public function test_reception_check_in_and_deduct_fails_and_rolls_back_when_sessions_exhausted(): void
    {
        $plan = $this->createPlan('Tennis Sunday');
        SportSessionTemplate::create([
            'plan_id' => $plan->id,
            'day_of_week' => 0,
            'start_time' => '14:00',
            'end_time' => '15:30',
            'is_active' => true,
        ]);

        // 10 allocated, 10 consumed -> 0 remaining
        $sub = $this->createSubscription($plan, 10, 10);

        $response = $this->postJson('/api/v1/reception/check-in-and-deduct', [
            'member_id'               => $this->member->id,
            'branch_id'               => $this->branch->id,
            'check_in_at'             => '2026-08-16 14:15:00',
            'player_subscription_ids' => [$sub->id],
        ]);

        $response->assertStatus(400);

        // Attendance MUST NOT exist (Clean Atomic Rollback)
        $this->assertDatabaseMissing('attendances', [
            'attendable_id' => $this->member->id,
        ]);
    }

    /**
     * 5. فحص إلزامية إدخال سبب التجاوز عند الحضور خارج وقت الجلسة المجدول
     */
    public function test_reception_check_in_and_deduct_enforces_reason_when_off_schedule(): void
    {
        $plan = $this->createPlan('Boxing Sunday');
        SportSessionTemplate::create([
            'plan_id' => $plan->id,
            'day_of_week' => 0,
            'start_time' => '18:00',
            'end_time' => '19:30',
            'is_active' => true,
        ]);

        $sub = $this->createSubscription($plan, 10, 0);

        // Attempt check-in outside scheduled time (10:00 vs 18:00) without notes -> fails
        $failResponse = $this->postJson('/api/v1/reception/check-in-and-deduct', [
            'member_id'               => $this->member->id,
            'branch_id'               => $this->branch->id,
            'check_in_at'             => '2026-08-16 10:00:00',
            'player_subscription_ids' => [$sub->id],
        ]);

        $failResponse->assertStatus(400);
        $this->assertStringContainsString('هذا ليس موعد فعاليتك المجدول', $failResponse->json('message'));
        $this->assertDatabaseMissing('attendances', ['attendable_id' => $this->member->id]);

        // Attempt again WITH reason -> succeeds
        $successResponse = $this->postJson('/api/v1/reception/check-in-and-deduct', [
            'member_id'               => $this->member->id,
            'branch_id'               => $this->branch->id,
            'check_in_at'             => '2026-08-16 10:00:00',
            'player_subscription_ids' => [$sub->id],
            'notes'                   => 'ظرف طارئ للاعب تم استثناؤه بموافقة المشرف',
        ]);

        $successResponse->assertStatus(200);
        $this->assertDatabaseHas('attendances', ['attendable_id' => $this->member->id]);
    }

    /**
     * 6. فحص منع تكرار تسجيل الحضور إذا كان العضو مسجلاً حضوراً ولم ينصرف
     */
    public function test_reception_check_in_and_deduct_prevents_double_check_in(): void
    {
        $plan = $this->createPlan('Crossfit Sunday');
        SportSessionTemplate::create([
            'plan_id' => $plan->id,
            'day_of_week' => 0,
            'start_time' => '08:00',
            'end_time' => '09:30',
            'is_active' => true,
        ]);

        $sub = $this->createSubscription($plan, 10, 0);

        // First check-in succeeds
        $resp1 = $this->postJson('/api/v1/reception/check-in-and-deduct', [
            'member_id'               => $this->member->id,
            'branch_id'               => $this->branch->id,
            'check_in_at'             => '2026-08-16 08:15:00',
            'player_subscription_ids' => [$sub->id],
        ]);
        $resp1->assertStatus(200);

        // Second check-in immediately fails
        $resp2 = $this->postJson('/api/v1/reception/check-in-and-deduct', [
            'member_id'               => $this->member->id,
            'branch_id'               => $this->branch->id,
            'check_in_at'             => '2026-08-16 08:30:00',
            'player_subscription_ids' => [$sub->id],
        ]);
        $resp2->assertStatus(400);
        $this->assertStringContainsString('already checked in', $resp2->json('message'));
    }

    /**
     * 7. التأكيد على بقاء الـ APIs السابقة تعمل بكفاءة تامة دون أي تغيير أو كسر للتوافقية
     */
    public function test_existing_checkin_and_deduct_apis_still_work_unaffected(): void
    {
        $plan = $this->createPlan('Swimming Sunday 2');
        SportSessionTemplate::create([
            'plan_id' => $plan->id,
            'day_of_week' => 0,
            'start_time' => '11:00',
            'end_time' => '12:30',
            'is_active' => true,
        ]);

        $sub = $this->createSubscription($plan, 10, 0);

        // 1. Old Check-in without subscriptions
        $checkInResponse = $this->postJson('/api/v1/attendances/check-in', [
            'attendable_type' => 'member',
            'attendable_id'   => $this->member->id,
            'branch_id'       => $this->branch->id,
            'check_in_at'     => '2026-08-16 11:15:00',
        ]);
        $checkInResponse->assertStatus(200);
        $attendanceId = $checkInResponse->json('data.id');

        $this->assertDatabaseHas('attendances', [
            'id'     => $attendanceId,
            'status' => 'checked_in',
        ]);
        $this->assertDatabaseMissing('attendance_consumptions', [
            'attendance_id' => $attendanceId,
        ]);

        // 2. Old Reception deduct endpoint
        $deductResponse = $this->postJson("/api/v1/reception/attendances/{$attendanceId}/deduct", [
            'player_subscription_ids' => [$sub->id],
        ]);
        $deductResponse->assertStatus(200);

        $this->assertDatabaseHas('attendance_consumptions', [
            'attendance_id'          => $attendanceId,
            'player_subscription_id' => $sub->id,
        ]);
        $this->assertDatabaseHas('player_subscription_items', [
            'player_subscription_id' => $sub->id,
            'sessions_consumed'      => 1,
        ]);
    }
}
