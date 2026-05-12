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
            'tenant_id' => $recipient->tenant_id ?? 1,
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
}
