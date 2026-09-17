<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Branch;
use Modules\ClubManager\Models\BranchShift;
use Modules\ClubManager\Models\BranchSetting;

class BranchShiftOvernightTest extends TestCase
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
            'username' => 'admin_' . uniqid(),
            'password' => 'password123',
            'is_active' => true,
        ]);

        $role = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'sanctum',
        ]);
        $this->adminUser->assignRole($role);
        $this->actingAs($this->adminUser, 'sanctum');

        $club = Club::create(['name' => 'Test Club', 'is_active' => true]);
        $this->branch = Branch::create(['club_id' => $club->id, 'name' => 'Main Branch', 'is_active' => true]);
    }

    public function test_can_create_overnight_branch_shift(): void
    {
        // 18:00 (06:00 PM) to 01:00 (01:00 AM)
        $response = $this->postJson("/api/v1/branches/{$this->branch->id}/shifts", [
            'name' => 'Evening Night Shift',
            'start_time' => '18:00',
            'end_time' => '01:00',
            'gender_allowed' => 'mixed',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('branch_shifts', [
            'branch_id' => $this->branch->id,
            'name' => 'Evening Night Shift',
            'start_time' => '18:00',
            'end_time' => '01:00',
        ]);
    }

    public function test_can_create_adjacent_non_overlapping_shifts_crossing_midnight(): void
    {
        // Shift 1: 08:00 to 16:00
        $res1 = $this->postJson("/api/v1/branches/{$this->branch->id}/shifts", [
            'name' => 'Morning Shift',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'gender_allowed' => 'mixed',
        ]);
        $res1->assertStatus(201);

        // Shift 2: 16:00 to 01:00
        $res2 = $this->postJson("/api/v1/branches/{$this->branch->id}/shifts", [
            'name' => 'Evening Shift',
            'start_time' => '16:00',
            'end_time' => '01:00',
            'gender_allowed' => 'mixed',
        ]);
        $res2->assertStatus(201);

        // Shift 3: 01:00 to 08:00
        $res3 = $this->postJson("/api/v1/branches/{$this->branch->id}/shifts", [
            'name' => 'Graveyard Shift',
            'start_time' => '01:00',
            'end_time' => '08:00',
            'gender_allowed' => 'mixed',
        ]);
        $res3->assertStatus(201);
    }

    public function test_detects_overlap_with_overnight_shift_in_late_night(): void
    {
        // Shift 1: 18:00 to 01:00
        BranchShift::create([
            'branch_id' => $this->branch->id,
            'name' => 'Evening Shift',
            'start_time' => '18:00',
            'end_time' => '01:00',
            'gender_allowed' => 'mixed',
        ]);

        // Shift 2: 00:30 to 06:00 (overlaps with 00:00 to 01:00 of Shift 1)
        $response = $this->postJson("/api/v1/branches/{$this->branch->id}/shifts", [
            'name' => 'Early Shift',
            'start_time' => '00:30',
            'end_time' => '06:00',
            'gender_allowed' => 'mixed',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_time']);
    }

    public function test_detects_overlap_with_overnight_shift_in_evening(): void
    {
        // Shift 1: 18:00 to 01:00
        BranchShift::create([
            'branch_id' => $this->branch->id,
            'name' => 'Evening Shift',
            'start_time' => '18:00',
            'end_time' => '01:00',
            'gender_allowed' => 'mixed',
        ]);

        // Shift 2: 20:00 to 02:00 (overlaps with 18:00 to 24:00 of Shift 1)
        $response = $this->postJson("/api/v1/branches/{$this->branch->id}/shifts", [
            'name' => 'Late Evening Shift',
            'start_time' => '20:00',
            'end_time' => '02:00',
            'gender_allowed' => 'mixed',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_time']);
    }

    public function test_can_update_shift_to_overnight(): void
    {
        $shift = BranchShift::create([
            'branch_id' => $this->branch->id,
            'name' => 'Regular Shift',
            'start_time' => '14:00',
            'end_time' => '22:00',
            'gender_allowed' => 'mixed',
        ]);

        $response = $this->putJson("/api/v1/branches/{$this->branch->id}/shifts/{$shift->id}", [
            'start_time' => '18:00',
            'end_time' => '01:00',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('branch_shifts', [
            'id' => $shift->id,
            'start_time' => '18:00',
            'end_time' => '01:00',
        ]);
    }

    public function test_can_update_branch_settings_to_overnight_working_hours(): void
    {
        $response = $this->putJson("/api/v1/branches/{$this->branch->id}/settings", [
            'working_hours_start' => '08:00',
            'working_hours_end' => '01:00',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('branch_settings', [
            'branch_id' => $this->branch->id,
            'working_hours_start' => '08:00',
            'working_hours_end' => '01:00',
        ]);
    }

    public function test_rejects_identical_start_and_end_time(): void
    {
        $responseShift = $this->postJson("/api/v1/branches/{$this->branch->id}/shifts", [
            'name' => 'Zero Duration Shift',
            'start_time' => '08:00',
            'end_time' => '08:00',
            'gender_allowed' => 'mixed',
        ]);
        $responseShift->assertStatus(422)
            ->assertJsonValidationErrors(['end_time']);

        $responseSettings = $this->putJson("/api/v1/branches/{$this->branch->id}/settings", [
            'working_hours_start' => '08:00',
            'working_hours_end' => '08:00',
        ]);
        $responseSettings->assertStatus(422)
            ->assertJsonValidationErrors(['working_hours_end']);
    }
}
