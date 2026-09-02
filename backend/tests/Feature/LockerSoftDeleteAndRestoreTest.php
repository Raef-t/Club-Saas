<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Branch;
use Modules\ClubManager\Models\Locker;
use Modules\MemberManager\Models\Member;
use Modules\SubscriptionManager\Models\LockerReservation;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Spatie\Permission\Models\Role;

class LockerSoftDeleteAndRestoreTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Club $club;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $person = Person::create(['full_name' => 'Admin User', 'gender' => 'male', 'type' => 'staff']);
        $this->user = User::create([
            'person_id' => $person->id,
            'username' => 'admin_test',
            'password' => 'password123',
            'is_active' => true,
        ]);
        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'sanctum']);
        $this->user->assignRole($role);
        $this->actingAs($this->user, 'sanctum');

        $this->club = Club::create(['name' => 'Test Club', 'is_active' => true]);
        $this->branch = Branch::create(['club_id' => $this->club->id, 'name' => 'Main Branch', 'is_active' => true]);
    }

    public function test_delete_locker_requires_confirmation_delete(): void
    {
        $locker = Locker::create([
            'branch_id' => $this->branch->id,
            'locker_number' => '101',
            'key_number' => '101',
            'status' => 'available',
        ]);

        // Attempt delete without confirmation -> should fail with 422
        $response = $this->deleteJson("/api/v1/lockers/{$locker->id}");
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['confirmation']);

        // Attempt delete with wrong confirmation -> should fail with 422
        $response = $this->deleteJson("/api/v1/lockers/{$locker->id}", ['confirmation' => 'yes']);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['confirmation']);

        // Attempt delete with valid confirmation -> should succeed with 200
        $response = $this->deleteJson("/api/v1/lockers/{$locker->id}", ['confirmation' => 'delete']);
        $response->assertStatus(200);

        $this->assertSoftDeleted('lockers', ['id' => $locker->id]);
    }

    public function test_delete_locker_succeeds_even_when_reserved_and_cascades_reservation(): void
    {
        $locker = Locker::create([
            'branch_id' => $this->branch->id,
            'locker_number' => '102',
            'key_number' => '102',
            'status' => 'with_member',
        ]);

        $memberPerson = Person::create(['full_name' => 'Player 1', 'gender' => 'male', 'type' => 'player']);
        $member = Member::create([
            'branch_id' => $this->branch->id,
            'person_id' => $memberPerson->id,
            'member_number' => 'M-102',
            'membership_status' => 'active',
            'join_date' => now(),
        ]);

        $reservation = LockerReservation::create([
            'locker_id' => $locker->id,
            'member_id' => $member->id,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'price' => 30000,
            'status' => 'active',
        ]);

        // Delete with confirmation 'delete'
        $response = $this->deleteJson("/api/v1/lockers/{$locker->id}", ['confirmation' => 'delete']);
        $response->assertStatus(200);

        $this->assertSoftDeleted('lockers', ['id' => $locker->id]);
        $this->assertSoftDeleted('locker_reservations', ['id' => $reservation->id]);
    }

    public function test_active_index_and_summary_exclude_soft_deleted_lockers(): void
    {
        $activeLocker = Locker::create([
            'branch_id' => $this->branch->id,
            'locker_number' => '103',
            'key_number' => '103',
            'status' => 'available',
        ]);

        $deletedLocker = Locker::create([
            'branch_id' => $this->branch->id,
            'locker_number' => '104',
            'key_number' => '104',
            'status' => 'available',
        ]);

        // Soft delete the second locker
        $this->deleteJson("/api/v1/lockers/{$deletedLocker->id}", ['confirmation' => 'delete'])
            ->assertStatus(200);

        // GET /v1/lockers should only contain active locker
        $response = $this->getJson("/api/v1/lockers?branch_id={$this->branch->id}&per_page=all");
        $response->assertStatus(200);

        $lockersData = $response->json('data.lockers');
        $this->assertCount(1, $lockersData);
        $this->assertEquals('103', $lockersData[0]['locker_number']);

        // Summary should reflect only 1 available locker
        $summary = $response->json('data.summary');
        $this->assertEquals(1, $summary['available_lockers_count']);
        $this->assertEquals(0, $summary['unavailable_lockers_count']);
    }

    public function test_trashed_endpoint_returns_soft_deleted_lockers(): void
    {
        $locker = Locker::create([
            'branch_id' => $this->branch->id,
            'locker_number' => '105',
            'key_number' => '105',
            'status' => 'available',
        ]);

        $this->deleteJson("/api/v1/lockers/{$locker->id}", ['confirmation' => 'delete'])
            ->assertStatus(200);

        $response = $this->getJson("/api/v1/lockers/trashed?branch_id={$this->branch->id}");
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('105', $data[0]['locker_number']);
    }

    public function test_restore_endpoint_restores_locker_and_reservations(): void
    {
        $locker = Locker::create([
            'branch_id' => $this->branch->id,
            'locker_number' => '106',
            'key_number' => '106',
            'status' => 'available',
        ]);

        $reservation = LockerReservation::create([
            'locker_id' => $locker->id,
            'start_date' => now(),
            'price' => 0,
            'status' => 'active',
        ]);

        // Delete
        $this->deleteJson("/api/v1/lockers/{$locker->id}", ['confirmation' => 'delete'])
            ->assertStatus(200);

        $this->assertSoftDeleted('lockers', ['id' => $locker->id]);
        $this->assertSoftDeleted('locker_reservations', ['id' => $reservation->id]);

        // Restore
        $restoreResponse = $this->postJson("/api/v1/lockers/{$locker->id}/restore");
        $restoreResponse->assertStatus(200);

        $this->assertNotSoftDeleted('lockers', ['id' => $locker->id]);
        $this->assertNotSoftDeleted('locker_reservations', ['id' => $reservation->id]);
    }

    public function test_restore_fails_if_duplicate_locker_number_exists_in_branch(): void
    {
        $locker1 = Locker::create([
            'branch_id' => $this->branch->id,
            'locker_number' => '107',
            'key_number' => '107',
            'status' => 'available',
        ]);

        // Delete locker1
        $this->deleteJson("/api/v1/lockers/{$locker1->id}", ['confirmation' => 'delete'])
            ->assertStatus(200);

        // Create new locker2 with the same locker number in the same branch
        $locker2 = Locker::create([
            'branch_id' => $this->branch->id,
            'locker_number' => '107',
            'key_number' => '107-B',
            'status' => 'available',
        ]);

        // Attempt to restore locker1 -> should fail because number 107 is occupied
        $restoreResponse = $this->postJson("/api/v1/lockers/{$locker1->id}/restore");
        $restoreResponse->assertStatus(422);

        $this->assertSoftDeleted('lockers', ['id' => $locker1->id]);
    }
}
