<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Branch;
use Modules\Sports\Models\Activity;
use Modules\Sports\Models\StaffActivity;
use Modules\StaffManager\Models\Staff;
use Modules\StaffManager\Models\StaffContract;
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Modules\SubscriptionManager\Models\SubscriptionPlanActivity;
use Modules\SubscriptionManager\Models\SubscriptionRevenueSplit;
use Modules\MemberManager\Models\Member;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Laravel\Sanctum\Sanctum;

class SubscriptionRevenueSplitTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $branch;
    protected $member;
    protected $coach;
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
            'username' => 'admin_split_' . uniqid(),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $role = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'sanctum',
        ]);
        $this->user->assignRole($role);
        Sanctum::actingAs($this->user, ['*']);

        $club = Club::create(['name' => 'Test Split Club', 'is_active' => true]);
        $this->branch = Branch::create(['club_id' => $club->id, 'name' => 'Main Split Branch', 'is_active' => true]);

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

        // Create Member
        $memberPerson = Person::create(['full_name' => 'Member Test', 'gender' => 'male', 'type' => 'player']);
        $this->member = Member::create([
            'person_id' => $memberPerson->id,
            'branch_id' => $this->branch->id,
            'member_number' => 'M-' . uniqid(),
            'status' => 'active',
        ]);

        // Create Coach & Contract with 70% commission
        $coachPerson = Person::create(['full_name' => 'Coach Dania', 'gender' => 'female', 'type' => 'staff']);
        $this->coach = Staff::create([
            'person_id' => $coachPerson->id,
            'role' => 'coach',
            'work_status' => 'active',
            'is_active' => true,
        ]);

        StaffContract::create([
            'staff_id'                => $this->coach->id,
            'employment_type'         => 'hybrid',
            'base_salary'             => 500.00,
            'commission_type'         => 'percentage',
            'commission_rate'         => 20.00, // 20% on general activities (Aerobics, etc.)
            'private_commission_rate' => 70.00, // 70% specifically on Private Equipment subscriptions
            'start_date'              => now()->toDateString(),
            'is_active'               => true,
        ]);

        // Create Activity & Plan
        $activity = Activity::create([
            'name' => 'Private Equipment Gym',
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $staffActivity = StaffActivity::create([
            'staff_id' => $this->coach->id,
            'activity_id' => $activity->id,
        ]);

        $this->plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Private Coach Plan',
            'session_count' => 12,
            'sessions_per_week' => 3,
            'base_price' => 300.00,
            'status' => 'active',
            'max_subscribers' => 0,
            'current_subscribers' => 0,
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $this->plan->id,
            'staff_activity_id' => $staffActivity->id,
        ]);
    }

    public function test_subscribing_member_creates_immutable_revenue_split_from_coach_contract(): void
    {
        $response = $this->postJson("/api/v1/player-subscriptions", [
            'plan_id' => $this->plan->id,
            'member_id' => $this->member->id,
            'start_date' => now()->toDateString(),
            'paid_amount' => 300.00,
        ]);

        $response->assertStatus(201);

        $subscriptionId = $response->json('data.id');
        $this->assertNotNull($subscriptionId);

        // Check subscription_revenue_splits table
        $split = SubscriptionRevenueSplit::where('player_subscription_id', $subscriptionId)->first();
        $this->assertNotNull($split, 'SubscriptionRevenueSplit should be automatically created.');

        $this->assertEquals($this->coach->id, $split->coach_id);
        $this->assertEquals($this->branch->id, $split->branch_id);
        $this->assertEquals(300.00, (float) $split->total_amount);
        $this->assertEquals(70.00, (float) $split->coach_percentage);
        $this->assertEquals(30.00, (float) $split->club_percentage);
        $this->assertEquals(210.00, (float) $split->coach_amount);
        $this->assertEquals(90.00, (float) $split->club_amount);
    }

    public function test_revenue_split_is_immutable_even_if_coach_contract_rate_changes_later(): void
    {
        // 1. First subscription with 70% rate
        $res1 = $this->postJson("/api/v1/player-subscriptions", [
            'plan_id' => $this->plan->id,
            'member_id' => $this->member->id,
            'start_date' => now()->toDateString(),
            'paid_amount' => 300.00,
        ]);
        $res1->assertStatus(201);
        $sub1Id = $res1->json('data.id');

        // 2. Coach gets a contract update to 85% private commission
        $contract = StaffContract::where('staff_id', $this->coach->id)->where('is_active', true)->first();
        $contract->update(['private_commission_rate' => 85.00]);

        // 3. Second subscription for Member 2 with new 85% rate
        $member2Person = Person::create(['full_name' => 'Member 2', 'gender' => 'male', 'type' => 'player']);
        $member2 = Member::create([
            'person_id' => $member2Person->id,
            'branch_id' => $this->branch->id,
            'member_number' => 'M-' . uniqid(),
            'status' => 'active',
        ]);

        $res2 = $this->postJson("/api/v1/player-subscriptions", [
            'plan_id' => $this->plan->id,
            'member_id' => $member2->id,
            'start_date' => now()->toDateString(),
            'paid_amount' => 300.00,
        ]);
        $res2->assertStatus(201);
        $sub2Id = $res2->json('data.id');

        // 4. Verify historical split 1 remains 70% (210$)
        $split1 = SubscriptionRevenueSplit::where('player_subscription_id', $sub1Id)->first();
        $this->assertEquals(70.00, (float) $split1->coach_percentage);
        $this->assertEquals(210.00, (float) $split1->coach_amount);
        $this->assertEquals(90.00, (float) $split1->club_amount);

        // 5. Verify new split 2 took 85% (255$)
        $split2 = SubscriptionRevenueSplit::where('player_subscription_id', $sub2Id)->first();
        $this->assertEquals(85.00, (float) $split2->coach_percentage);
        $this->assertEquals(255.00, (float) $split2->coach_amount);
        $this->assertEquals(45.00, (float) $split2->club_amount);
    }

    public function test_general_activity_plan_like_aerobics_does_not_create_subscription_revenue_split(): void
    {
        // Create an Aerobics activity and plan
        $aerobics = Activity::create([
            'name' => 'Aerobics Group Class',
            'branch_id' => $this->branch->id,
            'is_private_equipment' => false,
            'is_active' => true,
        ]);

        $aerobicsStaffActivity = StaffActivity::create([
            'staff_id' => $this->coach->id,
            'activity_id' => $aerobics->id,
        ]);

        $aerobicsPlan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Aerobics Monthly Plan',
            'session_count' => 12,
            'sessions_per_week' => 3,
            'base_price' => 150.00,
            'status' => 'active',
            'max_subscribers' => 20,
            'current_subscribers' => 0,
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $aerobicsPlan->id,
            'staff_activity_id' => $aerobicsStaffActivity->id,
        ]);

        $member3Person = Person::create(['full_name' => 'Member 3', 'gender' => 'male', 'type' => 'player']);
        $member3 = Member::create([
            'person_id' => $member3Person->id,
            'branch_id' => $this->branch->id,
            'member_number' => 'M-' . uniqid(),
            'status' => 'active',
        ]);

        $response = $this->postJson("/api/v1/player-subscriptions", [
            'plan_id' => $aerobicsPlan->id,
            'member_id' => $member3->id,
            'start_date' => now()->toDateString(),
            'paid_amount' => 150.00,
        ]);
        $response->assertStatus(201);
        $subId = $response->json('data.id');

        // Revenue split table should NOT have a record for general activity plans
        $split = SubscriptionRevenueSplit::where('player_subscription_id', $subId)->first();
        $this->assertNull($split, 'General activity plans (Aerobics) must not create a subscription_revenue_splits record.');
    }

    public function test_soft_deleting_subscription_with_refund_deletes_revenue_split(): void
    {
        $memberPerson = Person::create(['full_name' => 'Member Refunded', 'gender' => 'male', 'type' => 'player']);
        $member = Member::create([
            'person_id' => $memberPerson->id,
            'branch_id' => $this->branch->id,
            'member_number' => 'M-' . uniqid(),
            'status' => 'active',
        ]);

        $createRes = $this->postJson("/api/v1/player-subscriptions", [
            'plan_id' => $this->plan->id,
            'member_id' => $member->id,
            'start_date' => now()->toDateString(),
            'paid_amount' => 300.00,
        ]);
        $createRes->assertStatus(201);
        $subId = $createRes->json('data.id');

        $split = SubscriptionRevenueSplit::where('player_subscription_id', $subId)->first();
        $this->assertNotNull($split);

        // Delete with is_refunded = true
        $delRes = $this->deleteJson("/api/v1/player-subscriptions/{$subId}?is_refunded=true");
        $delRes->assertStatus(200);

        // Subscription should be soft deleted
        $this->assertSoftDeleted('player_subscriptions', ['id' => $subId]);

        // Revenue split should be soft deleted
        $this->assertSoftDeleted('subscription_revenue_splits', ['id' => $split->id]);

        // Restore subscription
        $restoreRes = $this->postJson("/api/v1/player-subscriptions/{$subId}/restore");
        $restoreRes->assertStatus(200);

        // Revenue split should be restored
        $this->assertDatabaseHas('subscription_revenue_splits', ['id' => $split->id, 'deleted_at' => null]);
    }

    public function test_soft_deleting_subscription_without_refund_preserves_revenue_split(): void
    {
        $memberPerson = Person::create(['full_name' => 'Member No Refund', 'gender' => 'male', 'type' => 'player']);
        $member = Member::create([
            'person_id' => $memberPerson->id,
            'branch_id' => $this->branch->id,
            'member_number' => 'M-' . uniqid(),
            'status' => 'active',
        ]);

        $createRes = $this->postJson("/api/v1/player-subscriptions", [
            'plan_id' => $this->plan->id,
            'member_id' => $member->id,
            'start_date' => now()->toDateString(),
            'paid_amount' => 300.00,
        ]);
        $createRes->assertStatus(201);
        $subId = $createRes->json('data.id');

        $split = SubscriptionRevenueSplit::where('player_subscription_id', $subId)->first();
        $this->assertNotNull($split);

        // Delete with is_refunded = false
        $delRes = $this->deleteJson("/api/v1/player-subscriptions/{$subId}?is_refunded=false");
        $delRes->assertStatus(200);

        // Subscription should be soft deleted
        $this->assertSoftDeleted('player_subscriptions', ['id' => $subId]);

        // Revenue split should NOT be deleted (preserved)
        $this->assertDatabaseHas('subscription_revenue_splits', ['id' => $split->id, 'deleted_at' => null]);
    }
}
