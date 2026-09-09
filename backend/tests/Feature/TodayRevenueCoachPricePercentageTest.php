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
use Modules\SubscriptionManager\Models\SubscriptionRevenueSplit;
use Modules\MemberManager\Models\Member;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;

class TodayRevenueCoachPricePercentageTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $branch;
    protected $coach;
    protected $member;
    protected $privateActivity;
    protected $standardActivity;

    protected function setUp(): void
    {
        parent::setUp();

        $person = Person::create([
            'full_name' => 'Admin Today Revenue Test',
            'gender' => 'male',
            'type' => 'staff',
        ]);

        $this->user = User::create([
            'person_id' => $person->id,
            'username' => 'admin_today_rev_' . uniqid(),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $role = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'sanctum',
        ]);
        $this->user->assignRole($role);
        Sanctum::actingAs($this->user, ['*']);

        $club = Club::create(['name' => 'Fitness Club', 'is_active' => true]);
        $this->branch = Branch::create(['club_id' => $club->id, 'name' => 'Central Branch', 'is_active' => true]);

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
            'name' => 'Central Safe',
            'account_id' => $accountId,
            'currency' => 'SYP',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Coach
        $coachPerson = Person::create(['full_name' => 'Captain Firas', 'gender' => 'male', 'type' => 'staff']);
        $this->coach = Staff::create([
            'person_id' => $coachPerson->id,
            'role' => 'coach',
            'work_status' => 'active',
            'is_active' => true,
        ]);

        // Member
        $memberPerson = Person::create(['full_name' => 'Samer Player', 'gender' => 'male', 'type' => 'player']);
        $this->member = Member::create([
            'person_id' => $memberPerson->id,
            'branch_id' => $this->branch->id,
            'member_number' => 'M-' . uniqid(),
            'status' => 'active',
        ]);

        // Private Activity (أجهزة خاص)
        $privateType = ActivityType::create([
            'name' => 'تدريب خاص',
            'is_active' => true,
            'is_session_based' => false,
            'has_unlimited_subscribers' => true,
        ]);

        $this->privateActivity = Activity::create([
            'branch_id' => $this->branch->id,
            'activity_type_id' => $privateType->id,
            'name' => 'أجهزة خاص',
            'is_private_equipment' => true,
            'is_active' => true,
        ]);

        // Standard Activity (General Fitness)
        $standardType = ActivityType::create([
            'name' => 'لياقة عامة',
            'is_active' => true,
            'is_session_based' => false,
            'has_unlimited_subscribers' => true,
        ]);

        $this->standardActivity = Activity::create([
            'branch_id' => $this->branch->id,
            'activity_type_id' => $standardType->id,
            'name' => 'حديد عام',
            'is_private_equipment' => false,
            'is_active' => true,
        ]);
    }

    /**
     * 1. وعندما تكون نسبة النادي 0%: لا يدخل coach_price بإيراد اليوم نهائياً، ويدخل فقط branch_price
     */
    public function test_today_revenue_excludes_coach_price_when_club_percentage_is_zero(): void
    {
        // Coach keeps 100% of private training, Club gets 0%
        StaffContract::create([
            'staff_id' => $this->coach->id,
            'employment_type' => 'hybrid',
            'commission_type' => 'percentage',
            'private_commission_rate' => 100.00,
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $staffActivity = StaffActivity::create([
            'staff_id' => $this->coach->id,
            'activity_id' => $this->privateActivity->id,
        ]);

        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'اشتراك خاص نسبة النادي صفر',
            'base_price' => 300.00,
            'coach_price' => 200.00,
            'branch_price' => 100.00,
            'status' => 'active',
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $plan->id,
            'staff_activity_id' => $staffActivity->id,
        ]);

        $payload = [
            'member_id' => $this->member->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'paid_amount' => 300.00,
            'coach_receipt_number' => 'REC-COACH-ZERO',
            'branch_receipt_number' => 'REC-CLUB-ZERO',
        ];

        $res = $this->postJson('/api/v1/player-subscriptions', $payload);
        $res->assertStatus(201);

        // Fetch subscriptions & stats
        $statsRes = $this->getJson('/api/v1/player-subscriptions?branch_id=' . $this->branch->id);
        $statsRes->assertStatus(200);

        $stats = $statsRes->json('stats');
        // Total paid by member is 300
        $this->assertEquals(300.00, (float) $stats['total_paid_amount']);
        // But today_revenue for the club must EXCLUDE coach_price (200.00) because club percentage is 0%!
        // It must be exactly 100.00 (branch_price).
        $this->assertEquals(100.00, (float) $stats['today_revenue']);
    }

    /**
     * 2. وعندما تكون هناك نسبة للنادي من coach_price: يقوم بحساب نسبة النادي وإدخالها مع حصة النادي
     */
    public function test_today_revenue_calculates_and_includes_club_percentage_when_configured(): void
    {
        // Coach gets 70%, Club gets 30% of coach_price
        StaffContract::create([
            'staff_id' => $this->coach->id,
            'employment_type' => 'hybrid',
            'commission_type' => 'percentage',
            'private_commission_rate' => 70.00,
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $staffActivity = StaffActivity::create([
            'staff_id' => $this->coach->id,
            'activity_id' => $this->privateActivity->id,
        ]);

        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'اشتراك خاص نسبة النادي ثلاثين بالمئة',
            'base_price' => 300.00,
            'coach_price' => 200.00,
            'branch_price' => 100.00,
            'status' => 'active',
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $plan->id,
            'staff_activity_id' => $staffActivity->id,
        ]);

        $payload = [
            'member_id' => $this->member->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'paid_amount' => 300.00,
            'coach_receipt_number' => 'REC-COACH-30',
            'branch_receipt_number' => 'REC-CLUB-30',
        ];

        $res = $this->postJson('/api/v1/player-subscriptions', $payload);
        $res->assertStatus(201);

        $statsRes = $this->getJson('/api/v1/player-subscriptions?branch_id=' . $this->branch->id);
        $statsRes->assertStatus(200);

        $stats = $statsRes->json('stats');
        // Club revenue = 100 (branch_price) + 30% of 200 (60) = 160.00
        $this->assertEquals(160.00, (float) $stats['today_revenue']);
    }

    /**
     * 3. دفعة موحدة واحدة (Unified single payment) بنسبة 0% وبنسبة 30%
     */
    public function test_today_revenue_with_single_unified_payment(): void
    {
        // Coach keeps 100% of coach price
        StaffContract::create([
            'staff_id' => $this->coach->id,
            'employment_type' => 'hybrid',
            'commission_type' => 'percentage',
            'private_commission_rate' => 100.00,
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $staffActivity = StaffActivity::create([
            'staff_id' => $this->coach->id,
            'activity_id' => $this->privateActivity->id,
        ]);

        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'اشتراك خاص دفعة واحدة',
            'base_price' => 300.00,
            'coach_price' => 200.00,
            'branch_price' => 100.00,
            'status' => 'active',
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $plan->id,
            'staff_activity_id' => $staffActivity->id,
        ]);

        // Subscribe without dual receipts (single payment of 300)
        $payload = [
            'member_id' => $this->member->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'paid_amount' => 300.00,
        ];

        $res = $this->postJson('/api/v1/player-subscriptions', $payload);
        $res->assertStatus(201);

        $statsRes = $this->getJson('/api/v1/player-subscriptions?branch_id=' . $this->branch->id);
        $statsRes->assertStatus(200);

        $stats = $statsRes->json('stats');
        // Single payment of 300 is pro-rated according to club_amount (100) / total_amount (300)
        $this->assertEquals(100.00, (float) $stats['today_revenue']);
    }

    /**
     * 4. جمع الاشتراكات العادية مع الاشتراكات الخاصة
     */
    public function test_today_revenue_combines_standard_and_private_subscriptions_correctly(): void
    {
        // 1. Coach contract: 0% club
        StaffContract::create([
            'staff_id' => $this->coach->id,
            'employment_type' => 'hybrid',
            'commission_type' => 'percentage',
            'private_commission_rate' => 100.00,
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $staffActivity = StaffActivity::create([
            'staff_id' => $this->coach->id,
            'activity_id' => $this->privateActivity->id,
        ]);

        // Private Plan: 200 coach + 100 club = 300 total
        $privatePlan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'اشتراك خاص تجريبي',
            'base_price' => 300.00,
            'coach_price' => 200.00,
            'branch_price' => 100.00,
            'status' => 'active',
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $privatePlan->id,
            'staff_activity_id' => $staffActivity->id,
        ]);

        // Standard Plan: 150 club (no coach price)
        $standardPlan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'اشتراك حديد عام',
            'base_price' => 150.00,
            'status' => 'active',
        ]);

        // Member 2
        $member2Person = Person::create(['full_name' => 'Rami Player', 'gender' => 'male', 'type' => 'player']);
        $member2 = Member::create([
            'person_id' => $member2Person->id,
            'branch_id' => $this->branch->id,
            'member_number' => 'M-RAMI',
            'status' => 'active',
        ]);

        // 1. Member 1 subscribes to standard plan (paid 150) -> 100% enters club revenue (150)
        $this->postJson('/api/v1/player-subscriptions', [
            'member_id' => $this->member->id,
            'plan_id' => $standardPlan->id,
            'start_date' => now()->toDateString(),
            'paid_amount' => 150.00,
        ])->assertStatus(201);

        // 2. Member 2 subscribes to private plan (paid 300) -> only branch_price (100) enters club revenue
        $this->postJson('/api/v1/player-subscriptions', [
            'member_id' => $member2->id,
            'plan_id' => $privatePlan->id,
            'start_date' => now()->toDateString(),
            'paid_amount' => 300.00,
            'coach_receipt_number' => 'C-MIX-01',
            'branch_receipt_number' => 'B-MIX-01',
        ])->assertStatus(201);

        // Fetch stats
        $statsRes = $this->getJson('/api/v1/player-subscriptions?branch_id=' . $this->branch->id);
        $statsRes->assertStatus(200);

        $stats = $statsRes->json('stats');
        // Total paid amount = 150 + 300 = 450.00
        $this->assertEquals(450.00, (float) $stats['total_paid_amount']);
        // Today revenue = 150 (standard) + 100 (private branch_price) = 250.00!
        $this->assertEquals(250.00, (float) $stats['today_revenue']);
    }

    /**
     * 5. اشتراك مباشر مسجل اليوم بدون سجلات دفعات منفصلة (whereDoesntHave('payments'))
     */
    public function test_today_revenue_direct_subscription_without_payments_rows(): void
    {
        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'اشتراك مباشر بدون دفعات',
            'base_price' => 300.00,
            'coach_price' => 200.00,
            'branch_price' => 100.00,
            'status' => 'active',
        ]);

        $sub = PlayerSubscription::create([
            'member_id' => $this->member->id,
            'plan_id' => $plan->id,
            'months_count' => 1,
            'total_amount' => 300.00,
            'paid_amount' => 300.00,
            'remaining_amount' => 0.00,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addMonth()->toDateString(),
            'status' => 'active',
            'created_at' => Carbon::today(),
        ]);

        SubscriptionRevenueSplit::create([
            'player_subscription_id' => $sub->id,
            'coach_id' => $this->coach->id,
            'branch_id' => $this->branch->id,
            'total_amount' => 300.00,
            'club_percentage' => 0.00,
            'coach_percentage' => 100.00,
            'club_amount' => 100.00,
            'coach_amount' => 200.00,
        ]);

        $statsRes = $this->getJson('/api/v1/player-subscriptions?branch_id=' . $this->branch->id);
        $statsRes->assertStatus(200);

        $stats = $statsRes->json('stats');
        // Only club_amount (100.00) enters today's revenue
        $this->assertEquals(100.00, (float) $stats['today_revenue']);
    }

    /**
     * 6. نسبة النادي مأخوذة من إعدادات الفرع الافتراضية عند عدم وجود عقد خاص
     */
    public function test_today_revenue_with_branch_default_percentages(): void
    {
        // Branch default: 25% club, 75% coach
        \Modules\ClubManager\Models\BranchSetting::create([
            'branch_id' => $this->branch->id,
            'default_coach_commission_percentage' => 75.00,
            'default_club_commission_percentage'  => 25.00,
        ]);

        $staffActivity = StaffActivity::create([
            'staff_id' => $this->coach->id,
            'activity_id' => $this->privateActivity->id,
        ]);

        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'اشتراك خاص نسبة الفرع الافتراضية',
            'base_price' => 300.00,
            'coach_price' => 200.00,
            'branch_price' => 100.00,
            'status' => 'active',
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $plan->id,
            'staff_activity_id' => $staffActivity->id,
        ]);

        $payload = [
            'member_id' => $this->member->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'paid_amount' => 300.00,
            'coach_receipt_number' => 'REC-COACH-DEFAULT',
            'branch_receipt_number' => 'REC-CLUB-DEFAULT',
        ];

        $res = $this->postJson('/api/v1/player-subscriptions', $payload);
        $res->assertStatus(201);

        $statsRes = $this->getJson('/api/v1/player-subscriptions?branch_id=' . $this->branch->id);
        $statsRes->assertStatus(200);

        $stats = $statsRes->json('stats');
        // Club gets 100.00 (branch_price) + 25% of 200.00 (50.00) = 150.00
        $this->assertEquals(150.00, (float) $stats['today_revenue']);
    }

    /**
     * 7. الدفع الجزئي يتم حسابه بالنسبة والتناسب
     */
    public function test_today_revenue_partial_payment(): void
    {
        // Club percentage 0%
        StaffContract::create([
            'staff_id' => $this->coach->id,
            'employment_type' => 'hybrid',
            'commission_type' => 'percentage',
            'private_commission_rate' => 100.00,
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $staffActivity = StaffActivity::create([
            'staff_id' => $this->coach->id,
            'activity_id' => $this->privateActivity->id,
        ]);

        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'اشتراك خاص دفع جزئي',
            'base_price' => 300.00,
            'coach_price' => 200.00,
            'branch_price' => 100.00,
            'status' => 'active',
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $plan->id,
            'staff_activity_id' => $staffActivity->id,
        ]);

        // Pay 150 out of 300 (single payment)
        $payload = [
            'member_id' => $this->member->id,
            'plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'paid_amount' => 150.00,
        ];

        $res = $this->postJson('/api/v1/player-subscriptions', $payload);
        $res->assertStatus(201);

        $statsRes = $this->getJson('/api/v1/player-subscriptions?branch_id=' . $this->branch->id);
        $statsRes->assertStatus(200);

        $stats = $statsRes->json('stats');
        // Total amount = 300, Club amount = 100, Club ratio = 1/3.
        // Paid = 150. Today revenue = 150 * (100 / 300) = 50.00!
        $this->assertEquals(50.00, (float) $stats['today_revenue']);
    }
}
