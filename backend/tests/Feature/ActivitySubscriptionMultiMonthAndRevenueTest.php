<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Branch;
use Modules\Sports\Models\Activity;
use Modules\Sports\Models\ActivityType;
use Modules\Sports\Models\StaffActivity;
use Modules\StaffManager\Models\Staff;
use Modules\StaffManager\Models\StaffContract;
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Modules\SubscriptionManager\Models\SubscriptionPlanActivity;
use Modules\SubscriptionManager\Models\PlayerSubscription;
use Modules\SubscriptionManager\Models\Invoice;
use Modules\SubscriptionManager\Models\Payment;
use Modules\MemberManager\Models\Member;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Laravel\Sanctum\Sanctum;

class ActivitySubscriptionMultiMonthAndRevenueTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $branch;
    protected $coach;
    protected $member;
    protected $standardActivity;

    protected function setUp(): void
    {
        parent::setUp();

        $person = Person::create([
            'full_name' => 'Admin Activity MultiMonth Test',
            'gender' => 'male',
            'type' => 'staff',
        ]);

        $this->user = User::create([
            'person_id' => $person->id,
            'username' => 'admin_act_mult_' . uniqid(),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $role = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'sanctum',
        ]);
        $this->user->assignRole($role);
        Sanctum::actingAs($this->user, ['*']);

        $club = Club::create(['name' => 'Sports Club', 'is_active' => true]);
        $this->branch = Branch::create(['club_id' => $club->id, 'name' => 'Sports Branch', 'is_active' => true]);

        // Create Safe
        $accountId = \Illuminate\Support\Facades\DB::table('acc_accounts')->insertGetId([
            'code' => '101' . rand(1000, 9999),
            'name' => 'Safe Account ' . uniqid(),
            'type' => 'asset',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('acc_safes')->insert([
            'branch_id' => $this->branch->id,
            'name' => 'Branch Safe',
            'account_id' => $accountId,
            'currency' => 'SYP',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Coach
        $coachPerson = Person::create(['full_name' => 'Coach Ahmad', 'gender' => 'male', 'type' => 'staff']);
        $this->coach = Staff::create([
            'person_id' => $coachPerson->id,
            'role' => 'coach',
            'work_status' => 'active',
            'is_active' => true,
        ]);

        // Member
        $memberPerson = Person::create(['full_name' => 'Omar Player', 'gender' => 'male', 'type' => 'player']);
        $this->member = Member::create([
            'person_id' => $memberPerson->id,
            'branch_id' => $this->branch->id,
            'member_number' => 'M-' . uniqid(),
            'status' => 'active',
        ]);

        // Standard Activity (Swimming / Football / etc.)
        $standardType = ActivityType::create([
            'name' => 'سباحة',
            'is_active' => true,
            'is_session_based' => false,
            'has_unlimited_subscribers' => true,
            'is_private_equipment' => false,
        ]);

        $this->standardActivity = Activity::create([
            'branch_id' => $this->branch->id,
            'activity_type_id' => $standardType->id,
            'name' => 'سباحة عامة',
            'is_private_equipment' => false,
            'is_active' => true,
        ]);
    }

    /**
     * Test issue 1 & 2:
     * Activity subscription of 500/month for 3 months (total 1500).
     * Player pays 1500.
     * Invoices must store total = 1500 (NOT 500).
     * Today's revenue in Subscriptions must be 1500 (NOT 0).
     */
    public function test_activity_subscription_multi_month_records_full_invoice_total_and_revenue(): void
    {
        $staffActivity = StaffActivity::create([
            'staff_id' => $this->coach->id,
            'activity_id' => $this->standardActivity->id,
        ]);

        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'اشتراك فعالية السباحة شهري',
            'base_price' => 500.00,
            'status' => 'active',
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $plan->id,
            'staff_activity_id' => $staffActivity->id,
        ]);

        $payload = [
            'member_id' => $this->member->id,
            'plan_id' => $plan->id,
            'months_count' => 3,
            'start_date' => now()->toDateString(),
            'paid_amount' => 1500.00,
            'receipt_number' => 'REC-ACT-001',
        ];

        $res = $this->postJson('/api/v1/player-subscriptions', $payload);
        $res->assertStatus(201);

        $subscriptionId = $res->json('data.id');
        $subscription = PlayerSubscription::with(['invoices.payments', 'revenueSplit'])->findOrFail($subscriptionId);

        // 1. Check Player Subscription amounts
        $this->assertEquals(1500.00, (float) $subscription->total_amount);
        $this->assertEquals(1500.00, (float) $subscription->paid_amount);
        $this->assertEquals(0.00, (float) $subscription->remaining_amount);

        // 2. Check Invoice
        $invoice = $subscription->invoices->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(1500.00, (float) $invoice->total, 'Invoice total must be 1500.00, not 500.00');
        $this->assertEquals('paid', $invoice->status);

        // 3. Check Payment
        $payment = $invoice->payments->first();
        $this->assertNotNull($payment);
        $this->assertEquals(1500.00, (float) $payment->amount);

        // 4. Check Today Revenue in Subscriptions
        $statsRes = $this->getJson('/api/v1/player-subscriptions?branch_id=' . $this->branch->id);
        $statsRes->assertStatus(200);
        $todayRevenue = (float) $statsRes->json('stats.today_revenue');
        $this->assertEquals(1500.00, $todayRevenue, "Today's revenue in subscriptions must be 1500.00, not 0.00");
    }

    /**
     * Test when plan has explicit coach_price and branch_price (e.g. coach: 0, branch: 500 or coach: 200, branch: 300)
     * For 3 months, invoice total must be 1500.
     */
    public function test_activity_subscription_with_explicit_split_pricing_multi_month(): void
    {
        $staffActivity = StaffActivity::create([
            'staff_id' => $this->coach->id,
            'activity_id' => $this->standardActivity->id,
        ]);

        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'اشتراك فعالية بسعر مقسم',
            'base_price' => 500.00,
            'coach_price' => 200.00,
            'branch_price' => 300.00,
            'status' => 'active',
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $plan->id,
            'staff_activity_id' => $staffActivity->id,
        ]);

        $payload = [
            'member_id' => $this->member->id,
            'plan_id' => $plan->id,
            'months_count' => 3,
            'start_date' => now()->toDateString(),
            'paid_amount' => 1500.00,
            'receipt_number' => 'REC-ACT-SPLIT-001',
        ];

        $res = $this->postJson('/api/v1/player-subscriptions', $payload);
        $res->assertStatus(201);

        $subscriptionId = $res->json('data.id');
        $subscription = PlayerSubscription::with('invoices')->findOrFail($subscriptionId);

        $invoice = $subscription->invoices->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(1500.00, (float) $invoice->total, 'Invoice total must be 1500.00 for 3 months with explicit split pricing');
        $this->assertEquals('paid', $invoice->status);
    }

    /**
     * Test updating months_count on an existing subscription updates invoice total
     */
    public function test_update_subscription_months_count_updates_invoice_total(): void
    {
        $staffActivity = StaffActivity::create([
            'staff_id' => $this->coach->id,
            'activity_id' => $this->standardActivity->id,
        ]);

        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'اشتراك قابل للتعديل',
            'base_price' => 500.00,
            'status' => 'active',
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $plan->id,
            'staff_activity_id' => $staffActivity->id,
        ]);

        // Initially 1 month
        $createRes = $this->postJson('/api/v1/player-subscriptions', [
            'member_id' => $this->member->id,
            'plan_id' => $plan->id,
            'months_count' => 1,
            'start_date' => now()->toDateString(),
            'paid_amount' => 500.00,
            'receipt_number' => 'REC-INIT-001',
        ]);
        $createRes->assertStatus(201);
        $subId = $createRes->json('data.id');

        $invoice = Invoice::where('player_subscription_id', $subId)->first();
        $this->assertEquals(500.00, (float) $invoice->total);

        // Update to 3 months
        $updateRes = $this->putJson("/api/v1/player-subscriptions/{$subId}", [
            'reason' => 'تعديل عدد الأشهر',
            'months_count' => 3,
            'paid_amount' => 1500.00,
        ]);
        $updateRes->assertStatus(200);

        $invoice->refresh();
        $this->assertEquals(1500.00, (float) $invoice->total, 'Invoice total must update to 1500.00 when months_count is updated to 3');
    }

    /**
     * Test Issue 2 specifically:
     * When a standard activity plan has a coach assigned, but the coach has NO contract commission
     * and branch has NO default commission, the club must receive 100% and today_revenue must be full amount.
     */
    public function test_standard_activity_with_coach_without_commission_gives_club_100_percent_revenue(): void
    {
        // Coach has no contract commission
        $staffActivity = StaffActivity::create([
            'staff_id' => $this->coach->id,
            'activity_id' => $this->standardActivity->id,
        ]);

        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'فعالية بدون نسبة مدرب',
            'base_price' => 500.00,
            'status' => 'active',
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $plan->id,
            'staff_activity_id' => $staffActivity->id,
        ]);

        $payload = [
            'member_id' => $this->member->id,
            'plan_id' => $plan->id,
            'months_count' => 1,
            'start_date' => now()->toDateString(),
            'paid_amount' => 500.00,
            'receipt_number' => 'REC-COACH-NO-COMM-001',
        ];

        $res = $this->postJson('/api/v1/player-subscriptions', $payload);
        $res->assertStatus(201);

        $subscriptionId = $res->json('data.id');
        $subscription = PlayerSubscription::with('revenueSplit')->findOrFail($subscriptionId);

        // Revenue split check if exists: club must have 100%, coach 0%
        if ($subscription->revenueSplit) {
            $this->assertEquals(100.00, (float) $subscription->revenueSplit->club_percentage);
            $this->assertEquals(0.00, (float) $subscription->revenueSplit->coach_percentage);
            $this->assertEquals(500.00, (float) $subscription->revenueSplit->club_amount);
            $this->assertEquals(0.00, (float) $subscription->revenueSplit->coach_amount);
        }

        // Today revenue check
        $statsRes = $this->getJson('/api/v1/player-subscriptions?branch_id=' . $this->branch->id);
        $statsRes->assertStatus(200);
        $todayRevenue = (float) $statsRes->json('stats.today_revenue');
        $this->assertEquals(500.00, $todayRevenue, "Today revenue in subscriptions must be 500.00, not 0.00");
    }
}
