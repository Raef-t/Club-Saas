<?php

namespace Modules\NotificationManager\Http\Controllers\Api\V1;

use Modules\NotificationManager\Models\NotificationLog;
use Modules\NotificationManager\Http\Resources\NotificationLogResource;
use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
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
}
