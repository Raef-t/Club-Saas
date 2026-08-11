<?php

namespace Modules\ClubManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\ClubManager\Models\ClubSetting;
use Illuminate\Http\Request;
use Modules\ClubManager\Http\Requests\UpdateClubSettingRequest;
use OpenApi\Attributes as OA;

class ClubSettingController extends BaseController
{
    #[OA\Get(
        path: '/v1/club-settings',
        summary: '⚙️ عرض إعدادات الأندية',
        description: 'استرجاع قائمة بجميع الإعدادات الخاصة بجميع الأندية.',
        tags: ['Club Settings'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الإعدادات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Club settings retrieved'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index()
    {
        return $this->successResponse(ClubSetting::all(), __('Club settings retrieved'));
    }

    #[OA\Get(
        path: '/v1/clubs/{club}/settings',
        summary: '🔍 إعدادات نادي محدد',
        description: 'استرجاع الإعدادات الخاصة بنادي محدد عن طريق معرف النادي.',
        tags: ['Club Settings'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'club', in: 'path', required: true, description: 'معرف النادي', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تفاصيل إعدادات النادي',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Club setting retrieved'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على إعدادات', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show($id)
    {
        $setting = ClubSetting::where('club_id', $id)->firstOrFail();
        return $this->successResponse($setting, __('Club setting retrieved'));
    }

    #[OA\Put(
        path: '/v1/clubs/{club}/settings',
        summary: '📝 تعديل إعدادات النادي',
        description: 'تحديث أو إنشاء الإعدادات الخاصة بنادي محدد.',
        tags: ['Club Settings'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'club', in: 'path', required: true, description: 'معرف النادي', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'theme_colors', type: 'object'),
                new OA\Property(property: 'language', type: 'string', example: 'ar'),
                new OA\Property(property: 'allowed_debt_limit', type: 'number', format: 'float', example: 100.00),
                new OA\Property(property: 'grace_period_days', type: 'integer', example: 5),
                new OA\Property(property: 'allow_partial_payment', type: 'boolean', example: true),
                new OA\Property(property: 'enabled_features', type: 'array', items: new OA\Items(type: 'string'), example: ['attendance', 'subscriptions']),
                new OA\Property(property: 'bg_image_url', type: 'string', example: 'https://example.com/bg.jpg')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تحديث الإعدادات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Club setting updated'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(UpdateClubSettingRequest $request, $id)
    {
        $setting = ClubSetting::firstOrCreate(['club_id' => $id]);
        
        $validated = $request->validated();

        $setting->update($validated);

        return $this->successResponse($setting, __('Club setting updated'));
    }
}
