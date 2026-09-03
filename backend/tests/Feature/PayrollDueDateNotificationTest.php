<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Modules\ClubManager\Models\Branch;
use Modules\ClubManager\Models\BranchSetting;
use Modules\ClubManager\Models\BranchHoliday;
use Modules\ClubManager\Models\Club;
use Modules\NotificationManager\Models\Notification;
use Modules\NotificationManager\Models\NotificationTemplate;
use Modules\NotificationManager\Models\NotificationRecipient;
use Modules\StaffManager\Models\PayrollRun;
use Tests\TestCase;

class PayrollDueDateNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $club;
    protected $branch;
    protected $branchSetting;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed Notification Template
        NotificationTemplate::updateOrCreate(
            ['system_key' => 'payroll_due_reminder'],
            [
                'name' => 'تنبيه استحقاق توليد الرواتب',
                'system_key' => 'payroll_due_reminder',
                'subject' => 'تنبيه: تم توليد الرواتب لفرع {اسم_الفرع} 💰',
                'body' => 'إدارة النادي الكريمة، اليوم هو موعد توليد رواتب شهر {شهر} لفرع {اسم_الفرع}. يرجى مراجعة الرواتب واعتمادها.',
                'variables' => ['اسم_الفرع', 'شهر', 'تاريخ_الاستحقاق'],
                'is_active' => true,
            ]
        );

        $this->club = Club::create([
            'name' => 'Test Club',
            'country' => 'Syria',
            'city' => 'Damascus',
        ]);

        $this->branch = Branch::create([
            'club_id' => $this->club->id,
            'name' => 'Main Branch',
            'city' => 'Damascus',
            'is_main' => true,
            'status' => 'active',
        ]);

        $this->branchSetting = BranchSetting::create([
            'branch_id' => $this->branch->id,
            'payroll_end_day' => 25,
        ]);

        // Create Admin
        $person = Person::create([
            'full_name' => 'Main Admin',
            'gender' => 'male',
            'type' => 'staff',
        ]);

        $this->adminUser = User::create([
            'person_id' => $person->id,
            'username' => 'admin_test_' . uniqid(),
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_sends_notification_when_today_is_payroll_due_day()
    {
        // Travel to payroll end day (25th of month)
        Carbon::setTestNow(Carbon::create(2026, 9, 25, 8, 0, 0));

        $this->artisan('payroll:check-due-date --force')
            ->assertExitCode(0);

        // Verify notification was created
        $this->assertDatabaseHas('notifications', [
            'sender_type' => 'system',
        ]);

        $notification = Notification::latest('id')->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('Main Branch', $notification->title);
        $this->assertStringContainsString('Main Branch', $notification->body);

        // Check target snapshot
        $this->assertEquals('payroll_due', $notification->target_snapshot['type']);
        $this->assertEquals($this->branch->id, $notification->target_snapshot['branch_id']);
        $this->assertEquals("/dashboard/payroll/generate?branch_id={$this->branch->id}", $notification->target_snapshot['action_url']);

        // Check recipient
        $this->assertDatabaseHas('notification_recipients', [
            'notification_id' => $notification->id,
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_skips_notification_when_today_is_not_payroll_due_day()
    {
        // Travel to 10th of month (not 25th)
        Carbon::setTestNow(Carbon::create(2026, 9, 10, 8, 0, 0));

        $this->artisan('payroll:check-due-date --force')
            ->assertExitCode(0);

        // Verify no notification was created
        $this->assertEquals(0, Notification::count());
    }

    public function test_handles_holiday_on_payroll_due_day()
    {
        // Travel to 25th of month (Friday)
        $date = Carbon::create(2026, 9, 25, 8, 0, 0);
        Carbon::setTestNow($date);

        // Create weekly holiday for Friday
        BranchHoliday::create([
            'branch_id' => $this->branch->id,
            'type' => 'weekly',
            'day_of_week' => $date->dayOfWeek,
        ]);

        $this->artisan('payroll:check-due-date --force')
            ->assertExitCode(0);

        // Due date notification should NOT be sent because of holiday
        $payrollDueNotification = Notification::whereJsonContains('target_snapshot->type', 'payroll_due')->first();
        $this->assertNull($payrollDueNotification);
    }

    public function test_skips_notification_when_payroll_run_already_confirmed()
    {
        // Travel to 25th of month
        $date = Carbon::create(2026, 9, 25, 8, 0, 0);
        Carbon::setTestNow($date);

        $calculationDay = (clone $date)->setDay(25);
        $periodEnd = $calculationDay->copy()->subDay()->endOfDay();
        $periodStart = $calculationDay->copy()->subMonthNoOverflow()->startOfDay();

        // Already confirmed payroll run
        PayrollRun::create([
            'branch_id' => $this->branch->id,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'status' => 'approved',
        ]);

        $this->artisan('payroll:check-due-date --force')
            ->assertExitCode(0);

        // Due date notification should NOT be sent
        $this->assertEquals(0, Notification::count());
    }
}
