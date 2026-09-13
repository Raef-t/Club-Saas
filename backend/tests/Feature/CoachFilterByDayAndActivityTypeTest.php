<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Modules\ClubManager\Models\Branch;
use Modules\ClubManager\Models\BranchHoliday;
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

class CoachFilterByDayAndActivityTypeTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $branch1;
    protected $branch2;
    protected $groupActivityType;
    protected $generalActivityType;

    protected $coachAhmed;   // Kickboxing (Group Session) on Sunday (day 0)
    protected $coachFiras;   // Swimming (Group Session) on Friday (day 5)
    protected $coachOmar;    // General Training in Branch 1 (Friday is Weekly Holiday)
    protected $coachSamer;   // General Training in Branch 2 (Branch 2 open on Friday)

    protected $kickboxingActivity;
    protected $swimmingActivity;
    protected $gymActivity1;
    protected $gymActivity2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $adminPerson = Person::create(['full_name' => 'Admin Test', 'type' => 'staff']);
        $this->admin = User::create([
            'username'  => 'admin_' . uniqid(),
            'password'  => bcrypt('secret123'),
            'person_id' => $adminPerson->id,
            'is_active' => true,
        ]);
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'sanctum']);
        $this->admin->assignRole($superAdminRole);

        $club = Club::create(['name' => 'Fitness Club', 'is_active' => true]);
        $this->branch1 = Branch::create(['club_id' => $club->id, 'name' => 'Branch 1', 'is_active' => true]);
        $this->branch2 = Branch::create(['club_id' => $club->id, 'name' => 'Branch 2', 'is_active' => true]);

        // Branch 1 has weekly holiday on Friday (day 5)
        BranchHoliday::create([
            'branch_id'   => $this->branch1->id,
            'type'        => 'weekly',
            'day_of_week' => 5, // Friday
        ]);

        // Activity Types
        $this->groupActivityType = ActivityType::create([
            'name'                     => 'حصة جماعية',
            'is_active'                => true,
            'is_session_based'         => true,
            'has_unlimited_subscribers'=> false,
            'has_shifts'               => false,
        ]);

        $this->generalActivityType = ActivityType::create([
            'name'                     => 'تدريب عام',
            'is_active'                => true,
            'is_session_based'         => false,
            'has_unlimited_subscribers'=> true,
            'has_shifts'               => true,
        ]);

        // 1. Coach Ahmed (Kickboxing on Sunday)
        $pAhmed = Person::create(['full_name' => 'Coach Ahmed', 'type' => 'coach', 'gender' => 'male']);
        $this->coachAhmed = Staff::create(['person_id' => $pAhmed->id, 'role' => 'coach', 'work_status' => 'active', 'is_active' => true]);
        $this->coachAhmed->branches()->attach($this->branch1->id);
        CoachDetail::create(['staff_id' => $this->coachAhmed->id, 'experience_years' => 4]);

        $this->kickboxingActivity = Activity::create([
            'branch_id'        => $this->branch1->id,
            'activity_type_id' => $this->groupActivityType->id,
            'name'             => 'كيك بوكسينغ',
            'is_active'        => true,
        ]);
        $this->coachAhmed->activities()->attach($this->kickboxingActivity->id);

        $saAhmed = StaffActivity::where('staff_id', $this->coachAhmed->id)->where('activity_id', $this->kickboxingActivity->id)->first();
        $planAhmed = SubscriptionPlan::create([
            'branch_id'     => $this->branch1->id,
            'name'          => 'خطة كيك بوكسينغ',
            'session_count' => 8,
            'base_price'    => 200,
            'status'        => SubscriptionPlanStatus::ACTIVE->value,
        ]);
        SubscriptionPlanActivity::create(['plan_id' => $planAhmed->id, 'staff_activity_id' => $saAhmed->id]);
        SportSessionTemplate::create([
            'plan_id'     => $planAhmed->id,
            'day_of_week' => 0, // Sunday
            'start_time'  => '18:00',
            'end_time'    => '19:30',
            'is_active'   => true,
        ]);

        // 2. Coach Firas (Swimming on Friday)
        $pFiras = Person::create(['full_name' => 'Coach Firas', 'type' => 'coach', 'gender' => 'male']);
        $this->coachFiras = Staff::create(['person_id' => $pFiras->id, 'role' => 'coach', 'work_status' => 'active', 'is_active' => true]);
        $this->coachFiras->branches()->attach($this->branch1->id);
        CoachDetail::create(['staff_id' => $this->coachFiras->id, 'experience_years' => 6]);

        $this->swimmingActivity = Activity::create([
            'branch_id'        => $this->branch1->id,
            'activity_type_id' => $this->groupActivityType->id,
            'name'             => 'سباحة متقدمة',
            'is_active'        => true,
        ]);
        $this->coachFiras->activities()->attach($this->swimmingActivity->id);

        $saFiras = StaffActivity::where('staff_id', $this->coachFiras->id)->where('activity_id', $this->swimmingActivity->id)->first();
        $planFiras = SubscriptionPlan::create([
            'branch_id'     => $this->branch1->id,
            'name'          => 'خطة سباحة الجمعة',
            'session_count' => 4,
            'base_price'    => 250,
            'status'        => SubscriptionPlanStatus::ACTIVE->value,
        ]);
        SubscriptionPlanActivity::create(['plan_id' => $planFiras->id, 'staff_activity_id' => $saFiras->id]);
        SportSessionTemplate::create([
            'plan_id'     => $planFiras->id,
            'day_of_week' => 5, // Friday
            'start_time'  => '10:00',
            'end_time'    => '11:30',
            'is_active'   => true,
        ]);

        // 3. Coach Omar (General training in Branch 1 - Closed on Friday)
        $pOmar = Person::create(['full_name' => 'Coach Omar', 'type' => 'coach', 'gender' => 'male']);
        $this->coachOmar = Staff::create(['person_id' => $pOmar->id, 'role' => 'coach', 'work_status' => 'active', 'is_active' => true]);
        $this->coachOmar->branches()->attach($this->branch1->id);
        CoachDetail::create(['staff_id' => $this->coachOmar->id, 'experience_years' => 3]);

        $this->gymActivity1 = Activity::create([
            'branch_id'        => $this->branch1->id,
            'activity_type_id' => $this->generalActivityType->id,
            'name'             => 'صالة الأجهزة فرع 1',
            'is_active'        => true,
        ]);
        $this->coachOmar->activities()->attach($this->gymActivity1->id);

        $shift1 = BranchShift::create([
            'branch_id'      => $this->branch1->id,
            'name'           => 'شفت فرع 1',
            'start_time'     => '08:00',
            'end_time'       => '16:00',
            'gender_allowed' => 'male',
        ]);
        StaffShift::create(['staff_id' => $this->coachOmar->id, 'branch_shift_id' => $shift1->id]);

        // 4. Coach Samer (General training in Branch 2 - Open on Friday)
        $pSamer = Person::create(['full_name' => 'Coach Samer', 'type' => 'coach', 'gender' => 'male']);
        $this->coachSamer = Staff::create(['person_id' => $pSamer->id, 'role' => 'coach', 'work_status' => 'active', 'is_active' => true]);
        $this->coachSamer->branches()->attach($this->branch2->id);
        CoachDetail::create(['staff_id' => $this->coachSamer->id, 'experience_years' => 2]);

        $this->gymActivity2 = Activity::create([
            'branch_id'        => $this->branch2->id,
            'activity_type_id' => $this->generalActivityType->id,
            'name'             => 'صالة الأجهزة فرع 2',
            'is_active'        => true,
        ]);
        $this->coachSamer->activities()->attach($this->gymActivity2->id);

        $shift2 = BranchShift::create([
            'branch_id'      => $this->branch2->id,
            'name'           => 'شفت فرع 2',
            'start_time'     => '09:00',
            'end_time'       => '17:00',
            'gender_allowed' => 'male',
        ]);
        StaffShift::create(['staff_id' => $this->coachSamer->id, 'branch_shift_id' => $shift2->id]);
    }

    public function test_can_filter_coaches_by_day_of_week_number()
    {
        Sanctum::actingAs($this->admin);

        // Filter for Friday (5)
        $response = $this->getJson('/api/v1/coaches?day_of_week=5');
        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();

        // Coach Firas (Friday session) and Coach Samer (Branch 2 open on Friday) should be returned
        $this->assertContains($this->coachFiras->id, $ids);
        $this->assertContains($this->coachSamer->id, $ids);

        // Coach Ahmed (Sunday only) and Coach Omar (Branch 1 weekly holiday on Friday) should NOT be returned
        $this->assertNotContains($this->coachAhmed->id, $ids);
        $this->assertNotContains($this->coachOmar->id, $ids);
    }

    public function test_can_filter_coaches_by_arabic_day_name()
    {
        Sanctum::actingAs($this->admin);

        // 1. Filter by "الجمعة"
        $resFriday = $this->getJson('/api/v1/coaches?day=' . urlencode('الجمعة'));
        $resFriday->assertStatus(200);

        $idsFriday = collect($resFriday->json('data'))->pluck('id')->all();
        $this->assertContains($this->coachFiras->id, $idsFriday);
        $this->assertContains($this->coachSamer->id, $idsFriday);
        $this->assertNotContains($this->coachAhmed->id, $idsFriday);
        $this->assertNotContains($this->coachOmar->id, $idsFriday);

        // 2. Filter by "يوم الجمعة"
        $resFullFriday = $this->getJson('/api/v1/coaches?day=' . urlencode('يوم الجمعة'));
        $resFullFriday->assertStatus(200);
        $idsFullFriday = collect($resFullFriday->json('data'))->pluck('id')->all();
        $this->assertContains($this->coachFiras->id, $idsFullFriday);
        $this->assertContains($this->coachSamer->id, $idsFullFriday);

        // 3. Filter by "الأحد" (Sunday)
        $resSunday = $this->getJson('/api/v1/coaches?day=' . urlencode('الأحد'));
        $resSunday->assertStatus(200);

        $idsSunday = collect($resSunday->json('data'))->pluck('id')->all();
        $this->assertContains($this->coachAhmed->id, $idsSunday);
    }

    public function test_can_filter_coaches_by_activity_type_id()
    {
        Sanctum::actingAs($this->admin);

        // Filter for groupActivityType
        $resGroup = $this->getJson('/api/v1/coaches?activity_type_id=' . $this->groupActivityType->id);
        $resGroup->assertStatus(200);

        $ids = collect($resGroup->json('data'))->pluck('id')->all();
        $this->assertContains($this->coachAhmed->id, $ids);
        $this->assertContains($this->coachFiras->id, $ids);
        $this->assertNotContains($this->coachOmar->id, $ids);
        $this->assertNotContains($this->coachSamer->id, $ids);

        // Filter for generalActivityType
        $resGeneral = $this->getJson('/api/v1/coaches?activity_type_id=' . $this->generalActivityType->id);
        $resGeneral->assertStatus(200);

        $generalIds = collect($resGeneral->json('data'))->pluck('id')->all();
        $this->assertContains($this->coachOmar->id, $generalIds);
        $this->assertContains($this->coachSamer->id, $generalIds);
        $this->assertNotContains($this->coachAhmed->id, $generalIds);
        $this->assertNotContains($this->coachFiras->id, $generalIds);
    }

    public function test_can_filter_coaches_by_activity_type_name_string()
    {
        Sanctum::actingAs($this->admin);

        // Filter by Arabic string "حصة جماعية"
        $res = $this->getJson('/api/v1/coaches?activity_type=' . urlencode('حصة جماعية'));
        $res->assertStatus(200);

        $ids = collect($res->json('data'))->pluck('id')->all();
        $this->assertContains($this->coachAhmed->id, $ids);
        $this->assertContains($this->coachFiras->id, $ids);
        $this->assertNotContains($this->coachOmar->id, $ids);
        $this->assertNotContains($this->coachSamer->id, $ids);

        // Filter by Arabic string "تدريب عام"
        $resGen = $this->getJson('/api/v1/coaches?activity_type=' . urlencode('تدريب عام'));
        $resGen->assertStatus(200);

        $idsGen = collect($resGen->json('data'))->pluck('id')->all();
        $this->assertContains($this->coachOmar->id, $idsGen);
        $this->assertContains($this->coachSamer->id, $idsGen);
        $this->assertNotContains($this->coachAhmed->id, $idsGen);
        $this->assertNotContains($this->coachFiras->id, $idsGen);
    }

    public function test_can_filter_coaches_by_both_day_and_activity_type()
    {
        Sanctum::actingAs($this->admin);

        // Friday (5) + Group Session (حصة جماعية)
        // Only Coach Firas has a group session (Swimming) on Friday!
        // Coach Ahmed has a group session on Sunday (not Friday).
        // Coach Samer works on Friday, but in General Training (not Group Session).
        $response = $this->getJson('/api/v1/coaches?day=' . urlencode('الجمعة') . '&activity_type=' . urlencode('حصة جماعية'));
        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($this->coachFiras->id, $ids);
        $this->assertNotContains($this->coachAhmed->id, $ids);
        $this->assertNotContains($this->coachOmar->id, $ids);
        $this->assertNotContains($this->coachSamer->id, $ids);

        // Sunday (0) + Group Session (حصة جماعية)
        // Only Coach Ahmed has a group session (Kickboxing) on Sunday!
        $resSunday = $this->getJson('/api/v1/coaches?day_of_week=0&activity_type=' . urlencode('حصة جماعية'));
        $resSunday->assertStatus(200);

        $idsSunday = collect($resSunday->json('data'))->pluck('id')->all();
        $this->assertContains($this->coachAhmed->id, $idsSunday);
        $this->assertNotContains($this->coachFiras->id, $idsSunday);
    }

    public function test_can_filter_coaches_by_day_and_specific_activity_id()
    {
        Sanctum::actingAs($this->admin);

        // Friday + Swimming activity ID
        $response = $this->getJson('/api/v1/coaches?day=' . urlencode('الجمعة') . '&activity_id=' . $this->swimmingActivity->id);
        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertEquals([$this->coachFiras->id], $ids);

        // Friday + Kickboxing activity ID (Kickboxing only on Sunday, so 0 coaches on Friday)
        $resKbFriday = $this->getJson('/api/v1/coaches?day=' . urlencode('الجمعة') . '&activity_id=' . $this->kickboxingActivity->id);
        $resKbFriday->assertStatus(200);
        $this->assertEmpty($resKbFriday->json('data'));
    }
}
