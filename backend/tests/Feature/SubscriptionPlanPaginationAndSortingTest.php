<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Branch;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Spatie\Permission\Models\Role;

class SubscriptionPlanPaginationAndSortingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $person = Person::create(['full_name' => 'Admin User', 'gender' => 'male', 'type' => 'staff']);
        $this->user = User::create([
            'person_id' => $person->id,
            'username' => 'admin_' . uniqid(),
            'password' => 'password123',
            'is_active' => true,
        ]);
        $role = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'sanctum',
        ]);
        $this->user->assignRole($role);
        $this->actingAs($this->user, 'sanctum');

        $club = Club::create(['name' => 'Test Club', 'is_active' => true]);
        $this->branch = Branch::create(['club_id' => $club->id, 'name' => 'Main Branch', 'is_active' => true]);
    }

    public function test_subscription_plans_index_is_sorted_by_name_ascending_by_default(): void
    {
        SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Zumba Plan',
            'base_price' => 150,
            'status' => 'active',
        ]);

        SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Aerobics Plan',
            'base_price' => 100,
            'status' => 'active',
        ]);

        SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Boxing Plan',
            'base_price' => 200,
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/v1/subscription-plans?branch_id={$this->branch->id}");
        $response->assertStatus(200);

        $names = collect($response->json('data'))->pluck('name')->toArray();
        $this->assertEquals(['Aerobics Plan', 'Boxing Plan', 'Zumba Plan'], $names);
    }

    public function test_subscription_plans_index_is_paginated_by_default_with_meta(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            // Plan names with zero-padded number to ensure alphabetical order matches numerical
            $num = str_pad($i, 2, '0', STR_PAD_LEFT);
            SubscriptionPlan::create([
                'branch_id' => $this->branch->id,
                'name' => "Plan {$num}",
                'base_price' => 100 + $i,
                'status' => 'active',
            ]);
        }

        $response = $this->getJson("/api/v1/subscription-plans?branch_id={$this->branch->id}");
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertEquals('success', $json['status']);
        $this->assertIsArray($json['data']);
        $this->assertCount(15, $json['data']);

        $this->assertArrayHasKey('meta', $json);
        $this->assertEquals(1, $json['meta']['current_page']);
        $this->assertEquals(15, $json['meta']['per_page']);
        $this->assertEquals(20, $json['meta']['total']);
        $this->assertEquals(2, $json['meta']['last_page']);

        // Check page 1 names
        $names = collect($json['data'])->pluck('name')->toArray();
        $this->assertEquals('Plan 01', $names[0]);
        $this->assertEquals('Plan 15', $names[14]);
    }

    public function test_subscription_plans_index_pagination_navigation_with_page_and_per_page(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $num = str_pad($i, 2, '0', STR_PAD_LEFT);
            SubscriptionPlan::create([
                'branch_id' => $this->branch->id,
                'name' => "Plan {$num}",
                'base_price' => 100 + $i,
                'status' => 'active',
            ]);
        }

        $response = $this->getJson("/api/v1/subscription-plans?branch_id={$this->branch->id}&page=2&per_page=4");
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertCount(4, $json['data']);
        $this->assertEquals(2, $json['meta']['current_page']);
        $this->assertEquals(4, $json['meta']['per_page']);
        $this->assertEquals(10, $json['meta']['total']);
        $this->assertEquals(3, $json['meta']['last_page']);

        $names = collect($json['data'])->pluck('name')->toArray();
        $this->assertEquals(['Plan 05', 'Plan 06', 'Plan 07', 'Plan 08'], $names);
    }

    public function test_subscription_plans_index_per_page_all_returns_unpaginated_and_sorted_by_name(): void
    {
        SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Yoga Plan',
            'base_price' => 120,
            'status' => 'active',
        ]);

        SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Crossfit Plan',
            'base_price' => 180,
            'status' => 'active',
        ]);

        SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Aquatics Plan',
            'base_price' => 220,
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/v1/subscription-plans?branch_id={$this->branch->id}&per_page=all");
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertCount(3, $json['data']);
        $this->assertArrayNotHasKey('meta', $json);

        $names = collect($json['data'])->pluck('name')->toArray();
        $this->assertEquals(['Aquatics Plan', 'Crossfit Plan', 'Yoga Plan'], $names);
    }

    public function test_subscription_plans_index_custom_sorting(): void
    {
        SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Alpha Plan',
            'base_price' => 100,
            'status' => 'active',
        ]);

        SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Beta Plan',
            'base_price' => 300,
            'status' => 'active',
        ]);

        SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Gamma Plan',
            'base_price' => 200,
            'status' => 'active',
        ]);

        // Custom sort: base_price descending
        $response = $this->getJson("/api/v1/subscription-plans?branch_id={$this->branch->id}&sort_by=base_price&order_direction=desc");
        $response->assertStatus(200);

        $prices = collect($response->json('data'))->pluck('base_price')->toArray();
        $this->assertEquals([300.0, 200.0, 100.0], array_map('floatval', $prices));
    }

    public function test_subscription_plans_registration_endpoint_is_sorted_by_name(): void
    {
        SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Pilates Plan',
            'base_price' => 150,
            'status' => 'active',
        ]);

        SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Cardio Plan',
            'base_price' => 90,
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/v1/subscription-plans/registration?branch_id={$this->branch->id}");
        $response->assertStatus(200);

        $names = collect($response->json('data'))->pluck('name')->toArray();
        $this->assertEquals(['Cardio Plan', 'Pilates Plan'], $names);
    }

    public function test_subscription_plans_trashed_endpoint_is_sorted_by_name(): void
    {
        $planZ = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Z-Plan',
            'base_price' => 100,
            'status' => 'active',
        ]);
        $planZ->delete();

        $planA = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'A-Plan',
            'base_price' => 100,
            'status' => 'active',
        ]);
        $planA->delete();

        $response = $this->getJson("/api/v1/subscription-plans/trashed?branch_id={$this->branch->id}&per_page=all");
        $response->assertStatus(200);

        $names = collect($response->json('data'))->pluck('name')->toArray();
        $this->assertEquals(['A-Plan', 'Z-Plan'], $names);
    }

    public function test_subscription_plans_search_by_name_number_and_price(): void
    {
        $plan1 = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'الاشتراك الذهبي المميز',
            'subscription_number' => 'PLAN-GOLD-001',
            'base_price' => 500,
            'status' => 'active',
        ]);

        $plan2 = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'اشتراك السباحة الفضي',
            'subscription_number' => 'PLAN-SILV-002',
            'base_price' => 250,
            'status' => 'active',
        ]);

        // Search by general search: name
        $res = $this->getJson("/api/v1/subscription-plans?search=الذهبي");
        $res->assertOk();
        $this->assertTrue(collect($res->json('data'))->contains('id', $plan1->id));
        $this->assertFalse(collect($res->json('data'))->contains('id', $plan2->id));

        // Search by general search: subscription_number
        $res = $this->getJson("/api/v1/subscription-plans?search=PLAN-SILV-002");
        $res->assertOk();
        $this->assertFalse(collect($res->json('data'))->contains('id', $plan1->id));
        $this->assertTrue(collect($res->json('data'))->contains('id', $plan2->id));

        // Search by general search: numeric price
        $res = $this->getJson("/api/v1/subscription-plans?search=500");
        $res->assertOk();
        $this->assertTrue(collect($res->json('data'))->contains('id', $plan1->id));
        $this->assertFalse(collect($res->json('data'))->contains('id', $plan2->id));

        // Search by specific name parameter
        $res = $this->getJson("/api/v1/subscription-plans?name=السباحة");
        $res->assertOk();
        $this->assertFalse(collect($res->json('data'))->contains('id', $plan1->id));
        $this->assertTrue(collect($res->json('data'))->contains('id', $plan2->id));

        // Search by specific subscription_number parameter
        $res = $this->getJson("/api/v1/subscription-plans?subscription_number=PLAN-GOLD-001");
        $res->assertOk();
        $this->assertTrue(collect($res->json('data'))->contains('id', $plan1->id));
        $this->assertFalse(collect($res->json('data'))->contains('id', $plan2->id));
    }
}
