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
use Modules\SubscriptionManager\Models\Payment;
use Modules\MemberManager\Models\Member;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Laravel\Sanctum\Sanctum;

class PaymentReceiptNumberTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $branch;
    protected $member;
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

        $accountId = \Illuminate\Support\Facades\DB::table('acc_accounts')->insertGetId([
            'code' => '101' . rand(1000, 9999),
            'name' => 'Safe Account ' . uniqid(),
            'type' => 'asset',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('acc_safes')->insert([
            'branch_id' => $this->branch->id,
            'name' => 'Main Safe',
            'account_id' => $accountId,
            'currency' => 'USD',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
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

        $this->plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Fitness Plan',
            'base_price' => 100.00,
            'final_price' => 100.00,
            'max_subscribers' => 50,
            'is_unlimited_subscribers' => false,
            'is_active' => true,
        ]);
    }

    public function test_subscribing_member_records_receipt_number()
    {
        $payload = [
            'member_id' => $this->member->id,
            'plan_id' => $this->plan->id,
            'start_date' => now()->toDateString(),
            'paid_amount' => 50.00,
            'payment_method' => 'cash',
            'receipt_number' => 'REC-SUB-999',
        ];

        $response = $this->postJson('/api/v1/player-subscriptions', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.receipt_number', 'REC-SUB-999')
            ->assertJsonPath('data.payments.0.receipt_number', 'REC-SUB-999');

        $subscriptionId = $response->json('data.id');

        $showResponse = $this->getJson("/api/v1/player-subscriptions/{$subscriptionId}");
        $showResponse->assertStatus(200)
            ->assertJsonPath('data.receipt_number', 'REC-SUB-999')
            ->assertJsonPath('data.payments.0.receipt_number', 'REC-SUB-999');

        $listResponse = $this->getJson('/api/v1/player-subscriptions');
        $listResponse->assertStatus(200)
            ->assertJsonPath('data.0.receipt_number', 'REC-SUB-999');

        $this->assertDatabaseHas('payments', [
            'amount' => 50.00,
            'receipt_number' => 'REC-SUB-999',
            'payment_method' => 'cash',
        ]);
    }

    public function test_recording_payment_on_subscription_saves_receipt_number()
    {
        $subPayload = [
            'member_id' => $this->member->id,
            'plan_id' => $this->plan->id,
            'start_date' => now()->toDateString(),
            'paid_amount' => 30.00,
            'payment_method' => 'cash',
            'receipt_number' => 'REC-FIRST-001',
        ];

        $subResponse = $this->postJson('/api/v1/player-subscriptions', $subPayload);
        $subResponse->assertStatus(201);
        $subscriptionId = $subResponse->json('data.id');

        $paymentPayload = [
            'amount' => 40.00,
            'payment_method' => 'card',
            'receipt_number' => 'REC-SECOND-002',
        ];

        $payResponse = $this->postJson("/api/v1/player-subscriptions/{$subscriptionId}/payment", $paymentPayload);
        $payResponse->assertStatus(200)
            ->assertJsonPath('data.receipt_number', 'REC-SECOND-002')
            ->assertJsonCount(2, 'data.payments');

        $this->assertDatabaseHas('payments', [
            'amount' => 40.00,
            'receipt_number' => 'REC-SECOND-002',
            'payment_method' => 'card',
        ]);
    }

    public function test_payment_resource_returns_receipt_number()
    {
        $payment = Payment::create([
            'receipt_number' => 'REC-VIEW-777',
            'safe_id' => null,
            'amount' => 25.00,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        $response = $this->getJson("/api/v1/payments/{$payment->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.receipt_number', 'REC-VIEW-777');
    }

    public function test_updating_payment_allows_updating_receipt_number()
    {
        $payment = Payment::create([
            'receipt_number' => 'REC-OLD-111',
            'safe_id' => null,
            'amount' => 50.00,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        $response = $this->putJson("/api/v1/payments/{$payment->id}", [
            'reason' => 'تصحيح رقم الإيصال',
            'receipt_number' => 'REC-NEW-222',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.receipt_number', 'REC-NEW-222');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'receipt_number' => 'REC-NEW-222',
        ]);
    }

    public function test_renewing_subscription_records_receipt_number()
    {
        $subPayload = [
            'member_id' => $this->member->id,
            'plan_id' => $this->plan->id,
            'start_date' => now()->toDateString(),
            'paid_amount' => 100.00,
            'payment_method' => 'cash',
            'receipt_number' => 'REC-INIT-100',
        ];

        $subResponse = $this->postJson('/api/v1/player-subscriptions', $subPayload);
        $subResponse->assertStatus(201);
        $subscriptionId = $subResponse->json('data.id');

        $renewPayload = [
            'plan_id' => $this->plan->id,
            'paid_amount' => 100.00,
            'payment_method' => 'card',
            'receipt_number' => 'REC-RENEW-200',
        ];

        $renewResponse = $this->postJson("/api/v1/player-subscriptions/{$subscriptionId}/renew", $renewPayload);
        $renewResponse->assertStatus(201);

        $this->assertDatabaseHas('payments', [
            'amount' => 100.00,
            'receipt_number' => 'REC-RENEW-200',
            'payment_method' => 'card',
        ]);
    }
}
