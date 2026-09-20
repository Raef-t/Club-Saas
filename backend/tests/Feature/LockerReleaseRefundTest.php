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

    public function test_release_locker_with_refund_creates_accounting_payment_voucher(): void
    {
        // 1. Accounting setup
        $safeAccount = \Modules\Accounting\Models\AccAccount::create([
            'code' => '1101',
            'name' => 'صندوق الفرع التجريبي',
            'type' => 'asset',
            'is_leaf' => true,
        ]);

        $revenueAccount = \Modules\Accounting\Models\AccAccount::create([
            'code' => '4100',
            'name' => 'إيراد تأجير الخزائن',
            'type' => 'revenue',
            'is_leaf' => true,
        ]);

        $period = \Modules\Accounting\Models\AccPeriod::create([
            'branch_id' => $this->branch->id,
            'name' => 'فترة تجريبية 2026',
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'status' => 'open',
        ]);

        $safe = \Modules\Accounting\Models\AccSafe::create([
            'name' => 'خزينة الاستقبال الرئيسية',
            'account_id' => $safeAccount->id,
            'currency' => 'SYP',
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        // 2. Locker & Reservation with Invoice and Payment
        $locker = Locker::create([
            'branch_id' => $this->branch->id,
            'locker_number' => 'L-900',
            'key_number' => 'K-900',
            'status' => 'with_member',
        ]);

        $invoiceId = DB::table('invoices')->insertGetId([
            'member_id' => $this->member->id,
            'branch_id' => $this->branch->id,
            'total' => 100.00,
            'status' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('payments')->insert([
            'invoice_id' => $invoiceId,
            'safe_id' => $safe->id,
            'amount' => 100.00,
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
            'price' => 100.00,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Release with refund
        $this->lockerService->releaseLocker($locker->id, 'Member cancelled early', true, 40.00);

        // 4. Assert reservation updated with refund & safe
        $res = DB::table('locker_reservations')->where('id', $resId)->first();
        $this->assertEquals(1, $res->is_refund);
        $this->assertEquals(40.00, $res->refund_amount);
        $this->assertEquals($safe->id, $res->refund_safe_id);

        // 5. Assert Accounting Journal Voucher (PV - سند صرف) created
        $journal = DB::table('acc_journals')
            ->where('source_type', 'locker_reservation_refund')
            ->where('source_id', $resId)
            ->first();

        $this->assertNotNull($journal, 'Payment voucher PV journal should be created');
        $this->assertEquals('PV', $journal->type);
        $this->assertEquals('posted', $journal->status);
        $this->assertEquals($safe->id, $journal->safe_id);
        $this->assertStringContainsString('سند صرف: استرجاع تأجير', $journal->description);

        // 6. Assert Journal Entries (Debit Revenue 40, Credit Safe 40)
        $entries = DB::table('acc_journal_entries')
            ->where('journal_id', $journal->id)
            ->get();

        $this->assertCount(2, $entries);

        $revenueEntry = $entries->firstWhere('account_id', $revenueAccount->id);
        $safeEntry = $entries->firstWhere('account_id', $safeAccount->id);

        $this->assertNotNull($revenueEntry);
        $this->assertNotNull($safeEntry);

        // Revenue account debited (reduces income)
        $this->assertEquals(40.00, (float) $revenueEntry->debit_syp);
        $this->assertEquals(0, (float) $revenueEntry->credit_syp);

        // Safe account credited (reduces safe cash balance)
        $this->assertEquals(0, (float) $safeEntry->debit_syp);
        $this->assertEquals(40.00, (float) $safeEntry->credit_syp);
    }

    public function test_release_via_api_with_explicit_safe_id(): void
    {
        $safeAccount = \Modules\Accounting\Models\AccAccount::create([
            'code' => '1102',
            'name' => 'صندوق إضافي',
            'type' => 'asset',
            'is_leaf' => true,
        ]);

        \Modules\Accounting\Models\AccAccount::create([
            'code' => '4100',
            'name' => 'إيراد تأجير الخزائن',
            'type' => 'revenue',
            'is_leaf' => true,
        ]);

        \Modules\Accounting\Models\AccPeriod::create([
            'branch_id' => $this->branch->id,
            'name' => 'فترة 2026',
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'status' => 'open',
        ]);

        $customSafe = \Modules\Accounting\Models\AccSafe::create([
            'name' => 'صندوق استرداد خاص',
            'account_id' => $safeAccount->id,
            'currency' => 'SYP',
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $locker = Locker::create([
            'branch_id' => $this->branch->id,
            'locker_number' => 'L-901',
            'key_number' => 'K-901',
            'status' => 'with_member',
        ]);

        $resId = DB::table('locker_reservations')->insertGetId([
            'locker_id' => $locker->id,
            'member_id' => $this->member->id,
            'status' => 'active',
            'price' => 80.00,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->deleteJson("/api/v1/lockers/{$locker->id}/reservations/current", [
            'reason' => 'Ending early with specific safe',
            'is_refund' => true,
            'refund_amount' => 50.00,
            'safe_id' => $customSafe->id,
        ]);

        $response->assertStatus(200);

        $res = DB::table('locker_reservations')->where('id', $resId)->first();
        $this->assertEquals(50.00, $res->refund_amount);
        $this->assertEquals($customSafe->id, $res->refund_safe_id);

        $journal = DB::table('acc_journals')
            ->where('source_type', 'locker_reservation_refund')
            ->where('source_id', $resId)
            ->first();

        $this->assertNotNull($journal);
        $this->assertEquals('PV', $journal->type);
        $this->assertEquals($customSafe->id, $journal->safe_id);
    }
}
