<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Branch;
use Modules\Authentication\Models\User;
use Modules\Authentication\Models\Person;
use Modules\MemberManager\Models\Member;
use Modules\StaffManager\Models\Staff;
use Modules\Sports\Models\ActivityType;
use Modules\Sports\Models\Activity;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

class PaginationShapeTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Club $club;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $person = Person::create([
            'full_name' => 'Admin User',
            'gender'    => 'male',
            'type'      => 'staff',
        ]);

        $this->adminUser = User::create([
            'person_id' => $person->id,
            'username'  => 'admin_test',
            'password'  => bcrypt('password'),
            'is_active' => true,
        ]);

        $role = Role::firstOrCreate([
            'name'       => 'super_admin',
            'guard_name' => 'sanctum',
        ]);
        $this->adminUser->assignRole($role);

        Sanctum::actingAs($this->adminUser);

        $this->club = Club::create(['name' => 'Main Club', 'is_active' => true]);
        $this->branch = Branch::create([
            'club_id'            => $this->club->id,
            'name'               => 'Main Branch',
            'gender_restriction' => 'mixed',
            'is_active'          => true,
        ]);
    }

    public function test_clubs_index_returns_paginated_array_with_root_meta_and_array_data(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            Club::create(['name' => "Club {$i}", 'is_active' => true]);
        }

        $response = $this->getJson('/api/v1/clubs?per_page=5');

        $response->assertStatus(200);
        $json = $response->json();

        // 1. Status is success
        $this->assertEquals('success', $json['status']);

        // 2. data is an array (res.data.data on front is array)
        $this->assertIsArray($json['data']);
        $this->assertCount(5, $json['data']);

        // 3. meta is at root level with pagination details
        $this->assertArrayHasKey('meta', $json);
        $this->assertEquals(1, $json['meta']['current_page']);
        $this->assertEquals(5, $json['meta']['per_page']);
        $this->assertEquals(21, $json['meta']['total']); // 20 + 1 from setUp
        $this->assertEquals(5, $json['meta']['last_page']);
    }

    public function test_clubs_index_supports_page_param(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            Club::create(['name' => "Club {$i}", 'is_active' => true]);
        }

        $response = $this->getJson('/api/v1/clubs?per_page=5&page=2');

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertIsArray($json['data']);
        $this->assertCount(5, $json['data']);
        $this->assertEquals(2, $json['meta']['current_page']);
    }

    public function test_clubs_index_supports_per_page_all_unpaginated(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            Club::create(['name' => "Club {$i}", 'is_active' => true]);
        }

        $response = $this->getJson('/api/v1/clubs?per_page=all');

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertIsArray($json['data']);
        $this->assertCount(11, $json['data']);
        $this->assertArrayNotHasKey('meta', $json);
    }

    public function test_branches_index_returns_paginated_array(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            Branch::create([
                'club_id'            => $this->club->id,
                'name'               => "Branch {$i}",
                'gender_restriction' => 'mixed',
                'is_active'          => true,
            ]);
        }

        $response = $this->getJson('/api/v1/branches?per_page=4');

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertIsArray($json['data']);
        $this->assertCount(4, $json['data']);
        $this->assertArrayHasKey('meta', $json);
        $this->assertEquals(4, $json['meta']['per_page']);
    }

    public function test_members_index_returns_paginated_array_with_resource_collection(): void
    {
        for ($i = 1; $i <= 8; $i++) {
            $person = Person::create([
                'full_name' => "Player {$i}",
                'gender'    => 'male',
                'type'      => 'player',
            ]);

            Member::create([
                'person_id'         => $person->id,
                'branch_id'         => $this->branch->id,
                'member_number'     => "MEM-TEST-{$i}",
                'membership_status' => 'active',
            ]);
        }

        $response = $this->getJson('/api/v1/members?per_page=3');

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertIsArray($json['data']);
        $this->assertCount(3, $json['data']);
        $this->assertArrayHasKey('meta', $json);
        $this->assertEquals(1, $json['meta']['current_page']);
        $this->assertEquals(3, $json['meta']['per_page']);
        $this->assertEquals(8, $json['meta']['total']);
    }

    public function test_staff_index_returns_paginated_array(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            $person = Person::create([
                'full_name' => "Staff {$i}",
                'gender'    => 'male',
                'type'      => 'staff',
            ]);

            Staff::create([
                'person_id' => $person->id,
                'role'      => 'receptionist',
                'is_active' => true,
            ]);
        }

        $response = $this->getJson('/api/v1/staff?per_page=2');

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertIsArray($json['data']);
        $this->assertCount(2, $json['data']);
        $this->assertArrayHasKey('meta', $json);
        $this->assertEquals(2, $json['meta']['per_page']);
        $this->assertEquals(6, $json['meta']['total']);
    }

    public function test_activities_index_returns_paginated_array(): void
    {
        $type = ActivityType::create([
            'name' => 'General Sports',
            'code' => 'GEN',
        ]);

        for ($i = 1; $i <= 5; $i++) {
            Activity::create([
                'name'             => "Activity {$i}",
                'activity_type_id' => $type->id,
                'branch_id'        => $this->branch->id,
                'is_active'        => true,
            ]);
        }

        $response = $this->getJson('/api/v1/activities?per_page=2');

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertIsArray($json['data']);
        $this->assertCount(2, $json['data']);
        $this->assertArrayHasKey('meta', $json);
        $this->assertEquals(2, $json['meta']['per_page']);
        $this->assertEquals(5, $json['meta']['total']);
    }

    public function test_subscription_plans_index_returns_paginated_array(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            \Modules\SubscriptionManager\Models\SubscriptionPlan::create([
                'branch_id'   => $this->branch->id,
                'name'        => "Plan {$i}",
                'base_price'  => 100 + $i,
                'status'      => 'active',
            ]);
        }

        $response = $this->getJson('/api/v1/subscription-plans?per_page=3');

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertIsArray($json['data']);
        $this->assertCount(3, $json['data']);
        $this->assertArrayHasKey('meta', $json);
        $this->assertEquals(3, $json['meta']['per_page']);
        $this->assertEquals(6, $json['meta']['total']);
    }

    public function test_users_index_returns_paginated_array(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $person = Person::create([
                'full_name' => "User {$i}",
                'gender'    => 'female',
                'type'      => 'staff',
            ]);

            User::create([
                'person_id' => $person->id,
                'username'  => "test_user_{$i}",
                'password'  => bcrypt('password'),
                'is_active' => true,
            ]);
        }

        $response = $this->getJson('/api/v1/users?per_page=2');

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertIsArray($json['data']);
        $this->assertCount(2, $json['data']);
        $this->assertArrayHasKey('meta', $json);
        $this->assertEquals(2, $json['meta']['per_page']);
    }

    public function test_trashed_clubs_returns_paginated_array(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            $c = Club::create(['name' => "Trashed Club {$i}", 'is_active' => true]);
            $c->delete();
        }

        $response = $this->getJson('/api/v1/clubs/trashed?per_page=2');

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertIsArray($json['data']);
        $this->assertCount(2, $json['data']);
        $this->assertArrayHasKey('meta', $json);
        $this->assertEquals(2, $json['meta']['per_page']);
        $this->assertEquals(6, $json['meta']['total']);
    }

    public function test_trashed_members_returns_paginated_array(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $person = Person::create([
                'full_name' => "Trashed Player {$i}",
                'gender'    => 'male',
                'type'      => 'player',
            ]);

            $m = Member::create([
                'person_id'         => $person->id,
                'branch_id'         => $this->branch->id,
                'member_number'     => "MEM-TRASH-{$i}",
                'membership_status' => 'active',
            ]);
            $m->delete();
        }

        $response = $this->getJson('/api/v1/members/trashed?per_page=2');

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertIsArray($json['data']);
        $this->assertCount(2, $json['data']);
        $this->assertArrayHasKey('meta', $json);
        $this->assertEquals(2, $json['meta']['per_page']);
        $this->assertEquals(4, $json['meta']['total']);
    }

    public function test_trashed_coaches_returns_paginated_array(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $person = Person::create([
                'full_name' => "Trashed Coach {$i}",
                'gender'    => 'male',
                'type'      => 'coach',
            ]);

            $coach = Staff::create([
                'person_id' => $person->id,
                'role'      => 'coach',
                'is_active' => true,
            ]);
            $coach->delete();
        }

        $response = $this->getJson('/api/v1/coaches/trashed?per_page=2');

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertIsArray($json['data']);
        $this->assertCount(2, $json['data']);
        $this->assertArrayHasKey('meta', $json);
        $this->assertEquals(2, $json['meta']['per_page']);
        $this->assertEquals(4, $json['meta']['total']);
    }

    public function test_coaches_index_without_per_page_returns_all_unpaginated(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $person = Person::create([
                'full_name' => "Coach {$i}",
                'gender'    => 'male',
                'type'      => 'coach',
            ]);

            Staff::create([
                'person_id' => $person->id,
                'role'      => 'coach',
                'is_active' => true,
            ]);
        }

        // Without per_page parameter
        $response = $this->getJson('/api/v1/coaches');

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertIsArray($json['data']);
        $this->assertCount(20, $json['data']);
        $this->assertArrayNotHasKey('meta', $json);
    }

    public function test_clubs_index_without_per_page_returns_all_unpaginated(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            Club::create(['name' => "Club Extra {$i}", 'is_active' => true]);
        }

        $response = $this->getJson('/api/v1/clubs');

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertIsArray($json['data']);
        $this->assertCount(21, $json['data']); // 20 + 1 from setUp
        $this->assertArrayNotHasKey('meta', $json);
    }
}
