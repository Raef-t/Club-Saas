<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Branch;
use Modules\Sports\Models\Activity;
use Modules\Sports\Models\ActivityType;
use Modules\StaffManager\Models\Staff;
use Modules\StaffManager\Models\CoachDetail;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\PersonContact;
use Modules\Authentication\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

class EntitySearchAndFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Branch $branch;
    protected ActivityType $activityType;

    protected function setUp(): void
    {
        parent::setUp();

        $adminPerson = Person::create([
            'full_name' => 'Admin Tester ' . uniqid(),
            'gender' => 'male',
            'type' => 'staff',
        ]);

        $this->adminUser = User::create([
            'person_id' => $adminPerson->id,
            'username' => 'admin_search_' . uniqid(),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $role = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'sanctum',
        ]);
        $this->adminUser->assignRole($role);
        Sanctum::actingAs($this->adminUser, ['*']);

        $club = Club::create([
            'name' => 'Entity Test Club ' . uniqid(),
            'is_active' => true,
        ]);
        $this->branch = Branch::create([
            'club_id' => $club->id,
            'name' => 'Entity Test Branch ' . uniqid(),
            'is_active' => true,
        ]);

        $this->activityType = ActivityType::create([
            'name' => 'نوع رياضي افتراضي ' . uniqid(),
            'is_active' => true,
        ]);
    }

    public function test_activity_search_by_name_and_price()
    {
        $act1 = Activity::create([
            'name' => 'سباحة حرة تدريبية',
            'session_price' => 50.00,
            'branch_id' => $this->branch->id,
            'activity_type_id' => $this->activityType->id,
            'is_active' => true,
        ]);

        $act2 = Activity::create([
            'name' => 'حديد وأثقال عام',
            'session_price' => 120.00,
            'branch_id' => $this->branch->id,
            'activity_type_id' => $this->activityType->id,
            'is_active' => true,
        ]);

        // Search by name parameter
        $res = $this->getJson('/api/v1/activities?name=سباحة');
        $res->assertOk();
        $this->assertTrue(collect($res->json('data'))->contains('id', $act1->id));
        $this->assertFalse(collect($res->json('data'))->contains('id', $act2->id));

        // Search by price parameter
        $res = $this->getJson('/api/v1/activities?price=120');
        $res->assertOk();
        $this->assertFalse(collect($res->json('data'))->contains('id', $act1->id));
        $this->assertTrue(collect($res->json('data'))->contains('id', $act2->id));

        // General search by name
        $res = $this->getJson('/api/v1/activities?search=حديد');
        $res->assertOk();
        $this->assertFalse(collect($res->json('data'))->contains('id', $act1->id));
        $this->assertTrue(collect($res->json('data'))->contains('id', $act2->id));

        // General search by numeric price
        $res = $this->getJson('/api/v1/activities?search=50');
        $res->assertOk();
        $this->assertTrue(collect($res->json('data'))->contains('id', $act1->id));
        $this->assertFalse(collect($res->json('data'))->contains('id', $act2->id));
    }

    public function test_activity_type_search_by_name()
    {
        $type1 = ActivityType::create([
            'name' => 'لياقة بدنية وجمباز',
            'is_active' => true,
        ]);

        $type2 = ActivityType::create([
            'name' => 'فنون قتالية ودفاع عن النفس',
            'is_active' => true,
        ]);

        // Search by general search query
        $res = $this->getJson('/api/v1/activity-types?search=لياقة');
        $res->assertOk();
        $this->assertTrue(collect($res->json('data'))->contains('id', $type1->id));
        $this->assertFalse(collect($res->json('data'))->contains('id', $type2->id));

        // Search by name parameter
        $res = $this->getJson('/api/v1/activity-types?name=قتالية');
        $res->assertOk();
        $this->assertFalse(collect($res->json('data'))->contains('id', $type1->id));
        $this->assertTrue(collect($res->json('data'))->contains('id', $type2->id));
    }

    public function test_staff_search_by_name_phone_and_username()
    {
        // Staff 1
        $person1 = Person::create([
            'full_name' => 'سامر المحمود',
            'gender' => 'male',
            'type' => 'staff',
        ]);
        PersonContact::create([
            'person_id' => $person1->id,
            'name' => 'Primary',
            'phone_number' => '0599111222',
            'relation' => 'self',
        ]);
        User::create([
            'person_id' => $person1->id,
            'username' => 'samer_staff_' . uniqid(),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $staff1 = Staff::create([
            'person_id' => $person1->id,
            'role' => 'reception',
            'work_status' => 'active',
            'is_active' => true,
        ]);
        $staff1->branches()->attach($this->branch->id);

        // Staff 2
        $person2 = Person::create([
            'full_name' => 'خالد العلي',
            'gender' => 'male',
            'type' => 'staff',
        ]);
        PersonContact::create([
            'person_id' => $person2->id,
            'name' => 'Primary',
            'phone_number' => '0599333444',
            'relation' => 'self',
        ]);
        $user2 = User::create([
            'person_id' => $person2->id,
            'username' => 'khaled_user_' . uniqid(),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $staff2 = Staff::create([
            'person_id' => $person2->id,
            'role' => 'accountant',
            'work_status' => 'active',
            'is_active' => true,
        ]);
        $staff2->branches()->attach($this->branch->id);

        // Search by name
        $res = $this->getJson('/api/v1/staff?name=سامر');
        $res->assertOk();
        $items = $res->json('data.data') ?? $res->json('data');
        $this->assertTrue(collect($items)->contains('id', $staff1->id));
        $this->assertFalse(collect($items)->contains('id', $staff2->id));

        // Search by phone
        $res = $this->getJson('/api/v1/staff?phone=0599333444');
        $res->assertOk();
        $items = $res->json('data.data') ?? $res->json('data');
        $this->assertFalse(collect($items)->contains('id', $staff1->id));
        $this->assertTrue(collect($items)->contains('id', $staff2->id));

        // Search by username
        $res = $this->getJson('/api/v1/staff?username=' . $user2->username);
        $res->assertOk();
        $items = $res->json('data.data') ?? $res->json('data');
        $this->assertFalse(collect($items)->contains('id', $staff1->id));
        $this->assertTrue(collect($items)->contains('id', $staff2->id));

        // General search by name
        $res = $this->getJson('/api/v1/staff?search=خالد');
        $res->assertOk();
        $items = $res->json('data.data') ?? $res->json('data');
        $this->assertFalse(collect($items)->contains('id', $staff1->id));
        $this->assertTrue(collect($items)->contains('id', $staff2->id));

        // General search by phone
        $res = $this->getJson('/api/v1/staff?search=0599111222');
        $res->assertOk();
        $items = $res->json('data.data') ?? $res->json('data');
        $this->assertTrue(collect($items)->contains('id', $staff1->id));
        $this->assertFalse(collect($items)->contains('id', $staff2->id));
    }

    public function test_coach_search_by_name_and_employment_type_filter()
    {
        // Coach 1: Fixed salary
        $p1 = Person::create(['full_name' => 'ماهر الكابتن', 'gender' => 'male', 'type' => 'coach']);
        $coach1 = Staff::create(['person_id' => $p1->id, 'role' => 'coach', 'work_status' => 'active', 'is_active' => true]);
        $coach1->coachDetail()->create(['experience_years' => 5]);
        $coach1->contracts()->create(['employment_type' => 'fixed_salary', 'base_salary' => 5000, 'start_date' => now()->toDateString(), 'is_active' => true]);
        $coach1->branches()->attach($this->branch->id);

        // Coach 2: Commission based
        $p2 = Person::create(['full_name' => 'ياسر المدرب', 'gender' => 'male', 'type' => 'coach']);
        $coach2 = Staff::create(['person_id' => $p2->id, 'role' => 'coach', 'work_status' => 'active', 'is_active' => true]);
        $coach2->coachDetail()->create(['experience_years' => 3]);
        $coach2->contracts()->create(['employment_type' => 'commission_based', 'base_salary' => 0, 'start_date' => now()->toDateString(), 'is_active' => true]);
        $coach2->branches()->attach($this->branch->id);

        // Coach 3: Hybrid
        $p3 = Person::create(['full_name' => 'فادي النجم', 'gender' => 'male', 'type' => 'coach']);
        $coach3 = Staff::create(['person_id' => $p3->id, 'role' => 'coach', 'work_status' => 'active', 'is_active' => true]);
        $coach3->coachDetail()->create(['experience_years' => 7]);
        $coach3->contracts()->create(['employment_type' => 'hybrid', 'base_salary' => 2000, 'start_date' => now()->toDateString(), 'is_active' => true]);
        $coach3->branches()->attach($this->branch->id);

        // Search by coach name
        $res = $this->getJson('/api/v1/coaches?name=ماهر');
        $res->assertOk();
        $this->assertTrue(collect($res->json('data'))->contains('id', $coach1->id));
        $this->assertFalse(collect($res->json('data'))->contains('id', $coach2->id));

        // Filter by English employment_type: fixed_salary
        $res = $this->getJson('/api/v1/coaches?employment_type=fixed_salary');
        $res->assertOk();
        $this->assertTrue(collect($res->json('data'))->contains('id', $coach1->id));
        $this->assertFalse(collect($res->json('data'))->contains('id', $coach2->id));
        $this->assertFalse(collect($res->json('data'))->contains('id', $coach3->id));

        // Filter by Arabic employment_type: راتب
        $res = $this->getJson('/api/v1/coaches?employment_type=راتب');
        $res->assertOk();
        $this->assertTrue(collect($res->json('data'))->contains('id', $coach1->id));
        $this->assertFalse(collect($res->json('data'))->contains('id', $coach2->id));
        $this->assertFalse(collect($res->json('data'))->contains('id', $coach3->id));

        // Filter by Arabic employment_type: نسبة
        $res = $this->getJson('/api/v1/coaches?employment_type=نسبة');
        $res->assertOk();
        $this->assertFalse(collect($res->json('data'))->contains('id', $coach1->id));
        $this->assertTrue(collect($res->json('data'))->contains('id', $coach2->id));
        $this->assertFalse(collect($res->json('data'))->contains('id', $coach3->id));

        // Filter by Arabic employment_type: نسبة وراتب
        $res = $this->getJson('/api/v1/coaches?employment_type=' . urlencode('نسبة وراتب'));
        $res->assertOk();
        $this->assertFalse(collect($res->json('data'))->contains('id', $coach1->id));
        $this->assertFalse(collect($res->json('data'))->contains('id', $coach2->id));
        $this->assertTrue(collect($res->json('data'))->contains('id', $coach3->id));
    }

    public function test_users_search_by_name_and_username()
    {
        $p1 = Person::create(['full_name' => 'عمرو دياب', 'gender' => 'male']);
        $u1 = User::create([
            'person_id' => $p1->id,
            'username' => 'amr_legend_' . uniqid(),
            'custom_username' => 'elhadaba_' . uniqid(),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $p2 = Person::create(['full_name' => 'تامر حسني', 'gender' => 'male']);
        $u2 = User::create([
            'person_id' => $p2->id,
            'username' => 'tamer_star_' . uniqid(),
            'custom_username' => 'elnegm_' . uniqid(),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        // Search by name
        $res = $this->getJson('/api/v1/users?name=عمرو');
        $res->assertOk();
        $this->assertTrue(collect($res->json('data'))->contains('id', $u1->id));
        $this->assertFalse(collect($res->json('data'))->contains('id', $u2->id));

        // Search by username
        $res = $this->getJson('/api/v1/users?username=' . $u2->username);
        $res->assertOk();
        $this->assertFalse(collect($res->json('data'))->contains('id', $u1->id));
        $this->assertTrue(collect($res->json('data'))->contains('id', $u2->id));

        // General search by name
        $res = $this->getJson('/api/v1/users?search=تامر');
        $res->assertOk();
        $this->assertFalse(collect($res->json('data'))->contains('id', $u1->id));
        $this->assertTrue(collect($res->json('data'))->contains('id', $u2->id));

        // General search by custom_username
        $res = $this->getJson('/api/v1/users?search=' . $u1->custom_username);
        $res->assertOk();
        $this->assertTrue(collect($res->json('data'))->contains('id', $u1->id));
        $this->assertFalse(collect($res->json('data'))->contains('id', $u2->id));
    }
}
