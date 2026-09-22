<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Branch;
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Modules\SubscriptionManager\Models\PlayerSubscription;
use Modules\SubscriptionManager\Models\SubscriptionFreeze;
use Modules\SubscriptionManager\Models\Invoice;
use Modules\SubscriptionManager\Models\Payment;
use Modules\MemberManager\Models\Member;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\PersonContact;
use Modules\Authentication\Models\User;
use Modules\SubscriptionManager\Services\SubscriptionService;
use Modules\SubscriptionManager\Services\Reports\FrozenAndTerminatedReportService;
use Laravel\Sanctum\Sanctum;

class FrozenAndTerminatedReportFieldsTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $branch;
    protected $member;
    protected $memberUser;
    protected $safeId;
    protected $accountId;
    protected $plan;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'club_saas',
            'database.connections.mysql.host' => '127.0.0.1',
            'database.connections.mysql.username' => 'root',
            'database.connections.mysql.password' => '',
        ]);
        \Illuminate\Support\Facades\DB::purge('mysql');
        \Illuminate\Support\Facades\DB::reconnect('mysql');

        $person = Person::create([
            'full_name' => 'Admin User',
            'gender' => 'male',
            'type' => 'staff',
        ]);

        $this->user = User::create([
            'person_id' => $person->id,
            'username' => 'admin_ft_' . uniqid(),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $role = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'sanctum',
        ]);
        $this->user->assignRole($role);
        Sanctum::actingAs($this->user, ['*']);

        $club = Club::create(['name' => 'Gold Club', 'is_active' => true]);
        $this->branch = Branch::create(['club_id' => $club->id, 'name' => 'Main Branch', 'is_active' => true]);

        // Accounting Account and Safe
        $this->accountId = \Illuminate\Support\Facades\DB::table('acc_accounts')->insertGetId([
            'code' => '101' . rand(1000, 9999),
            'name' => 'صندوق الفرع الرئيسي',
            'type' => 'asset',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->safeId = \Illuminate\Support\Facades\DB::table('acc_safes')->insertGetId([
            'branch_id' => $this->branch->id,
            'name' => 'خزينة الفرع',
            'account_id' => $this->accountId,
            'currency' => 'SYP',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Member with Person, Contact, and User
        $memberPerson = Person::create([
            'full_name' => 'سامر الخطيب',
            'gender' => 'male',
            'type' => 'player',
        ]);

        PersonContact::create([
            'person_id' => $memberPerson->id,
            'name' => 'سامر الخطيب',
            'phone_number' => '0988112233',
            'relation' => 'self',
        ]);

        $this->memberUser = User::create([
            'person_id' => $memberPerson->id,
            'username' => 'samer_' . uniqid(),
            'custom_username' => 'samer_custom_' . uniqid(),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->member = Member::create([
            'person_id' => $memberPerson->id,
            'branch_id' => $this->branch->id,
            'member_number' => 'MEM-FT-' . uniqid(),
            'status' => 'active',
        ]);

        $this->plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'خطة اللياقة البدنية',
            'base_price' => 300.00,
            'session_count' => 12,
            'status' => 'active',
        ]);
    }

    public function test_cancelled_subscription_persists_reason_and_returns_account_and_phone(): void
    {
        $sub = PlayerSubscription::create([
            'member_id' => $this->member->id,
            'plan_id' => $this->plan->id,
            'months_count' => 1,
            'total_amount' => 300.00,
            'paid_amount' => 300.00,
            'remaining_amount' => 0.00,
            'currency' => 'SYP',
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'member_id' => $this->member->id,
            'branch_id' => $this->branch->id,
            'player_subscription_id' => $sub->id,
            'total' => 300.00,
            'currency' => 'SYP',
            'status' => 'paid',
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'receipt_number' => 'REC-CANCEL-TEST',
            'safe_id' => $this->safeId,
            'amount' => 300.00,
            'currency' => 'SYP',
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        // Cancel via subscription service with specific reason
        $cancellationReason = 'تم الإلغاء بطلب من الإدارة بسبب مخالفة التعليمات';
        $service = app(SubscriptionService::class);
        $updatedSub = $service->cancelSubscription($sub->id, $cancellationReason);

        $this->assertEquals('terminated', $updatedSub->status->value);
        $this->assertEquals($cancellationReason, $updatedSub->reason);

        // Fetch report
        $reportService = app(FrozenAndTerminatedReportService::class);
        $report = $reportService->getReport(['status' => 'terminated']);

        $this->assertNotEmpty($report['records']);
        $record = collect($report['records'])->firstWhere('subscription_id', $sub->id);
        $this->assertNotNull($record);

        // Verify account name, username, and custom username
        $this->assertEquals('صندوق الفرع الرئيسي', $record['account_name']);
        $this->assertEquals($this->memberUser->username, $record['username']);
        $this->assertEquals($this->memberUser->custom_username, $record['custom_username']);

        // Verify phone number from contacts
        $this->assertEquals('0988112233', $record['member_phone']);
        $this->assertNotEmpty($record['contact_persons']);
        $this->assertEquals('0988112233', $record['contact_persons'][0]['phone_number']);

        // Verify reason is NOT "غير مدون"
        $this->assertEquals($cancellationReason, $record['reason']);
        $this->assertEquals($cancellationReason, $record['termination_reason']);
        $this->assertNotNull($record['termination_details']);
        $this->assertEquals($cancellationReason, $record['termination_details']['reason']);
    }

    public function test_frozen_subscription_returns_reason_days_and_account(): void
    {
        $sub = PlayerSubscription::create([
            'member_id' => $this->member->id,
            'plan_id' => $this->plan->id,
            'months_count' => 1,
            'total_amount' => 300.00,
            'paid_amount' => 300.00,
            'remaining_amount' => 0.00,
            'currency' => 'SYP',
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->addDays(25)->toDateString(),
            'status' => 'frozen',
        ]);

        SubscriptionFreeze::create([
            'player_subscription_id' => $sub->id,
            'freeze_start_date' => now()->subDays(2)->toDateString(),
            'freeze_end_date' => now()->addDays(12)->toDateString(),
            'reason' => 'إصابة رياضية مؤقتة',
            'status' => 'active',
        ]);

        $reportService = app(FrozenAndTerminatedReportService::class);
        $report = $reportService->getReport(['status' => 'frozen']);

        $this->assertNotEmpty($report['records']);
        $record = collect($report['records'])->firstWhere('subscription_id', $sub->id);
        $this->assertNotNull($record);

        $this->assertEquals('frozen', $record['status']);
        $this->assertEquals('إصابة رياضية مؤقتة', $record['reason']);
        $this->assertEquals('إصابة رياضية مؤقتة', $record['freeze_reason']);
        $this->assertEquals($this->memberUser->username, $record['username']);
        $this->assertEquals('0988112233', $record['member_phone']);
        $this->assertGreaterThan(0, $record['frozen_days']);
    }
}
