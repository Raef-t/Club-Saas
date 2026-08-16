<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Models\Branch;
use Modules\Sports\Models\Activity;
use Modules\Sports\Models\ActivityType;
use Modules\Sports\Models\StaffActivity;
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Modules\SubscriptionManager\Models\SubscriptionPlanActivity;
use Modules\SubscriptionManager\Services\SubscriptionPlanActivityService;
use Modules\SubscriptionManager\Services\SubscriptionService;
use Modules\MemberManager\Models\Member;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;

use Laravel\Sanctum\Sanctum;

class SubscriptionPlanEquipmentCapacityTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $branch;
    protected $generalEquipmentActivity;
    protected $privateEquipmentActivity;
    protected $groupClassActivity;

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

        Sanctum::actingAs($this->user);

        $club = Club::firstOrCreate(['name' => 'Test Club'], ['is_active' => true]);
        $this->branch = Branch::firstOrCreate(['name' => 'Test Branch', 'club_id' => $club->id], ['is_active' => true]);

        $generalType = ActivityType::firstOrCreate(['name' => 'تدريب عام'], [
            'is_active' => true,
            'is_session_based' => false,
            'has_unlimited_subscribers' => true,
        ]);

        $this->groupClassType = ActivityType::firstOrCreate(['name' => 'حصة جماعية'], [
            'is_active' => true,
            'is_session_based' => true,
            'has_unlimited_subscribers' => false,
        ]);

        $this->generalEquipmentActivity = Activity::firstOrCreate(['name' => 'أجهزة عام', 'branch_id' => $this->branch->id], [
            'activity_type_id' => $generalType->id,
            'is_private_equipment' => false,
            'is_active' => true,
        ]);

        $this->privateEquipmentActivity = Activity::firstOrCreate(['name' => 'أجهزة خاص', 'branch_id' => $this->branch->id], [
            'activity_type_id' => $generalType->id,
            'is_private_equipment' => true,
            'is_active' => true,
        ]);

        $this->groupClassActivity = Activity::firstOrCreate(['name' => 'كروسفيت', 'branch_id' => $this->branch->id], [
            'activity_type_id' => $this->groupClassType->id,
            'is_private_equipment' => false,
            'is_active' => true,
        ]);
    }

    public function test_creating_subscription_plan_with_general_equipment_forces_unlimited_subscribers(): void
    {
        $payload = [
            'branch_id' => $this->branch->id,
            'name' => 'خطة أجهزة عام',
            'base_price' => 200.00,
            'max_subscribers' => 30, // should be forced to 0
            'activities' => [
                ['activity_id' => $this->generalEquipmentActivity->id],
            ],
        ];

        $response = $this->postJson('/api/v1/subscription-plans', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.max_subscribers', 0)
            ->assertJsonPath('data.is_unlimited_subscribers', true);

        $planId = $response->json('data.id');
        $this->assertDatabaseHas('subscription_plans', [
            'id' => $planId,
            'max_subscribers' => 0,
        ]);
    }

    public function test_creating_subscription_plan_with_private_equipment_forces_unlimited_subscribers(): void
    {
        $payload = [
            'branch_id' => $this->branch->id,
            'name' => 'خطة أجهزة خاص',
            'base_price' => 350.00,
            'max_subscribers' => 15, // should be forced to 0
            'activities' => [
                ['activity_id' => $this->privateEquipmentActivity->id],
            ],
        ];

        $response = $this->postJson('/api/v1/subscription-plans', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.max_subscribers', 0)
            ->assertJsonPath('data.is_unlimited_subscribers', true);

        $planId = $response->json('data.id');
        $this->assertDatabaseHas('subscription_plans', [
            'id' => $planId,
            'max_subscribers' => 0,
        ]);
    }

    public function test_creating_group_class_plan_retains_specified_capacity(): void
    {
        $payload = [
            'branch_id' => $this->branch->id,
            'name' => 'خطة كروسفيت محددة',
            'base_price' => 150.00,
            'max_subscribers' => 20,
            'activities' => [
                ['activity_id' => $this->groupClassActivity->id],
            ],
        ];

        $response = $this->postJson('/api/v1/subscription-plans', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.max_subscribers', 20)
            ->assertJsonPath('data.is_unlimited_subscribers', false);
    }

    public function test_updating_subscription_plan_to_include_equipment_forces_unlimited(): void
    {
        // First create a limited group class plan
        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'خطة قابلة للتعديل',
            'base_price' => 100.00,
            'max_subscribers' => 10,
            'status' => 'active',
        ]);

        $updatePayload = [
            'name' => 'خطة تم تحويلها لأجهزة عام',
            'base_price' => 120.00,
            'max_subscribers' => 50,
            'activities' => [
                ['activity_id' => $this->generalEquipmentActivity->id],
            ],
        ];

        $response = $this->putJson("/api/v1/subscription-plans/{$plan->id}", $updatePayload);

        $response->assertStatus(200)
            ->assertJsonPath('data.max_subscribers', 0)
            ->assertJsonPath('data.is_unlimited_subscribers', true);

        $plan->refresh();
        $this->assertEquals(0, $plan->max_subscribers);
    }

    public function test_subscription_plan_activity_service_updates_parent_plan_capacity(): void
    {
        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'خطة أجهزة تجريبية',
            'base_price' => 180.00,
            'max_subscribers' => 25,
            'status' => 'active',
        ]);

        $service = app(SubscriptionPlanActivityService::class);
        $service->create([
            'plan_id' => $plan->id,
            'activity_id' => $this->generalEquipmentActivity->id,
        ]);

        $plan->refresh();
        $this->assertEquals(0, $plan->max_subscribers);
        $this->assertTrue($plan->is_unlimited_subscribers);
    }

    public function test_registration_plans_endpoint_returns_equipment_plan_with_unlimited_status(): void
    {
        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'الاشتراك العام للأجهزة',
            'base_price' => 200.00,
            'max_subscribers' => 0,
            'current_subscribers' => 150, // high number of subscribers
            'status' => 'active',
        ]);

        $staffActivity = StaffActivity::firstOrCreate([
            'activity_id' => $this->generalEquipmentActivity->id,
            'staff_id' => null,
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $plan->id,
            'staff_activity_id' => $staffActivity->id,
        ]);

        $response = $this->getJson("/api/v1/subscription-plans/registration?branch_id={$this->branch->id}");

        $response->assertStatus(200);
        $found = collect($response->json('data'))->firstWhere('id', $plan->id);
        $this->assertNotNull($found);
        $this->assertTrue($found['is_unlimited_subscribers']);
        $this->assertEquals(0, $found['max_subscribers']);
    }

    public function test_equipment_plan_subscribes_members_without_capacity_limits(): void
    {
        $plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'خطة تدريب عام مفتوحة',
            'base_price' => 150.00,
            'max_subscribers' => 0,
            'current_subscribers' => 500,
            'status' => 'active',
        ]);

        $subscriptionService = app(SubscriptionService::class);

        // Verify available scope finds it
        $available = SubscriptionPlan::available()->where('id', $plan->id)->exists();
        $this->assertTrue($available);

        // Verify incrementing does not cap or mark completed
        $subscriptionService->incrementPlanSubscribers($plan);
        $plan->refresh();
        $this->assertEquals(501, $plan->current_subscribers);
        $this->assertEquals('active', $plan->status->value);
    }

    public function test_activity_resource_derives_is_unlimited_from_activity_type(): void
    {
        $yogaActivity = Activity::create([
            'branch_id' => $this->branch->id,
            'name' => 'يوغا',
            'activity_type_id' => $this->groupClassType->id, // has_unlimited_subscribers: false
            'is_private_equipment' => false,
            'is_active' => true,
        ]);

        $this->assertFalse($yogaActivity->hasUnlimitedSubscribers());
        $resource = new \Modules\Sports\Http\Resources\ActivityResource($yogaActivity);
        $data = $resource->toArray(request());
        $this->assertFalse($data['is_unlimited_subscribers']);

        $equipmentResource = new \Modules\Sports\Http\Resources\ActivityResource($this->generalEquipmentActivity);
        $equipmentData = $equipmentResource->toArray(request());
        $this->assertTrue($equipmentData['is_unlimited_subscribers']);
    }

    public function test_yoga_plan_strictly_preserves_finite_capacity_limits(): void
    {
        $yogaActivity = Activity::create([
            'branch_id' => $this->branch->id,
            'name' => 'يوغا صباحية',
            'activity_type_id' => $this->groupClassType->id, // has_unlimited_subscribers = false
            'is_private_equipment' => false,
            'is_active' => true,
        ]);

        $payload = [
            'branch_id' => $this->branch->id,
            'name' => 'خطة يوغا محدودة',
            'base_price' => 120.00,
            'max_subscribers' => 15,
            'activities' => [
                ['activity_id' => $yogaActivity->id],
            ],
        ];

        $response = $this->postJson('/api/v1/subscription-plans', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.max_subscribers', 15)
            ->assertJsonPath('data.is_unlimited_subscribers', false);

        $planId = $response->json('data.id');
        $plan = SubscriptionPlan::find($planId);
        $this->assertEquals(15, $plan->max_subscribers);
        $this->assertFalse($plan->is_unlimited_subscribers);
    }
}
