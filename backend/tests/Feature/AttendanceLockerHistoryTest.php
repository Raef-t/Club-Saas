<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Branch;
use Modules\ClubManager\Models\Locker;
use Modules\MemberManager\Models\Member;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Modules\AttendanceManager\Models\Attendance;
use Laravel\Sanctum\Sanctum;

class AttendanceLockerHistoryTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $branch;
    protected $member;
    protected $locker2;
    protected $locker10;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'club_db',
            'database.connections.mysql.host' => '127.0.0.1',
            'database.connections.mysql.username' => 'root',
            'database.connections.mysql.password' => '',
        ]);
        \Illuminate\Support\Facades\DB::purge('mysql');
        \Illuminate\Support\Facades\DB::reconnect('mysql');

        $person = Person::create([
            'full_name' => 'Admin Test User',
            'gender' => 'male',
            'type' => 'staff',
        ]);

        $this->user = User::create([
            'person_id' => $person->id,
            'username' => 'test_admin_' . uniqid(),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $role = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'sanctum',
        ]);
        $this->user->assignRole($role);
        Sanctum::actingAs($this->user, ['*']);

        $club = Club::firstOrCreate(['id' => 1], [
            'name' => 'Test Club',
        ]);

        $this->branch = Branch::firstOrCreate(['id' => 1], [
            'club_id' => $club->id,
            'name' => 'Main Branch',
        ]);

        $memberPerson = Person::create([
            'full_name' => 'تجربة لاعبة محمد',
            'gender' => 'female',
            'type' => 'player',
        ]);

        $this->member = Member::create([
            'branch_id' => $this->branch->id,
            'person_id' => $memberPerson->id,
            'member_number' => 'M-' . uniqid(),
            'join_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->locker2 = Locker::create([
            'branch_id' => $this->branch->id,
            'locker_number' => '2',
            'status' => 'available',
        ]);

        $this->locker10 = Locker::create([
            'branch_id' => $this->branch->id,
            'locker_number' => '10',
            'status' => 'available',
        ]);
    }

    public function test_attendance_history_retains_correct_lockers_for_multiple_visits(): void
    {
        // 1. Visit 1 at 10:00 AM with Locker #2
        $response1 = $this->postJson('/api/v1/attendances/check-in', [
            'attendable_type' => 'member',
            'attendable_id'   => $this->member->id,
            'branch_id'       => $this->branch->id,
            'locker_id'       => $this->locker2->id,
            'check_in_at'     => '2026-08-18 10:00:00',
        ]);
        $response1->assertStatus(200);
        $attendanceId1 = $response1->json('data.id');

        // Check out at 11:00 AM
        $checkOutResponse1 = $this->postJson("/api/v1/attendances/check-out/{$attendanceId1}", [
            'check_out_at' => '2026-08-18 11:00:00',
        ]);
        $checkOutResponse1->assertStatus(200);

        // 2. Visit 2 at 12:00 PM (or 14:00) with Locker #10
        $response2 = $this->postJson('/api/v1/attendances/check-in', [
            'attendable_type' => 'member',
            'attendable_id'   => $this->member->id,
            'branch_id'       => $this->branch->id,
            'locker_id'       => $this->locker10->id,
            'check_in_at'     => '2026-08-18 12:00:00',
        ]);
        $response2->assertStatus(200);
        $attendanceId2 = $response2->json('data.id');

        // 3. Query Attendance History
        $historyResponse = $this->getJson("/api/v1/attendances/history?attendable_type=member&attendable_id={$this->member->id}");
        $historyResponse->assertStatus(200);

        $records = collect($historyResponse->json('data'));
        $record1 = $records->firstWhere('id', $attendanceId1);
        $record2 = $records->firstWhere('id', $attendanceId2);

        $this->assertNotNull($record1);
        $this->assertNotNull($record2);

        // Visit 1 should have locker 2
        $this->assertEquals($this->locker2->id, $record1['locker_id']);
        $this->assertEquals('2', $record1['locker_number']);

        // Visit 2 should have locker 10
        $this->assertEquals($this->locker10->id, $record2['locker_id']);
        $this->assertEquals('10', $record2['locker_number']);
    }

    public function test_locker_assigned_after_checkin_links_to_open_attendance(): void
    {
        // 1. Check in without locker
        $response = $this->postJson('/api/v1/attendances/check-in', [
            'attendable_type' => 'member',
            'attendable_id'   => $this->member->id,
            'branch_id'       => $this->branch->id,
            'check_in_at'     => '2026-08-18 10:00:00',
        ]);
        $response->assertStatus(200);
        $attendanceId = $response->json('data.id');

        // 2. Reserve / assign locker #2 to member
        $reserveResponse = $this->postJson("/api/v1/lockers/{$this->locker2->id}/reservations", [
            'reservation_type' => 'assign',
            'holder_type'      => 'member',
            'holder_id'        => $this->member->id,
            'start_date'       => '2026-08-18',
        ]);
        $reserveResponse->assertStatus(200);

        // 3. Attendance record should now be linked to locker #2
        $attendance = Attendance::find($attendanceId);
        $this->assertEquals($this->locker2->id, $attendance->locker_id);

        // 4. Query history
        $historyResponse = $this->getJson("/api/v1/attendances/history?attendable_type=member&attendable_id={$this->member->id}");
        $historyResponse->assertStatus(200);
        $records = collect($historyResponse->json('data'));
        $record = $records->firstWhere('id', $attendanceId);

        $this->assertEquals($this->locker2->id, $record['locker_id']);
        $this->assertEquals('2', $record['locker_number']);
    }
}
