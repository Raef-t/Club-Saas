<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\ClubManager\Models\Branch;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Locker;
use Modules\ClubManager\Services\LockerService;
use Modules\MemberManager\Models\Member;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LockerReleaseRefundTest extends TestCase
{
    use RefreshDatabase;

    protected $branch;
    protected $member;
    protected $lockerService;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $person = Person::create(['full_name' => 'Admin User', 'gender' => 'male', 'type' => 'staff']);
        $this->user = User::create([
            'person_id' => $person->id,
            'username' => 'admin_test_' . uniqid(),
            'password' => 'password123',
            'is_active' => true,
        ]);
        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'sanctum']);
        $this->user->assignRole($role);
        $this->actingAs($this->user, 'sanctum');

        $club = Club::create(['name' => 'Club Test']);
        $this->branch = Branch::create(['club_id' => $club->id, 'name' => 'Branch Test']);

        $memberPerson = Person::create(['full_name' => 'Member Test', 'gender' => 'male', 'type' => 'player']);
        $this->member = Member::create([
            'person_id' => $memberPerson->id,
            'branch_id' => $this->branch->id,
            'member_number' => 'M-' . uniqid(),
            'status' => 'active',
        ]);

        $this->lockerService = app(LockerService::class);
    }

    public function test_release_locker_with_no_refund(): void
    {
        $locker = Locker::create([
            'branch_id' => $this->branch->id,
            'locker_number' => 'L-201',
            'key_number' => 'K-201',
            'status' => 'with_member',
        ]);

        $resId = DB::table('locker_reservations')->insertGetId([
            'locker_id' => $locker->id,
            'member_id' => $this->member->id,
            'status' => 'active',
            'price' => 50,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->lockerService->releaseLocker($locker->id, 'Normal release', false);

        $locker->refresh();
        $this->assertEquals('available', $locker->status);

        $res = DB::table('locker_reservations')->where('id', $resId)->first();
        $this->assertEquals('expired', $res->status);
        $this->assertEquals('Normal release', $res->reason);
        $this->assertEquals(0, $res->is_refund);
        $this->assertNull($res->refund_amount);
    }

    public function test_release_locker_with_explicit_refund_amount(): void
    {
        $locker = Locker::create([
            'branch_id' => $this->branch->id,
            'locker_number' => 'L-202',
            'key_number' => 'K-202',
            'status' => 'with_member',
        ]);

        $resId = DB::table('locker_reservations')->insertGetId([
            'locker_id' => $locker->id,
            'member_id' => $this->member->id,
            'status' => 'active',
            'price' => 100,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->lockerService->releaseLocker($locker->id, 'Early release with refund', true, 60.00);

        $locker->refresh();
        $this->assertEquals('available', $locker->status);

        $res = DB::table('locker_reservations')->where('id', $resId)->first();
        $this->assertEquals('expired', $res->status);
        $this->assertEquals('Early release with refund', $res->reason);
        $this->assertEquals(1, $res->is_refund);
        $this->assertEquals(60.00, $res->refund_amount);
    }

    public function test_release_locker_with_is_refund_true_calculates_from_payments_and_invoice(): void
    {
        $locker = Locker::create([
            'branch_id' => $this->branch->id,
            'locker_number' => 'L-203',
            'key_number' => 'K-203',
            'status' => 'with_member',
        ]);

        // Create invoice
        $invoiceId = DB::table('invoices')->insertGetId([
            'member_id' => $this->member->id,
            'branch_id' => $this->branch->id,
            'total' => 75.00,
            'status' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create completed payment
        DB::table('payments')->insert([
            'invoice_id' => $invoiceId,
            'amount' => 75.00,
            'payment_method' => 'cash',
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $resId = DB::table('locker_reservations')->insertGetId([
            'locker_id' => $locker->id,
            'member_id' => $this->member->id,
            'invoice_id' => $invoiceId,
            'status' => 'active',
            'price' => 75.00,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // When refundAmount is not passed, it should automatically calculate 75.00 from invoice/payments
        $this->lockerService->releaseLocker($locker->id, 'Auto refund full price', true);

        $locker->refresh();
        $this->assertEquals('available', $locker->status);

        $res = DB::table('locker_reservations')->where('id', $resId)->first();
        $this->assertEquals('expired', $res->status);
        $this->assertEquals(1, $res->is_refund);
        $this->assertEquals(75.00, $res->refund_amount);
    }

    public function test_release_via_api_endpoint_with_is_refund_and_reason(): void
    {
        $locker = Locker::create([
            'branch_id' => $this->branch->id,
            'locker_number' => 'L-204',
            'key_number' => 'K-204',
            'status' => 'with_member',
        ]);

        $invoiceId = DB::table('invoices')->insertGetId([
            'member_id' => $this->member->id,
            'branch_id' => $this->branch->id,
            'total' => 50.00,
            'status' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $resId = DB::table('locker_reservations')->insertGetId([
            'locker_id' => $locker->id,
            'member_id' => $this->member->id,
            'invoice_id' => $invoiceId,
            'status' => 'active',
            'price' => 50.00,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(12)->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->deleteJson("/api/v1/lockers/{$locker->id}/reservations/current", [
            'reason' => 'Ending early and returning partial money',
            'is_refund' => true,
            'refund_amount' => 30.00,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'Locker released successfully.',
                 ]);

        $locker->refresh();
        $this->assertEquals('available', $locker->status);

        $res = DB::table('locker_reservations')->where('id', $resId)->first();
        $this->assertEquals('expired', $res->status);
        $this->assertEquals('Ending early and returning partial money', $res->reason);
        $this->assertEquals(1, $res->is_refund);
        $this->assertEquals(30.00, $res->refund_amount);
    }

    public function test_release_locker_early_requires_reason(): void
    {
        $locker = Locker::create([
            'branch_id' => $this->branch->id,
            'locker_number' => 'L-205',
            'key_number' => 'K-205',
            'status' => 'with_member',
        ]);

        DB::table('locker_reservations')->insert([
            'locker_id' => $locker->id,
            'member_id' => $this->member->id,
            'status' => 'active',
            'price' => 50,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->lockerService->releaseLocker($locker->id, '');
    }
}
