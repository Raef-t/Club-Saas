<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\AttendanceManager\Models\Attendance;
use Modules\AttendanceManager\Models\AttendanceConsumption;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Modules\ClubManager\Models\Branch;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Locker;
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

class SameDayMultiActivityAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $branch;
    protected $member;
    protected $coach;
    protected $activityType;
    protected $locker;

    protected function setUp(): void
    {
        parent::setUp();

        $person = Person::create([
            'full_name' => 'Reception Staff',
            'gender' => 'female',
            'type' => 'staff',
        ]);

        $this->user = User::create([
            'person_id' => $person->id,
            'username' => 'reception_staff_' . uniqid(),
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
        $this->branch = Branch::create(['club_id' => $club->id, 'name' => 'Central Branch']);

        $coachPerson = Person::create([
            'full_name' => 'Coach Mary',
            'gender' => 'female',
            'type' => 'coach',
        ]);

        $this->coach = Staff::create([
            'person_id' => $coachPerson->id,
            'role' => 'coach',
        ]);

        $memberPerson = Person::create([
            'full_name' => 'Player Layla',
            'gender' => 'female',
            'type' => 'player',
        ]);

        $this->member = Member::create([
            'branch_id' => $this->branch->id,
            'person_id' => $memberPerson->id,
            'member_number' => 'M-' . uniqid(),
            'join_date' => '2026-01-01',
            'status' => 'active',
            'membership_status' => 'active',
        ]);

        $this->activityType = ActivityType::create([
            'name' => 'Fitness',
            'is_active' => true,
        ]);

        $this->locker = Locker::create([
            'branch_id' => $this->branch->id,
            'locker_number' => 'LK-101',
            'key_number' => 'K-101',
            'status' => 'available',
        ]);
    }

    private function createPlan(string $name, ?int $sessionCount = 10): SubscriptionPlan
    {
        $activity = Activity::create([
            'branch_id' => $this->branch->id,
            'activity_type_id' => $this->activityType->id,
            'name' => $name . ' Activity',
            'is_active' => true,
        ]);

        $staffActivity = StaffActivity::create([
            'staff_id' => $this->coach->id,
            'activity_id' => $activity->id,
        ]);

        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => $name,
            'period_type' => 'monthly',
            'period_count' => 1,
            'base_price' => 200,
            'price' => 200,
            'status' => SubscriptionPlanStatus::ACTIVE,
            'is_active' => true,
            'session_count' => $sessionCount,
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $plan->id,
            'staff_activity_id' => $staffActivity->id,
        ]);

        return $plan;
    }

    private function createSubscription(SubscriptionPlan $plan, int $allocated = 10, int $consumed = 0): PlayerSubscription
    {
        $sub = PlayerSubscription::create([
            'member_id' => $this->member->id,
            'plan_id' => $plan->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-09-30',
            'status' => PlayerSubscriptionStatus::ACTIVE,
            'total_amount' => 200,
            'paid_amount' => 200,
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
     * اختبار: تسجيل حضور للفعالية الثانية في نفس اليوم دون إنشاء سجل حضور جديد والاحتفاظ بنفس الحضور والخزانة
     */
    public function test_member_can_check_in_to_second_activity_on_same_day_without_new_attendance_record(): void
    {
        // 2026-08-16 is a Sunday (dayOfWeek = 0)
        Carbon::setTestNow('2026-08-16 10:00:00');

        $aerobicsPlan = $this->createPlan('Aerobics Sunday');
        SportSessionTemplate::create([
            'plan_id' => $aerobicsPlan->id,
            'day_of_week' => 0,
            'start_time' => '09:00',
            'end_time' => '11:00',
            'is_active' => true,
        ]);

        $gymPlan = $this->createPlan('General Gym Sunday');
        SportSessionTemplate::create([
            'plan_id' => $gymPlan->id,
            'day_of_week' => 0,
            'start_time' => '10:00',
            'end_time' => '13:00',
            'is_active' => true,
        ]);

        $subAerobics = $this->createSubscription($aerobicsPlan, 10, 0);
        $subGym = $this->createSubscription($gymPlan, 12, 0);

        // 1. First Check-in: Aerobics with Locker
        $resp1 = $this->postJson('/api/v1/reception/check-in-and-deduct', [
            'member_id'               => $this->member->id,
            'branch_id'               => $this->branch->id,
            'check_in_at'             => '2026-08-16 10:00:00',
            'player_subscription_ids' => [$subAerobics->id],
            'locker_id'               => $this->locker->id,
            'notes'                   => 'Aerobics session check-in',
        ]);

        $resp1->assertStatus(200);
        $attendanceId = $resp1->json('data.id');
        $this->assertNotNull($attendanceId);
        $this->assertEquals($this->locker->id, $resp1->json('data.locker.id'));

        // Verify Attendance DB state
        $this->assertEquals(1, Attendance::where('attendable_id', $this->member->id)->count());
        $this->assertEquals(1, AttendanceConsumption::where('attendance_id', $attendanceId)->count());
        $this->assertEquals(1, PlayerSubscriptionItem::where('player_subscription_id', $subAerobics->id)->value('sessions_consumed'));

        // 2. Second Check-in: Gym Machines during the same visit
        Carbon::setTestNow('2026-08-16 11:15:00');

        $resp2 = $this->postJson('/api/v1/reception/check-in-and-deduct', [
            'member_id'               => $this->member->id,
            'branch_id'               => $this->branch->id,
            'check_in_at'             => '2026-08-16 11:15:00',
            'player_subscription_ids' => [$subGym->id],
            'notes'                   => 'Gym machines session check-in',
        ]);

        $resp2->assertStatus(200);

        // Verification: SAME attendance record ID is returned, NO new attendance created
        $this->assertEquals($attendanceId, $resp2->json('data.id'));
        $this->assertEquals(1, Attendance::where('attendable_id', $this->member->id)->count());

        // Verification: Both consumptions are attached to this attendance record
        $this->assertEquals(2, AttendanceConsumption::where('attendance_id', $attendanceId)->count());

        // Verification: 1 session deducted from Gym plan as well
        $this->assertEquals(1, PlayerSubscriptionItem::where('player_subscription_id', $subGym->id)->value('sessions_consumed'));

        // Verification: Locker is retained from the first check-in
        $this->assertEquals($this->locker->id, $resp2->json('data.locker.id'));
    }

    /**
     * اختبار: استعلام اشتراكات الاستقبال يستبعد الفعالية التي تم حضورها بالفعل ويعرض الفعالية المتبقية فقط مع تفاصيل الخزانة
     */
    public function test_reception_subscriptions_endpoint_filters_already_consumed_activities_in_current_visit(): void
    {
        Carbon::setTestNow('2026-08-16 10:00:00');

        $aerobicsPlan = $this->createPlan('Aerobics Sunday');
        SportSessionTemplate::create([
            'plan_id' => $aerobicsPlan->id,
            'day_of_week' => 0,
            'start_time' => '09:00',
            'end_time' => '11:00',
            'is_active' => true,
        ]);

        $gymPlan = $this->createPlan('General Gym Sunday');
        SportSessionTemplate::create([
            'plan_id' => $gymPlan->id,
            'day_of_week' => 0,
            'start_time' => '10:00',
            'end_time' => '13:00',
            'is_active' => true,
        ]);

        $subAerobics = $this->createSubscription($aerobicsPlan, 10, 0);
        $subGym = $this->createSubscription($gymPlan, 12, 0);

        // Before check-in: both subscriptions appear
        $resBefore = $this->getJson("/api/v1/reception/members/{$this->member->id}/subscriptions?date=2026-08-16");
        $resBefore->assertStatus(200);
        $this->assertCount(2, $resBefore->json('data'));
        $this->assertFalse($resBefore->json('meta.is_currently_checked_in'));
        $this->assertTrue($resBefore->json('meta.show_locker_selection'));

        // Perform Check-in for Aerobics + Locker
        $this->postJson('/api/v1/reception/check-in-and-deduct', [
            'member_id'               => $this->member->id,
            'branch_id'               => $this->branch->id,
            'check_in_at'             => '2026-08-16 10:00:00',
            'player_subscription_ids' => [$subAerobics->id],
            'locker_id'               => $this->locker->id,
        ])->assertStatus(200);

        // After Aerobics check-in: ONLY Gym subscription appears!
        $resAfter = $this->getJson("/api/v1/reception/members/{$this->member->id}/subscriptions?date=2026-08-16");
        $resAfter->assertStatus(200);

        $data = $resAfter->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($subGym->id, $data[0]['player_subscription_id']);

        // Verification of Locker Suppression:
        $this->assertTrue($resAfter->json('meta.is_currently_checked_in'));
        $this->assertTrue($resAfter->json('meta.has_current_locker'));
        $this->assertFalse($resAfter->json('meta.show_locker_selection'));
        $this->assertEquals($this->locker->id, $resAfter->json('meta.current_locker.id'));
        $this->assertEquals('LK-101', $resAfter->json('meta.current_locker.locker_number'));

        $this->assertTrue($data[0]['has_locker_in_current_attendance']);
        $this->assertFalse($data[0]['show_locker_selection']);
        $this->assertEquals($this->locker->id, $data[0]['current_locker']['id']);
    }

    /**
     * اختبار: عند استهلاك كافة فعاليات اليوم يرجع الاستعلام قائمة فارغة مع رسالة واضحة ومؤشر all_today_activities_attended
     */
    public function test_reception_subscriptions_endpoint_returns_informative_message_when_all_today_activities_consumed(): void
    {
        Carbon::setTestNow('2026-08-16 10:00:00');

        $aerobicsPlan = $this->createPlan('Aerobics Sunday');
        SportSessionTemplate::create([
            'plan_id' => $aerobicsPlan->id,
            'day_of_week' => 0,
            'start_time' => '09:00',
            'end_time' => '11:00',
            'is_active' => true,
        ]);

        $subAerobics = $this->createSubscription($aerobicsPlan, 10, 0);

        // Check in
        $this->postJson('/api/v1/reception/check-in-and-deduct', [
            'member_id'               => $this->member->id,
            'branch_id'               => $this->branch->id,
            'check_in_at'             => '2026-08-16 10:00:00',
            'player_subscription_ids' => [$subAerobics->id],
        ])->assertStatus(200);

        // Query subscriptions now:
        $res = $this->getJson("/api/v1/reception/members/{$this->member->id}/subscriptions?date=2026-08-16");
        $res->assertStatus(200);
        $this->assertEmpty($res->json('data'));
        $this->assertTrue($res->json('meta.all_today_activities_attended'));
        $this->assertTrue($res->json('meta.is_currently_checked_in'));
        $this->assertStringContainsString('لا يملك فعاليات أخرى لتسجيله عليها', $res->json('message'));
    }

    /**
     * اختبار: فلترة قائمة الأعضاء عبر available_for_attendance تخفي اللاعب بعد استهلاك جميع فعالياته لليوم
     */
    public function test_members_list_with_available_for_attendance_filter_hides_member_when_all_today_activities_consumed(): void
    {
        Carbon::setTestNow('2026-08-16 10:00:00');

        $aerobicsPlan = $this->createPlan('Aerobics Sunday');
        SportSessionTemplate::create([
            'plan_id' => $aerobicsPlan->id,
            'day_of_week' => 0,
            'start_time' => '09:00',
            'end_time' => '11:00',
            'is_active' => true,
        ]);

        $gymPlan = $this->createPlan('General Gym Sunday');
        SportSessionTemplate::create([
            'plan_id' => $gymPlan->id,
            'day_of_week' => 0,
            'start_time' => '10:00',
            'end_time' => '13:00',
            'is_active' => true,
        ]);

        $subAerobics = $this->createSubscription($aerobicsPlan, 10, 0);
        $subGym = $this->createSubscription($gymPlan, 12, 0);

        // 1. Before any check-in: member is in available list
        $res1 = $this->getJson('/api/v1/members?available_for_attendance=true');
        $res1->assertStatus(200);
        $memberIds = collect($res1->json('data'))->pluck('id')->all();
        $this->assertContains($this->member->id, $memberIds);

        // 2. Check in for Aerobics only: Gym still remaining -> member STILL appears in available list!
        $this->postJson('/api/v1/reception/check-in-and-deduct', [
            'member_id'               => $this->member->id,
            'branch_id'               => $this->branch->id,
            'check_in_at'             => '2026-08-16 10:00:00',
            'player_subscription_ids' => [$subAerobics->id],
        ])->assertStatus(200);

        $res2 = $this->getJson('/api/v1/members?available_for_attendance=true');
        $res2->assertStatus(200);
        $memberIds2 = collect($res2->json('data'))->pluck('id')->all();
        $this->assertContains($this->member->id, $memberIds2);

        // 3. Check in for Gym as well: all today's activities consumed -> member DISAPPEARS from available list!
        $this->postJson('/api/v1/reception/check-in-and-deduct', [
            'member_id'               => $this->member->id,
            'branch_id'               => $this->branch->id,
            'check_in_at'             => '2026-08-16 11:30:00',
            'player_subscription_ids' => [$subGym->id],
        ])->assertStatus(200);

        $res3 = $this->getJson('/api/v1/members?available_for_attendance=true');
        $res3->assertStatus(200);
        $memberIds3 = collect($res3->json('data'))->pluck('id')->all();
        $this->assertNotContains($this->member->id, $memberIds3);
    }

    /**
     * اختبار: منع تكرار خصم نفس الاشتراك مرتين في نفس الحضور
     */
    public function test_cannot_check_in_again_for_the_same_subscription_in_same_visit(): void
    {
        Carbon::setTestNow('2026-08-16 10:00:00');

        $aerobicsPlan = $this->createPlan('Aerobics Sunday');
        SportSessionTemplate::create([
            'plan_id' => $aerobicsPlan->id,
            'day_of_week' => 0,
            'start_time' => '09:00',
            'end_time' => '11:00',
            'is_active' => true,
        ]);

        $subAerobics = $this->createSubscription($aerobicsPlan, 10, 0);

        // First check-in succeeds
        $this->postJson('/api/v1/reception/check-in-and-deduct', [
            'member_id'               => $this->member->id,
            'branch_id'               => $this->branch->id,
            'check_in_at'             => '2026-08-16 10:00:00',
            'player_subscription_ids' => [$subAerobics->id],
        ])->assertStatus(200);

        // Attempting to check in again for the same Aerobics subscription fails
        $failResp = $this->postJson('/api/v1/reception/check-in-and-deduct', [
            'member_id'               => $this->member->id,
            'branch_id'               => $this->branch->id,
            'check_in_at'             => '2026-08-16 10:30:00',
            'player_subscription_ids' => [$subAerobics->id],
        ]);

        $failResp->assertStatus(400);
        $this->assertStringContainsString('already checked in', $failResp->json('message'));
    }
}
