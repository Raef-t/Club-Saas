<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Branch;
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Modules\SubscriptionManager\Models\PlayerSubscription;
use Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus;
use Modules\MemberManager\Models\Member;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class SubscriberSearchByNameNumberUsernameTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Branch $branch;
    protected SubscriptionPlan $plan;
    protected Member $member1;
    protected Member $member2;
    protected User $playerUser1;
    protected User $playerUser2;

    protected function setUp(): void
    {
        parent::setUp();

        $adminPerson = Person::create([
            'full_name' => 'Admin Tester ' . uniqid(),
            'gender' => 'male',
            'type' => 'staff',
        ]);

        $this->adminUser = User::create([
            'person_id' => $adminPerson->id,
            'username' => 'admin_sub_search_' . uniqid(),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $role = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'sanctum',
        ]);
        $this->adminUser->assignRole($role);
        Sanctum::actingAs($this->adminUser, ['*']);

        $club = Club::create([
            'name' => 'Search Test Club ' . uniqid(),
            'is_active' => true,
        ]);
        $this->branch = Branch::create([
            'club_id' => $club->id,
            'name' => 'Search Branch ' . uniqid(),
            'is_active' => true,
        ]);

        $this->plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Search Fitness Plan ' . uniqid(),
            'base_price' => 200.00,
            'status' => 'active',
        ]);

        // Member 1
        $person1 = Person::create([
            'full_name' => 'Tariq Al-Mansoor',
            'gender' => 'male',
            'type' => 'player',
        ]);
        $this->member1 = Member::create([
            'person_id' => $person1->id,
            'branch_id' => $this->branch->id,
            'member_number' => 'MEM-TRQ-' . uniqid(),
            'membership_status' => 'active',
            'join_date' => now()->toDateString(),
        ]);
        $this->playerUser1 = User::create([
            'person_id' => $person1->id,
            'username' => 'tariq_user_' . uniqid(),
            'custom_username' => 'tariq_custom_' . uniqid(),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        // Member 2
        $person2 = Person::create([
            'full_name' => 'Layla Hassan',
            'gender' => 'female',
            'type' => 'player',
        ]);
        $this->member2 = Member::create([
            'person_id' => $person2->id,
            'branch_id' => $this->branch->id,
            'member_number' => 'MEM-LYL-' . uniqid(),
            'membership_status' => 'active',
            'join_date' => now()->toDateString(),
        ]);
        $this->playerUser2 = User::create([
            'person_id' => $person2->id,
            'username' => 'layla_user_' . uniqid(),
            'custom_username' => 'layla_custom_' . uniqid(),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
    }

    public function test_search_player_subscriptions_by_username(): void
    {
        $sub1 = PlayerSubscription::create([
            'member_id' => $this->member1->id,
            'plan_id' => $this->plan->id,
            'months_count' => 1,
            'total_amount' => 200.00,
            'paid_amount' => 200.00,
            'remaining_amount' => 0.00,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addMonth()->toDateString(),
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
        ]);

        $sub2 = PlayerSubscription::create([
            'member_id' => $this->member2->id,
            'plan_id' => $this->plan->id,
            'months_count' => 1,
            'total_amount' => 200.00,
            'paid_amount' => 200.00,
            'remaining_amount' => 0.00,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addMonth()->toDateString(),
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
        ]);

        // 1. General search query param by username
        $res = $this->getJson("/api/v1/player-subscriptions?search={$this->playerUser1->username}");
        $res->assertStatus(200);
        $ids = collect($res->json('data'))->pluck('id')->toArray();
        $this->assertContains($sub1->id, $ids);
        $this->assertNotContains($sub2->id, $ids);

        // 2. General search query param by custom_username
        $resCustom = $this->getJson("/api/v1/player-subscriptions?search={$this->playerUser2->custom_username}");
        $resCustom->assertStatus(200);
        $idsCustom = collect($resCustom->json('data'))->pluck('id')->toArray();
        $this->assertContains($sub2->id, $idsCustom);
        $this->assertNotContains($sub1->id, $idsCustom);

        // 3. Direct username filter param
        $resDirectUser = $this->getJson("/api/v1/player-subscriptions?username={$this->playerUser1->username}");
        $resDirectUser->assertStatus(200);
        $idsDirectUser = collect($resDirectUser->json('data'))->pluck('id')->toArray();
        $this->assertContains($sub1->id, $idsDirectUser);
        $this->assertNotContains($sub2->id, $idsDirectUser);

        // 4. Direct number / member_number filter param
        $resNum = $this->getJson("/api/v1/player-subscriptions?number={$this->member2->member_number}");
        $resNum->assertStatus(200);
        $idsNum = collect($resNum->json('data'))->pluck('id')->toArray();
        $this->assertContains($sub2->id, $idsNum);
        $this->assertNotContains($sub1->id, $idsNum);

        // 5. Direct name filter param
        $resName = $this->getJson("/api/v1/player-subscriptions?name=Tariq");
        $resName->assertStatus(200);
        $idsName = collect($resName->json('data'))->pluck('id')->toArray();
        $this->assertContains($sub1->id, $idsName);
        $this->assertNotContains($sub2->id, $idsName);
    }

    public function test_search_plan_active_players_by_name_number_and_username(): void
    {
        $sub1 = PlayerSubscription::create([
            'member_id' => $this->member1->id,
            'plan_id' => $this->plan->id,
            'months_count' => 1,
            'total_amount' => 200.00,
            'paid_amount' => 200.00,
            'remaining_amount' => 0.00,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addMonth()->toDateString(),
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
        ]);

        $sub2 = PlayerSubscription::create([
            'member_id' => $this->member2->id,
            'plan_id' => $this->plan->id,
            'months_count' => 1,
            'total_amount' => 200.00,
            'paid_amount' => 200.00,
            'remaining_amount' => 0.00,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addMonth()->toDateString(),
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
        ]);

        // 1. Search by username in plan players
        $resSearchUser = $this->getJson("/api/v1/subscription-plans/{$this->plan->id}/players?search={$this->playerUser1->username}");
        $resSearchUser->assertStatus(200);
        $players = $resSearchUser->json('data.players');
        $this->assertCount(1, $players);
        $this->assertEquals($this->playerUser1->username, $players[0]['username']);

        // 2. Direct filter by username in plan players
        $resDirectUser = $this->getJson("/api/v1/subscription-plans/{$this->plan->id}/players?username={$this->playerUser2->username}");
        $resDirectUser->assertStatus(200);
        $players2 = $resDirectUser->json('data.players');
        $this->assertCount(1, $players2);
        $this->assertEquals($this->playerUser2->username, $players2[0]['username']);

        // 3. Search by member_number in plan players
        $resSearchNum = $this->getJson("/api/v1/subscription-plans/{$this->plan->id}/players?number={$this->member1->member_number}");
        $resSearchNum->assertStatus(200);
        $playersNum = $resSearchNum->json('data.players');
        $this->assertCount(1, $playersNum);
        $this->assertEquals($this->member1->member_number, $playersNum[0]['member_number']);

        // 4. Search by name in plan players
        $resSearchName = $this->getJson("/api/v1/subscription-plans/{$this->plan->id}/players?name=Layla");
        $resSearchName->assertStatus(200);
        $playersName = $resSearchName->json('data.players');
        $this->assertCount(1, $playersName);
        $this->assertEquals('Layla Hassan', $playersName[0]['full_name']);
    }

    public function test_search_members_endpoint_by_name_number_and_username(): void
    {
        // 1. General search by username
        $res = $this->getJson("/api/v1/members?search={$this->playerUser1->username}");
        $res->assertStatus(200);
        $ids = collect($res->json('data'))->pluck('id')->toArray();
        $this->assertContains($this->member1->id, $ids);
        $this->assertNotContains($this->member2->id, $ids);

        // 2. Direct filter by username
        $resUser = $this->getJson("/api/v1/members?username={$this->playerUser2->username}");
        $resUser->assertStatus(200);
        $idsUser = collect($resUser->json('data'))->pluck('id')->toArray();
        $this->assertContains($this->member2->id, $idsUser);
        $this->assertNotContains($this->member1->id, $idsUser);

        // 3. Direct filter by member_number
        $resNum = $this->getJson("/api/v1/members?member_number={$this->member1->member_number}");
        $resNum->assertStatus(200);
        $idsNum = collect($resNum->json('data'))->pluck('id')->toArray();
        $this->assertContains($this->member1->id, $idsNum);
        $this->assertNotContains($this->member2->id, $idsNum);

        // 4. Direct filter by name
        $resName = $this->getJson("/api/v1/members?name=Layla");
        $resName->assertStatus(200);
        $idsName = collect($resName->json('data'))->pluck('id')->toArray();
        $this->assertContains($this->member2->id, $idsName);
        $this->assertNotContains($this->member1->id, $idsName);
    }
}
