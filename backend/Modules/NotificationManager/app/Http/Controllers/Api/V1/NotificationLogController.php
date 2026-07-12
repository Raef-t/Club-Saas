<?php

namespace Modules\NotificationManager\Http\Controllers\Api\V1;

use Modules\NotificationManager\Models\NotificationLog;
use Modules\NotificationManager\Http\Resources\NotificationLogResource;
use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class NotificationLogController extends BaseController
{
    #[OA\Get(
        path: '/v1/notification-logs',
        summary: '📜 عرض سجل الإشعارات',
        description: 'استرجاع جميع الإشعارات المرسلة مع إمكانية الفلترة.',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'channel', in: 'query', required: false, description: 'قناة الإرسال (مثال: email, sms)', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'status', in: 'query', required: false, description: 'حالة الإشعار (مثال: sent, failed)', schema: new OA\Schema(type: 'string'))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع السجل بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Logs retrieved'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index(Request $request)
    {
        $logs = NotificationLog::orderBy('created_at', 'desc')
            ->when($request->channel, fn($q) => $q->where('channel', $request->channel))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->paginate(20);

        return $this->successResponse(NotificationLogResource::collection($logs), __('Logs retrieved'));
    }

    #[OA\Get(
        path: '/v1/notification-logs/{id}',
        summary: '🔍 تفاصيل الإشعار',
        description: 'استرجاع تفاصيل سجل إشعار محدد.',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف السجل', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تفاصيل الإشعار',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Log retrieved'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 السجل غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show($id)
    {
        $log = NotificationLog::findOrFail($id);
        return $this->successResponse(new NotificationLogResource($log), __('Log retrieved'));
    }

    #[OA\Delete(
        path: '/v1/notification-logs/{id}',
        summary: '🗑️ حذف إشعار',
        description: 'حذف سجل إشعار من النظام.',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف السجل', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم الحذف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Log deleted'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 السجل غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy($id)
    {
        $log = NotificationLog::findOrFail($id);
        $log->delete();
        return $this->successResponse(null, __('Log deleted'));
    }

    #[OA\Get(
        path: '/v1/notification-stats',
        summary: '📊 إحصائيات الإشعارات',
        description: 'عرض إحصائيات مفصلة عن حالة تسليم الإشعارات.',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ الإحصائيات المسترجعة',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Stats retrieved'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function stats()
    {
        $stats = NotificationLog::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return $this->successResponse($stats, __('Stats retrieved'));
    }

    /**
     * Get the authenticated member's notifications with pagination.
     */
    #[OA\Get(
        path: '/v1/my-notifications',
        summary: '🔔 إشعاراتي',
        description: 'استرجاع قائمة الإشعارات الخاصة بالعضو المسجل للدخول.',
        tags: ['Member Notifications'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة', schema: new OA\Schema(type: 'integer', example: 15))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الإشعارات',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Notifications retrieved successfully'),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'items', type: 'array', items: new OA\Items(type: 'object')),
                    new OA\Property(property: 'pagination', type: 'object')
                ])
            ]
        )
    )]
    #[OA\Response(response: 403, description: '🚫 الملف الشخصي للعضو غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Member profile not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function myNotifications(Request $request)
    {
        $user = $request->user();
        $member = $this->resolveMember($user);

        if (!$member) {
            return $this->errorResponse(__('Member profile not found.'), 403);
        }

        $perPage = $request->input('per_page', 15);

        $notifications = NotificationLog::where('recipient_id', $member->id)
            ->where('recipient_type', \Modules\MemberManager\Models\Member::class)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return $this->successResponse([
            'items' => NotificationLogResource::collection($notifications),
            'pagination' => [
                'total' => $notifications->total(),
                'per_page' => $notifications->perPage(),
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
            ],
        ], __('Notifications retrieved successfully'));
    }

    /**
     * Mark a single notification as read.
     */
    #[OA\Post(
        path: '/v1/my-notifications/{id}/read',
        summary: '✅ تحديد إشعار كمقروء',
        description: 'تغيير حالة إشعار معين إلى مقروء.',
        tags: ['Member Notifications'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الإشعار', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم التحديد كمقروء',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Notification marked as read'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 الإشعار غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Notification not found.')]))]
    #[OA\Response(response: 403, description: '🚫 الملف الشخصي للعضو غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Member profile not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        $member = $this->resolveMember($user);

        if (!$member) {
            return $this->errorResponse(__('Member profile not found.'), 403);
        }

        $notification = NotificationLog::where('id', $id)
            ->where('recipient_id', $member->id)
            ->where('recipient_type', \Modules\MemberManager\Models\Member::class)
            ->first();

        if (!$notification) {
            return $this->errorResponse(__('Notification not found.'), 404);
        }

        $notification->update(['read_at' => now()]);

        return $this->successResponse(null, __('Notification marked as read'));
    }

    /**
     * Mark all member's notifications as read.
     */
    #[OA\Post(
        path: '/v1/my-notifications/read-all',
        summary: '✅ تحديد كل الإشعارات كمقروءة',
        description: 'تغيير حالة جميع إشعارات العضو إلى مقروءة.',
        tags: ['Member Notifications'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم التحديد بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'All notifications marked as read'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ]
        )
    )]
    #[OA\Response(response: 403, description: '🚫 الملف الشخصي للعضو غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Member profile not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        $member = $this->resolveMember($user);

        if (!$member) {
            return $this->errorResponse(__('Member profile not found.'), 403);
        }

        NotificationLog::where('recipient_id', $member->id)
            ->where('recipient_type', \Modules\MemberManager\Models\Member::class)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->successResponse(null, __('All notifications marked as read'));
    }

    /**
     * Resolve the Member record from the authenticated user.
     */
    protected function resolveMember($user): ?object
    {
        if ($user instanceof \Modules\MemberManager\Models\Member) {
            return $user;
        }

        if (isset($user->person_id)) {
            return DB::table('members')
                ->where('person_id', $user->person_id)
                ->first();
        }

        return null;
    }
}
