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
        summary: '📜 View notification history',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
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
        summary: '🔍 View single log detail',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function show($id)
    {
        $log = NotificationLog::findOrFail($id);
        return $this->successResponse(new NotificationLogResource($log), __('Log retrieved'));
    }

    #[OA\Delete(
        path: '/v1/notification-logs/{id}',
        summary: '🗑 Delete a log entry',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Log deleted')
        ]
    )]
    public function destroy($id)
    {
        $log = NotificationLog::findOrFail($id);
        $log->delete();
        return $this->successResponse(null, __('Log deleted'));
    }

    #[OA\Get(
        path: '/v1/notification-stats',
        summary: '📊 Notification delivery statistics',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
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
        summary: '🔔 List authenticated member\'s notifications',
        tags: ['Member Notifications'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Notifications retrieved')
        ]
    )]
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
        summary: '✅ Mark notification as read',
        tags: ['Member Notifications'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Notification marked as read')
        ]
    )]
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
        summary: '✅ Mark all notifications as read',
        tags: ['Member Notifications'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'All notifications marked as read')
        ]
    )]
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
                ->whereNull('deleted_at')
                ->first();
        }

        return null;
    }
}
