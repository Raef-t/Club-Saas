<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Modules\ClubManager\Models\Branch;
use Modules\ClubManager\Models\Club;
use Modules\MemberManager\Models\Member;
use Modules\NotificationManager\Models\NotificationTemplate;
use Modules\NotificationManager\Services\NotificationService;
use Modules\Sports\Models\Activity;
use Modules\Sports\Models\StaffActivity;
use Modules\StaffManager\Models\Staff;
use Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus;
use Modules\SubscriptionManager\Models\PlayerSubscription;
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Modules\SubscriptionManager\Models\SubscriptionPlanActivity;
use Modules\SubscriptionManager\Services\SubscriptionPlanSuspensionService;
use Tests\TestCase;

class SubscriptionPlanSuspensionNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $branch;
    protected $member;
    protected $memberUser;
    protected $coach;
    protected $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $person = Person::create([
            'full_name' => 'Admin User',
            'gender' => 'male',
            'type' => 'staff',
        ]);

        $this->user = User::create([
            'person_id' => $person->id,
            'username' => 'admin_susp_' . uniqid(),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $role = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'sanctum',
        ]);
        $this->user->assignRole($role);
        Sanctum::actingAs($this->user, ['*']);

        $club = Club::create(['name' => 'Test Notification Club', 'is_active' => true]);
        $this->branch = Branch::create(['club_id' => $club->id, 'name' => 'Main Branch', 'is_active' => true]);

        // Create Member Person and User
        $memberPerson = Person::create(['full_name' => 'Player Ahmad', 'gender' => 'male', 'type' => 'player']);
        $this->memberUser = User::create([
            'person_id' => $memberPerson->id,
            'username' => 'ahmad_' . uniqid(),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->member = Member::create([
            'person_id' => $memberPerson->id,
            'branch_id' => $this->branch->id,
            'member_number' => 'M-' . uniqid(),
            'status' => 'active',
        ]);

        // Create Coach
        $coachPerson = Person::create(['full_name' => 'Coach Sami', 'gender' => 'male', 'type' => 'staff']);
        $this->coach = Staff::create([
            'person_id' => $coachPerson->id,
            'role' => 'coach',
            'work_status' => 'active',
            'is_active' => true,
        ]);

        // Create Activity & Plan
        $activity = Activity::create([
            'name' => 'Swimming Activity',
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $staffActivity = StaffActivity::create([
            'staff_id' => $this->coach->id,
            'activity_id' => $activity->id,
        ]);

        $this->plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'Swimming Pro Plan',
            'session_count' => 12,
            'sessions_per_week' => 3,
            'base_price' => 200.00,
            'status' => 'active',
            'max_subscribers' => 0,
            'current_subscribers' => 0,
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $this->plan->id,
            'staff_activity_id' => $staffActivity->id,
        ]);
    }

    public function test_suspension_sends_notification_using_fixed_template_without_remaining_days()
    {
        // 1. Setup Notification Template
        NotificationTemplate::updateOrCreate(
            ['system_key' => 'subscription_plan_suspension'],
            [
                'name' => 'إيقاف الفعالية',
                'system_key' => 'subscription_plan_suspension',
                'subject' => 'تنبيه هام: تعليق فعالية {اسم الفعالية} مؤقتاً ⚠️',
                'body' => 'عزيزي {اسم اللاعب}، نود إعلامك بتعليق فعالية "{اسم الفعالية}" مع الكوتش {اسم الكوتش} من تاريخ {تاريخ البداية} إلى تاريخ {تاريخ النهاية} بسبب {السبب}. تم تمديد اشتراكك تلقائياً ليصبح تاريخ النهاية الجديد: {تاريخ الانتهاء الجديد}.',
                'variables' => ['اسم اللاعب', 'اسم الفعالية', 'اسم الكوتش', 'تاريخ البداية', 'تاريخ النهاية', 'يوم البداية', 'يوم النهاية', 'السبب', 'تاريخ الانتهاء الجديد'],
                'is_active' => true,
            ]
        );

        // 2. Create Active Subscription for Member
        $subscription = PlayerSubscription::create([
            'member_id' => $this->member->id,
            'plan_id' => $this->plan->id,
            'start_date' => Carbon::today()->subDays(5)->toDateString(),
            'end_date' => Carbon::today()->addDays(20)->toDateString(),
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
            'payment_status' => 'paid',
            'branch_id' => $this->branch->id,
        ]);

        // Mock NotificationService to inspect notification payload
        $capturedNotifications = [];
        $mockNotificationService = $this->createMock(NotificationService::class);
        $mockNotificationService->expects($this->once())
            ->method('createNotification')
            ->willReturnCallback(function ($payload) use (&$capturedNotifications) {
                $capturedNotifications[] = $payload;
                return true;
            });

        $service = new SubscriptionPlanSuspensionService($mockNotificationService);

        $suspendStartDate = Carbon::today()->addDays(1)->toDateString();
        $suspendEndDate = Carbon::today()->addDays(5)->toDateString();

        $suspension = $service->suspend($this->plan->id, [
            'suspend_start_date' => $suspendStartDate,
            'suspend_end_date' => $suspendEndDate,
            'reason' => 'صيانة المسبح واعتذار الكوتش',
        ], $this->user->id);

        $this->assertNotEmpty($capturedNotifications);
        $notification = $capturedNotifications[0];

        // Verify notification attributes
        $this->assertStringContainsString('Swimming Pro Plan', $notification['title']);
        $this->assertStringContainsString('Player Ahmad', $notification['body']);
        $this->assertStringContainsString('Swimming Pro Plan', $notification['body']);
        $this->assertStringContainsString('Coach Sami', $notification['body']);
        $this->assertStringContainsString($suspendStartDate, $notification['body']);
        $this->assertStringContainsString($suspendEndDate, $notification['body']);
        $this->assertStringContainsString('صيانة المسبح واعتذار الكوتش', $notification['body']);

        // MUST NOT contain remaining days count
        $this->assertStringNotContainsString('أيامك المتبقية', $notification['body']);
        $this->assertStringNotContainsString('الأيام المتبقية', $notification['body']);
        $this->assertStringNotContainsString('المتبقية', $notification['body']);

        // Verify target snapshot
        $this->assertEquals($this->plan->id, $notification['target_snapshot']['plan_id']);
        $this->assertEquals('subscription_plan_suspension', $notification['target_snapshot']['type']);
    }

    public function test_suspension_fallback_notification_does_not_contain_remaining_days()
    {
        // Deactivate template to force fallback
        NotificationTemplate::where('system_key', 'subscription_plan_suspension')->update(['is_active' => false]);

        $subscription = PlayerSubscription::create([
            'member_id' => $this->member->id,
            'plan_id' => $this->plan->id,
            'start_date' => Carbon::today()->subDays(5)->toDateString(),
            'end_date' => Carbon::today()->addDays(20)->toDateString(),
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
            'payment_status' => 'paid',
            'branch_id' => $this->branch->id,
        ]);

        $capturedNotifications = [];
        $mockNotificationService = $this->createMock(NotificationService::class);
        $mockNotificationService->expects($this->once())
            ->method('createNotification')
            ->willReturnCallback(function ($payload) use (&$capturedNotifications) {
                $capturedNotifications[] = $payload;
                return true;
            });

        $service = new SubscriptionPlanSuspensionService($mockNotificationService);

        $suspendStartDate = Carbon::today()->addDays(1)->toDateString();
        $suspendEndDate = Carbon::today()->addDays(3)->toDateString();

        $suspension = $service->suspend($this->plan->id, [
            'suspend_start_date' => $suspendStartDate,
            'suspend_end_date' => $suspendEndDate,
            'reason' => 'عطلة مؤقتة',
        ], $this->user->id);

        $this->assertNotEmpty($capturedNotifications);
        $notification = $capturedNotifications[0];

        $this->assertStringContainsString('Player Ahmad', $notification['body']);
        $this->assertStringContainsString($suspendStartDate, $notification['body']);
        $this->assertStringNotContainsString('أيامك المتبقية', $notification['body']);
        $this->assertStringNotContainsString('الأيام المتبقية', $notification['body']);
    }

    public function test_lift_suspension_sends_resumption_notification()
    {
        $subscription = PlayerSubscription::create([
            'member_id' => $this->member->id,
            'plan_id' => $this->plan->id,
            'start_date' => Carbon::today()->subDays(5)->toDateString(),
            'end_date' => Carbon::today()->addDays(20)->toDateString(),
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
            'payment_status' => 'paid',
            'branch_id' => $this->branch->id,
        ]);

        $capturedNotifications = [];
        $mockNotificationService = $this->createMock(NotificationService::class);
        $mockNotificationService->expects($this->exactly(2))
            ->method('createNotification')
            ->willReturnCallback(function ($payload) use (&$capturedNotifications) {
                $capturedNotifications[] = $payload;
                return true;
            });

        $service = new SubscriptionPlanSuspensionService($mockNotificationService);

        $suspendStartDate = Carbon::today()->addDays(2)->toDateString();
        $suspendEndDate = Carbon::today()->addDays(6)->toDateString();

        $suspension = $service->suspend($this->plan->id, [
            'suspend_start_date' => $suspendStartDate,
            'suspend_end_date' => $suspendEndDate,
            'reason' => 'إيقاف مؤقت',
        ], $this->user->id);

        $service->liftSuspension($this->plan->id, $suspension->id);

        $this->assertCount(2, $capturedNotifications);
        $resumptionNotification = $capturedNotifications[1];

        $this->assertEquals('subscription_plan_resumption', $resumptionNotification['target_snapshot']['type']);
        $this->assertStringContainsString('Swimming Pro Plan', $resumptionNotification['title']);
    }
}
