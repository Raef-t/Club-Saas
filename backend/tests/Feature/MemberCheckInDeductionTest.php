<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\AttendanceManager\Models\Attendance;
use Modules\AttendanceManager\Models\GateDevice;
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

class MemberCheckInDeductionTest extends TestCase
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
            'full_name' => 'Receptionist User',
            'gender' => 'female',
            'type' => 'staff',
        ]);

        $this->user = User::create([
            'person_id' => $person->id,
            'username' => 'reception_' . uniqid(),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $role = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'sanctum',
        ]);
        $this->user->assignRole($role);
        Sanctum::actingAs($this->user, ['*']);

        $club = Club::create(['name' => 'Fitness Club']);
        $this->branch = Branch::create(['club_id' => $club->id, 'name' => 'Downtown Branch']);

        $coachPerson = Person::create([
            'full_name' => 'Coach Maya',
            'gender' => 'female',
            'type' => 'coach',
        ]);

        $this->coach = Staff::create([
            'person_id' => $coachPerson->id,
            'role' => 'coach',
        ]);

        $memberPerson = Person::create([
            'full_name' => 'Subscriber Laila',
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
            'name' => 'Aerobics',
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

    private function createSubscription(SubscriptionPlan $plan, int $allocated = 12, int $consumed = 0, bool $unlimited = false): PlayerSubscription
    {
        $sub = PlayerSubscription::create([
            'member_id' => $this->member->id,
            'plan_id' => $plan->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
            'total_amount' => 150,
            'paid_amount' => 150,
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
     * التحقق من أن تسجيل الدخول بدون تمرير معرف الاشتراك يخصم تلقائياً من جلسات المشترك
     */
    public function test_member_checkin_without_subscription_ids_automatically_deducts_session(): void
    {
        // 2026-08-16 is a Sunday (day 0)
        $plan = $this->createPlan('Yoga Sunday');
        SportSessionTemplate::create([
            'plan_id' => $plan->id,
            'day_of_week' => 0,
            'start_time' => '10:00',
            'end_time' => '11:30',
            'is_active' => true,
        ]);

        $sub = $this->createSubscription($plan, 10, 0);

        $response = $this->postJson('/api/v1/attendances/check-in', [
            'attendable_type' => 'member',
            'attendable_id'   => $this->member->id,
            'branch_id'       => $this->branch->id,
            'check_in_at'     => '2026-08-16 10:15:00',
        ]);

        $response->assertStatus(200);

        // Attendance recorded
        $this->assertDatabaseHas('attendances', [
            'attendable_id' => $this->member->id,
            'status'        => 'checked_in',
        ]);

        // Session consumed and recorded in attendance_consumptions
        $this->assertDatabaseHas('attendance_consumptions', [
            'player_subscription_id' => $sub->id,
            'subscription_plan_id'   => $plan->id,
        ]);

        // Item sessions_consumed incremented
        $this->assertDatabaseHas('player_subscription_items', [
            'player_subscription_id' => $sub->id,
            'sessions_consumed'      => 1,
        ]);
    }

    /**
     * التحقق من أن تسجيل الدخول يفشل بالكامل ولا يسجل حضور إذا لم تكن هناك جلسات متبقية
     */
    public function test_member_checkin_fails_and_records_no_attendance_when_sessions_exhausted(): void
    {
        $plan = $this->createPlan('Pilates Sunday');
        SportSessionTemplate::create([
            'plan_id' => $plan->id,
            'day_of_week' => 0,
            'start_time' => '12:00',
            'end_time' => '13:00',
            'is_active' => true,
        ]);

        // 10 allocated, 10 consumed (0 remaining)
        $sub = $this->createSubscription($plan, 10, 10);

        $response = $this->postJson('/api/v1/attendances/check-in', [
            'attendable_type' => 'member',
            'attendable_id'   => $this->member->id,
            'branch_id'       => $this->branch->id,
            'check_in_at'     => '2026-08-16 12:15:00',
        ]);

        $response->assertStatus(400);

        // Attendance must NOT exist
        $this->assertDatabaseMissing('attendances', [
            'attendable_id' => $this->member->id,
        ]);
    }

    /**
     * التحقق من الدخول عبر البوابة وتلقائية خصم الجلسة
     */
    public function test_gate_scan_automatically_deducts_session_for_member(): void
    {
        $rawToken = 'gate_test_secret_token_12345';
        $hashedToken = hash('sha256', $rawToken);

        $gate = GateDevice::create([
            'club_id'     => $this->branch->club_id,
            'branch_id'   => $this->branch->id,
            'name'        => 'Main Gate Turnstile',
            'api_token'   => $hashedToken,
            'is_active'   => true,
        ]);

        $openPlan = $this->createPlan('Open Gym All Day', null);
        $sub = $this->createSubscription($openPlan, 20, 0);

        $qrService = app(\Modules\Authentication\Services\PersonQrCodeService::class);
        $qrPayload = $qrService->getTodayCodeForPerson($this->member->person_id);

        $response = $this->withToken($rawToken)->postJson('/api/v1/gates/scan', [
            'qr_code' => $qrPayload,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.action', 'unlock_door');

        // Check attendance & consumption
        $this->assertDatabaseHas('attendances', [
            'attendable_id' => $this->member->id,
            'status'        => 'checked_in',
        ]);

        $this->assertDatabaseHas('attendance_consumptions', [
            'player_subscription_id' => $sub->id,
        ]);

        $this->assertDatabaseHas('player_subscription_items', [
            'player_subscription_id' => $sub->id,
            'sessions_consumed'      => 1,
        ]);
    }

    /**
     * التحقق من أن تسجيل الدخول عبر مسح QR للمشترك يخصم الجلسة تلقائياً
     */
    public function test_qr_checkin_automatically_deducts_session_for_member(): void
    {
        // 1. Set up staff scanning user and branch
        $staffPerson = Person::create([
            'full_name' => 'Staff Scanner',
            'gender' => 'female',
            'type' => 'staff',
        ]);
        $staff = Staff::create([
            'person_id' => $staffPerson->id,
            'role' => 'receptionist',
        ]);
        \Illuminate\Support\Facades\DB::table('staff_branches')->insert([
            'staff_id' => $staff->id,
            'branch_id' => $this->branch->id,
        ]);

        $staffUser = User::create([
            'person_id' => $staffPerson->id,
            'username' => 'staff_scanner_' . uniqid(),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $role = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'sanctum',
        ]);
        $staffUser->assignRole($role);
        Sanctum::actingAs($staffUser, ['*']);

        // 2. Setup subscription
        $openPlan = $this->createPlan('QR Open Gym', null);
        $sub = $this->createSubscription($openPlan, 15, 0);

        // 3. Get today's QR code for member's person
        $qrService = app(\Modules\Authentication\Services\PersonQrCodeService::class);
        $qrCode = $qrService->getTodayCodeForPerson($this->member->person_id);

        $response = $this->postJson('/api/v1/qr/check-in', [
            'qr_code' => $qrCode,
        ]);

        $response->assertStatus(200);

        // Check attendance & consumption
        $this->assertDatabaseHas('attendances', [
            'attendable_id' => $this->member->id,
            'status'        => 'checked_in',
        ]);

        $this->assertDatabaseHas('attendance_consumptions', [
            'player_subscription_id' => $sub->id,
        ]);

        $this->assertDatabaseHas('player_subscription_items', [
            'player_subscription_id' => $sub->id,
            'sessions_consumed'      => 1,
        ]);
    }
}
