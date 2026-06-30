<?php

namespace Modules\StaffManager\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StaffManager\Models\StaffUnavailability;
use Modules\StaffManager\Models\Staff;
use OpenApi\Attributes as OA;

class StaffUnavailabilityController extends Controller
{
    #[OA\Get(
        path: '/v1/staff/{staff}/unavailabilities',
        summary: '📅 عرض أوقات عدم توفر الموظف',
        description: 'استرجاع جميع فترات غياب وعدم توفر الموظف للعمل.',
        tags: ['Staff Unavailability'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'staff', in: 'path', required: true, description: 'معرف الموظف', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الفترات بنجاح',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'object'))
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index($staffId)
    {
        $unavailabilities = StaffUnavailability::where('staff_id', $staffId)->get();
        return response()->json($unavailabilities);
    }

    #[OA\Post(
        path: '/v1/staff/{staff}/unavailabilities',
        summary: '➕ إضافة فترة عدم توفر',
        description: 'تسجيل فترة زمنية يكون فيها الموظف غير متاح للعمل.',
        tags: ['Staff Unavailability'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'staff', in: 'path', required: true, description: 'معرف الموظف', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['start_datetime', 'end_datetime'],
            properties: [
                new OA\Property(property: 'start_datetime', type: 'string', format: 'date-time', example: '2023-11-01 09:00:00'),
                new OA\Property(property: 'end_datetime', type: 'string', format: 'date-time', example: '2023-11-01 17:00:00'),
                new OA\Property(property: 'reason', type: 'string', example: 'إجازة مرضية')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إضافة الفترة بنجاح',
        content: new OA\JsonContent(type: 'object')
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function store(Request $request, $staffId)
    {
        $validated = $request->validate([
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
            'reason' => 'nullable|string',
        ]);

        $validated['staff_id'] = $staffId;
        $unavailability = StaffUnavailability::create($validated);

        return response()->json($unavailability, 201);
    }

    #[OA\Delete(
        path: '/v1/staff/{staff}/unavailabilities/{unavailability}',
        summary: '🗑️ حذف فترة عدم التوفر',
        description: 'إزالة فترة مسجلة لعدم توفر الموظف.',
        tags: ['Staff Unavailability'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'staff', in: 'path', required: true, description: 'معرف الموظف', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'unavailability', in: 'path', required: true, description: 'معرف الفترة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 204,
        description: '✅ تم الحذف بنجاح'
    )]
    #[OA\Response(response: 404, description: '🚫 الفترة غير موجودة', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy($staffId, $id)
    {
        $unavailability = StaffUnavailability::where('staff_id', $staffId)->findOrFail($id);
        $unavailability->delete();

        return response()->json(null, 204);
    }
}
