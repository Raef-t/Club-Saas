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

class StaffGenderAndPhoneUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $femaleBranch;
    protected $maleBranch;
    protected $mixedBranch;

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
        $this->femaleBranch = Branch::create([
            'club_id' => $club->id,
            'name' => 'Female Only Branch',
            'gender_restriction' => 'female',
            'is_active' => true,
        ]);
        $this->maleBranch = Branch::create([
            'club_id' => $club->id,
            'name' => 'Male Only Branch',
            'gender_restriction' => 'male',
            'is_active' => true,
        ]);
        $this->mixedBranch = Branch::create([
            'club_id' => $club->id,
            'name' => 'Mixed Branch',
            'gender_restriction' => 'mixed',
            'is_active' => true,
        ]);
    }

    public function test_cannot_add_male_staff_to_female_branch()
    {
        $payload = [
            'first_name'      => 'Ahmad',
            'last_name'       => 'Ali',
            'phone_number'    => '966500000001',
            'country_code'    => '+966',
            'gender'          => 'male',
            'role'            => 'reception',
            'employment_type' => 'fixed_salary',
            'base_salary'     => 4000,
            'branch_ids'      => [$this->femaleBranch->id],
        ];

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/staff', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['gender']);
    }

    public function test_cannot_add_male_staff_when_branch_id_is_passed_as_single_id()
    {
        $payload = [
            'first_name'      => 'Ahmad',
            'last_name'       => 'Ali',
            'phone_number'    => '966500000001',
            'country_code'    => '+966',
            'gender'          => 'male',
            'role'            => 'reception',
            'employment_type' => 'fixed_salary',
            'base_salary'     => 4000,
            'branch_id'       => $this->femaleBranch->id,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/staff', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['gender']);
    }

    public function test_cannot_update_staff_gender_to_male_in_female_branch()
    {
        $payload = [
            'first_name'      => 'Fatima',
            'last_name'       => 'Ali',
            'phone_number'    => '966500000002',
            'country_code'    => '+966',
            'gender'          => 'female',
            'role'            => 'reception',
            'employment_type' => 'fixed_salary',
            'base_salary'     => 4000,
            'branch_ids'      => [$this->femaleBranch->id],
        ];

        $createResponse = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/staff', $payload);
        $createResponse->assertStatus(201);
        $staffId = $createResponse->json('data.id');

        $updatePayload = [
            'reason'          => 'تغيير الجنس بالخطأ',
            'first_name'      => 'Fatima',
            'last_name'       => 'Ali',
            'phone_number'    => '966500000002',
            'country_code'    => '+966',
            'gender'          => 'male',
            'role'            => 'reception',
            'employment_type' => 'fixed_salary',
            'base_salary'     => 4000,
            'branch_ids'      => [$this->femaleBranch->id],
        ];

        $updateResponse = $this->actingAs($this->admin, 'sanctum')->putJson("/api/v1/staff/{$staffId}", $updatePayload);
        $updateResponse->assertStatus(422)
                       ->assertJsonValidationErrors(['gender']);
    }

    public function test_can_update_staff_phone_number()
    {
        $payload = [
            'first_name'      => 'Mona',
            'last_name'       => 'Kareem',
            'phone_number'    => '966500000003',
            'country_code'    => '+966',
            'gender'          => 'female',
            'role'            => 'reception',
            'employment_type' => 'fixed_salary',
            'base_salary'     => 4000,
            'branch_ids'      => [$this->femaleBranch->id],
        ];

        $createResponse = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/staff', $payload);
        $createResponse->assertStatus(201);
        $staffId = $createResponse->json('data.id');
        $this->assertEquals('966500000003', $createResponse->json('data.person.phone_number'));

        // Update phone number
        $updatePayload = [
            'reason'          => 'تحديث رقم الهاتف',
            'first_name'      => 'Mona',
            'last_name'       => 'Kareem',
            'phone_number'    => '966599999999',
            'country_code'    => '+966',
            'gender'          => 'female',
            'role'            => 'reception',
            'employment_type' => 'fixed_salary',
            'base_salary'     => 4000,
            'branch_ids'      => [$this->femaleBranch->id],
        ];

        $updateResponse = $this->actingAs($this->admin, 'sanctum')->putJson("/api/v1/staff/{$staffId}", $updatePayload);
        $updateResponse->assertStatus(200);

        // Verify updated phone in response and DB
        $this->assertEquals('966599999999', $updateResponse->json('data.person.phone_number'));

        $showResponse = $this->actingAs($this->admin, 'sanctum')->getJson("/api/v1/staff/{$staffId}");
        $showResponse->assertStatus(200);
        $this->assertEquals('966599999999', $showResponse->json('data.person.phone_number'));
    }

    public function test_cannot_add_male_coach_to_female_branch()
    {
        $payload = [
            'first_name'      => 'Tariq',
            'last_name'       => 'Coach',
            'phone_number'    => '966511111111',
            'country_code'    => '+966',
            'gender'          => 'male',
            'branch_ids'      => [$this->femaleBranch->id],
            'employment_type' => 'fixed_salary',
            'base_salary'     => 5000,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/coaches', $payload);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['gender']);
    }

    public function test_can_update_coach_phone_number()
    {
        $payload = [
            'first_name'      => 'Sarah',
            'last_name'       => 'Coach',
            'phone_number'    => '966522222222',
            'country_code'    => '+966',
            'gender'          => 'female',
            'branch_ids'      => [$this->femaleBranch->id],
            'employment_type' => 'fixed_salary',
            'base_salary'     => 5000,
        ];

        $createResponse = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/coaches', $payload);
        $createResponse->assertStatus(201);
        $coachId = $createResponse->json('data.id');

        $updatePayload = [
            'reason'          => 'تحديث رقم الكوتش',
            'phone_number'    => '966588888888',
        ];

        $updateResponse = $this->actingAs($this->admin, 'sanctum')->putJson("/api/v1/coaches/{$coachId}", $updatePayload);
        $updateResponse->assertStatus(200);
        $this->assertEquals('966588888888', $updateResponse->json('data.person.phone_number'));
    }
}
