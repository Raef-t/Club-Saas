<?php

namespace App\Services;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Illuminate\Support\Facades\Log;
use Exception;

class FirebaseService
{
    /**
     * @var \Kreait\Firebase\Contract\Messaging|null
     */
    protected $messaging;

    public function __construct()
    {
        try {
            $this->messaging = Firebase::messaging();
        } catch (Exception $e) {
            Log::warning('Firebase Messaging initialization failed: ' . $e->getMessage());
            $this->messaging = null;
        }
    }

    /**
     * إرسال إشعار لتوكن واحد
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): array
    {
        if (!$this->messaging) {
            return [
                'success' => false,
                'error'   => 'Firebase service not initialized',
            ];
        }

        try {
            $message = CloudMessage::fromArray([
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'data' => $this->normalizeData($data),
            ]);

            $result = $this->messaging->send($message);

            Log::info('✅ تم إرسال الإشعار لتوكن واحد بنجاح', [
                'token_sample' => substr($token, 0, 10) . '...',
            ]);

            return [
                'success' => true,
                'result'  => $result,
            ];
        } catch (Exception $e) {
            Log::error("❌ FCM Error (single token): " . $e->getMessage(), [
                'token_sample' => substr($token, 0, 10) . '...',
            ]);

            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * إرسال إشعار لعدة توكنات دفعة واحدة   
     */
    public function sendToMultipleTokens(array $tokens, string $title, string $body, array $data = []): array
    {
        if (empty($tokens)) {
            Log::warning('⚠️ لا توجد توكنات مقدمة للإرسال', [
                'tokens_count' => 0
            ]);

            return [
                'success' => false,
                'error'   => 'No tokens provided',
            ];
        }

        // 🔍 تنقية التوكنات: تأكد من أنها نصوص غير فارغة
        $validTokens = array_values(array_filter($tokens, function ($token) {
            return is_string($token) && trim($token) !== '';
        }));

        // 📊 تسجيل إحصائيات التوكنات
        $invalidCount = count($tokens) - count($validTokens);
        if ($invalidCount > 0) {
            Log::warning('⚠️ تم تجاهل توكنات FCM غير صالحة', [
                'invalid_count' => $invalidCount,
                'original_count' => count($tokens),
                'valid_count' => count($validTokens),
                'token_types' => array_map('gettype', $tokens)
            ]);
        }

        if (empty($validTokens)) {
            Log::error('❌ لا توجد توكنات صالحة للإرسال بعد التنقية', [
                'original_tokens_count' => count($tokens)
            ]);

            return [
                'success' => false,
                'error'   => 'لا توجد توكنات صالحة للإرسال',
            ];
        }

        // 📝 تسجيل معلومات الإرسال قبل التنفيذ
        Log::info('📤 بدء إرسال إشعار لـ ' . count($validTokens) . ' توكن', [
            'notification_title' => $title,
            'notification_body_sample' => substr($body, 0, 50) . '...',
            'valid_tokens_count' => count($validTokens),
            'first_token_sample' => substr($validTokens[0], 0, 10) . '...',
        ]);

        if (!$this->messaging) {
            return [
                'success' => false,
                'error'   => 'Firebase service not initialized',
            ];
        }

        try {
            $notification = Notification::create($title, $body);

            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withData($this->normalizeData($data));

            $report = $this->messaging->sendMulticast($message, $validTokens);

            $invalidTokens = [];

            foreach ($report->failures()->getItems() as $failure) {
                $invalidTokens[] = $failure->target()->value();
            }

            // ✅ تسجيل النتائج
            if ($report->successes()->count() > 0) {
                Log::info('✅ نجاح إرسال الإشعارات', [
                    'success_count' => $report->successes()->count(),
                    'failure_count' => $report->failures()->count(),
                    'total_tokens' => count($validTokens),
                    'success_rate' => round(($report->successes()->count() / count($validTokens)) * 100, 2) . '%'
                ]);
            }

            // ⚠️ تسجيل التوكنات غير الصالحة من قبل Firebase
            if (!empty($invalidTokens)) {
                Log::warning('❌ توكنات FCM مرفوضة من قبل الخادم', [
                    'count' => count($invalidTokens),
                    'invalid_tokens_sample' => array_map(function ($t) {
                        return substr($t, 0, 10) . '...';
                    }, array_slice($invalidTokens, 0, 3))
                ]);
            }

            // 🎯 إذا فشل جميع التوكنات
            if ($report->failures()->count() === count($validTokens)) {
                Log::error('❌ فشل إرسال جميع الإشعارات', [
                    'total_tokens' => count($validTokens),
                    'failures_details' => array_map(function ($f) {
                        return [
                            'token' => substr($f->target()->value(), 0, 10) . '...',
                            'error' => $f->error()->getMessage()
                        ];
                    }, iterator_to_array($report->failures()->getItems()))
                ]);
            }

            return [
                'success'        => true,
                'success_count'  => $report->successes()->count(),
                'failure_count'  => $report->failures()->count(),
                'invalid_tokens' => $invalidTokens,
            ];
        } catch (Exception $e) {
            Log::error('🔥 FCM Error (multicast): ' . $e->getMessage(), [
                'exception_class' => get_class($e),
                'exception_message' => $e->getMessage(),
                'exception_trace' => substr($e->getTraceAsString(), 0, 200),
                'valid_tokens_count' => count($validTokens),
                'first_token_sample' => isset($validTokens[0]) ? substr($validTokens[0], 0, 10) . '...' : null
            ]);

            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * إرسال إشعار إلى Topic
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): array
    {
        if (!$this->messaging) {
            return [
                'success' => false,
                'error'   => 'Firebase service not initialized',
            ];
        }

        try {
            $message = CloudMessage::fromArray([
                'topic' => $topic,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'data' => $this->normalizeData($data),
            ]);

            $result = $this->messaging->send($message);

            Log::info('✅ تم إرسال الإشعار للموضوع بنجاح', [
                'topic' => $topic,
                'title' => $title
            ]);

            return [
                'success' => true,
                'result'  => $result,
            ];
        } catch (Exception $e) {
            Log::error("❌ FCM Error (topic): " . $e->getMessage(), [
                'topic' => $topic,
            ]);

            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * تطبيع بيانات الإشعار لضمان توافقها مع Firebase
     */
    private function normalizeData(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $normalized[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
            } elseif (is_bool($value)) {
                $normalized[$key] = $value ? '1' : '0';
            } elseif (is_null($value)) {
                $normalized[$key] = '';
            } else {
                $normalized[$key] = (string) $value;
            }
        }

        return $normalized;
    }
}
