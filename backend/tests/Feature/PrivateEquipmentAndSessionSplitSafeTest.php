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
use Modules\SubscriptionManager\Models\SubscriptionRevenueSplit;
use Modules\SubscriptionManager\Models\Payment;
use Modules\MemberManager\Models\Member;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Laravel\Sanctum\Sanctum;

class PrivateEquipmentAndSessionSplitSafeTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $branch;
    protected $coach;
    protected $member;
    protected $safeId;

    protected function setUp(): void
    {
        parent::setUp();

        $person = Person::create([
            'full_name' => 'Admin Tester',
            'gender' => 'male',
            'type' => 'staff',
        ]);

        $this->user = User::create([
            'person_id' => $person->id,
            'username' => 'admin_safe_split_' . uniqid(),
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
        $this->branch = Branch::create(['club_id' => $club->id, 'name' => 'Main Branch', 'is_active' => true]);

        // Create Safe and account
        $accountId = \Illuminate\Support\Facades\DB::table('acc_accounts')->insertGetId([
            'code' => '101' . rand(1000, 9999),
            'name' => 'Branch Safe Account ' . uniqid(),
            'type' => 'asset',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->safeId = \Illuminate\Support\Facades\DB::table('acc_safes')->insertGetId([
            'branch_id' => $this->branch->id,
            'name' => 'Main Safe',
            'account_id' => $accountId,
            'currency' => 'SYP',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Coach
        $coachPerson = Person::create(['full_name' => 'Coach Hani', 'gender' => 'male', 'type' => 'staff']);
        $this->coach = Staff::create([
            'person_id' => $coachPerson->id,
            'role' => 'coach',
            'work_status' => 'active',
            'is_active' => true,
        ]);

        // Member
        $memberPerson = Person::create(['full_name' => 'Samer Member', 'gender' => 'male', 'type' => 'player']);
        $this->member = Member::create([
            'person_id' => $memberPerson->id,
            'branch_id' => $this->branch->id,
            'member_number' => 'M-' . uniqid(),
            'status' => 'active',
        ]);
    }

    /**
     * 1. Private equipment: coach private_commission_rate is 100%.
     * Rule: Money does NOT enter the safe at all (safe_id = null).
     * Splits are recorded in revenue_splits table.
     */
    public function test_private_equipment_with_100_percent_coach_rate_does_not_enter_safe()
    {
        StaffContract::create([
            'staff_id' => $this->coach->id,
            'employment_type' => 'commission_based',
            'private_commission_rate' => 100.00,
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $privateType = ActivityType::create([
            'name' => 'أجهزة خاصة',
            'is_active' => true,
            'is_private_equipment' => true,
            'is_session_based' => false,
        ]);

        $activity = Activity::create([
            'branch_id' => $this->branch->id,
            'activity_type_id' => $privateType->id,
            'name' => 'تدريب أجهزة خاص',
            'is_private_equipment' => true,
            'is_active' => true,
        ]);

        $staffActivity = StaffActivity::create([
            'staff_id' => $this->coach->id,
            'activity_id' => $activity->id,
        ]);

        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'اشتراك أجهزة خاص 100%',
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
            'start_date' => now()->toDateString(),
            'paid_amount' => 500.00,
            'coach_receipt_number' => 'REC-COACH-100',
        ];

        $res = $this->postJson('/api/v1/player-subscriptions', $payload);
        $res->assertStatus(201);
        $subId = $res->json('data.id');

        // Verify revenue split is saved
        $this->assertDatabaseHas('subscription_revenue_splits', [
            'player_subscription_id' => $subId,
            'coach_id' => $this->coach->id,
            'total_amount' => 500.00,
            'coach_percentage' => 100.00,
            'club_percentage' => 0.00,
            'coach_amount' => 500.00,
            'club_amount' => 0.00,
        ]);

        // Verify payment safe_id is NULL (did not enter safe)
        $this->assertDatabaseHas('payments', [
            'receipt_number' => 'REC-COACH-100',
            'amount' => 500.00,
            'safe_id' => null,
        ]);
    }

    /**
     * 2. Private equipment: coach private_commission_rate < 100% (e.g. 70%).
     * Rule: Club percentage (30%) is calculated and enters the safe (safe_id = $safeId).
     * Coach portion does not enter the safe (safe_id = null).
     * Splits are recorded in revenue_splits table.
     */
    public function test_private_equipment_with_less_than_100_percent_coach_rate_enters_safe_for_club_share()
    {
        StaffContract::create([
            'staff_id' => $this->coach->id,
            'employment_type' => 'hybrid',
            'private_commission_rate' => 70.00,
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $privateType = ActivityType::create([
            'name' => 'أجهزة خاصة 70-30',
            'is_active' => true,
            'is_private_equipment' => true,
            'is_session_based' => false,
        ]);

        $activity = Activity::create([
            'branch_id' => $this->branch->id,
            'activity_type_id' => $privateType->id,
            'name' => 'تدريب أجهزة خاص 70',
            'is_private_equipment' => true,
            'is_active' => true,
        ]);

        $staffActivity = StaffActivity::create([
            'staff_id' => $this->coach->id,
            'activity_id' => $activity->id,
        ]);

        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'اشتراك أجهزة خاص 70%',
            'base_price' => 1000.00,
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
            'paid_amount' => 1000.00,
            'coach_receipt_number' => 'REC-COACH-70',
            'branch_receipt_number' => 'REC-CLUB-30',
        ];

        $res = $this->postJson('/api/v1/player-subscriptions', $payload);
        $res->assertStatus(201);
        $subId = $res->json('data.id');

        // Revenue split
        $this->assertDatabaseHas('subscription_revenue_splits', [
            'player_subscription_id' => $subId,
            'coach_id' => $this->coach->id,
            'total_amount' => 1000.00,
            'coach_percentage' => 70.00,
            'club_percentage' => 30.00,
            'coach_amount' => 700.00,
            'club_amount' => 300.00,
        ]);

        // Coach payment: safe_id is NULL
        $this->assertDatabaseHas('payments', [
            'receipt_number' => 'REC-COACH-70',
            'amount' => 700.00,
            'safe_id' => null,
            'reason' => 'دفعة اشتراك المدرب',
        ]);

        // Club payment: safe_id is branch safe
        $this->assertDatabaseHas('payments', [
            'receipt_number' => 'REC-CLUB-30',
            'amount' => 300.00,
            'safe_id' => $this->safeId,
            'reason' => 'دفعة اشتراك النادي',
        ]);
    }

    /**
     * 3. Group training (is_session_based is true):
     * User rule: Entire price enters the safe, split is recorded in revenue_splits table.
     */
    public function test_group_session_entire_price_enters_safe_and_split_is_stored()
    {
        StaffContract::create([
            'staff_id' => $this->coach->id,
            'employment_type' => 'percentage',
            'commission_rate' => 40.00,
            'start_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $groupType = ActivityType::create([
            'name' => 'تدريب جماعي كيك بوكسينغ',
            'is_active' => true,
            'is_private_equipment' => false,
            'is_session_based' => true,
        ]);

        $activity = Activity::create([
            'branch_id' => $this->branch->id,
            'activity_type_id' => $groupType->id,
            'name' => 'كيك بوكسينغ جماعي',
            'is_active' => true,
        ]);

        $staffActivity = StaffActivity::create([
            'staff_id' => $this->coach->id,
            'activity_id' => $activity->id,
        ]);

        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'كيك بوكسينغ 12 جلسة',
            'base_price' => 600.00,
            'session_count' => 12,
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
            'paid_amount' => 600.00,
            'receipt_number' => 'REC-GROUP-ALL',
        ];

        $res = $this->postJson('/api/v1/player-subscriptions', $payload);
        $res->assertStatus(201);
        $subId = $res->json('data.id');

        // Revenue split is recorded
        $this->assertDatabaseHas('subscription_revenue_splits', [
            'player_subscription_id' => $subId,
            'coach_id' => $this->coach->id,
            'total_amount' => 600.00,
            'coach_percentage' => 40.00,
            'club_percentage' => 60.00,
            'coach_amount' => 240.00,
            'club_amount' => 360.00,
        ]);

        // Payment: entire 600.00 enters the safe!
        $this->assertDatabaseHas('payments', [
            'receipt_number' => 'REC-GROUP-ALL',
            'amount' => 600.00,
            'safe_id' => $this->safeId,
            'reason' => 'دفعة اشتراك',
        ]);
    }

    /**
     * 4. Update subscription: explicit status (e.g. frozen) is preserved and not overridden.
     */
    public function test_updating_subscription_respects_explicit_status()
    {
        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'اشتراك عادي',
            'base_price' => 200.00,
            'status' => 'active',
        ]);

        $payload = [
            'member_id' => $this->member->id,
            'plan_id' => $plan->id,
            'start_date' => now()->subDays(50)->toDateString(),
            'end_date' => now()->subDays(20)->toDateString(), // expired dates
            'paid_amount' => 200.00,
        ];

        $createRes = $this->postJson('/api/v1/player-subscriptions', $payload);
        $createRes->assertStatus(201);
        $subId = $createRes->json('data.id');

        // Admin updates status explicitly to 'frozen'
        $updateRes = $this->putJson("/api/v1/player-subscriptions/{$subId}", [
            'status' => 'frozen',
            'reason' => 'تجميد الاشتراك لظروف خاصة',
        ]);

        $updateRes->assertStatus(200);
        $this->assertEquals('frozen', $updateRes->json('data.status'));
        $this->assertDatabaseHas('player_subscriptions', [
            'id' => $subId,
            'status' => 'frozen',
        ]);
    }
}
