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
use Modules\SubscriptionManager\Models\SubscriptionRevenueSplit;
use Modules\MemberManager\Models\Member;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Laravel\Sanctum\Sanctum;

class FinancialEntitiesCurrencyTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $branch;
    protected $member;
    protected $coach;
    protected $activity;
    protected $staffActivity;

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

        $club = Club::create([
            'name' => 'Test Club ' . uniqid(),
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'club_id' => $club->id,
            'name' => 'Main Branch',
            'status' => 'active',
        ]);

        // Safes
        $accountId = \Illuminate\Support\Facades\DB::table('acc_accounts')->insertGetId([
            'code' => '101' . rand(1000, 9999),
            'name' => 'Safe Account ' . uniqid(),
            'type' => 'asset',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('acc_safes')->insert([
            'branch_id' => $this->branch->id,
            'name' => 'SYP Safe',
            'account_id' => $accountId,
            'currency' => 'SYP',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('acc_safes')->insert([
            'branch_id' => $this->branch->id,
            'name' => 'USD Safe',
            'account_id' => $accountId,
            'currency' => 'USD',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $coachPerson = Person::create([
            'full_name' => 'Coach Test',
            'gender' => 'male',
            'type' => 'staff',
        ]);

        $this->coach = Staff::create([
            'person_id' => $coachPerson->id,
            'branch_id' => $this->branch->id,
            'role' => 'coach',
            'status' => 'active',
        ]);

        $activityType = ActivityType::firstOrCreate(
            ['name' => 'Fitness Type'],
            ['is_session_based' => true]
        );

        $this->activity = Activity::create([
            'branch_id' => $this->branch->id,
            'activity_type_id' => $activityType->id,
            'name' => 'Gym Activity',
        ]);

        $this->staffActivity = StaffActivity::create([
            'staff_id' => $this->coach->id,
            'activity_id' => $this->activity->id,
        ]);

        $memberPerson = Person::create([
            'full_name' => 'Member Test',
            'gender' => 'male',
            'type' => 'player',
        ]);

        $this->member = Member::create([
            'person_id' => $memberPerson->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
        ]);
    }

    public function test_subscription_plan_defaults_to_syp_currency_and_currency_type()
    {
        $response = $this->postJson('/api/v1/subscription-plans', [
            'branch_id' => $this->branch->id,
            'name' => 'Default SYP Plan',
            'base_price' => 50000,
            'session_count' => 12,
            'sessions_per_week' => 3,
            'staff_activity_ids' => [$this->staffActivity->id],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.currency', 'SYP');
        $response->assertJsonPath('data.currency_type', 'SYP');

        $planId = $response->json('data.id');
        $this->assertDatabaseHas('subscription_plans', [
            'id' => $planId,
            'currency' => 'SYP',
        ]);

        $showResponse = $this->getJson('/api/v1/subscription-plans/' . $planId);
        $showResponse->assertStatus(200);
        $showResponse->assertJsonPath('data.currency', 'SYP');
        $showResponse->assertJsonPath('data.currency_type', 'SYP');
    }

    public function test_subscription_plan_with_custom_usd_currency()
    {
        $response = $this->postJson('/api/v1/subscription-plans', [
            'branch_id' => $this->branch->id,
            'name' => 'USD Plan',
            'base_price' => 100,
            'currency' => 'USD',
            'session_count' => 10,
            'sessions_per_week' => 2,
            'staff_activity_ids' => [$this->staffActivity->id],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.currency', 'USD');
        $response->assertJsonPath('data.currency_type', 'USD');

        $planId = $response->json('data.id');
        $this->assertDatabaseHas('subscription_plans', [
            'id' => $planId,
            'currency' => 'USD',
        ]);
    }

    public function test_subscribing_member_propagates_currency_across_subscription_invoice_payments_and_splits()
    {
        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'USD Plan For Member',
            'base_price' => 150.00,
            'final_price' => 150.00,
            'coach_price' => 100.00,
            'branch_price' => 50.00,
            'currency' => 'USD',
            'status' => 'active',
            'max_subscribers' => 0,
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $plan->id,
            'staff_activity_id' => $this->staffActivity->id,
        ]);

        $response = $this->postJson('/api/v1/player-subscriptions', [
            'member_id' => $this->member->id,
            'plan_id' => $plan->id,
            'paid_amount' => 150.00,
            'coach_paid_amount' => 100.00,
            'branch_paid_amount' => 50.00,
            'start_date' => now()->toDateString(),
            'coach_receipt_number' => 'REC-COACH-USD-1',
            'branch_receipt_number' => 'REC-CLUB-USD-1',
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.currency', 'USD');
        $response->assertJsonPath('data.currency_type', 'USD');
        $response->assertJsonPath('data.revenue_split.currency', 'USD');
        $response->assertJsonPath('data.revenue_split.currency_type', 'USD');

        $subId = $response->json('data.id');

        // Check database records
        $this->assertDatabaseHas('player_subscriptions', [
            'id' => $subId,
            'currency' => 'USD',
        ]);

        $this->assertDatabaseHas('invoices', [
            'player_subscription_id' => $subId,
            'currency' => 'USD',
        ]);

        $this->assertDatabaseHas('subscription_revenue_splits', [
            'player_subscription_id' => $subId,
            'currency' => 'USD',
        ]);

        $this->assertDatabaseHas('payments', [
            'receipt_number' => 'REC-COACH-USD-1',
            'currency' => 'USD',
        ]);

        $this->assertDatabaseHas('payments', [
            'receipt_number' => 'REC-CLUB-USD-1',
            'currency' => 'USD',
        ]);
    }

    public function test_player_subscriptions_index_returns_currency_in_items_and_stats()
    {
        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Standard SYP Plan',
            'base_price' => 75000.00,
            'final_price' => 75000.00,
            'currency' => 'SYP',
            'status' => 'active',
            'max_subscribers' => 0,
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $plan->id,
            'staff_activity_id' => $this->staffActivity->id,
        ]);

        $sub = PlayerSubscription::create([
            'member_id' => $this->member->id,
            'plan_id' => $plan->id,
            'months_count' => 1,
            'total_amount' => 75000.00,
            'paid_amount' => 75000.00,
            'remaining_amount' => 0.00,
            'currency' => 'SYP',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/player-subscriptions');

        $response->assertStatus(200);
        $response->assertJsonPath('stats.currency', 'SYP');
        $response->assertJsonPath('stats.currency_type', 'SYP');

        $data = $response->json('data.data') ?? $response->json('data');
        $found = collect($data)->firstWhere('id', $sub->id);
        $this->assertNotNull($found);
        $this->assertEquals('SYP', $found['currency']);
        $this->assertEquals('SYP', $found['currency_type']);
    }

    public function test_payment_index_and_show_return_currency_and_currency_type()
    {
        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Payment Test Plan',
            'base_price' => 200.00,
            'currency' => 'USD',
            'status' => 'active',
        ]);

        $sub = PlayerSubscription::create([
            'member_id' => $this->member->id,
            'plan_id' => $plan->id,
            'months_count' => 1,
            'total_amount' => 200.00,
            'paid_amount' => 200.00,
            'remaining_amount' => 0.00,
            'currency' => 'USD',
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'member_id' => $this->member->id,
            'branch_id' => $this->branch->id,
            'player_subscription_id' => $sub->id,
            'currency' => 'USD',
            'total' => 200.00,
            'status' => 'paid',
        ]);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'receipt_number' => 'REC-PAY-CURR-01',
            'currency' => 'USD',
            'amount' => 200.00,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        $response = $this->getJson('/api/v1/payments/' . $payment->id);
        $response->assertStatus(200);
        $response->assertJsonPath('data.currency', 'USD');
        $response->assertJsonPath('data.currency_type', 'USD');
    }

    public function test_update_subscription_propagates_currency_to_invoice_payments_and_split()
    {
        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Initial Plan',
            'base_price' => 100.00,
            'final_price' => 100.00,
            'currency' => 'SYP',
            'status' => 'active',
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $plan->id,
            'staff_activity_id' => $this->staffActivity->id,
        ]);

        $sub = PlayerSubscription::create([
            'member_id' => $this->member->id,
            'plan_id' => $plan->id,
            'months_count' => 1,
            'total_amount' => 100.00,
            'paid_amount' => 100.00,
            'remaining_amount' => 0.00,
            'currency' => 'SYP',
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'member_id' => $this->member->id,
            'branch_id' => $this->branch->id,
            'player_subscription_id' => $sub->id,
            'currency' => 'SYP',
            'total' => 100.00,
            'status' => 'paid',
        ]);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'receipt_number' => 'REC-UPDATE-1',
            'currency' => 'SYP',
            'amount' => 100.00,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        $response = $this->putJson('/api/v1/player-subscriptions/' . $sub->id, [
            'currency' => 'USD',
            'paid_amount' => 100.00,
            'reason' => 'Change currency from SYP to USD',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.currency', 'USD');
        $response->assertJsonPath('data.currency_type', 'USD');

        $this->assertDatabaseHas('player_subscriptions', [
            'id' => $sub->id,
            'currency' => 'USD',
        ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'currency' => 'USD',
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'currency' => 'USD',
        ]);
    }

    public function test_record_payment_propagates_subscription_currency()
    {
        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Partial Plan USD',
            'base_price' => 300.00,
            'currency' => 'USD',
            'status' => 'active',
        ]);

        $sub = PlayerSubscription::create([
            'member_id' => $this->member->id,
            'plan_id' => $plan->id,
            'months_count' => 1,
            'total_amount' => 300.00,
            'paid_amount' => 100.00,
            'remaining_amount' => 200.00,
            'currency' => 'USD',
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $response = $this->postJson("/api/v1/player-subscriptions/{$sub->id}/payment", [
            'amount' => 100.00,
            'receipt_number' => 'REC-PARTIAL-USD',
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.currency', 'USD');
        $response->assertJsonPath('data.currency_type', 'USD');

        $this->assertDatabaseHas('payments', [
            'receipt_number' => 'REC-PARTIAL-USD',
            'currency' => 'USD',
            'amount' => 100.00,
        ]);
    }

    public function test_my_invoices_returns_currency_and_currency_type()
    {
        // Act as member's user
        $memberUser = User::create([
            'person_id' => $this->member->person_id,
            'username' => 'member_user_' . uniqid(),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $memberUser->assignRole('super_admin');
        Sanctum::actingAs($memberUser, ['*']);

        $invoice = Invoice::create([
            'member_id' => $this->member->id,
            'branch_id' => $this->branch->id,
            'currency' => 'USD',
            'total' => 120.00,
            'status' => 'paid',
        ]);

        $response = $this->getJson('/api/v1/my-invoices');

        $response->assertStatus(200);
        $response->assertJsonPath('data.currency', 'SYP');
        $response->assertJsonPath('data.currency_type', 'SYP');

        $invoices = $response->json('data.invoices');
        $this->assertNotEmpty($invoices);
        $this->assertEquals('USD', $invoices[0]['currency']);
        $this->assertEquals('USD', $invoices[0]['currency_type']);
    }

    public function test_subscriptions_report_returns_currency_and_currency_type()
    {
        $response = $this->getJson('/api/v1/reports/subscriptions');

        $response->assertStatus(200);
        $response->assertJsonPath('data.summary.currency', 'SYP');
        $response->assertJsonPath('data.summary.currency_type', 'SYP');

        $records = $response->json('data.records');
        if (!empty($records)) {
            $this->assertArrayHasKey('currency', $records[0]);
            $this->assertArrayHasKey('currency_type', $records[0]);
        }
    }
}
