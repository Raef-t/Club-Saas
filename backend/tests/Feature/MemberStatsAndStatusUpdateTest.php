<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Branch;
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Modules\SubscriptionManager\Models\PlayerSubscription;
use Modules\MemberManager\Models\Member;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

class MemberStatsAndStatusUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Branch $branch1;
    protected Branch $branch2;
    protected Member $activeMaleMember;
    protected Member $activeMaleMember2;
    protected Member $inactiveFemaleMember;

    protected function setUp(): void
    {
        parent::setUp();

        $adminPerson = Person::create([
            'full_name' => 'Admin Tester ' . uniqid(),
            'gender'    => 'male',
            'type'      => 'staff',
        ]);

        $this->adminUser = User::create([
            'person_id' => $adminPerson->id,
            'username'  => 'admin_tester_' . uniqid(),
            'password'  => bcrypt('password'),
            'is_active' => true,
        ]);

        $role = Role::firstOrCreate([
            'name'       => 'super_admin',
            'guard_name' => 'sanctum',
        ]);
        $this->adminUser->assignRole($role);
        Sanctum::actingAs($this->adminUser, ['*']);

        $club = Club::create([
            'name'      => 'Test Club ' . uniqid(),
            'is_active' => true,
        ]);

        $this->branch1 = Branch::create([
            'club_id'   => $club->id,
            'name'      => 'Branch Alpha ' . uniqid(),
            'is_active' => true,
        ]);

        $this->branch2 = Branch::create([
            'club_id'   => $club->id,
            'name'      => 'Branch Beta ' . uniqid(),
            'is_active' => true,
        ]);

        // Member 1: Active Male in Branch 1
        $p1 = Person::create(['full_name' => 'Ahmad Active', 'gender' => 'male']);
        $this->activeMaleMember = Member::create([
            'person_id'         => $p1->id,
            'branch_id'         => $this->branch1->id,
            'member_number'     => 'MEM-2026-0001',
            'membership_status' => 'active',
            'join_date'         => now()->subDays(10),
        ]);

        // Member 2: Active Male in Branch 1
        $p2 = Person::create(['full_name' => 'Samer Active', 'gender' => 'male']);
        $this->activeMaleMember2 = Member::create([
            'person_id'         => $p2->id,
            'branch_id'         => $this->branch1->id,
            'member_number'     => 'MEM-2026-0002',
            'membership_status' => 'active',
            'join_date'         => now()->subDays(5),
        ]);

        // Member 3: Inactive Female in Branch 1
        $p3 = Person::create(['full_name' => 'Noor Inactive', 'gender' => 'female']);
        $this->inactiveFemaleMember = Member::create([
            'person_id'         => $p3->id,
            'branch_id'         => $this->branch1->id,
            'member_number'     => 'MEM-2026-0003',
            'membership_status' => 'inactive',
            'join_date'         => now()->subDays(20),
        ]);

        // Member 4: In Branch 2
        $p4 = Person::create(['full_name' => 'Rami Other', 'gender' => 'male']);
        Member::create([
            'person_id'         => $p4->id,
            'branch_id'         => $this->branch2->id,
            'member_number'     => 'MEM-2026-0004',
            'membership_status' => 'active',
            'join_date'         => now()->subDays(2),
        ]);
    }

    public function test_members_index_returns_accurate_stats_and_is_active_flags(): void
    {
        // 1. Scoped to branch 1 with pagination
        $response = $this->getJson('/api/v1/members?branch_id=' . $this->branch1->id . '&per_page=15');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data',
            'stats' => [
                'total_members',
                'active_members',
                'inactive_members',
                'male_members',
                'female_members',
            ],
            'meta' => ['total', 'current_page'],
        ]);

        $stats = $response->json('stats');
        $this->assertEquals(3, $stats['total_members']);
        $this->assertEquals(2, $stats['active_members']);
        $this->assertEquals(1, $stats['inactive_members']);
        $this->assertEquals(2, $stats['male_members']);
        $this->assertEquals(1, $stats['female_members']);

        // 2. Also non-paginated (per_page=all) returns stats
        $nonPaginated = $this->getJson('/api/v1/members?branch_id=' . $this->branch1->id . '&per_page=all');
        $nonPaginated->assertStatus(200);
        $this->assertArrayHasKey('stats', $nonPaginated->json());
        $this->assertEquals(3, $nonPaginated->json('stats.total_members'));

        // Check is_active and status fields on records
        $items = $response->json('data');
        $this->assertCount(3, $items);

        $ahmad = collect($items)->firstWhere('member_number', 'MEM-2026-0001');
        $this->assertNotNull($ahmad);
        $this->assertTrue($ahmad['is_active']);
        $this->assertEquals('active', $ahmad['membership_status']);
        $this->assertEquals('active', $ahmad['status']);

        $noor = collect($items)->firstWhere('member_number', 'MEM-2026-0003');
        $this->assertNotNull($noor);
        $this->assertFalse($noor['is_active']);
        $this->assertEquals('inactive', $noor['membership_status']);
        $this->assertEquals('inactive', $noor['status']);
    }

    public function test_members_stats_endpoint_returns_accurate_stats(): void
    {
        $response = $this->getJson('/api/v1/members/stats?branch_id=' . $this->branch1->id);

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals(3, $data['total_members']);
        $this->assertEquals(2, $data['active_members']);
        $this->assertEquals(1, $data['inactive_members']);
        $this->assertEquals(2, $data['male_members']);
        $this->assertEquals(1, $data['female_members']);
    }

    public function test_update_member_status_from_active_to_inactive(): void
    {
        $this->assertEquals('active', $this->activeMaleMember->membership_status);

        $response = $this->putJson('/api/v1/members/' . $this->activeMaleMember->id, [
            'reason'            => 'Temporarily suspended by club administration',
            'membership_status' => 'inactive',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.membership_status', 'inactive');
        $response->assertJsonPath('data.status', 'inactive');
        $response->assertJsonPath('data.is_active', false);

        $this->activeMaleMember->refresh();
        $this->assertEquals('inactive', $this->activeMaleMember->membership_status);
        $this->assertFalse($this->activeMaleMember->is_active);
    }

    public function test_update_member_status_using_status_and_is_active_aliases(): void
    {
        // 1. Update inactive female to active using status: active
        $response = $this->putJson('/api/v1/members/' . $this->inactiveFemaleMember->id, [
            'reason' => 'Reactivating membership upon request',
            'status' => 'active',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.membership_status', 'active');
        $response->assertJsonPath('data.is_active', true);

        $this->inactiveFemaleMember->refresh();
        $this->assertEquals('active', $this->inactiveFemaleMember->membership_status);

        // 2. Update to inactive using is_active: false
        $response2 = $this->putJson('/api/v1/members/' . $this->inactiveFemaleMember->id, [
            'reason'    => 'Deactivating again',
            'is_active' => false,
        ]);

        $response2->assertStatus(200);
        $response2->assertJsonPath('data.membership_status', 'inactive');
        $response2->assertJsonPath('data.is_active', false);

        $this->inactiveFemaleMember->refresh();
        $this->assertEquals('inactive', $this->inactiveFemaleMember->membership_status);
    }

    public function test_filter_members_by_status(): void
    {
        // Active filter
        $activeRes = $this->getJson('/api/v1/members?branch_id=' . $this->branch1->id . '&status=active');
        $activeRes->assertStatus(200);
        $this->assertCount(2, $activeRes->json('data'));

        // Inactive filter
        $inactiveRes = $this->getJson('/api/v1/members?branch_id=' . $this->branch1->id . '&status=inactive');
        $inactiveRes->assertStatus(200);
        $this->assertCount(1, $inactiveRes->json('data'));
        $this->assertEquals('MEM-2026-0003', $inactiveRes->json('data.0.member_number'));

        // is_active alias filter
        $inactiveByFlag = $this->getJson('/api/v1/members?branch_id=' . $this->branch1->id . '&is_active=false');
        $inactiveByFlag->assertStatus(200);
        $this->assertCount(1, $inactiveByFlag->json('data'));
    }

    public function test_player_subscription_resource_exposes_member_status_and_is_active(): void
    {
        $plan = SubscriptionPlan::create([
            'branch_id'  => $this->branch1->id,
            'name'       => 'Test Plan ' . uniqid(),
            'base_price' => 100.00,
            'status'     => 'active',
        ]);

        $subscription = PlayerSubscription::create([
            'member_id'    => $this->inactiveFemaleMember->id,
            'plan_id'      => $plan->id,
            'status'       => 'active',
            'total_amount' => 100.00,
            'paid_amount'  => 100.00,
            'start_date'   => now()->toDateString(),
            'end_date'     => now()->addMonth()->toDateString(),
            'currency'     => 'SYP',
        ]);

        $response = $this->getJson('/api/v1/player-subscriptions/' . $subscription->id);

        $response->assertStatus(200);
        $response->assertJsonPath('data.member.member_number', 'MEM-2026-0003');
        $response->assertJsonPath('data.member.membership_status', 'inactive');
        $response->assertJsonPath('data.member.status', 'inactive');
        $response->assertJsonPath('data.member.is_active', false);
    }
}
