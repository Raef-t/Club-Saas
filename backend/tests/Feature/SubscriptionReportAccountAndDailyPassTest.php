<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Branch;
use Modules\Sports\Models\Activity;
use Modules\Sports\Models\ActivityType;
use Modules\Sports\Models\StaffActivity;
use Modules\StaffManager\Models\Staff;
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Modules\SubscriptionManager\Models\SubscriptionPlanActivity;
use Modules\SubscriptionManager\Models\PlayerSubscription;
use Modules\SubscriptionManager\Models\Invoice;
use Modules\SubscriptionManager\Models\Payment;
use Modules\MemberManager\Models\Member;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\PersonContact;
use Modules\Authentication\Models\User;
use Modules\SubscriptionManager\Services\Reports\AllSubscriptionsReportService;
use Modules\SubscriptionManager\Services\Reports\RenewalReportService;
use Laravel\Sanctum\Sanctum;

class SubscriptionReportAccountAndDailyPassTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $branch;
    protected $member;
    protected $coach;
    protected $safeId;
    protected $accountId;

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
            'full_name' => 'Super Admin',
            'gender' => 'male',
            'type' => 'staff',
        ]);

        $this->user = User::create([
            'person_id' => $person->id,
            'username' => 'admin_rep_' . uniqid(),
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
            'name' => 'صندوق الصالة الرئيسي',
            'type' => 'asset',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->safeId = \Illuminate\Support\Facades\DB::table('acc_safes')->insertGetId([
            'branch_id' => $this->branch->id,
            'name' => 'خزينة الصالة',
            'account_id' => $this->accountId,
            'currency' => 'SYP',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Coach
        $coachPerson = Person::create([
            'full_name' => 'الكابتن رامي المنصور',
            'gender' => 'male',
            'type' => 'staff',
        ]);
        $this->coach = Staff::create([
            'person_id' => $coachPerson->id,
            'role' => 'coach',
            'work_status' => 'active',
            'is_active' => true,
        ]);

        // Member
        $memberPerson = Person::create([
            'full_name' => 'هدى خالد',
            'gender' => 'female',
            'type' => 'player',
        ]);
        PersonContact::create([
            'person_id' => $memberPerson->id,
            'name' => 'هدى خالد',
            'phone_number' => '0999888777',
            'relation' => 'self',
        ]);
        $this->memberUser = User::create([
            'person_id' => $memberPerson->id,
            'username' => 'huda_' . uniqid(),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->member = Member::create([
            'person_id' => $memberPerson->id,
            'branch_id' => $this->branch->id,
            'member_number' => 'MEM-' . uniqid(),
            'status' => 'active',
        ]);
    }

    public function test_all_subscriptions_report_returns_account_name_safe_name_and_coach_fields(): void
    {
        $activityType = ActivityType::create([
            'name' => 'لياقة خاصة',
            'is_active' => true,
            'is_private_equipment' => true,
        ]);

        $activity = Activity::create([
            'branch_id' => $this->branch->id,
            'activity_type_id' => $activityType->id,
            'name' => 'تدريب خاص',
            'is_private_equipment' => true,
            'is_active' => true,
        ]);

        $staffActivity = StaffActivity::create([
            'staff_id' => $this->coach->id,
            'activity_id' => $activity->id,
        ]);

        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'خطة برايفت أجهزة',
            'base_price' => 500.00,
            'coach_price' => 225.00,
            'branch_price' => 275.00,
            'session_count' => 12,
            'status' => 'active',
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $plan->id,
            'staff_activity_id' => $staffActivity->id,
        ]);

        $sub = PlayerSubscription::create([
            'member_id' => $this->member->id,
            'plan_id' => $plan->id,
            'months_count' => 1,
            'total_amount' => 500.00,
            'paid_amount' => 500.00,
            'remaining_amount' => 0.00,
            'currency' => 'SYP',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'active',
        ]);

        $sub->items()->create([
            'sessions_allocated' => 12,
            'sessions_consumed' => 2,
            'is_unlimited' => false,
        ]);

        $invoice = Invoice::create([
            'member_id' => $this->member->id,
            'branch_id' => $this->branch->id,
            'player_subscription_id' => $sub->id,
            'total' => 500.00,
            'currency' => 'SYP',
            'status' => 'paid',
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'receipt_number' => 'REC-CLUB-TEST',
            'safe_id' => $this->safeId,
            'amount' => 275.00,
            'currency' => 'SYP',
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        $service = app(AllSubscriptionsReportService::class);
        $report = $service->getReport(['search' => 'هدى خالد']);

        $this->assertNotEmpty($report['records']);
        $record = $report['records'][0];

        $this->assertEquals('صندوق الصالة الرئيسي', $record['account_name']);
        $this->assertEquals('خزينة الصالة', $record['safe_name']);
        $this->assertEquals($this->memberUser->username, $record['username']);
        $this->assertEquals('0999888777', $record['member_phone']);
        $this->assertFalse($record['is_daily_entry']);

        // Check coach inside item breakdown
        $this->assertNotEmpty($record['items']);
        $item = $record['items'][0];
        $this->assertEquals('الكابتن رامي المنصور', $item['coach_name']);
        $this->assertNotNull($item['coach']);
        $this->assertEquals('الكابتن رامي المنصور', $item['coach']['name']);
        $this->assertNotEmpty($item['coach']['first_name']);
        $this->assertNotEmpty($item['coach']['last_name']);
    }

    public function test_renewal_report_excludes_daily_entry_plans(): void
    {
        $dailyType = ActivityType::create([
            'name' => 'دخولية يومية تجريبية',
            'is_active' => true,
            'is_daily_entry' => true,
        ]);

        $activity = Activity::create([
            'branch_id' => $this->branch->id,
            'activity_type_id' => $dailyType->id,
            'name' => 'دخول يومي تجريبي',
            'is_active' => true,
        ]);

        $staffActivity = StaffActivity::create([
            'staff_id' => $this->coach->id,
            'activity_id' => $activity->id,
        ]);

        $dailyPlan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'دخول يومي عام',
            'base_price' => 50.00,
            'session_count' => 1,
            'status' => 'active',
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $dailyPlan->id,
            'staff_activity_id' => $staffActivity->id,
        ]);

        $dailySub = PlayerSubscription::create([
            'member_id' => $this->member->id,
            'plan_id' => $dailyPlan->id,
            'months_count' => 1,
            'total_amount' => 50.00,
            'paid_amount' => 50.00,
            'remaining_amount' => 0.00,
            'currency' => 'SYP',
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->subDays(4)->toDateString(),
            'status' => 'finished',
        ]);

        $service = app(RenewalReportService::class);
        $report = $service->getReport(['search' => 'هدى خالد']);

        // Daily entry should NOT appear in renewal report
        $dailyRecords = collect($report['records'])->filter(fn($r) => $r['subscription_id'] === $dailySub->id);
        $this->assertTrue($dailyRecords->isEmpty());
    }

    public function test_player_subscription_resource_outputs_account_safe_and_coach_details(): void
    {
        $activityType = ActivityType::create([
            'name' => 'لياقة خاصة 2',
            'is_active' => true,
            'is_private_equipment' => true,
        ]);

        $activity = Activity::create([
            'branch_id' => $this->branch->id,
            'activity_type_id' => $activityType->id,
            'name' => 'تدريب خاص 2',
            'is_private_equipment' => true,
            'is_active' => true,
        ]);

        $staffActivity = StaffActivity::create([
            'staff_id' => $this->coach->id,
            'activity_id' => $activity->id,
        ]);

        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'خطة برايفت أجهزة 2',
            'base_price' => 500.00,
            'coach_price' => 225.00,
            'branch_price' => 275.00,
            'session_count' => 12,
            'status' => 'active',
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $plan->id,
            'staff_activity_id' => $staffActivity->id,
        ]);

        $sub = PlayerSubscription::create([
            'member_id' => $this->member->id,
            'plan_id' => $plan->id,
            'months_count' => 1,
            'total_amount' => 500.00,
            'paid_amount' => 275.00,
            'remaining_amount' => 225.00,
            'currency' => 'SYP',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'active',
        ]);

        \Modules\SubscriptionManager\Models\PlayerSubscriptionItem::create([
            'player_subscription_id' => $sub->id,
            'sessions_allocated' => 12,
            'sessions_consumed' => 0,
            'is_unlimited' => false,
        ]);

        $invoice = Invoice::create([
            'member_id' => $this->member->id,
            'branch_id' => $this->branch->id,
            'player_subscription_id' => $sub->id,
            'total' => 500.00,
            'currency' => 'SYP',
            'status' => 'paid',
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'receipt_number' => 'REC-CLUB-TEST-2',
            'amount' => 275.00,
            'safe_id' => $this->safeId,
            'currency' => 'SYP',
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        $sub->load(['plan.planActivities.staffActivity.staff.person', 'plan.planActivities.staffActivity.activity', 'payments.safe.account', 'items', 'member.person.user', 'member.person.contacts']);

        $resource = (new \Modules\SubscriptionManager\Http\Resources\PlayerSubscriptionResource($sub))->toArray(request());

        $this->assertEquals('صندوق الصالة الرئيسي', $resource['account_name']);
        $this->assertEquals('خزينة الصالة', $resource['safe_name']);
        $this->assertNotEmpty($resource['items']);
        $itemResource = $resource['items'][0];
        $this->assertNotNull($itemResource['coach']);
        $this->assertEquals('الكابتن رامي المنصور', $itemResource['coach']['name']);
        $this->assertNotEmpty($itemResource['coach']['first_name']);
        $this->assertNotEmpty($itemResource['coach']['last_name']);
        $this->assertEquals('0999888777', $resource['member']['person']['phone']);
    }
}
