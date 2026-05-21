<?php

namespace Modules\NotificationManager\Services;

use Modules\NotificationManager\Models\NotificationTemplate;
use Modules\NotificationManager\Models\NotificationLog;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send a notification using a template slug
     */
    public function sendFromTemplate($recipient, string $slug, array $data = [])
    {
        $template = NotificationTemplate::where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return null;
        }

        $subject = $this->parseTemplate($template->getTranslation('subject', app()->getLocale()), $data);
        $content = $this->parseTemplate($template->getTranslation('content', app()->getLocale()), $data);

        return $this->logAndDispatch($recipient, $template->channel, $subject, $content);
    }

    protected function parseTemplate($text, array $data)
    {
        if (!$text) return "";
        foreach ($data as $key => $value) {
            $text = str_replace("{" . $key . "}", $value, $text);
        }
        return $text;
    }

    protected function logAndDispatch($recipient, string $channel, $subject, $content)
    {
        $log = NotificationLog::create([
            'recipient_id' => $recipient->id,
            'recipient_type' => get_class($recipient),
            'channel' => $channel,
            'subject' => $subject,
            'content' => $content,
            'status' => 'pending',
        ]);

        // Logic for actual sending (integration with SMS/Email APIs) would go here
        // For now, it stays in pending or 'logged' status
        
        return $log;
    }

    /**
     * Send notification for subscription expiring soon.
     */
    public function notifySubscriptionExpiring($recipient, array $subscriptionData)
    {
        return $this->sendFromTemplate($recipient, 'subscription_expiring', $subscriptionData);
    }

    /**
     * Send notification for subscription expired.
     */
    public function notifySubscriptionExpired($recipient, array $subscriptionData)
    {
        return $this->sendFromTemplate($recipient, 'subscription_expired', $subscriptionData);
    }

    /**
     * Send notification for payment due.
     */
    public function notifyPaymentDue($recipient, array $paymentData)
    {
        return $this->sendFromTemplate($recipient, 'payment_due', $paymentData);
    }

    /**
     * Send notification for new member welcome.
     */
    public function notifyWelcome($recipient, array $memberData)
    {
        return $this->sendFromTemplate($recipient, 'welcome', $memberData);
    }
}
