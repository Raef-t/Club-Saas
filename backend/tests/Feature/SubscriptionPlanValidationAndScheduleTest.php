<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Modules\ClubManager\Models\Branch;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Facility;
use Modules\Sports\Models\Activity;
use Modules\Sports\Models\ActivityType;
use Modules\Sports\Models\SportSessionTemplate;
use Modules\Sports\Models\StaffActivity;
use Modules\StaffManager\Models\Staff;
use Modules\SubscriptionManager\Enums\SubscriptionPlanStatus;
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Modules\SubscriptionManager\Models\SubscriptionPlanActivity;
use Modules\SubscriptionManager\Models\SubscriptionPlanSuspension;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubscriptionPlanValidationAndScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $femaleBranch;
    protected $maleBranch;
    protected $mixedBranch;
    protected $activity;
    protected $coach;
    protected $staffActivity;
    protected $facility;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $adminPerson = Person::create([
            'full_name' => 'Super Admin',
            'type'      => 'staff',
        ]);

        $this->admin = User::create([
            'username'  => 'admin_' . uniqid(),
            'password'  => bcrypt('secret123'),
            'person_id' => $adminPerson->id,
            'is_active' => true,
        ]);

        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'sanctum']);
        $this->admin->assignRole($superAdminRole);
        $this->actingAs($this->admin, 'sanctum');

        $club = Club::create(['name' => 'Main Club', 'is_active' => true]);

        $this->femaleBranch = Branch::create([
            'club_id'            => $club->id,
            'name'               => 'تكنوجيم إناث',
            'gender_restriction' => 'female',
            'is_active'          => true,
        ]);

        $this->maleBranch = Branch::create([
            'club_id'            => $club->id,
            'name'               => 'تكنوجيم ذكور',
            'gender_restriction' => 'male',
            'is_active'          => true,
        ]);

        $this->mixedBranch = Branch::create([
            'club_id'            => $club->id,
            'name'               => 'الفرع المختلط',
            'gender_restriction' => 'mixed',
            'is_active'          => true,
        ]);

        $type = ActivityType::create([
            'name'                      => 'حصص تدريبية',
            'is_active'                 => true,
            'is_session_based'          => true,
            'has_unlimited_subscribers' => false,
        ]);

        $this->activity = Activity::create([
            'branch_id'        => $this->femaleBranch->id,
            'activity_type_id' => $type->id,
            'name'             => 'كيك بوكسينغ',
            'is_active'        => true,
        ]);

        $coachPerson = Person::create([
            'full_name' => 'Coach Sara',
            'type'      => 'coach',
            'gender'    => 'female',
        ]);

        $this->coach = Staff::create([
            'person_id'   => $coachPerson->id,
            'role'        => 'coach',
            'work_status' => 'active',
            'is_active'   => true,
        ]);
        $this->coach->branches()->attach($this->femaleBranch->id);

        $this->staffActivity = StaffActivity::create([
            'staff_id'    => $this->coach->id,
            'activity_id' => $this->activity->id,
        ]);

        $this->facility = Facility::create([
            'branch_id'          => $this->femaleBranch->id,
            'name'               => 'قاعة الكيك بوكسينغ',
            'capacity'           => 20,
            'gender_restriction' => 'female',
        ]);
    }

    public function test_creating_plan_with_inconsistent_sessions_fails_validation(): void
    {
        $response = $this->postJson('/api/v1/subscription-plans', [
            'branch_id'          => $this->femaleBranch->id,
            'name'               => 'خطة غير متوافقة الجلسات',
            'base_price'         => 100,
            'sessions_per_week'  => 2,
            'session_count'      => 10,
            'gender_restriction' => 'female',
            'activities'         => [
                ['activity_id' => $this->activity->id, 'coach_id' => $this->coach->id],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['session_count']);
        $this->assertStringContainsString('عدد الجلسات الإجمالي يجب أن يساوي عدد الجلسات في الأسبوع مضروباً في 4', $response->json('errors.session_count.0'));
    }

    public function test_creating_plan_with_consistent_sessions_succeeds(): void
    {
        $response = $this->postJson('/api/v1/subscription-plans', [
            'branch_id'          => $this->femaleBranch->id,
            'name'               => 'خطة متوافقة الجلسات',
            'base_price'         => 100,
            'sessions_per_week'  => 2,
            'session_count'      => 8,
            'gender_restriction' => 'female',
            'activities'         => [
                ['activity_id' => $this->activity->id, 'coach_id' => $this->coach->id],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertEquals(2, $response->json('data.sessions_per_week'));
        $this->assertEquals(8, $response->json('data.session_count'));
    }

    public function test_creating_plan_with_sessions_per_week_auto_calculates_session_count(): void
    {
        $response = $this->postJson('/api/v1/subscription-plans', [
            'branch_id'          => $this->femaleBranch->id,
            'name'               => 'خطة باحتساب تلقائي للجلسات',
            'base_price'         => 150,
            'sessions_per_week'  => 3,
            'gender_restriction' => 'female',
            'activities'         => [
                ['activity_id' => $this->activity->id, 'coach_id' => $this->coach->id],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertEquals(3, $response->json('data.sessions_per_week'));
        $this->assertEquals(12, $response->json('data.session_count'));
    }

    public function test_creating_plan_with_templates_count_mismatching_sessions_per_week_fails(): void
    {
        $response = $this->postJson('/api/v1/subscription-plans', [
            'branch_id'          => $this->femaleBranch->id,
            'name'               => 'خطة بتعارض عدد القوالب',
            'base_price'         => 100,
            'sessions_per_week'  => 2,
            'session_count'      => 8,
            'gender_restriction' => 'female',
            'activities'         => [
                ['activity_id' => $this->activity->id, 'coach_id' => $this->coach->id],
            ],
            'session_templates'  => [
                ['facility_id' => $this->facility->id, 'day_of_week' => 0, 'start_time' => '10:00', 'end_time' => '11:00'],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['session_templates']);
    }

    public function test_female_branch_rejects_male_gender_restriction(): void
    {
        $response = $this->postJson('/api/v1/subscription-plans', [
            'branch_id'          => $this->femaleBranch->id,
            'name'               => 'خطة ذكور في نادي إناث',
            'base_price'         => 100,
            'sessions_per_week'  => 2,
            'session_count'      => 8,
            'gender_restriction' => 'male',
            'activities'         => [
                ['activity_id' => $this->activity->id, 'coach_id' => $this->coach->id],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['gender_restriction']);
        $this->assertStringContainsString('الفرع مخصص للإناث فقط', $response->json('errors.gender_restriction.0'));
    }

    public function test_female_branch_rejects_mixed_gender_restriction(): void
    {
        $response = $this->postJson('/api/v1/subscription-plans', [
            'branch_id'          => $this->femaleBranch->id,
            'name'               => 'خطة مختلط في نادي إناث',
            'base_price'         => 100,
            'sessions_per_week'  => 2,
            'session_count'      => 8,
            'gender_restriction' => 'mixed',
            'activities'         => [
                ['activity_id' => $this->activity->id, 'coach_id' => $this->coach->id],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['gender_restriction']);
    }

    public function test_male_branch_rejects_female_gender_restriction(): void
    {
        $response = $this->postJson('/api/v1/subscription-plans', [
            'branch_id'          => $this->maleBranch->id,
            'name'               => 'خطة إناث في نادي ذكور',
            'base_price'         => 100,
            'sessions_per_week'  => 2,
            'session_count'      => 8,
            'gender_restriction' => 'female',
            'activities'         => [
                ['activity_id' => $this->activity->id, 'coach_id' => $this->coach->id],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['gender_restriction']);
        $this->assertStringContainsString('الفرع مخصص للذكور فقط', $response->json('errors.gender_restriction.0'));
    }

    public function test_schedule_hides_sessions_when_plan_is_suspended(): void
    {
        $plan = SubscriptionPlan::create([
            'branch_id'          => $this->femaleBranch->id,
            'name'               => 'خطة كيك بوكسينغ نشطة',
            'base_price'         => 100,
            'sessions_per_week'  => 1,
            'session_count'      => 4,
            'gender_restriction' => 'female',
            'status'             => SubscriptionPlanStatus::ACTIVE->value,
        ]);

        SubscriptionPlanActivity::create([
            'plan_id'           => $plan->id,
            'staff_activity_id' => $this->staffActivity->id,
        ]);

        $template = SportSessionTemplate::create([
            'plan_id'     => $plan->id,
            'facility_id' => $this->facility->id,
            'day_of_week' => 1, // Monday
            'start_time'  => '10:00',
            'end_time'    => '11:00',
            'is_active'   => true,
        ]);

        // Verify it appears in schedule
        $scheduleResponse = $this->getJson('/api/v1/session-templates/schedule?branch_id=' . $this->femaleBranch->id);
        $scheduleResponse->assertStatus(200);
        $mondaySessions = $scheduleResponse->json('data.Monday') ?? [];
        $this->assertNotEmpty($mondaySessions);
        $this->assertEquals($template->id, $mondaySessions[0]['id']);

        // Suspend the plan
        SubscriptionPlanSuspension::create([
            'plan_id'            => $plan->id,
            'coach_id'           => $this->coach->id,
            'suspend_start_date' => Carbon::today()->toDateString(),
            'suspend_end_date'   => Carbon::today()->addDays(7)->toDateString(),
            'suspension_days'    => 7,
            'reason'             => 'إجازة الكوتش',
            'status'             => 'active',
            'created_by'         => $this->admin->id,
        ]);

        // Verify it is now hidden from schedule
        $scheduleResponseAfter = $this->getJson('/api/v1/session-templates/schedule?branch_id=' . $this->femaleBranch->id);
        $scheduleResponseAfter->assertStatus(200);
        $mondaySessionsAfter = $scheduleResponseAfter->json('data.Monday') ?? [];
        $this->assertEmpty($mondaySessionsAfter);
    }

    public function test_schedule_hides_sessions_when_plan_is_inactive(): void
    {
        $plan = SubscriptionPlan::create([
            'branch_id'          => $this->femaleBranch->id,
            'name'               => 'خطة غير نشطة',
            'base_price'         => 100,
            'sessions_per_week'  => 1,
            'session_count'      => 4,
            'gender_restriction' => 'female',
            'status'             => SubscriptionPlanStatus::INACTIVE->value,
        ]);

        SubscriptionPlanActivity::create([
            'plan_id'           => $plan->id,
            'staff_activity_id' => $this->staffActivity->id,
        ]);

        SportSessionTemplate::create([
            'plan_id'     => $plan->id,
            'facility_id' => $this->facility->id,
            'day_of_week' => 2, // Tuesday
            'start_time'  => '14:00',
            'end_time'    => '15:00',
            'is_active'   => true,
        ]);

        $scheduleResponse = $this->getJson('/api/v1/session-templates/schedule?branch_id=' . $this->femaleBranch->id);
        $scheduleResponse->assertStatus(200);
        $tuesdaySessions = $scheduleResponse->json('data.Tuesday') ?? [];
        $this->assertEmpty($tuesdaySessions);
    }

    public function test_schedule_hides_sessions_when_activity_is_inactive(): void
    {
        $plan = SubscriptionPlan::create([
            'branch_id'          => $this->femaleBranch->id,
            'name'               => 'خطة بنشاط ملغى التفعيل',
            'base_price'         => 100,
            'sessions_per_week'  => 1,
            'session_count'      => 4,
            'gender_restriction' => 'female',
            'status'             => SubscriptionPlanStatus::ACTIVE->value,
        ]);

        SubscriptionPlanActivity::create([
            'plan_id'           => $plan->id,
            'staff_activity_id' => $this->staffActivity->id,
        ]);

        SportSessionTemplate::create([
            'plan_id'     => $plan->id,
            'facility_id' => $this->facility->id,
            'day_of_week' => 3, // Wednesday
            'start_time'  => '16:00',
            'end_time'    => '17:00',
            'is_active'   => true,
        ]);

        // Deactivate the activity
        $this->activity->update(['is_active' => false]);

        $scheduleResponse = $this->getJson('/api/v1/session-templates/schedule?branch_id=' . $this->femaleBranch->id);
        $scheduleResponse->assertStatus(200);
        $wednesdaySessions = $scheduleResponse->json('data.Wednesday') ?? [];
        $this->assertEmpty($wednesdaySessions);
    }
}
