<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Modules\ClubManager\Models\Branch;
use Modules\ClubManager\Models\Club;
use Modules\StaffManager\Models\Staff;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StaffRoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $person = Person::create([
            'full_name'  => 'Admin User',
            'first_name' => 'Admin',
            'last_name'  => 'User',
            'gender'     => 'male',
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
    }

    public function test_can_onboard_staff_with_accountant_role()
    {
        $payload = [
            'first_name'      => 'Ahmad',
            'last_name'       => 'Ali',
            'phone_number'    => '966500000001',
            'country_code'    => '+966',
            'role'            => 'accountant',
            'employment_type' => 'fixed_salary',
            'base_salary'     => 4000,
            'branch_ids'      => [$this->branch->id],
        ];

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/staff', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('staff', [
            'role' => 'accountant',
        ]);

        $staff = Staff::latest('id')->first();
        $user = User::where('person_id', $staff->person_id)->first();
        $this->assertNotNull($user);
        $this->assertEquals('accountant', $user->role);
        $this->assertTrue($user->hasRole('accountant', 'sanctum'));

        // Test login returns roles array and preserves type
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'username' => $user->username,
            'password' => '12345678',
        ]);
        $loginResponse->assertStatus(200)
            ->assertJsonPath('data.user.type', 'staff')
            ->assertJsonPath('data.user.roles', ['accountant']);
    }

    public function test_can_onboard_staff_with_custom_created_role()
    {
        $customRole = Role::firstOrCreate(['name' => 'inventory_manager', 'guard_name' => 'sanctum'], [
            'name_ar' => 'مدير المخزون',
            'is_visible' => true,
        ]);

        $payload = [
            'first_name'      => 'Sami',
            'last_name'       => 'Kareem',
            'phone_number'    => '966500000002',
            'country_code'    => '+966',
            'role'            => 'inventory_manager',
            'employment_type' => 'fixed_salary',
            'base_salary'     => 6000,
            'branch_ids'      => [$this->branch->id],
        ];

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/staff', $payload);

        $response->assertStatus(201);
        $staff = Staff::latest('id')->first();
        $user = User::where('person_id', $staff->person_id)->first();
        $this->assertNotNull($user);
        $this->assertEquals('inventory_manager', $user->role);
        $this->assertTrue($user->hasRole('inventory_manager', 'sanctum'));
    }

    public function test_cannot_onboard_staff_with_nonexistent_role()
    {
        $payload = [
            'first_name'      => 'Khaled',
            'last_name'       => 'Omar',
            'phone_number'    => '966500000003',
            'role'            => 'non_existent_role_xyz',
            'employment_type' => 'fixed_salary',
            'branch_ids'      => [$this->branch->id],
        ];

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/staff', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['role']);
    }

    public function test_updating_staff_role_syncs_user_spatie_role()
    {
        // 1. Onboard staff as reception
        $payload = [
            'first_name'      => 'Tariq',
            'last_name'       => 'Mansour',
            'phone_number'    => '966500000004',
            'country_code'    => '+966',
            'role'            => 'reception',
            'employment_type' => 'fixed_salary',
            'base_salary'     => 4000,
            'branch_ids'      => [$this->branch->id],
        ];

        $createResponse = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/staff', $payload);
        $createResponse->assertStatus(201);

        $staff = Staff::latest('id')->first();
        $user = User::where('person_id', $staff->person_id)->first();
        $this->assertEquals('reception', $user->role);
        $this->assertTrue($user->hasRole('reception', 'sanctum'));

        // 2. Update role to accountant
        $updatePayload = [
            'reason'          => 'ترقية وظيفية إلى محاسب',
            'first_name'      => 'Tariq',
            'last_name'       => 'Mansour',
            'phone_number'    => '966500000004',
            'country_code'    => '+966',
            'role'            => 'accountant',
            'employment_type' => 'fixed_salary',
            'base_salary'     => 5500,
            'branch_ids'      => [$this->branch->id],
        ];

        $updateResponse = $this->actingAs($this->admin, 'sanctum')->putJson("/api/v1/staff/{$staff->id}", $updatePayload);
        $updateResponse->assertStatus(200);

        // Verify role synced on user in DB column and spatie
        $user->refresh();
        $this->assertEquals('accountant', $user->role);
        $this->assertFalse($user->hasRole('reception', 'sanctum'));
        $this->assertTrue($user->hasRole('accountant', 'sanctum'));
    }
}
