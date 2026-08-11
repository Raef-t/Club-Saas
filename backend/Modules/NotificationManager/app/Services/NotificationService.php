<?php

namespace Modules\NotificationManager\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\NotificationManager\Models\Notification;
use Modules\NotificationManager\Models\NotificationAttachment;
use Modules\NotificationManager\Models\NotificationRecipient;
use Modules\NotificationManager\Events\NotificationCreated;

class NotificationService
{
    /**
     * إنشاء إشعار جديد مع مستلميه ومرفقاته
     *
     * @param array $data {
     *   title: string,
     *   body: string,
     *   sender_id: int|null,
     *   sender_type: string|null (admin|system),
     *   target_snapshot: array|null,
     *   user_ids: int[],
     *   attachments: UploadedFile[]|null,
     * }
     */
    public function createNotification(array $data): Notification
    {
        return DB::transaction(function () use ($data) {

            // 1️⃣ إنشاء الإشعار الرئيسي
            $notification = Notification::create([
                'title'           => $data['title'],
                'body'            => $data['body'],
                'sender_id'       => $data['sender_id'] ?? null,
                'sender_type'     => $data['sender_type'] ?? null,
                'target_snapshot' => $data['target_snapshot'] ?? null,
            ]);

            // 2️⃣ رفع المرفقات
            if (!empty($data['attachments'])) {
                foreach ($data['attachments'] as $file) {
                    $path = $file->store("notifications/{$notification->id}", 'public');

                    NotificationAttachment::create([
                        'notification_id' => $notification->id,
                        'file_name'       => $file->getClientOriginalName(),
                        'file_path'       => $path,
                        'mime_type'       => $file->getClientMimeType(),
                        'size'            => $file->getSize(),
                    ]);
                }
            }

            // 3️⃣ ربط المستلمين
            $userIds = $data['user_ids'] ?? [];

            if (!empty($userIds)) {
                $userIds = array_unique(array_filter($userIds));
                $now = now();
                $recipientsData = array_map(fn($userId) => [
                    'notification_id' => $notification->id,
                    'user_id'         => $userId,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ], $userIds);

                NotificationRecipient::insert($recipientsData);

                Log::info('📦 تم إنشاء مستلمي الإشعار', [
                    'notification_id'  => $notification->id,
                    'recipients_count' => count($userIds),
                ]);
            }

            // 4️⃣ إطلاق الحدث
            event(new NotificationCreated($notification));

            return $notification->load([
                'attachments',
                'recipients',
            ]);
        });
    }

    /**
     * تعليم إشعار كمقروء لمستخدم محدد
     */
    public function markAsRead(int $recipientId, int $userId): bool
    {
        $recipient = NotificationRecipient::where('id', $recipientId)
            ->where('user_id', $userId)
            ->first();

        if (!$recipient) return false;

        if (is_null($recipient->read_at)) {
            $recipient->update(['read_at' => now()]);
        }

        return true;
    }

    /**
     * تعليم جميع إشعارات المستخدم كمقروءة
     */
    public function markAllAsRead(int $userId): int
    {
        return NotificationRecipient::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * عدد الإشعارات غير المقروءة للمستخدم
     */
    public function unreadCount(int $userId): int
    {
        return NotificationRecipient::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * حذف إشعار من قائمة مستخدم (حذف سجل الاستقبال فقط)
     */
    public function removeFromUserList(int $recipientId, int $userId): bool
    {
        return (bool) NotificationRecipient::where('id', $recipientId)
            ->where('user_id', $userId)
            ->delete();
    }
}
