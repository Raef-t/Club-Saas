<?php

namespace Modules\NotificationManager\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use OpenApi\Attributes as OA;
use Modules\NotificationManager\Http\Requests\StoreNotificationRequest;
use Modules\NotificationManager\Http\Requests\AdminNotificationIndexRequest;
use Modules\NotificationManager\Http\Requests\SendNotificationToUsersRequest;
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

    #[OA\Get(
        path: '/v1/notifications',
        summary: '📋 قائمة إشعاراتي',
        description: 'جلب قائمة إشعارات المستخدم المسجل دخوله مع دعم الترقيم (Pagination).',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        required: false,
        description: 'عدد العناصر في الصفحة (الافتراضي: 15)',
        schema: new OA\Schema(type: 'integer', example: 15)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب قائمة الإشعارات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'boolean', example: true),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'reception_id', type: 'integer', example: 10),
                            new OA\Property(property: 'title', type: 'string', example: 'تنبيه جديد'),
                            new OA\Property(property: 'body', type: 'string', example: 'محتوى الإشعار...'),
                            new OA\Property(property: 'is_read', type: 'boolean', example: false),
                            new OA\Property(property: 'read_at', type: 'string', format: 'date-time', nullable: true, example: null),
                            new OA\Property(property: 'delivered_at', type: 'string', format: 'date-time', nullable: true, example: '2026-07-25T09:00:00Z'),
                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-07-25T08:30:00Z')
                        ]
                    )
                ),
                new OA\Property(
                    property: 'meta',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        new OA\Property(property: 'last_page', type: 'integer', example: 5),
                        new OA\Property(property: 'per_page', type: 'integer', example: 15),
                        new OA\Property(property: 'total', type: 'integer', example: 72)
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح (تطلب تسجيل الدخول)',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')
            ]
        )
    )]
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

    #[OA\Get(
        path: '/v1/notifications/unread/count',
        summary: '🔢 عدد الإشعارات غير المقروءة',
        description: 'استرجاع عدد الإشعارات غير المقروءة للمستخدم الحالي.',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب العدد بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'boolean', example: true),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'unread_count', type: 'integer', example: 5)
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')
            ]
        )
    )]
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notificationService->unreadCount($request->user()->id);

        return response()->json([
            'status' => true,
            'data'   => ['unread_count' => $count],
        ]);
    }

    #[OA\Get(
        path: '/v1/notifications/{reception}',
        summary: '🔍 تفاصيل إشعار محدد للمستخدم',
        description: 'استرجاع تفاصيل إشعار معين للمستخدم الحالي مع المرفقات، ويتم تحديث حالة الاستلام (delivered_at) تلقائياً.',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'reception',
        in: 'path',
        required: true,
        description: 'معرّف سجل استلام الإشعار (reception_id)',
        schema: new OA\Schema(type: 'integer', example: 10)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب تفاصيل الإشعار بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'boolean', example: true),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 10),
                        new OA\Property(property: 'title', type: 'string', example: 'تنبيه جديد'),
                        new OA\Property(property: 'body', type: 'string', example: 'تفاصيل الإشعار...'),
                        new OA\Property(property: 'delivered_at', type: 'string', format: 'date-time', example: '2026-07-25T09:30:00Z'),
                        new OA\Property(property: 'read_at', type: 'string', format: 'date-time', nullable: true, example: null),
                        new OA\Property(property: 'attachments', type: 'array', items: new OA\Items(type: 'object'))
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: '🚫 الإشعار غير موجود أو لا تملك صلاحية الوصول إليه',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'الإشعار غير موجود أو لا تملك صلاحية الوصول إليه.')
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')
            ]
        )
    )]
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

        if (is_null($recipient->delivered_at)) {
            $recipient->update(['delivered_at' => now()]);
        }

        return response()->json([
            'status' => true,
            'data'   => new NotificationUserDetailResource($recipient),
        ]);
    }

    #[OA\Patch(
        path: '/v1/notifications/{reception}/read',
        summary: '✔ تعليم إشعار كمقروء',
        description: 'تغيير حالة الإشعار إلى مقروء وتسجيل تاريخ القراءة.',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'reception',
        in: 'path',
        required: true,
        description: 'معرّف سجل استلام الإشعار (reception_id)',
        schema: new OA\Schema(type: 'integer', example: 10)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تعليم الإشعار كمقروء',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم تعليم الإشعار كمقروء.')
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: '🚫 الإشعار غير موجود',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'الإشعار غير موجود.')
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')
            ]
        )
    )]
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

    #[OA\Post(
        path: '/v1/notifications/mark-all-as-read',
        summary: '☑ تعليم جميع الإشعارات كمقروءة',
        description: 'تغيير حالة جميع إشعارات المستخدم غير المقروءة إلى مقروءة بنقرة واحدة.',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تعليم جميع الإشعارات كمقروءة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم تعليم 5 إشعار كمقروء.'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'updated_count', type: 'integer', example: 5)
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')
            ]
        )
    )]
    public function markAllAsRead(Request $request): JsonResponse
    {
        $updated = $this->notificationService->markAllAsRead($request->user()->id);

        return response()->json([
            'status'  => true,
            'message' => "تم تعليم {$updated} إشعار كمقروء.",
            'data'    => ['updated_count' => $updated],
        ]);
    }

    #[OA\Delete(
        path: '/v1/notifications/{reception}',
        summary: '🗑️ حذف إشعار من قائمة المستخدم',
        description: 'حذف الإشعار المحدد من قائمة إشعارات المستخدم الحالي.',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'reception',
        in: 'path',
        required: true,
        description: 'معرّف سجل استلام الإشعار (reception_id)',
        schema: new OA\Schema(type: 'integer', example: 10)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم حذف الإشعار بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم حذف الإشعار من قائمتك.')
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: '🚫 الإشعار غير موجود',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'الإشعار غير موجود.')
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')
            ]
        )
    )]
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

    #[OA\Post(
        path: '/v1/admin/notifications',
        summary: '➕ إنشاء وتوزيع إشعار جديد (للأدمن)',
        description: 'إنشاء إشعار جديد وتوجيهه لمستخدمين محددين، لجميع المستخدمين، أو حسب الفرع، مع إمكانية إرفاق ملفات.',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        description: 'بيانات الإشعار والمستهدفين والمرفقات',
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['title', 'body', 'target_snapshot[type]'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', minLength: 3, maxLength: 255, example: 'تنبيه صيانة النادي'),
                    new OA\Property(property: 'body', type: 'string', minLength: 5, maxLength: 2000, example: 'سيتم إغلاق الصالة غداً لغرض الصيانة الدوريّة.'),
                    new OA\Property(property: 'sender_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'sender_type', type: 'string', enum: ['admin', 'system', 'user'], default: 'admin', example: 'admin'),
                    new OA\Property(property: 'target_snapshot[type]', type: 'string', enum: ['all', 'branch', 'custom'], example: 'custom', description: 'نوع استهداف المستلمين'),
                    new OA\Property(
                        property: 'target_snapshot[user_ids][]',
                        type: 'array',
                        items: new OA\Items(type: 'integer', example: 5),
                        description: 'قائمة معرفات المستخدمين (مطلوب في حال اختيار custom)'
                    ),
                    new OA\Property(property: 'target_snapshot[branch_id]', type: 'integer', nullable: true, example: 2, description: 'معرف الفرع (مطلوب في حال اختيار branch)'),
                    new OA\Property(
                        property: 'attachments[]',
                        type: 'array',
                        items: new OA\Items(type: 'string', format: 'binary'),
                        description: 'المرفقات (حد أقصى 5 ملفات، كل ملف 10MB كحد أقصى)'
                    )
                ]
            )
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء الإشعار وإرساله بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم إنشاء الإشعار وإرساله بنجاح.'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 101),
                        new OA\Property(property: 'title', type: 'string', example: 'تنبيه صيانة النادي'),
                        new OA\Property(property: 'recipients_count', type: 'integer', example: 15)
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: '⚠️ خطأ في التحقق من صحة البيانات المدخلة (Validation Error)',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'),
                new OA\Property(
                    property: 'errors',
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'title',
                            type: 'array',
                            items: new OA\Items(type: 'string', example: 'حقل العنوان مطلوب.')
                        ),
                        new OA\Property(
                            property: 'target_snapshot.type',
                            type: 'array',
                            items: new OA\Items(type: 'string', example: 'نوع المستهدفين يجب أن يكون (all, branch, custom).')
                        )
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: '🚫 غير مسموح (يتطلب صلاحيات الأدمن/المدير)',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'User does not have the right roles.')
            ]
        )
    )]
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

    #[OA\Post(
        path: '/v1/admin/notifications/send-to-users',
        summary: '🎯 إرسال إشعار لمجموعة مستخدمين مخصصة (User IDs)',
        description: 'إرسال إشعار مباشر لمصفوفة محددة من معرّفات المستخدمين (user_ids) مع إمكانية إضافة مرفقات.',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        description: 'بيانات الإشعار وقائمة المستهدفين',
        content: [
            new OA\JsonContent(
                required: ['title', 'body', 'user_ids'],
                properties: [
                    new OA\Property(
                        property: 'user_ids',
                        type: 'array',
                        items: new OA\Items(type: 'integer', example: 4),
                        description: 'مصفوفة معرّفات المستخدمين المستهدفين'
                    ),
                    new OA\Property(property: 'title', type: 'string', minLength: 3, maxLength: 255, example: 'تنبيه خاص للمستخدمين'),
                    new OA\Property(property: 'body', type: 'string', minLength: 5, maxLength: 2000, example: 'أهلاً بك، هذا إشعار موجه لك وللمجموعة المحددة.'),
                    new OA\Property(property: 'sender_type', type: 'string', enum: ['admin', 'system', 'user'], default: 'admin', example: 'admin')
                ]
            ),
            new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['title', 'body', 'user_ids'],
                    properties: [
                        new OA\Property(
                            property: 'user_ids',
                            type: 'array',
                            items: new OA\Items(type: 'integer', example: 4),
                            description: 'مصفوفة معرّفات المستخدمين المستهدفين'
                        ),
                        new OA\Property(property: 'title', type: 'string', minLength: 3, maxLength: 255, example: 'تنبيه خاص للمستخدمين'),
                        new OA\Property(property: 'body', type: 'string', minLength: 5, maxLength: 2000, example: 'أهلاً بك، هذا إشعار موجه لك وللمجموعة المحددة.'),
                        new OA\Property(property: 'sender_type', type: 'string', enum: ['admin', 'system', 'user'], default: 'admin', example: 'admin'),
                        new OA\Property(
                            property: 'attachments',
                            type: 'array',
                            items: new OA\Items(type: 'string', format: 'binary'),
                            description: 'المرفقات (اختيارية، حد أقصى 5 ملفات)'
                        )
                    ]
                )
            )
        ]
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إرسال الإشعار بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم إرسال الإشعار إلى المستخدمين بنجاح.'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'notification_id', type: 'integer', example: 105),
                        new OA\Property(property: 'title', type: 'string', example: 'تنبيه خاص للمستخدمين'),
                        new OA\Property(property: 'recipients_count', type: 'integer', example: 2),
                        new OA\Property(
                            property: 'target_user_ids',
                            type: 'array',
                            items: new OA\Items(type: 'integer', example: 4)
                        )
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: '⚠️ خطأ في البيانات المدخلة (مصفوفة user_ids أو المحتوى)',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'),
                new OA\Property(
                    property: 'errors',
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'user_ids',
                            type: 'array',
                            items: new OA\Items(type: 'string', example: 'حقل قائمة المستخدمين (user_ids) مطلوب.')
                        )
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    #[OA\Response(response: 403, description: '🚫 غير مسموح (يتطلب صلاحيات الأدمن)', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'User does not have the right roles.')]))]
    public function sendToUsers(SendNotificationToUsersRequest $request): JsonResponse
    {
        $data = $request->validated();

        $activeUserIds = \Modules\Authentication\Models\User::query()
            ->whereIn('id', $data['user_ids'])
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        if (empty($activeUserIds)) {
            return response()->json([
                'status'  => false,
                'message' => 'لا يوجد مستخدمون نشطون ضمن المعرفات المحددة.',
            ], 422);
        }

        $attachments = $request->file('attachments');
        if ($attachments instanceof \Illuminate\Http\UploadedFile) {
            $attachments = [$attachments];
        } elseif (!is_array($attachments)) {
            $attachments = [];
        }

        $notification = $this->notificationService->createNotification([
            'title'           => $data['title'],
            'body'            => $data['body'],
            'sender_id'       => $request->user()->id,
            'sender_type'     => $data['sender_type'] ?? 'admin',
            'target_snapshot' => [
                'type'     => 'custom',
                'user_ids' => $activeUserIds,
            ],
            'user_ids'        => $activeUserIds,
            'attachments'     => $attachments,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم إرسال الإشعار إلى المستخدمين بنجاح.',
            'data'    => [
                'notification_id'  => $notification->id,
                'title'            => $notification->title,
                'body'             => $notification->body,
                'recipients_count' => count($activeUserIds),
                'target_user_ids'  => $activeUserIds,
            ],
        ], 201);
    }

    #[OA\Get(
        path: '/v1/admin/notifications',
        summary: '📜 عرض جميع الإشعارات مع الفلترة (للأدمن)',
        description: 'استرجاع قائمة الإشعارات المرسلة في النظام مع إمكانية الفلترة حسب نوع المرسل، النطاق الزمني، المرفقات، والصفحات.',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'user_id', in: 'query', required: false, description: 'تصفية حسب مستخدم معين', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'sender_type', in: 'query', required: false, description: 'نوع المرسل (admin, system, user)', schema: new OA\Schema(type: 'string', enum: ['admin', 'system', 'user']))]
    #[OA\Parameter(name: 'from', in: 'query', required: false, description: 'من تاريخ (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'to', in: 'query', required: false, description: 'إلى تاريخ (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'has_attachments', in: 'query', required: false, description: 'تصفية الإشعارات التي تحتوي على مرفقات فقط (true/false)', schema: new OA\Schema(type: 'boolean'))]
    #[OA\Parameter(name: 'read', in: 'query', required: false, description: 'تصفية حسب حالة القراءة', schema: new OA\Schema(type: 'boolean'))]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر بالصفحة (1-100)', schema: new OA\Schema(type: 'integer', default: 15))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', default: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع قائمة إشعارات الأدمن بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'boolean', example: true),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 101),
                            new OA\Property(property: 'title', type: 'string', example: 'تنبيه صيانة النادي'),
                            new OA\Property(property: 'sender_type', type: 'string', example: 'admin'),
                            new OA\Property(property: 'recipients_count', type: 'integer', example: 50),
                            new OA\Property(property: 'read_count', type: 'integer', example: 35),
                            new OA\Property(property: 'delivered_count', type: 'integer', example: 48),
                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-07-25T08:00:00Z')
                        ]
                    )
                ),
                new OA\Property(
                    property: 'meta',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        new OA\Property(property: 'last_page', type: 'integer', example: 3),
                        new OA\Property(property: 'per_page', type: 'integer', example: 15),
                        new OA\Property(property: 'total', type: 'integer', example: 45)
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: '⚠️ خطأ في معلمات الاستعلام (Query Parameters)',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'),
                new OA\Property(
                    property: 'errors',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'from', type: 'array', items: new OA\Items(type: 'string', example: 'تاريخ البداية غير صالح'))
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: '🚫 غير مسموح (يتطلب صلاحيات الأدمن)',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'User does not have the right roles.')
            ]
        )
    )]
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

    #[OA\Get(
        path: '/v1/admin/notifications/{notification}',
        summary: '🔍 تفاصيل إشعار مع المستلمين (للأدمن)',
        description: 'عرض بيانات إشعار شاملة مع تفاصيل المستلمين والمرفقات.',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'notification',
        in: 'path',
        required: true,
        description: 'معرّف الإشعار (Notification ID)',
        schema: new OA\Schema(type: 'integer', example: 101)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع تفاصيل الإشعار للإدارة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'boolean', example: true),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 101),
                        new OA\Property(property: 'title', type: 'string', example: 'تنبيه صيانة النادي'),
                        new OA\Property(property: 'body', type: 'string', example: 'سيتم إغلاق الصالة غداً لغرض الصيانة الدوريّة.'),
                        new OA\Property(property: 'sender_type', type: 'string', example: 'admin'),
                        new OA\Property(property: 'target_snapshot', type: 'object'),
                        new OA\Property(property: 'recipients_count', type: 'integer', example: 50),
                        new OA\Property(property: 'attachments', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-07-25T08:00:00Z')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: '🚫 الإشعار غير موجود في النظام',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'No query results for model [Modules\\NotificationManager\\Models\\Notification].')
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: '🚫 غير مسموح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'User does not have the right roles.')
            ]
        )
    )]
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

    #[OA\Delete(
        path: '/v1/admin/notifications/{notification}',
        summary: '🗑️ حذف إشعار بالكامل من النظام (للأدمن)',
        description: 'حذف الإشعار وكافة سجلات استلامه والمرفقات التابعة له من النظام نهائياً.',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'notification',
        in: 'path',
        required: true,
        description: 'معرّف الإشعار (Notification ID)',
        schema: new OA\Schema(type: 'integer', example: 101)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم حذف الإشعار بالكامل من النظام',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'تم حذف الإشعار من النظام بالكامل.')
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: '🚫 الإشعار غير موجود',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'No query results for model [Modules\\NotificationManager\\Models\\Notification].')
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: '🚫 غير مسموح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'User does not have the right roles.')
            ]
        )
    )]
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
