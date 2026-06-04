<?php

namespace Modules\NotificationManager\Listeners;

use Modules\NotificationManager\Events\NotificationLogged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param NotificationLogged $event
     * @return void
     */
    public function handle(NotificationLogged $event): void
    {
        $log = $event->log;

        Log::info("🔔 [Queue] Processing notification #{$log->id} via channel: {$log->channel}", [
            'recipient_type' => $log->recipient_type,
            'recipient_id' => $log->recipient_id,
            'subject' => $log->subject,
        ]);

        try {
            // Mock dispatch logic based on the channel
            switch ($log->channel) {
                case 'email':
                    // In the future: Mail::to($recipient->email)->send(...)
                    Log::info("📨 [Mock Send] Email sent to recipient {$log->recipient_id} of type {$log->recipient_type}");
                    break;
                case 'sms':
                    // In the future: SmsProvider::send(...)
                    Log::info("💬 [Mock Send] SMS sent to recipient {$log->recipient_id} of type {$log->recipient_type}");
                    break;
                case 'whatsapp':
                    // In the future: WhatsAppProvider::send(...)
                    Log::info("🟢 [Mock Send] WhatsApp sent to recipient {$log->recipient_id} of type {$log->recipient_type}");
                    break;
                case 'push':
                    Log::info("📱 [Mock Send] Push notification sent to recipient {$log->recipient_id} of type {$log->recipient_type}");
                    break;
                default:
                    throw new \Exception("Unsupported notification channel: {$log->channel}");
            }

            // Update status on success
            $log->update([
                'status' => 'sent',
                'updated_at' => now(),
            ]);

            Log::info("✅ [Queue] Successfully sent notification #{$log->id}");

        } catch (\Throwable $e) {
            Log::error("❌ [Queue] Failed to dispatch notification #{$log->id}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            // Update status on failure
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'updated_at' => now(),
            ]);
        }
    }
}
