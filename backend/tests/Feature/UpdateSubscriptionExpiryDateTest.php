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
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Modules\SubscriptionManager\Models\SubscriptionPlanActivity;
use Modules\SubscriptionManager\Models\PlayerSubscription;
use Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus;
use Modules\MemberManager\Models\Member;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Laravel\Sanctum\Sanctum;

class UpdateSubscriptionExpiryDateTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $branch;
    protected $coach;
    protected $member;
    protected $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $person = Person::create([
            'full_name' => 'Admin Test User',
            'gender' => 'male',
            'type' => 'staff',
        ]);

        $this->user = User::create([
            'person_id' => $person->id,
            'username' => 'admin_expiry_' . uniqid(),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $role = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'sanctum',
        ]);
        $this->user->assignRole($role);
        Sanctum::actingAs($this->user, ['*']);

        $club = Club::create(['name' => 'Gold Gym', 'is_active' => true]);
        $this->branch = Branch::create(['club_id' => $club->id, 'name' => 'Damascus Branch', 'is_active' => true]);

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
            'name' => 'Main Safe',
            'account_id' => $accountId,
            'currency' => 'SYP',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Coach
        $coachPerson = Person::create(['full_name' => 'Captain Omar', 'gender' => 'male', 'type' => 'staff']);
        $this->coach = Staff::create([
            'person_id' => $coachPerson->id,
            'role' => 'coach',
            'work_status' => 'active',
            'is_active' => true,
        ]);

        // Member
        $memberPerson = Person::create(['full_name' => 'Tariq Player', 'gender' => 'male', 'type' => 'player']);
        $this->member = Member::create([
            'person_id' => $memberPerson->id,
            'branch_id' => $this->branch->id,
            'member_number' => 'M-' . uniqid(),
            'status' => 'active',
        ]);

        // Activity Type & Activity
        $activityType = ActivityType::create([
            'name' => 'لياقة بدنية',
            'is_active' => true,
            'is_session_based' => false,
            'has_unlimited_subscribers' => true,
        ]);

        $activity = Activity::create([
            'branch_id' => $this->branch->id,
            'activity_type_id' => $activityType->id,
            'name' => 'فتنس',
            'is_active' => true,
        ]);

        $staffActivity = StaffActivity::create([
            'staff_id' => $this->coach->id,
            'activity_id' => $activity->id,
        ]);

        $this->plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'الاشتراك الشهري',
            'base_price' => 100.00,
            'sessions_per_week' => 3,
            'session_count' => 12,
            'max_subscribers' => 0,
            'current_subscribers' => 0,
            'status' => 'active',
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $this->plan->id,
            'staff_activity_id' => $staffActivity->id,
        ]);
    }

    public function test_updating_subscription_end_date_to_past_marks_it_as_finished_and_decrements_subscribers()
    {
        // 1. Create an active subscription
        $createRes = $this->postJson('/api/v1/player-subscriptions', [
            'member_id' => $this->member->id,
            'plan_id' => $this->plan->id,
            'months_count' => 1,
            'start_date' => now()->toDateString(),
            'paid_amount' => 100.00,
            'payment_method' => 'cash',
        ]);

        $createRes->assertStatus(201);
        $subId = $createRes->json('data.id');

        $this->plan->refresh();
        $this->assertEquals(1, $this->plan->current_subscribers);

        // 2. Update subscription end date to a past date (even sending status => 'active' as frontend form does)
        $pastDate = now()->subDays(3)->toDateString();
        $updateRes = $this->putJson("/api/v1/player-subscriptions/{$subId}", [
            'end_date' => $pastDate,
            'status' => 'active', // Frontend sends form.status which is active
            'reason' => 'تعديل تاريخ النهاية إلى الماضي',
        ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('data.status', PlayerSubscriptionStatus::FINISHED->value)
            ->assertJsonPath('data.status_label', 'منتهي');

        $this->assertDatabaseHas('player_subscriptions', [
            'id' => $subId,
            'status' => PlayerSubscriptionStatus::FINISHED->value,
            'end_date' => $pastDate,
        ]);

        $this->plan->refresh();
        $this->assertEquals(0, $this->plan->current_subscribers);
    }

    public function test_updating_subscription_start_date_and_months_without_end_date_expires_and_calculates_end_date()
    {
        $createRes = $this->postJson('/api/v1/player-subscriptions', [
            'member_id' => $this->member->id,
            'plan_id' => $this->plan->id,
            'months_count' => 1,
            'start_date' => now()->toDateString(),
            'paid_amount' => 100.00,
            'payment_method' => 'cash',
        ]);

        $createRes->assertStatus(201);
        $subId = $createRes->json('data.id');

        // Update start date to 3 months ago with months_count = 1 (ends 2 months ago) without passing end_date
        $startDatePast = now()->subMonths(3)->toDateString();
        $expectedEndDate = \Carbon\Carbon::parse($startDatePast)->addMonth()->toDateString();

        $updateRes = $this->putJson("/api/v1/player-subscriptions/{$subId}", [
            'start_date' => $startDatePast,
            'months_count' => 1,
            'status' => 'active',
            'reason' => 'تعديل تاريخ البدء فقط',
        ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('data.status', PlayerSubscriptionStatus::FINISHED->value)
            ->assertJsonPath('data.status_label', 'منتهي')
            ->assertJsonPath('data.end_date', $expectedEndDate);

        $this->assertDatabaseHas('player_subscriptions', [
            'id' => $subId,
            'status' => PlayerSubscriptionStatus::FINISHED->value,
            'end_date' => $expectedEndDate,
        ]);

        $this->plan->refresh();
        $this->assertEquals(0, $this->plan->current_subscribers);
    }

    public function test_extending_finished_subscription_to_future_reactivates_it_and_increments_subscribers()
    {
        // 1. Manually create an expired/finished subscription
        $sub = PlayerSubscription::create([
            'member_id' => $this->member->id,
            'plan_id' => $this->plan->id,
            'months_count' => 1,
            'total_amount' => 100.00,
            'paid_amount' => 100.00,
            'remaining_amount' => 0,
            'start_date' => now()->subMonths(2)->toDateString(),
            'end_date' => now()->subMonth()->toDateString(),
            'status' => PlayerSubscriptionStatus::FINISHED->value,
        ]);

        $this->assertEquals(0, $this->plan->fresh()->current_subscribers);

        // 2. Extend subscription into the future (sending status => 'finished' from prefill)
        $futureDate = now()->addMonth()->toDateString();
        $updateRes = $this->putJson("/api/v1/player-subscriptions/{$sub->id}", [
            'end_date' => $futureDate,
            'status' => 'finished',
            'reason' => 'تمديد الاشتراك المنتهي',
        ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('data.status', PlayerSubscriptionStatus::ACTIVE->value)
            ->assertJsonPath('data.status_label', 'فعال')
            ->assertJsonPath('data.end_date', $futureDate);

        $this->assertDatabaseHas('player_subscriptions', [
            'id' => $sub->id,
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
            'end_date' => $futureDate,
        ]);

        $this->plan->refresh();
        $this->assertEquals(1, $this->plan->current_subscribers);
    }

    public function test_terminated_subscription_is_not_overwritten_on_date_update()
    {
        $sub = PlayerSubscription::create([
            'member_id' => $this->member->id,
            'plan_id' => $this->plan->id,
            'months_count' => 1,
            'total_amount' => 100.00,
            'paid_amount' => 100.00,
            'remaining_amount' => 0,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => PlayerSubscriptionStatus::TERMINATED->value,
        ]);

        $pastDate = now()->subDays(5)->toDateString();
        $updateRes = $this->putJson("/api/v1/player-subscriptions/{$sub->id}", [
            'end_date' => $pastDate,
            'status' => 'terminated',
            'reason' => 'تعديل تاريخ لاشتراك ملغى إدارياً',
        ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('data.status', PlayerSubscriptionStatus::TERMINATED->value);

        $this->assertDatabaseHas('player_subscriptions', [
            'id' => $sub->id,
            'status' => PlayerSubscriptionStatus::TERMINATED->value,
        ]);
    }

    public function test_updating_active_subscription_with_future_date_remains_active()
    {
        $createRes = $this->postJson('/api/v1/player-subscriptions', [
            'member_id' => $this->member->id,
            'plan_id' => $this->plan->id,
            'months_count' => 1,
            'start_date' => now()->toDateString(),
            'paid_amount' => 100.00,
            'payment_method' => 'cash',
        ]);

        $createRes->assertStatus(201);
        $subId = $createRes->json('data.id');

        $futureDate = now()->addMonths(2)->toDateString();
        $updateRes = $this->putJson("/api/v1/player-subscriptions/{$subId}", [
            'end_date' => $futureDate,
            'status' => 'active',
            'reason' => 'تعديل تاريخ لتاريخ مستقبلي',
        ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('data.status', PlayerSubscriptionStatus::ACTIVE->value)
            ->assertJsonPath('data.status_label', 'فعال');

        $this->assertDatabaseHas('player_subscriptions', [
            'id' => $subId,
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
        ]);
    }
}
