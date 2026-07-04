<?php

namespace Modules\NotificationManager\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Authentication\Models\UserDevice;

class FcmTokenController extends Controller
{
    /**
     * POST /v1/fcm-tokens
     * تسجيل أو تحديث توكن FCM للمستخدم في جدول user_devices
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token'   => ['required', 'string'],
            'device_info' => ['nullable', 'array'],
        ]);

        $device = UserDevice::updateOrCreate(
            ['fcm_token' => $request->fcm_token],
            [
                'user_id'     => $request->user()->id,
                'device_info' => $request->input('device_info', []),
            ]
        );

        return response()->json([
            'status'  => true,
            'message' => 'تم حفظ توكن الجهاز بنجاح.',
            'data'    => ['id' => $device->id],
        ]);
    }

    /**
     * DELETE /v1/fcm-tokens/{token}
     * حذف توكن FCM (عند تسجيل الخروج)
     */
    public function destroy(Request $request, string $token): JsonResponse
    {
        $deleted = UserDevice::where('fcm_token', $token)
            ->where('user_id', $request->user()->id)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'status'  => false,
                'message' => 'التوكن غير موجود.',
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف توكن الجهاز.',
        ]);
    }
}
