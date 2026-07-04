<?php

namespace Modules\NotificationManager\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\NotificationManager\Http\Requests\StoreNotificationRequest;
use Modules\NotificationManager\Http\Requests\AdminNotificationIndexRequest;
use Modules\NotificationManager\Http\Resources\UserNotificationListResource;
use Modules\NotificationManager\Http\Resources\NotificationUserDetailResource;
use Modules\NotificationManager\Http\Resources\AdminNotificationResource;
use Modules\NotificationManager\Http\Resources\NotificationAdminDetailResource;
use Modules\NotificationManager\Models\Notification;
use Modules\NotificationManager\Models\NotificationRecipient;
use Modules\NotificationManager\Services\NotificationService;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    // =========================================================
    // 🔹 واجهات المستخدم العادي
    // =========================================================

    /**
     * GET /notifications
     * جلب قائمة إشعارات المستخدم المسجل دخوله
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);

        $recipients = NotificationRecipient::with([
                'notification.attachments',
            ])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'status' => true,
            'data'   => UserNotificationListResource::collection($recipients),
            'meta'   => [
                'current_page' => $recipients->currentPage(),
                'last_page'    => $recipients->lastPage(),
                'per_page'     => $recipients->perPage(),
                'total'        => $recipients->total(),
            ],
        ]);
    }

    /**
     * GET /notifications/unread/count
     * عدد الإشعارات غير المقروءة للمستخدم
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notificationService->unreadCount($request->user()->id);

        return response()->json([
            'status' => true,
            'data'   => ['unread_count' => $count],
        ]);
    }

    /**
     * GET /notifications/{reception}
     * جلب تفاصيل إشعار واحد للمستخدم
     */
    public function show(Request $request, int $receptionId): JsonResponse
    {
        $recipient = NotificationRecipient::with([
                'notification.attachments',
            ])
            ->where('id', $receptionId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$recipient) {
            return response()->json([
                'status'  => false,
                'message' => 'الإشعار غير موجود أو لا تملك صلاحية الوصول إليه.',
            ], 404);
        }

        // تعليم كمستلم إذا لم يُستلم بعد
        if (is_null($recipient->delivered_at)) {
            $recipient->update(['delivered_at' => now()]);
        }

        return response()->json([
            'status' => true,
            'data'   => new NotificationUserDetailResource($recipient),
        ]);
    }

    /**
     * PATCH /notifications/{reception}/read
     * تعليم إشعار كمقروء
     */
    public function markAsRead(Request $request, int $receptionId): JsonResponse
    {
        $success = $this->notificationService->markAsRead($receptionId, $request->user()->id);

        if (!$success) {
            return response()->json([
                'status'  => false,
                'message' => 'الإشعار غير موجود.',
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'تم تعليم الإشعار كمقروء.',
        ]);
    }

    /**
     * POST /notifications/mark-all-as-read
     * تعليم جميع الإشعارات كمقروءة
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $updated = $this->notificationService->markAllAsRead($request->user()->id);

        return response()->json([
            'status'  => true,
            'message' => "تم تعليم {$updated} إشعار كمقروء.",
            'data'    => ['updated_count' => $updated],
        ]);
    }

    /**
     * DELETE /notifications/{reception}
     * حذف إشعار من قائمة المستخدم
     */
    public function destroy(Request $request, int $receptionId): JsonResponse
    {
        $success = $this->notificationService->removeFromUserList($receptionId, $request->user()->id);

        if (!$success) {
            return response()->json([
                'status'  => false,
                'message' => 'الإشعار غير موجود.',
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف الإشعار من قائمتك.',
        ]);
    }

    // =========================================================
    // 🔹 واجهات الإدارة
    // =========================================================

    /**
     * POST /notifications
     * إنشاء إشعار جديد
     */
    public function store(StoreNotificationRequest $request): JsonResponse
    {
        $data = $request->validated();

        // تحديد المستلمين بناءً على target_snapshot
        $userIds = $this->resolveRecipients($data['target_snapshot'] ?? []);

        $notification = $this->notificationService->createNotification([
            'title'           => $data['title'],
            'body'            => $data['body'],
            'sender_id'       => $data['sender_id'] ?? $request->user()->id,
            'sender_type'     => $data['sender_type'] ?? 'admin',
            'target_snapshot' => $data['target_snapshot'] ?? null,
            'user_ids'        => $userIds,
            'attachments'     => $request->file('attachments') ?? [],
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم إنشاء الإشعار وإرساله بنجاح.',
            'data'    => [
                'id'               => $notification->id,
                'title'            => $notification->title,
                'recipients_count' => $notification->recipients->count(),
            ],
        ], 201);
    }

    /**
     * GET /admin/notifications
     * عرض جميع الإشعارات في النظام (للإدارة)
     */
    public function adminIndex(AdminNotificationIndexRequest $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);

        $query = Notification::withCount([
                'recipients as recipients_count',
                'recipients as read_count'      => fn($q) => $q->whereNotNull('read_at'),
                'recipients as delivered_count' => fn($q) => $q->whereNotNull('delivered_at'),
            ])
            ->with('attachments')
            ->latest();

        // فلاتر
        if ($request->filled('sender_type')) {
            $query->where('sender_type', $request->sender_type);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }
        if ($request->boolean('has_attachments', null) !== null && $request->has('has_attachments')) {
            $request->boolean('has_attachments')
                ? $query->has('attachments')
                : $query->doesntHave('attachments');
        }

        $notifications = $query->paginate($perPage);

        return response()->json([
            'status' => true,
            'data'   => AdminNotificationResource::collection($notifications),
            'meta'   => [
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
                'per_page'     => $notifications->perPage(),
                'total'        => $notifications->total(),
            ],
        ]);
    }

    /**
     * GET /admin/notifications/{notification}
     * عرض تفاصيل إشعار (للإدارة) مع جميع المستلمين
     */
    public function adminShow(Notification $notification): JsonResponse
    {
        $notification->load(['attachments', 'recipients']);

        // نعرض أول مستلم كـ representative (أو يمكن توسيع هذا لاحقاً)
        $firstRecipient = $notification->recipients->first();

        if (!$firstRecipient) {
            return response()->json([
                'status' => true,
                'data'   => [
                    'id'               => $notification->id,
                    'title'            => $notification->title,
                    'body'             => $notification->body,
                    'sender_type'      => $notification->sender_type,
                    'target_snapshot'  => $notification->target_snapshot,
                    'recipients_count' => 0,
                    'attachments'      => $notification->attachments,
                    'created_at'       => $notification->created_at,
                ],
            ]);
        }

        return response()->json([
            'status' => true,
            'data'   => new NotificationAdminDetailResource($firstRecipient->load(['notification.attachments', 'notification.recipients'])),
        ]);
    }

    /**
     * DELETE /admin/notifications/{notification}
     * حذف إشعار من النظام بالكامل (للإدارة)
     */
    public function adminDestroy(Notification $notification): JsonResponse
    {
        $notification->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف الإشعار من النظام بالكامل.',
        ]);
    }

    /**
     * تحديد قائمة المستلمين بناءً على target_snapshot
     * (منطق عام - يمكن توسيعه لاحقاً حسب متطلبات النظام)
     */
    private function resolveRecipients(array $targetSnapshot): array
    {
        $type = $targetSnapshot['type'] ?? 'custom';

        /** @var \Illuminate\Database\Eloquent\Builder $baseQuery */
        $baseQuery = \Modules\Authentication\Models\User::query()->where('is_active', true);

        if ($type === 'all') {
            return $baseQuery->pluck('id')->toArray();
        }

        if ($type === 'branch') {
            $branchId = $targetSnapshot['branch_id'] ?? null;
            if (!$branchId) return [];

            // 1. جلب معرفات الأشخاص (person_id) للموظفين في هذا الفرع
            $staffPersonIds = \Modules\StaffManager\Models\Staff::whereHas('branches', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })->pluck('person_id')->toArray();

            // 2. جلب معرفات الأشخاص (person_id) للأعضاء المشتركين في هذا الفرع
            $memberPersonIds = \Modules\MemberManager\Models\Member::where('branch_id', $branchId)
                ->pluck('person_id')->toArray();

            // دمج القائمتين وإزالة التكرار
            $personIds = array_unique(array_merge($staffPersonIds, $memberPersonIds));

            // إرجاع معرّفات المستخدمين (user_id) النشطين المرتبطين بهؤلاء الأشخاص
            return $baseQuery
                ->whereIn('person_id', $personIds)
                ->pluck('id')
                ->toArray();
        }

        if ($type === 'custom') {
            $userIds = $targetSnapshot['user_ids'] ?? [];
            if (empty($userIds)) return [];

            // التحقق من أن المستخدمين المحددين نشطون فعلاً
            return $baseQuery
                ->whereIn('id', $userIds)
                ->pluck('id')
                ->toArray();
        }

        return [];
    }

}
