<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Branch;
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Modules\SubscriptionManager\Models\PlayerSubscription;
use Modules\SubscriptionManager\Models\Payment;
use Modules\SubscriptionManager\Models\Invoice;
use Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus;
use Modules\MemberManager\Models\Member;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;

class PlayerSubscriptionFilterAndStatsTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $branch;
    protected $branch2;
    protected $member1;
    protected $member2;
    protected $plan1;
    protected $plan2;

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
            'full_name' => 'Admin Tester ' . uniqid(),
            'gender' => 'male',
            'type' => 'staff',
        ]);

        $this->user = User::create([
            'person_id' => $person->id,
            'username' => 'admin_filter_' . uniqid(),
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
            'name' => 'Fitness Club ' . uniqid(),
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'club_id' => $club->id,
            'name' => 'East Branch ' . uniqid(),
            'status' => 'active',
        ]);

        $this->branch2 = Branch::create([
            'club_id' => $club->id,
            'name' => 'West Branch ' . uniqid(),
            'status' => 'active',
        ]);

        // Member 1
        $person1 = Person::create([
            'full_name' => 'Ahmad Al-Khatib',
            'gender' => 'male',
            'type' => 'player',
        ]);
        $this->member1 = Member::create([
            'person_id' => $person1->id,
            'branch_id' => $this->branch->id,
            'member_number' => 'MEM1-' . uniqid(),
            'membership_status' => 'active',
        ]);

        // Member 2
        $person2 = Person::create([
            'full_name' => 'Sara Mahmoud',
            'gender' => 'female',
            'type' => 'player',
        ]);
        $this->member2 = Member::create([
            'person_id' => $person2->id,
            'branch_id' => $this->branch->id,
            'member_number' => 'MEM2-' . uniqid(),
            'membership_status' => 'active',
        ]);

        $this->plan1 = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Gold Gym Plan',
            'base_price' => 150.00,
            'final_price' => 150.00,
            'max_subscribers' => 50,
            'is_unlimited_subscribers' => false,
            'is_active' => true,
        ]);

        $this->plan2 = SubscriptionPlan::create([
            'branch_id' => $this->branch2->id,
            'name' => 'Silver Pool Plan',
            'base_price' => 100.00,
            'final_price' => 100.00,
            'max_subscribers' => 50,
            'is_unlimited_subscribers' => false,
            'is_active' => true,
        ]);
    }

    public function test_search_by_member_name_and_number()
    {
        $sub1 = PlayerSubscription::create([
            'member_id' => $this->member1->id,
            'plan_id' => $this->plan1->id,
            'months_count' => 1,
            'total_amount' => 150.00,
            'paid_amount' => 150.00,
            'remaining_amount' => 0.00,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addMonth()->toDateString(),
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
        ]);

        $sub2 = PlayerSubscription::create([
            'member_id' => $this->member2->id,
            'plan_id' => $this->plan1->id,
            'months_count' => 1,
            'total_amount' => 150.00,
            'paid_amount' => 150.00,
            'remaining_amount' => 0.00,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addMonth()->toDateString(),
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
        ]);

        // Search by name "Ahmad"
        $resName = $this->getJson('/api/v1/player-subscriptions?search=Ahmad');
        $resName->assertStatus(200);
        $dataName = $resName->json('data');
        $this->assertNotEmpty($dataName);
        $this->assertTrue(collect($dataName)->pluck('id')->contains($sub1->id));
        $this->assertFalse(collect($dataName)->pluck('id')->contains($sub2->id));

        // Search by member number
        $resNum = $this->getJson('/api/v1/player-subscriptions?search=' . $this->member2->member_number);
        $resNum->assertStatus(200);
        $dataNum = $resNum->json('data');
        $this->assertNotEmpty($dataNum);
        $this->assertTrue(collect($dataNum)->pluck('id')->contains($sub2->id));
        $this->assertFalse(collect($dataNum)->pluck('id')->contains($sub1->id));

        // Direct search param member_number
        $resDirect = $this->getJson('/api/v1/player-subscriptions?member_number=' . $this->member1->member_number);
        $resDirect->assertStatus(200);
        $this->assertTrue(collect($resDirect->json('data'))->pluck('id')->contains($sub1->id));
    }

    public function test_filter_by_status_active_expiring_finished_frozen_terminated()
    {
        // 1. Active (not expiring soon, ends in 20 days)
        $subActive = PlayerSubscription::create([
            'member_id' => $this->member1->id,
            'plan_id' => $this->plan1->id,
            'months_count' => 1,
            'total_amount' => 150.00,
            'paid_amount' => 150.00,
            'remaining_amount' => 0.00,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDays(20)->toDateString(),
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
        ]);

        // 2. Expiring soon (ends in 3 days)
        $subExpiring = PlayerSubscription::create([
            'member_id' => $this->member1->id,
            'plan_id' => $this->plan1->id,
            'months_count' => 1,
            'total_amount' => 150.00,
            'paid_amount' => 150.00,
            'remaining_amount' => 0.00,
            'start_date' => Carbon::today()->subDays(27)->toDateString(),
            'end_date' => Carbon::today()->addDays(3)->toDateString(),
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
        ]);

        // 3. Finished / Expired
        $subFinished = PlayerSubscription::create([
            'member_id' => $this->member2->id,
            'plan_id' => $this->plan1->id,
            'months_count' => 1,
            'total_amount' => 150.00,
            'paid_amount' => 150.00,
            'remaining_amount' => 0.00,
            'start_date' => Carbon::today()->subMonths(2)->toDateString(),
            'end_date' => Carbon::today()->subMonth()->toDateString(),
            'status' => PlayerSubscriptionStatus::FINISHED->value,
        ]);

        // 4. Frozen
        $subFrozen = PlayerSubscription::create([
            'member_id' => $this->member2->id,
            'plan_id' => $this->plan1->id,
            'months_count' => 1,
            'total_amount' => 150.00,
            'paid_amount' => 150.00,
            'remaining_amount' => 0.00,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addMonth()->toDateString(),
            'status' => PlayerSubscriptionStatus::FROZEN->value,
        ]);

        // 5. Terminated
        $subTerminated = PlayerSubscription::create([
            'member_id' => $this->member2->id,
            'plan_id' => $this->plan1->id,
            'months_count' => 1,
            'total_amount' => 150.00,
            'paid_amount' => 150.00,
            'remaining_amount' => 0.00,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addMonth()->toDateString(),
            'status' => PlayerSubscriptionStatus::TERMINATED->value,
        ]);

        // Test filter: active
        $resActive = $this->getJson('/api/v1/player-subscriptions?status=active');
        $resActive->assertStatus(200);
        $idsActive = collect($resActive->json('data'))->pluck('id');
        $this->assertTrue($idsActive->contains($subActive->id));
        $this->assertFalse($idsActive->contains($subFinished->id));
        $this->assertFalse($idsActive->contains($subFrozen->id));
        $this->assertFalse($idsActive->contains($subTerminated->id));

        // Test filter: expiring_soon
        $resExpiring = $this->getJson('/api/v1/player-subscriptions?status=expiring_soon');
        $resExpiring->assertStatus(200);
        $idsExpiring = collect($resExpiring->json('data'))->pluck('id');
        $this->assertTrue($idsExpiring->contains($subExpiring->id));
        $this->assertFalse($idsExpiring->contains($subActive->id));
        $this->assertFalse($idsExpiring->contains($subFinished->id));

        // Test Arabic filter: تنتهي_قريبا
        $resExpiringAr = $this->getJson('/api/v1/player-subscriptions?status=تنتهي_قريبا');
        $resExpiringAr->assertStatus(200);
        $this->assertTrue(collect($resExpiringAr->json('data'))->pluck('id')->contains($subExpiring->id));

        // Test filter: finished
        $resFinished = $this->getJson('/api/v1/player-subscriptions?status=finished');
        $resFinished->assertStatus(200);
        $this->assertTrue(collect($resFinished->json('data'))->pluck('id')->contains($subFinished->id));
        $this->assertFalse(collect($resFinished->json('data'))->pluck('id')->contains($subActive->id));

        // Test filter: frozen (مجمد)
        $resFrozen = $this->getJson('/api/v1/player-subscriptions?status=مجمد');
        $resFrozen->assertStatus(200);
        $this->assertTrue(collect($resFrozen->json('data'))->pluck('id')->contains($subFrozen->id));

        // Test filter: terminated (تم إنهاؤه من الإدارة)
        $resTerminated = $this->getJson('/api/v1/player-subscriptions?status=terminated');
        $resTerminated->assertStatus(200);
        $this->assertTrue(collect($resTerminated->json('data'))->pluck('id')->contains($subTerminated->id));
    }

    public function test_filter_by_period_today_monthly_all()
    {
        // Subscription created today
        $subToday = PlayerSubscription::create([
            'member_id' => $this->member1->id,
            'plan_id' => $this->plan1->id,
            'months_count' => 1,
            'total_amount' => 150.00,
            'paid_amount' => 150.00,
            'remaining_amount' => 0.00,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addMonth()->toDateString(),
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
            'created_at' => Carbon::today()->setTime(10, 0, 0),
        ]);

        // Subscription created 45 days ago (different month)
        $subOld = PlayerSubscription::create([
            'member_id' => $this->member2->id,
            'plan_id' => $this->plan1->id,
            'months_count' => 1,
            'total_amount' => 150.00,
            'paid_amount' => 150.00,
            'remaining_amount' => 0.00,
            'start_date' => Carbon::today()->subDays(45)->toDateString(),
            'end_date' => Carbon::today()->subDays(15)->toDateString(),
            'status' => PlayerSubscriptionStatus::FINISHED->value,
        ]);
        \Illuminate\Support\Facades\DB::table('player_subscriptions')
            ->where('id', $subOld->id)
            ->update(['created_at' => Carbon::today()->subDays(45)]);

        // 1. Period: today (تسجلت اليوم)
        $resToday = $this->getJson('/api/v1/player-subscriptions?period=today');
        $resToday->assertStatus(200);
        $idsToday = collect($resToday->json('data'))->pluck('id');
        $this->assertTrue($idsToday->contains($subToday->id));
        $this->assertFalse($idsToday->contains($subOld->id));

        // 2. Period: monthly (هذا الشهر)
        $resMonth = $this->getJson('/api/v1/player-subscriptions?period=monthly');
        $resMonth->assertStatus(200);
        $idsMonth = collect($resMonth->json('data'))->pluck('id');
        $this->assertTrue($idsMonth->contains($subToday->id));
        $this->assertFalse($idsMonth->contains($subOld->id));

        // 3. Specific month filter
        $oldDate = Carbon::today()->subDays(45);
        $resSpecific = $this->getJson('/api/v1/player-subscriptions?month=' . $oldDate->month . '&year=' . $oldDate->year);
        $resSpecific->assertStatus(200);
        $this->assertTrue(collect($resSpecific->json('data'))->pluck('id')->contains($subOld->id));
        if ($oldDate->month !== Carbon::today()->month) {
            $this->assertFalse(collect($resSpecific->json('data'))->pluck('id')->contains($subToday->id));
        }

        // 4. Period: all (كل الاشتراكات)
        $resAll = $this->getJson('/api/v1/player-subscriptions?period=all');
        $resAll->assertStatus(200);
        $idsAll = collect($resAll->json('data'))->pluck('id');
        $this->assertTrue($idsAll->contains($subToday->id));
        $this->assertTrue($idsAll->contains($subOld->id));
    }

    public function test_statistics_embedded_in_list_and_dedicated_endpoint()
    {
        // 1. Active subscription created today with payment
        $sub1 = PlayerSubscription::create([
            'member_id' => $this->member1->id,
            'plan_id' => $this->plan1->id,
            'months_count' => 1,
            'total_amount' => 200.00,
            'paid_amount' => 200.00,
            'remaining_amount' => 0.00,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addMonth()->toDateString(),
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
            'created_at' => Carbon::today(),
        ]);

        $inv1 = Invoice::create([
            'member_id' => $this->member1->id,
            'branch_id' => $this->branch->id,
            'player_subscription_id' => $sub1->id,
            'total' => 200.00,
            'status' => 'paid',
            'created_at' => Carbon::today(),
        ]);

        Payment::create([
            'invoice_id' => $inv1->id,
            'amount' => 200.00,
            'payment_method' => 'cash',
            'receipt_number' => 'REC-TODAY-001',
            'created_at' => Carbon::today(),
        ]);

        // 2. Finished subscription created in the past with payment in the past
        $sub2 = PlayerSubscription::create([
            'member_id' => $this->member2->id,
            'plan_id' => $this->plan1->id,
            'months_count' => 1,
            'total_amount' => 150.00,
            'paid_amount' => 150.00,
            'remaining_amount' => 0.00,
            'start_date' => Carbon::today()->subMonths(2)->toDateString(),
            'end_date' => Carbon::today()->subMonth()->toDateString(),
            'status' => PlayerSubscriptionStatus::FINISHED->value,
        ]);

        $inv2 = Invoice::create([
            'member_id' => $this->member2->id,
            'branch_id' => $this->branch->id,
            'player_subscription_id' => $sub2->id,
            'total' => 150.00,
            'status' => 'paid',
        ]);

        $pay2 = Payment::create([
            'invoice_id' => $inv2->id,
            'amount' => 150.00,
            'payment_method' => 'cash',
            'receipt_number' => 'REC-OLD-001',
        ]);

        \Illuminate\Support\Facades\DB::table('player_subscriptions')
            ->where('id', $sub2->id)
            ->update(['created_at' => Carbon::today()->subMonths(2)]);
        \Illuminate\Support\Facades\DB::table('invoices')
            ->where('id', $inv2->id)
            ->update(['created_at' => Carbon::today()->subMonths(2)]);
        \Illuminate\Support\Facades\DB::table('payments')
            ->where('id', $pay2->id)
            ->update(['created_at' => Carbon::today()->subMonths(2)]);

        // 3. Test stats in single endpoint GET /api/v1/player-subscriptions
        $listRes = $this->getJson('/api/v1/player-subscriptions?branch_id=' . $this->branch->id);
        $listRes->assertStatus(200);
        $listRes->assertJsonStructure([
            'status',
            'message',
            'data',
            'stats' => [
                'active_subscriptions',
                'total_subscriptions',
                'total_paid_amount',
                'today_revenue',
            ],
        ]);

        $stats = $listRes->json('stats');
        $this->assertEquals(1, $stats['active_subscriptions']);
        $this->assertEquals(2, $stats['total_subscriptions']);
        $this->assertEquals(350.00, (float) $stats['total_paid_amount']);
        $this->assertEquals(200.00, (float) $stats['today_revenue']);
    }
}
