<?php

namespace Modules\ClubManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Modules\ClubManager\Models\FacilityWorkingHour;
use Modules\ClubManager\Http\Requests\StoreFacilityWorkingHourRequest;
use OpenApi\Attributes as OA;

class FacilityWorkingHourController extends BaseController
{
    #[OA\Get(
        path: '/v1/facilities/{facility}/working-hours',
        summary: '🕒 عرض أوقات عمل المرفق',
        description: 'استرجاع جميع أوقات العمل المسجلة لمرفق معين.',
        tags: ['Facility Working Hours'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'facility', in: 'path', required: true, description: 'معرف المرفق', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع أوقات العمل بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Facility working hours retrieved'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object', properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'day_of_week', type: 'integer', example: 1),
                    new OA\Property(property: 'open_time', type: 'string', example: '08:00:00'),
                    new OA\Property(property: 'close_time', type: 'string', example: '22:00:00')
                ]))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index($facilityId)
    {
        $hours = FacilityWorkingHour::where('facility_id', $facilityId)->get();
        return $this->successResponse($hours, __('Facility working hours retrieved'));
    }

    #[OA\Post(
        path: '/v1/facilities/{facility}/working-hours',
        summary: '➕ إضافة وقت عمل للمرفق',
        description: 'إضافة أو تحديث وقت عمل المرفق ليوم معين في الأسبوع.',
        tags: ['Facility Working Hours'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'facility', in: 'path', required: true, description: 'معرف المرفق', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['day_of_week', 'open_time', 'close_time'],
            properties: [
                new OA\Property(property: 'day_of_week', type: 'integer', description: 'من 0 (الأحد) إلى 6 (السبت)', example: 1),
                new OA\Property(property: 'open_time', type: 'string', format: 'time', example: '08:00:00'),
                new OA\Property(property: 'close_time', type: 'string', format: 'time', example: '22:00:00')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء وقت العمل بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Facility working hours updated'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function store(StoreFacilityWorkingHourRequest $request, $facilityId)
    {
        $validated = $request->validated();

        $validated['facility_id'] = $facilityId;

        $workingHour = FacilityWorkingHour::updateOrCreate(
            ['facility_id' => $facilityId, 'day_of_week' => $validated['day_of_week']],
            $validated
        );

        return $this->successResponse($workingHour, __('Facility working hours updated'), 201);
    }

    #[OA\Delete(
        path: '/v1/facilities/{facility}/working-hours/{working_hour}',
        summary: '🗑️ حذف وقت العمل للمرفق',
        description: 'إزالة وقت عمل مسجل مسبقاً لمرفق.',
        tags: ['Facility Working Hours'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'facility', in: 'path', required: true, description: 'معرف المرفق', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'working_hour', in: 'path', required: true, description: 'معرف وقت العمل', schema: new OA\Schema(type: 'integer', example: 10))]
    #[OA\Response(
        response: 200,
        description: '✅ تم الحذف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Facility working hour deleted'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على وقت العمل', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy($facilityId, $id)
    {
        $workingHour = FacilityWorkingHour::where('facility_id', $facilityId)->findOrFail($id);
        $workingHour->delete();

        return $this->successResponse(null, __('Facility working hour deleted'), 200);
    }
}
