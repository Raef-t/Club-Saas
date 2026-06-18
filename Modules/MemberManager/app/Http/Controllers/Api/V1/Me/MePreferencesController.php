<?php

namespace Modules\MemberManager\Http\Controllers\Api\V1\Me;

use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\MemberManager\Http\Requests\Api\V1\Me\UpdatePreferencesRequest;
use OpenApi\Attributes as OA;

class MePreferencesController extends BaseController
{
    /**
     * Get current preferences.
     */
    #[OA\Get(
        path: '/v1/me/preferences',
        summary: '⚙️ عرض التفضيلات الشخصية',
        description: 'استرجاع التفضيلات الحالية للمستخدم (مثل: لغة العرض، والإشعارات).',
        tags: ['Member App'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع التفضيلات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Preferences retrieved successfully'),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'language', type: 'string', example: 'ar'),
                    new OA\Property(property: 'notifications_enabled', type: 'boolean', example: true)
                ])
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show(Request $request)
    {
        $user = $request->user();
        $profile = $this->resolvePlayerProfile($user);

        return $this->successResponse([
            'language' => $profile->preferences['language'] ?? 'ar',
            'notifications_enabled' => $profile->preferences['notifications_enabled'] ?? true,
        ], __('Preferences retrieved successfully'));
    }

    /**
     * Update member preferences (language, notifications).
     */
    #[OA\Put(
        path: '/v1/me/preferences',
        summary: '✏️ تحديث التفضيلات',
        description: 'تعديل تفضيلات المستخدم الخاصة بالتطبيق.',
        tags: ['Member App'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'language', type: 'string', example: 'en'),
                new OA\Property(property: 'notifications_enabled', type: 'boolean', example: false)
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم التحديث بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Preferences updated successfully'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ]
        )
    )]
    #[OA\Response(response: 403, description: '🚫 الملف الشخصي غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Member profile not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(UpdatePreferencesRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();
        $personId = $this->resolvePersonId($user);

        if (!$personId) {
            return $this->errorResponse(__('Member profile not found.'), 403);
        }

        $profile = DB::table('player_profiles')
            ->where('person_id', $personId)
            ->first();

        $existingPreferences = $profile ? json_decode($profile->preferences ?? '{}', true) : [];
        $mergedPreferences = array_merge($existingPreferences, $validated);

        if ($profile) {
            DB::table('player_profiles')
                ->where('person_id', $personId)
                ->update([
                    'preferences' => json_encode($mergedPreferences),
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('player_profiles')->insert([
                'person_id' => $personId,
                'preferences' => json_encode($mergedPreferences),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $this->successResponse(null, __('Preferences updated successfully'));
    }

    /**
     * Resolve person_id from the authenticated user.
     */
    protected function resolvePersonId($user): ?int
    {
        if ($user instanceof \Modules\MemberManager\Models\Member) {
            return $user->person_id;
        }

        return $user->person_id ?? null;
    }

    /**
     * Resolve the player_profiles record.
     */
    protected function resolvePlayerProfile($user): object
    {
        $personId = $this->resolvePersonId($user);

        if ($personId) {
            $profile = DB::table('player_profiles')
                ->where('person_id', $personId)
                ->first();

            if ($profile) {
                $profile->preferences = json_decode($profile->preferences ?? '{}', true);
                return $profile;
            }
        }

        return (object) ['preferences' => []];
    }
}
