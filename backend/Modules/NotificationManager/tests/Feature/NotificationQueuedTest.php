<?php

namespace Modules\NotificationManager\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\NotificationManager\Models\NotificationTemplate;
use Modules\NotificationManager\Models\NotificationLog;
use Modules\NotificationManager\Services\NotificationService;
use Modules\Authentication\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Modules\NotificationManager\Events\NotificationLogged;
use Modules\NotificationManager\Listeners\ProcessNotification;

class NotificationQueuedTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_dispatches_event_and_listener_updates_status()
    {
        // 1. Create a template
        $template = NotificationTemplate::create([
            'slug' => 'test_welcome',
            'subject' => ['en' => 'Welcome {name}', 'ar' => 'أهلاً {name}'],
            'content' => ['en' => 'Hello {name}, welcome to our club!', 'ar' => 'أهلاً {name}، مرحباً بك في نادينا!'],
            'channel' => 'email',
            'is_active' => true,
        ]);

        // 2. Create a mock user/recipient
        $user = User::create([
            'username' => 'test_notified_user',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        // Fake the queue to assert it was pushed
        Queue::fake();

        $service = app(NotificationService::class);
        $log = $service->sendFromTemplate($user, 'test_welcome', ['name' => 'John Doe']);

        $this->assertNotNull($log);
        $this->assertEquals('pending', $log->status);

        // Assert event was dispatched and listener was queued
        Queue::assertPushed(ProcessNotification::class, function ($job) use ($log) {
            return $job->log->id === $log->id;
        });
    }

    public function test_listener_processes_and_updates_log_status_to_sent()
    {
        // 1. Create a template
        $template = NotificationTemplate::create([
            'slug' => 'test_welcome_sync',
            'subject' => ['en' => 'Welcome {name}'],
            'content' => ['en' => 'Hello {name}'],
            'channel' => 'email',
            'is_active' => true,
        ]);

        $user = User::create([
            'username' => 'test_sync_user',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        // Use sync queue connection to execute the listener immediately
        config(['queue.default' => 'sync']);

        $service = app(NotificationService::class);
        $log = $service->sendFromTemplate($user, 'test_welcome_sync', ['name' => 'John Doe']);

        // Refresh database state
        $log->refresh();

        $this->assertEquals('sent', $log->status);
        $this->assertNull($log->error_message);
    }
}
