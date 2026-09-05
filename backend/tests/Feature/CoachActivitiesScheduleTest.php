<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Modules\ClubManager\Models\Branch;
use Modules\ClubManager\Models\BranchShift;
use Modules\ClubManager\Models\Club;
use Modules\Sports\Models\Activity;
use Modules\Sports\Models\ActivityType;
use Modules\Sports\Models\SportSessionTemplate;
use Modules\Sports\Models\StaffActivity;
use Modules\StaffManager\Models\CoachDetail;
use Modules\StaffManager\Models\Staff;
use Modules\StaffManager\Models\StaffShift;
use Modules\SubscriptionManager\Enums\SubscriptionPlanStatus;
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Modules\SubscriptionManager\Models\SubscriptionPlanActivity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CoachActivitiesScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $branch;
    protected $coachStaff;
    protected $generalActivity;
    protected $privateActivity;
    protected $sessionActivity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $person = Person::create([
            'full_name'  => 'Admin User',
            'type'       => 'staff',
        ]);

        $this->admin = User::create([
            'username'  => 'admin_' . uniqid(),
            'password'  => bcrypt('secret123'),
            'person_id' => $person->id,
            'is_active' => true,
        ]);

        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'sanctum']);
        $this->admin->assignRole($superAdminRole);

        $club = Club::create(['name' => 'Main Club', 'is_active' => true]);
        $this->branch = Branch::create(['club_id' => $club->id, 'name' => 'Main Branch', 'is_active' => true]);

        // Coach Person and Staff
        $coachPerson = Person::create([
            'full_name' => 'Captain Ahmed',
            'type'      => 'coach',
            'gender'    => 'male',
        ]);

        $this->coachStaff = Staff::create([
            'person_id'   => $coachPerson->id,
            'role'        => 'coach',
            'work_status' => 'active',
        ]);

        $this->coachStaff->branches()->attach($this->branch->id);

        CoachDetail::create([
            'staff_id'         => $this->coachStaff->id,
            'experience_years' => 5,
        ]);

        // 1. General training activity + branch shift
        $generalType = ActivityType::create([
            'name'                     => 'تدريب عام',
            'is_active'                => true,
            'is_session_based'         => false,
            'has_unlimited_subscribers'=> true,
            'has_shifts'               => true,
        ]);

        $this->generalActivity = Activity::create([
            'branch_id'        => $this->branch->id,
            'activity_type_id' => $generalType->id,
            'name'             => 'أجهزة وتدريب عام',
            'is_active'        => true,
        ]);

        $branchShift = BranchShift::create([
            'branch_id'      => $this->branch->id,
            'name'           => 'الشفت الصباحي',
            'start_time'     => '08:00',
            'end_time'       => '16:00',
            'gender_allowed' => 'male',
        ]);

        StaffShift::create([
            'staff_id'        => $this->coachStaff->id,
            'branch_shift_id' => $branchShift->id,
        ]);

        // 2. Private training activity
        $privateType = ActivityType::create([
            'name'                     => 'تدريب خاص',
            'is_active'                => true,
            'is_session_based'         => false,
            'has_unlimited_subscribers'=> true,
            'has_shifts'               => false,
        ]);

        $this->privateActivity = Activity::create([
            'branch_id'            => $this->branch->id,
            'activity_type_id'     => $privateType->id,
            'name'                 => 'حديد خاص',
            'is_private_equipment' => true,
            'is_active'            => true,
        ]);

        // 3. Session-based activity (Kickboxing)
        $sessionType = ActivityType::create([
            'name'                     => 'حصة جماعية',
            'is_active'                => true,
            'is_session_based'         => true,
            'has_unlimited_subscribers'=> false,
            'has_shifts'               => false,
        ]);

        $this->sessionActivity = Activity::create([
            'branch_id'        => $this->branch->id,
            'activity_type_id' => $sessionType->id,
            'name'             => 'كيك بوكسينغ',
            'is_active'        => true,
        ]);

        // Attach all 3 activities to coach
        $this->coachStaff->activities()->attach([
            $this->generalActivity->id,
            $this->privateActivity->id,
            $this->sessionActivity->id,
        ]);

        // Create plan and weekly session template for sessionActivity
        $staffActivity = StaffActivity::where('staff_id', $this->coachStaff->id)
            ->where('activity_id', $this->sessionActivity->id)
            ->first();

        $plan = SubscriptionPlan::create([
            'branch_id'    => $this->branch->id,
            'name'         => 'اشتراك كيك بوكسينغ شهري',
            'session_count'=> 12,
            'base_price'   => 300,
            'status'       => SubscriptionPlanStatus::ACTIVE->value,
        ]);

        SubscriptionPlanActivity::create([
            'plan_id'           => $plan->id,
            'staff_activity_id' => $staffActivity->id,
        ]);

        SportSessionTemplate::create([
            'plan_id'     => $plan->id,
            'day_of_week' => 0, // Sunday
            'start_time'  => '17:00',
            'end_time'    => '18:30',
            'is_active'   => true,
        ]);
    }

    public function test_coach_show_returns_correct_timing_and_schedules_per_activity()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson("/api/v1/coaches/{$this->coachStaff->id}");
        $response->assertStatus(200);

        $activities = $response->json('data.activities');
        $this->assertCount(3, $activities);

        // Check General Training activity
        $general = collect($activities)->firstWhere('id', $this->generalActivity->id);
        $this->assertNotNull($general);
        $this->assertEquals('shifts', $general['schedule_type']);
        $this->assertCount(1, $general['shifts']);
        $this->assertEquals('الشفت الصباحي', $general['shifts'][0]['name']);
        $this->assertEquals('08:00', $general['shifts'][0]['start_time']);
        $this->assertEquals('16:00', $general['shifts'][0]['end_time']);
        $this->assertEmpty($general['schedules']);

        // Check Private Training activity
        $private = collect($activities)->firstWhere('id', $this->privateActivity->id);
        $this->assertNotNull($private);
        $this->assertEquals('none', $private['schedule_type']);
        $this->assertEmpty($private['shifts']);
        $this->assertEmpty($private['schedules']);

        // Check Session activity
        $session = collect($activities)->firstWhere('id', $this->sessionActivity->id);
        $this->assertNotNull($session);
        $this->assertEquals('schedule', $session['schedule_type']);
        $this->assertEmpty($session['shifts']);
        $this->assertCount(1, $session['schedules']);

        $schedule = $session['schedules'][0];
        $this->assertEquals('اشتراك كيك بوكسينغ شهري', $schedule['plan_name']);
        $this->assertEquals(0, $schedule['day_of_week']);
        $this->assertEquals('Sunday', $schedule['day_name']);
        $this->assertEquals('الأحد', $schedule['day_name_ar']);
        $this->assertEquals('17:00', $schedule['start_time']);
        $this->assertEquals('18:30', $schedule['end_time']);
        $this->assertTrue($schedule['is_active']);
    }

    public function test_coach_index_returns_correct_timing_and_schedules_per_activity()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/coaches');
        $response->assertStatus(200);

        $coaches = $response->json('data');
        $this->assertNotEmpty($coaches);

        $coach = collect($coaches)->firstWhere('id', $this->coachStaff->id);
        $this->assertNotNull($coach);

        $activities = $coach['activities'];
        $this->assertCount(3, $activities);

        $session = collect($activities)->firstWhere('id', $this->sessionActivity->id);
        $this->assertEquals('schedule', $session['schedule_type']);
        $this->assertEquals('اشتراك كيك بوكسينغ شهري', $session['schedules'][0]['plan_name']);
        $this->assertEquals('الأحد', $session['schedules'][0]['day_name_ar']);
    }
}
