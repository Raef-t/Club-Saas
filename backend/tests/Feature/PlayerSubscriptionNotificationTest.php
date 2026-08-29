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
use Modules\NotificationManager\Models\Notification;
use Modules\NotificationManager\Models\NotificationRecipient;
use Modules\Sports\Models\Activity;
use Modules\Sports\Models\StaffActivity;
use Modules\StaffManager\Models\Staff;
use Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus;
use Modules\SubscriptionManager\Models\PlayerSubscription;
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Modules\SubscriptionManager\Models\SubscriptionPlanActivity;
use Modules\SubscriptionManager\Services\SubscriptionService;
use Tests\TestCase;

class PlayerSubscriptionNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $branch;
    protected $member;
    protected $memberUser;
    protected $coach;
    protected $plan;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed notification templates
        $this->seedNotificationTemplates();

        // Admin User
        $person = Person::create([
            'full_name' => 'Admin User',
            'gender' => 'male',
            'type' => 'staff',
        ]);

        $this->adminUser = User::create([
            'person_id' => $person->id,
            'username' => 'admin_sub_notif_' . uniqid(),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $role = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'sanctum',
        ]);
        $this->adminUser->assignRole($role);
        Sanctum::actingAs($this->adminUser, ['*']);

        $club = Club::create(['name' => 'Test Notification Club', 'is_active' => true]);
        $this->branch = Branch::create(['club_id' => $club->id, 'name' => 'Main Branch', 'is_active' => true]);

        // Member Person and User
        $memberPerson = Person::create(['full_name' => 'فاطمة محمد', 'gender' => 'female', 'type' => 'player']);
        $this->memberUser = User::create([
            'person_id' => $memberPerson->id,
            'username' => 'fatima_' . uniqid(),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->member = Member::create([
            'person_id' => $memberPerson->id,
            'branch_id' => $this->branch->id,
            'member_number' => 'M-' . uniqid(),
            'status' => 'active',
        ]);

        // Coach
        $coachPerson = Person::create(['full_name' => 'Coach Sami', 'gender' => 'male', 'type' => 'staff']);
        $this->coach = Staff::create([
            'person_id' => $coachPerson->id,
            'role' => 'coach',
            'work_status' => 'active',
            'is_active' => true,
        ]);

        // Activity & Plan
        $activity = Activity::create([
            'name' => 'Gym Activity',
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $staffActivity = StaffActivity::create([
            'staff_id' => $this->coach->id,
            'activity_id' => $activity->id,
        ]);

        $this->plan = SubscriptionPlan::create([
            'branch_id' => $this->branch->id,
            'name' => 'باقة اللياقة الشهرية',
            'session_count' => 12,
            'sessions_per_week' => 3,
            'base_price' => 300.00,
            'status' => 'active',
            'max_subscribers' => 10,
            'current_subscribers' => 1,
        ]);

        SubscriptionPlanActivity::create([
            'plan_id' => $this->plan->id,
            'staff_activity_id' => $staffActivity->id,
        ]);
    }

    protected function seedNotificationTemplates(): void
    {
        $templates = [
            [
                'name' => 'حذف الاشتراك واسترداد المبلغ',
                'system_key' => 'subscription_deleted_refunded',
                'subject' => 'تم حذف اشتراكك واسترداد المبلغ 💰',
                'body' => 'أهلاً بك {اسم اللاعب}، نود إعلامك بأنه تم حذف اشتراكك "{اسم الاشتراك}" واسترداد المبلغ بنجاح.{السبب}',
                'variables' => ['اسم اللاعب', 'اسم الاشتراك', 'السبب'],
                'is_active' => true,
            ],
            [
                'name' => 'حذف الاشتراك',
                'system_key' => 'subscription_deleted',
                'subject' => 'تم حذف اشتراكك 🗑️',
                'body' => 'أهلاً بك {اسم اللاعب}، نود إعلامك بأنه تم حذف اشتراكك "{اسم الاشتراك}".{السبب}',
                'variables' => ['اسم اللاعب', 'اسم الاشتراك', 'السبب'],
                'is_active' => true,
            ],
            [
                'name' => 'استرجاع الاشتراك',
                'system_key' => 'subscription_restored',
                'subject' => 'تم استرجاع اشتراكك ♻️',
                'body' => 'أهلاً بك {اسم اللاعب}، تم استرجاع وتفعيل اشتراكك "{اسم الاشتراك}" بنجاح. نتمنى لك تدريباً ممتعاً!',
                'variables' => ['اسم اللاعب', 'اسم الاشتراك'],
                'is_active' => true,
            ],
            [
                'name' => 'تفعيل اشتراك جديد',
                'system_key' => 'subscription_created',
                'subject' => 'تم تفعيل اشتراكك بنجاح 🎉',
                'body' => 'أهلاً بك {اسم اللاعب}، يسعدنا انضمامك! تم تفعيل اشتراكك "{اسم الاشتراك}" بنجاح حتى تاريخ {تاريخ الانتهاء}. نتمنى لك تدريباً ممتعاً وموفقاً!',
                'variables' => ['اسم اللاعب', 'اسم الاشتراك', 'تاريخ الانتهاء'],
                'is_active' => true,
            ],
            [
                'name' => 'تجديد الاشتراك',
                'system_key' => 'subscription_renewed',
                'subject' => 'تم تجديد اشتراكك بنجاح 🔄',
                'body' => 'أهلاً بك {اسم اللاعب}، تم تجديد اشتراكك "{اسم الاشتراك}" بنجاح حتى تاريخ {تاريخ الانتهاء}. نشكر ثقتك واستمرارك معنا!',
                'variables' => ['اسم اللاعب', 'اسم الاشتراك', 'تاريخ الانتهاء'],
                'is_active' => true,
            ],
            [
                'name' => 'إلغاء الاشتراك',
                'system_key' => 'subscription_cancelled',
                'subject' => 'تم إلغاء اشتراكك ❌',
                'body' => 'أهلاً بك {اسم اللاعب}، نود إعلامك بأنه تم إلغاء اشتراكك "{اسم الاشتراك}".{السبب}',
                'variables' => ['اسم اللاعب', 'اسم الاشتراك', 'السبب'],
                'is_active' => true,
            ],
            [
                'name' => 'تسجيل دفعة مالية للاشتراك',
                'system_key' => 'subscription_payment_recorded',
                'subject' => 'تم تسجيل دفعة مالية 💳',
                'body' => 'أهلاً بك {اسم اللاعب}، تم استلام دفعة مالية بقيمة {المبلغ} لاشتراكك "{اسم الاشتراك}". المبلغ المتبقي: {المبلغ المتبقي}.',
                'variables' => ['اسم اللاعب', 'اسم الاشتراك', 'المبلغ', 'المبلغ المتبقي'],
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            NotificationTemplate::updateOrCreate(
                ['system_key' => $template['system_key']],
                $template
            );
        }
    }

    public function test_delete_subscription_with_refund_sends_notification_and_decrements_plan_subscribers()
    {
        $subscription = PlayerSubscription::create([
            'member_id' => $this->member->id,
            'plan_id' => $this->plan->id,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDays(30)->toDateString(),
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
            'total_amount' => 300,
            'paid_amount' => 300,
            'remaining_amount' => 0,
        ]);

        $initialSubscribers = $this->plan->fresh()->current_subscribers;

        $response = $this->deleteJson("/api/v1/player-subscriptions/{$subscription->id}?is_refunded=true&reason=طلب+اللاعبة+استرداد+المبلغ");

        $response->assertStatus(200);

        // Assert subscription is soft deleted
        $this->assertSoftDeleted('player_subscriptions', ['id' => $subscription->id]);

        // Assert plan current_subscribers decremented
        $this->assertEquals($initialSubscribers - 1, $this->plan->fresh()->current_subscribers);

        // Assert notification created for memberUser
        $notification = Notification::latest('id')->first();
        $this->assertNotNull($notification);
        $this->assertEquals('تم حذف اشتراكك واسترداد المبلغ 💰', $notification->title);
        $this->assertStringContainsString('فاطمة محمد', $notification->body);
        $this->assertStringContainsString('باقة اللياقة الشهرية', $notification->body);
        $this->assertStringContainsString('استرداد المبلغ', $notification->body);

        $recipient = NotificationRecipient::where('notification_id', $notification->id)
            ->where('user_id', $this->memberUser->id)
            ->first();
        $this->assertNotNull($recipient);
    }

    public function test_delete_subscription_without_refund_sends_deleted_notification()
    {
        $subscription = PlayerSubscription::create([
            'member_id' => $this->member->id,
            'plan_id' => $this->plan->id,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDays(30)->toDateString(),
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
            'total_amount' => 300,
            'paid_amount' => 300,
            'remaining_amount' => 0,
        ]);

        $response = $this->deleteJson("/api/v1/player-subscriptions/{$subscription->id}", [
            'is_refunded' => false,
            'reason' => 'حذف إداري',
        ]);

        $response->assertStatus(200);
        $this->assertSoftDeleted('player_subscriptions', ['id' => $subscription->id]);

        $notification = Notification::latest('id')->first();
        $this->assertNotNull($notification);
        $this->assertEquals('تم حذف اشتراكك 🗑️', $notification->title);
        $this->assertStringContainsString('فاطمة محمد', $notification->body);
        $this->assertStringContainsString('باقة اللياقة الشهرية', $notification->body);
    }

    public function test_restore_subscription_sends_restored_notification_and_increments_subscribers()
    {
        $subscription = PlayerSubscription::create([
            'member_id' => $this->member->id,
            'plan_id' => $this->plan->id,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDays(30)->toDateString(),
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
            'total_amount' => 300,
            'paid_amount' => 300,
            'remaining_amount' => 0,
        ]);

        $subscription->delete();

        $initialSubscribers = $this->plan->fresh()->current_subscribers;

        $response = $this->postJson("/api/v1/player-subscriptions/{$subscription->id}/restore");

        $response->assertStatus(200);
        $this->assertNotSoftDeleted('player_subscriptions', ['id' => $subscription->id]);

        $this->assertEquals($initialSubscribers + 1, $this->plan->fresh()->current_subscribers);

        $notification = Notification::latest('id')->first();
        $this->assertNotNull($notification);
        $this->assertEquals('تم استرجاع اشتراكك ♻️', $notification->title);
        $this->assertStringContainsString('فاطمة محمد', $notification->body);
    }

    public function test_subscribe_member_sends_created_notification()
    {
        $response = $this->postJson('/api/v1/player-subscriptions', [
            'member_id' => $this->member->id,
            'plan_id' => $this->plan->id,
            'paid_amount' => 300.00,
            'start_date' => Carbon::today()->toDateString(),
            'months_count' => 1,
        ]);

        $response->assertStatus(201);

        $notification = Notification::latest('id')->first();
        $this->assertNotNull($notification);
        $this->assertEquals('تم تفعيل اشتراكك بنجاح 🎉', $notification->title);
        $this->assertStringContainsString('فاطمة محمد', $notification->body);
        $this->assertStringContainsString('باقة اللياقة الشهرية', $notification->body);
    }

    public function test_renew_subscription_sends_renewed_notification()
    {
        $subscription = PlayerSubscription::create([
            'member_id' => $this->member->id,
            'plan_id' => $this->plan->id,
            'start_date' => Carbon::today()->subDays(30)->toDateString(),
            'end_date' => Carbon::today()->toDateString(),
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
            'total_amount' => 300,
            'paid_amount' => 300,
            'remaining_amount' => 0,
        ]);

        $response = $this->postJson("/api/v1/player-subscriptions/{$subscription->id}/renew", [
            'paid_amount' => 300.00,
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(201);

        $notification = Notification::latest('id')->first();
        $this->assertNotNull($notification);
        $this->assertEquals('تم تجديد اشتراكك بنجاح 🔄', $notification->title);
        $this->assertStringContainsString('فاطمة محمد', $notification->body);
        $this->assertStringContainsString('باقة اللياقة الشهرية', $notification->body);
    }

    public function test_cancel_subscription_sends_cancelled_notification()
    {
        $subscription = PlayerSubscription::create([
            'member_id' => $this->member->id,
            'plan_id' => $this->plan->id,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDays(30)->toDateString(),
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
            'total_amount' => 300,
            'paid_amount' => 300,
            'remaining_amount' => 0,
        ]);

        $response = $this->postJson("/api/v1/player-subscriptions/{$subscription->id}/cancel", [
            'reason' => 'ظروف خاصة',
        ]);

        $response->assertStatus(200);

        $notification = Notification::latest('id')->first();
        $this->assertNotNull($notification);
        $this->assertEquals('تم إلغاء اشتراكك ❌', $notification->title);
        $this->assertStringContainsString('فاطمة محمد', $notification->body);
        $this->assertStringContainsString('ظروف خاصة', $notification->body);
    }

    public function test_record_payment_sends_payment_notification()
    {
        $subscription = PlayerSubscription::create([
            'member_id' => $this->member->id,
            'plan_id' => $this->plan->id,
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDays(30)->toDateString(),
            'status' => PlayerSubscriptionStatus::ACTIVE->value,
            'total_amount' => 300,
            'paid_amount' => 100,
            'remaining_amount' => 200,
        ]);

        $response = $this->postJson("/api/v1/player-subscriptions/{$subscription->id}/payment", [
            'amount' => 100.00,
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(200);

        $notification = Notification::latest('id')->first();
        $this->assertNotNull($notification);
        $this->assertEquals('تم تسجيل دفعة مالية 💳', $notification->title);
        $this->assertStringContainsString('فاطمة محمد', $notification->body);
        $this->assertStringContainsString('100.00', $notification->body);
    }
}
