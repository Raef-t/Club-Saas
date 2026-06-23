<?php

namespace Modules\StaffManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Modules\StaffManager\Models\StaffWorkingHour;
use OpenApi\Attributes as OA;

class StaffWorkingHourController extends BaseController
{
    #[OA\Get(
        path: '/v1/staff/{staff}/working-hours',
        summary: '🕒 عرض أوقات عمل الموظف',
        description: 'استرجاع جميع أوقات العمل المسجلة لموظف معين.',
        tags: ['Staff Working Hours'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'staff', in: 'path', required: true, description: 'معرف الموظف', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع أوقات العمل بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Staff working hours retrieved'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
            ]
        )
    )]
    public function index($staffId)
    {
        $hours = StaffWorkingHour::where('staff_id', $staffId)->get();
        return $this->successResponse($hours, __('Staff working hours retrieved'));
    }

    #[OA\Post(
        path: '/v1/staff/{staff}/working-hours',
        summary: '➕ إضافة وقت عمل للموظف',
        description: 'إضافة أو تحديث وقت عمل الموظف ليوم معين في الأسبوع.',
        tags: ['Staff Working Hours'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'staff', in: 'path', required: true, description: 'معرف الموظف', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['day_of_week', 'start_time', 'end_time'],
            properties: [
                new OA\Property(property: 'day_of_week', type: 'integer', description: 'من 0 (الأحد) إلى 6 (السبت)', example: 1),
                new OA\Property(property: 'start_time', type: 'string', format: 'time', example: '08:00:00'),
                new OA\Property(property: 'end_time', type: 'string', format: 'time', example: '16:00:00')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء وقت العمل بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Staff working hours updated'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات')]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
    public function store(Request $request, $staffId)
    {
        $validated = $request->validate([
            'day_of_week' => 'required|integer|between:0,6',
            'start_time' => 'required|date_format:H:i|before:end_time',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $workingHour = StaffWorkingHour::updateOrCreate(
            ['staff_id' => $staffId, 'day_of_week' => $validated['day_of_week']],
            $validated
        );

        return $this->successResponse($workingHour, __('Staff working hours updated'), 201);
    }

    #[OA\Delete(
        path: '/v1/staff/{staff}/working-hours/{working_hour}',
        summary: '🗑️ حذف وقت العمل للموظف',
        description: 'إزالة وقت عمل مسجل مسبقاً للموظف.',
        tags: ['Staff Working Hours'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم الحذف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Staff working hour deleted'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على وقت العمل')]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
    public function destroy($staffId, $id)
    {
        $workingHour = StaffWorkingHour::where('staff_id', $staffId)->findOrFail($id);
        $workingHour->delete();

        return $this->successResponse(null, __('Staff working hour deleted'), 200);
    }
}
