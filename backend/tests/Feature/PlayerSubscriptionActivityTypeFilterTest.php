<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Branch;
use Modules\Sports\Models\ActivityType;
use Modules\Sports\Models\Activity;
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
use Carbon\Carbon;

class PlayerSubscriptionActivityTypeFilterTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Branch $branch;
    protected Staff $coach;

    protected ActivityType $generalType;
    protected ActivityType $privateType;
    protected ActivityType $groupSessionType;
    protected ActivityType $swimmingType;

    protected SubscriptionPlan $generalPlan;
    protected SubscriptionPlan $privatePlan;
    protected SubscriptionPlan $groupPlan;
    protected SubscriptionPlan $swimmingPlan;

    protected Member $member1;
    protected Member $member2;
    protected Member $member3;

    protected PlayerSubscription $subGeneral;
    protected PlayerSubscription $subPrivate;
    protected PlayerSubscription $subGroup;
    protected PlayerSubscription $subSwimming;

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
            'full_name' => 'Admin Act Filter ' . uniqid(),
            'gender' => 'male',
            'type' => 'staff',
        ]);

        $this->user = User::create([
            'person_id' => $person->id,
            'username' => 'admin_act_' . uniqid(),
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
            'name' => 'Filter Test Club ' . uniqid(),
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'club_id' => $club->id,
            'name' => 'Main Filter Branch ' . uniqid(),
            'status' => 'active',
        ]);

        // Coach
        $coachPerson = Person::create(['full_name' => 'Coach Omar ' . uniqid(), 'gender' => 'male', 'type' => 'staff']);
        $this->coach = Staff::create([
            'person_id' => $coachPerson->id,
            'branch_id' => $this->branch->id,
            'employment_type' => 'trainer',
            'job_title' => 'Fitness Trainer',
            'role' => 'coach',
            'work_status' => 'active',
            'is_active' => true,
        ]);

        // 1. Activity Types
        $this->generalType = ActivityType::create([
            'name' => 'تدريب عام',
            'is_active' => true,
            'is_session_based' => false,
            'has_unlimited_subscribers' => true,
            'has_shifts' => true,
            'is_private_equipment' => false,
        ]);

        $this->privateType = ActivityType::create([
            'name' => 'تدريب خاص',
            'is_active' => true,
            'is_session_based' => false,
            'has_unlimited_subscribers' => true,
            'has_shifts' => false,
            'is_private_equipment' => true,
        ]);

        $this->groupSessionType = ActivityType::create([
            'name' => 'حصة جماعية',
            'is_active' => true,
            'is_session_based' => true,
            'has_unlimited_subscribers' => false,
            'has_shifts' => false,
            'is_private_equipment' => false,
        ]);

        $this->swimmingType = ActivityType::create([
            'name' => 'سباحة حرة',
            'is_active' => true,
            'is_session_based' => false,
            'has_unlimited_subscribers' => true,
            'is_private_equipment' => false,
        ]);

        // 2. Activities
        $generalAct = Activity::create([
            'branch_id' => $this->branch->id,
            'activity_type_id' => $this->generalType->id,
            'name' => 'أجهزة وتدريب عام',
            'is_active' => true,
        ]);

        $privateAct = Activity::create([
            'branch_id' => $this->branch->id,
            'activity_type_id' => $this->privateType->id,
            'name' => 'حديد خاص',
            'is_private_equipment' => true,
            'is_active' => true,
        ]);

        $groupAct = Activity::create([
            'branch_id' => $this->branch->id,
            'activity_type_id' => $this->groupSessionType->id,
            'name' => 'كيك بوكسينغ',
            'is_active' => true,
        ]);

        $swimmingAct = Activity::create([
            'branch_id' => $this->branch->id,
            'activity_type_id' => $this->swimmingType->id,
            'name' => 'مسبح أولمبي',
            'is_active' => true,
        ]);

        // Staff Activities
        $saGeneral = StaffActivity::create(['staff_id' => $this->coach->id, 'activity_id' => $generalAct->id]);
        $saPrivate = StaffActivity::create(['staff_id' => $this->coach->id, 'activity_id' => $privateAct->id]);
        $saGroup = StaffActivity::create(['staff_id' => $this->coach->id, 'activity_id' => $groupAct->id]);
        $saSwimming = StaffActivity::create(['staff_id' => $this->coach->id, 'activity_id' => $swimmingAct->id]);

        // 3. Plans
        $this->generalPlan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'باقة التدريب العام',
            'base_price' => 100.00,
            'status' => 'active',
        ]);
        SubscriptionPlanActivity::create(['plan_id' => $this->generalPlan->id, 'staff_activity_id' => $saGeneral->id]);

        $this->privatePlan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'باقة التدريب الخاص VIP',
            'base_price' => 300.00,
            'status' => 'active',
        ]);
        SubscriptionPlanActivity::create(['plan_id' => $this->privatePlan->id, 'staff_activity_id' => $saPrivate->id]);

        $this->groupPlan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'باقة حصص كيك بوكسينغ',
            'session_count' => 12,
            'base_price' => 150.00,
            'status' => 'active',
        ]);
        SubscriptionPlanActivity::create(['plan_id' => $this->groupPlan->id, 'staff_activity_id' => $saGroup->id]);

        $this->swimmingPlan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'باقة السباحة الأولمبية',
            'base_price' => 200.00,
            'status' => 'active',
        ]);
        SubscriptionPlanActivity::create(['plan_id' => $this->swimmingPlan->id, 'staff_activity_id' => $saSwimming->id]);

        // 4. Members
        $p1 = Person::create(['full_name' => 'Tariq Player', 'gender' => 'male', 'type' => 'player']);
        $this->member1 = Member::create([
            'person_id' => $p1->id,
            'branch_id' => $this->branch->id,
            'member_number' => 'MEM-GEN-' . uniqid(),
            'membership_status' => 'active',
        ]);

        $p2 = Person::create(['full_name' => 'Sami Private', 'gender' => 'male', 'type' => 'player']);
        $this->member2 = Member::create([
            'person_id' => $p2->id,
            'branch_id' => $this->branch->id,
            'member_number' => 'MEM-PRV-' . uniqid(),
            'membership_status' => 'active',
        ]);

        $p3 = Person::create(['full_name' => 'Lina Group', 'gender' => 'female', 'type' => 'player']);
        $this->member3 = Member::create([
            'person_id' => $p3->id,
            'branch_id' => $this->branch->id,
            'member_number' => 'MEM-GRP-' . uniqid(),
            'membership_status' => 'active',
        ]);

        // 5. Subscriptions
        $this->subGeneral = PlayerSubscription::create([
            'member_id' => $this->member1->id,
            'plan_id' => $this->generalPlan->id,
            'months_count' => 1,
            'total_amount' => 100.00,
            'paid_amount' => 100.00,
            'remaining_amount' => 0.00,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addMonth()->toDateString(),
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
        ]);

        $this->subPrivate = PlayerSubscription::create([
            'member_id' => $this->member2->id,
            'plan_id' => $this->privatePlan->id,
            'months_count' => 1,
            'total_amount' => 300.00,
            'paid_amount' => 300.00,
            'remaining_amount' => 0.00,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addMonth()->toDateString(),
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
        ]);

        $this->subGroup = PlayerSubscription::create([
            'member_id' => $this->member3->id,
            'plan_id' => $this->groupPlan->id,
            'months_count' => 1,
            'total_amount' => 150.00,
            'paid_amount' => 150.00,
            'remaining_amount' => 0.00,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addMonth()->toDateString(),
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
        ]);

        $this->subSwimming = PlayerSubscription::create([
            'member_id' => $this->member1->id,
            'plan_id' => $this->swimmingPlan->id,
            'months_count' => 1,
            'total_amount' => 200.00,
            'paid_amount' => 200.00,
            'remaining_amount' => 0.00,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addMonth()->toDateString(),
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
        ]);
    }

    /**
     * Test filtering by Private (الخاص / خاص / private)
     */
    public function test_filter_by_private_arabic_and_english(): void
    {
        // 1. Filter: activity_type=الخاص
        $response1 = $this->getJson("/api/v1/player-subscriptions?branch_id={$this->branch->id}&activity_type=الخاص");
        $response1->assertStatus(200);
        $ids1 = collect($response1->json('data'))->pluck('id');
        $this->assertTrue($ids1->contains($this->subPrivate->id), 'Should contain private subscription');
        $this->assertFalse($ids1->contains($this->subGeneral->id), 'Should not contain general subscription');
        $this->assertFalse($ids1->contains($this->subGroup->id), 'Should not contain group session subscription');

        // 2. Filter: activity_type=خاص
        $response2 = $this->getJson("/api/v1/player-subscriptions?branch_id={$this->branch->id}&activity_type=خاص");
        $response2->assertStatus(200);
        $ids2 = collect($response2->json('data'))->pluck('id');
        $this->assertTrue($ids2->contains($this->subPrivate->id));
        $this->assertFalse($ids2->contains($this->subGeneral->id));

        // 3. Filter: type=private
        $response3 = $this->getJson("/api/v1/player-subscriptions?branch_id={$this->branch->id}&type=private");
        $response3->assertStatus(200);
        $ids3 = collect($response3->json('data'))->pluck('id');
        $this->assertTrue($ids3->contains($this->subPrivate->id));
        $this->assertFalse($ids3->contains($this->subGeneral->id));
    }

    /**
     * Test filtering by Public/General (العام / عام / general)
     */
    public function test_filter_by_general_arabic_and_english(): void
    {
        // 1. Filter: activity_type=العام
        $response1 = $this->getJson("/api/v1/player-subscriptions?branch_id={$this->branch->id}&activity_type=العام");
        $response1->assertStatus(200);
        $ids1 = collect($response1->json('data'))->pluck('id');
        $this->assertTrue($ids1->contains($this->subGeneral->id), 'Should contain general subscription');
        $this->assertFalse($ids1->contains($this->subPrivate->id), 'Should not contain private subscription');
        $this->assertFalse($ids1->contains($this->subGroup->id), 'Should not contain group session subscription');

        // 2. Filter: activity_type=عام
        $response2 = $this->getJson("/api/v1/player-subscriptions?branch_id={$this->branch->id}&activity_type=عام");
        $response2->assertStatus(200);
        $ids2 = collect($response2->json('data'))->pluck('id');
        $this->assertTrue($ids2->contains($this->subGeneral->id));
        $this->assertFalse($ids2->contains($this->subPrivate->id));

        // 3. Filter: activity_type=general
        $response3 = $this->getJson("/api/v1/player-subscriptions?branch_id={$this->branch->id}&activity_type=general");
        $response3->assertStatus(200);
        $ids3 = collect($response3->json('data'))->pluck('id');
        $this->assertTrue($ids3->contains($this->subGeneral->id));
        $this->assertFalse($ids3->contains($this->subPrivate->id));
    }

    /**
     * Test filtering by Group Session (الحصة الجماعية / حصة جماعية / group_session)
     */
    public function test_filter_by_group_session_arabic_and_english(): void
    {
        // 1. Filter: activity_type=الحصة الجماعية
        $response1 = $this->getJson("/api/v1/player-subscriptions?branch_id={$this->branch->id}&activity_type=الحصة الجماعية");
        $response1->assertStatus(200);
        $ids1 = collect($response1->json('data'))->pluck('id');
        $this->assertTrue($ids1->contains($this->subGroup->id), 'Should contain group session subscription');
        $this->assertFalse($ids1->contains($this->subGeneral->id), 'Should not contain general subscription');
        $this->assertFalse($ids1->contains($this->subPrivate->id), 'Should not contain private subscription');

        // 2. Filter: activity_type=حصة جماعية
        $response2 = $this->getJson("/api/v1/player-subscriptions?branch_id={$this->branch->id}&activity_type=حصة جماعية");
        $response2->assertStatus(200);
        $ids2 = collect($response2->json('data'))->pluck('id');
        $this->assertTrue($ids2->contains($this->subGroup->id));
        $this->assertFalse($ids2->contains($this->subGeneral->id));

        // 3. Filter: activity_type=group_session
        $response3 = $this->getJson("/api/v1/player-subscriptions?branch_id={$this->branch->id}&activity_type=group_session");
        $response3->assertStatus(200);
        $ids3 = collect($response3->json('data'))->pluck('id');
        $this->assertTrue($ids3->contains($this->subGroup->id));
        $this->assertFalse($ids3->contains($this->subGeneral->id));
    }

    /**
     * Test filtering by Activity Type ID (activity_type_id)
     */
    public function test_filter_by_activity_type_id(): void
    {
        $response = $this->getJson("/api/v1/player-subscriptions?branch_id={$this->branch->id}&activity_type_id={$this->swimmingType->id}");
        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($this->subSwimming->id));
        $this->assertFalse($ids->contains($this->subGeneral->id));
        $this->assertFalse($ids->contains($this->subPrivate->id));
        $this->assertFalse($ids->contains($this->subGroup->id));
    }

    /**
     * Test filtering by custom activity type name (e.g. سباحة)
     */
    public function test_filter_by_custom_activity_type_name(): void
    {
        $response = $this->getJson("/api/v1/player-subscriptions?branch_id={$this->branch->id}&activity_type=سباحة");
        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($this->subSwimming->id));
        $this->assertFalse($ids->contains($this->subGeneral->id));
    }

    /**
     * Test filtering by "all" or "الكل" returns all subscriptions
     */
    public function test_filter_by_all_returns_all_subscriptions(): void
    {
        // 1. activity_type=all
        $responseAll = $this->getJson("/api/v1/player-subscriptions?branch_id={$this->branch->id}&activity_type=all");
        $responseAll->assertStatus(200);
        $idsAll = collect($responseAll->json('data'))->pluck('id');
        $this->assertTrue($idsAll->contains($this->subGeneral->id));
        $this->assertTrue($idsAll->contains($this->subPrivate->id));
        $this->assertTrue($idsAll->contains($this->subGroup->id));
        $this->assertTrue($idsAll->contains($this->subSwimming->id));

        // 2. activity_type=الكل
        $responseKul = $this->getJson("/api/v1/player-subscriptions?branch_id={$this->branch->id}&activity_type=الكل");
        $responseKul->assertStatus(200);
        $idsKul = collect($responseKul->json('data'))->pluck('id');
        $this->assertTrue($idsKul->contains($this->subGeneral->id));
        $this->assertTrue($idsKul->contains($this->subPrivate->id));
        $this->assertTrue($idsKul->contains($this->subGroup->id));
        $this->assertTrue($idsKul->contains($this->subSwimming->id));
    }

    /**
     * Test combining activity_type filter with search and pagination
     */
    public function test_combined_activity_type_with_search_and_pagination(): void
    {
        // Search for "Sami" (member2 who has private subscription) with activity_type=الخاص
        $responseMatch = $this->getJson("/api/v1/player-subscriptions?branch_id={$this->branch->id}&activity_type=الخاص&search=Sami&per_page=15&page=1");
        $responseMatch->assertStatus(200);
        $idsMatch = collect($responseMatch->json('data'))->pluck('id');
        $this->assertTrue($idsMatch->contains($this->subPrivate->id));

        // Search for "Tariq" (member1 who has general subscription) with activity_type=الخاص -> should be empty
        $responseMismatch = $this->getJson("/api/v1/player-subscriptions?branch_id={$this->branch->id}&activity_type=الخاص&search=Tariq&per_page=15&page=1");
        $responseMismatch->assertStatus(200);
        $idsMismatch = collect($responseMismatch->json('data'))->pluck('id');
        $this->assertFalse($idsMismatch->contains($this->subGeneral->id));
        $this->assertEmpty($idsMismatch);
    }
}
