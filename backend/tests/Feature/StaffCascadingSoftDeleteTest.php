<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Branch;
use Modules\ClubManager\Models\BranchShift;
use Modules\StaffManager\Models\Staff;
use Modules\StaffManager\Models\StaffContract;
use Modules\StaffManager\Models\StaffShift;
use Modules\StaffManager\Models\StaffBranch;
use Modules\StaffManager\Models\CoachDetail;

class StaffCascadingSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $adminPerson = Person::create([
            'full_name' => 'Admin Test',
            'gender' => 'male',
            'type' => 'staff',
        ]);

        $this->adminUser = User::create([
            'person_id' => $adminPerson->id,
            'username' => 'admin_test_' . uniqid(),
            'password' => 'password123',
            'is_active' => true,
        ]);

        $role = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'sanctum',
        ]);
        $this->adminUser->assignRole($role);
        $this->actingAs($this->adminUser, 'sanctum');

        $club = Club::create(['name' => 'Gym Club', 'is_active' => true]);
        $this->branch = Branch::create(['club_id' => $club->id, 'name' => 'Main Branch', 'is_active' => true]);
    }

    public function test_staff_cascading_soft_delete_and_restore_via_api(): void
    {
        // 1. Arrange: Person -> User -> Staff -> Contract, Shift, Branch
        $staffPerson = Person::create([
            'full_name' => 'Receptionist Staff',
            'gender' => 'female',
            'type' => 'staff',
        ]);

        $staffUser = User::create([
            'person_id' => $staffPerson->id,
            'username' => 'reception_test_' . uniqid(),
            'password' => 'password123',
            'is_active' => true,
        ]);

        $staff = Staff::create([
            'person_id' => $staffPerson->id,
            'role' => 'receptionist',
            'is_active' => true,
            'start_date' => now(),
            'work_status' => 'active',
        ]);

        $staffBranch = StaffBranch::create([
            'staff_id' => $staff->id,
            'branch_id' => $this->branch->id,
        ]);

        $contract = StaffContract::create([
            'staff_id' => $staff->id,
            'employment_type' => 'fixed_salary',
            'base_salary' => 5000,
            'is_active' => true,
            'start_date' => now(),
        ]);

        $branchShift = BranchShift::create([
            'branch_id' => $this->branch->id,
            'name' => 'Morning Shift',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);

        $shift = StaffShift::create([
            'staff_id' => $staff->id,
            'branch_shift_id' => $branchShift->id,
        ]);

        // 2. Act: Delete Staff via API endpoint
        $response = $this->deleteJson("/api/v1/staff/{$staff->id}?confirmation=delete");
        $response->assertStatus(200);

        // 3. Assert: All cascaded items are soft-deleted
        $this->assertSoftDeleted('staff', ['id' => $staff->id]);
        $this->assertSoftDeleted('people', ['id' => $staffPerson->id]);
        $this->assertSoftDeleted('authentication_users', ['id' => $staffUser->id]);
        $this->assertSoftDeleted('staff_contracts', ['id' => $contract->id]);
        $this->assertSoftDeleted('staff_shifts', ['id' => $shift->id]);
        $this->assertSoftDeleted('staff_branches', ['id' => $staffBranch->id]);

        // 4. Act: Restore Staff via API endpoint
        $restoreResponse = $this->postJson("/api/v1/staff/{$staff->id}/restore");
        $restoreResponse->assertStatus(200);

        // 5. Assert: All cascaded items are restored
        $this->assertNotSoftDeleted('staff', ['id' => $staff->id]);
        $this->assertNotSoftDeleted('people', ['id' => $staffPerson->id]);
        $this->assertNotSoftDeleted('authentication_users', ['id' => $staffUser->id]);
        $this->assertNotSoftDeleted('staff_contracts', ['id' => $contract->id]);
        $this->assertNotSoftDeleted('staff_shifts', ['id' => $shift->id]);
        $this->assertNotSoftDeleted('staff_branches', ['id' => $staffBranch->id]);
    }

    public function test_coach_cascading_soft_delete_and_restore_via_api(): void
    {
        // 1. Arrange: Person -> User -> Staff (coach) -> CoachDetail
        $coachPerson = Person::create([
            'full_name' => 'Coach Ahmed',
            'gender' => 'male',
            'type' => 'coach',
        ]);

        $coachUser = User::create([
            'person_id' => $coachPerson->id,
            'username' => 'coach_test_' . uniqid(),
            'password' => 'password123',
            'is_active' => true,
        ]);

        $coach = Staff::create([
            'person_id' => $coachPerson->id,
            'role' => 'coach',
            'is_active' => true,
            'start_date' => now(),
            'work_status' => 'active',
        ]);

        $coachDetail = CoachDetail::create([
            'staff_id' => $coach->id,
            'bio' => 'Experienced Fitness Coach',
            'experience_years' => 5,
            'gym_type' => 'fitness',
        ]);

        // 2. Act: Delete Coach via API endpoint
        $response = $this->deleteJson("/api/v1/staff/{$coach->id}?confirmation=delete");
        $response->assertStatus(200);

        // 3. Assert: Soft deleted
        $this->assertSoftDeleted('staff', ['id' => $coach->id]);
        $this->assertSoftDeleted('people', ['id' => $coachPerson->id]);
        $this->assertSoftDeleted('authentication_users', ['id' => $coachUser->id]);
        $this->assertSoftDeleted('coach_details', ['id' => $coachDetail->id]);

        // 4. Act: Restore Coach via API endpoint
        $restoreResponse = $this->postJson("/api/v1/staff/{$coach->id}/restore");
        $restoreResponse->assertStatus(200);

        // 5. Assert: Restored
        $this->assertNotSoftDeleted('staff', ['id' => $coach->id]);
        $this->assertNotSoftDeleted('people', ['id' => $coachPerson->id]);
        $this->assertNotSoftDeleted('authentication_users', ['id' => $coachUser->id]);
        $this->assertNotSoftDeleted('coach_details', ['id' => $coachDetail->id]);
    }
}
