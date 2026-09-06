<?php

namespace Modules\StaffManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\StaffManager\Services\StaffShiftService;
use Modules\StaffManager\Http\Requests\StoreStaffShiftRequest;
use Modules\StaffManager\Http\Requests\UpdateStaffShiftRequest;
use Modules\StaffManager\Http\Resources\StaffShiftResource;
use OpenApi\Attributes as OA;

class StaffShiftController extends BaseController
{
    protected $service;

    public function __construct(StaffShiftService $service)
    {
        $this->service = $service;
    }

    #[OA\Get(
        path: '/v1/staff-shifts',
        summary: '🕒 عرض مناوبات الموظفين',
        description: 'استرجاع قائمة بجميع مناوبات الموظفين المخصصة في الفروع مع التصفية والترقيم الصفحات.',
        tags: ['Staff Shifts'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'branch_id',
        in: 'query',
        required: false,
        description: 'تصفية المناوبات حسب معرف الفرع',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Parameter(
        name: 'staff_id',
        in: 'query',
        required: false,
        description: 'تصفية المناوبات حسب معرف الموظف',
        schema: new OA\Schema(type: 'integer', example: 5)
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        required: false,
        description: 'عدد العناصر في كل صفحة (أو اكتب "all" لجلب كل السجلات بدون ترقيم صفحات)',
        schema: new OA\Schema(type: 'string', example: '15')
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        required: false,
        description: 'رقم الصفحة المستهدفة',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع قائمة المناوبات بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Retrieved successfully',
                'data' => [
                    [
                        'id' => 1,
                        'staff_id' => 5,
                        'branch_shift_id' => 2,
                        'branch_shift' => [
                            'id' => 2,
                            'name' => 'الوردية الصباحية - Morning Shift',
                            'start_time' => '08:00:00',
                            'end_time' => '16:00:00'
                        ]
                    ]
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح - رمز المرور مفقود أو غير صالح',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ]
        )
    )]
    public function index(\Illuminate\Http\Request $request)
    {
        return $this->successResponse(StaffShiftResource::collection($this->service->getAll($request->all())), 'Retrieved successfully');
    }

    #[OA\Post(
        path: '/v1/staff-shifts',
        summary: '➕ إضافة مناوبة لموظف',
        description: 'تخصيص وتسجيل مناوبة عمل جديدة لموظف محدد.',
        tags: ['Staff Shifts'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        description: 'بيانات تخصيص المناوبة للموظف',
        content: new OA\JsonContent(
            required: ['staff_id', 'branch_shift_id'],
            properties: [
                new OA\Property(property: 'staff_id', type: 'integer', example: 5, description: 'معرف الموظف (مطلوب وموجود بجدول الموظفين)'),
                new OA\Property(property: 'branch_shift_id', type: 'integer', example: 2, description: 'معرف مناوبة الفرع (مطلوب وموجود بجدول branch_shifts)')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء مناوبة الموظف بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Created successfully',
                'data' => [
                    'id' => 1,
                    'staff_id' => 5,
                    'branch_shift_id' => 2,
                    'branch_shift' => [
                        'id' => 2,
                        'name' => 'الوردية الصباحية - Morning Shift',
                        'start_time' => '08:00:00',
                        'end_time' => '16:00:00'
                    ]
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: '⚠️ خطأ في التحقق من البيانات (مثال: عدم وجود الموظف أو المناوبة)',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'The given data was invalid.',
                'errors' => [
                    'staff_id' => ['The selected staff id is invalid.'],
                    'branch_shift_id' => ['The selected branch shift id is invalid.']
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ]
        )
    )]
    public function store(StoreStaffShiftRequest $request)
    {
        $record = $this->service->create($request->validated());
        return $this->successResponse(new StaffShiftResource($record), 'Created successfully', 201);
    }

    #[OA\Get(
        path: '/v1/staff-shifts/{staff_shift}',
        summary: '🔍 تفاصيل مناوبة الموظف',
        description: 'استرجاع تفاصيل مناوبة عمل مخصصة لموظف بواسطة المعرف الرقمي لمناوبة الموظف.',
        tags: ['Staff Shifts'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'staff_shift',
        in: 'path',
        required: true,
        description: 'المعرف الرقمي لمناوبة الموظف',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع تفاصيل المناوبة بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Retrieved successfully',
                'data' => [
                    'id' => 1,
                    'staff_id' => 5,
                    'branch_shift_id' => 2,
                    'branch_shift' => [
                        'id' => 2,
                        'name' => 'الوردية الصباحية - Morning Shift',
                        'start_time' => '08:00:00',
                        'end_time' => '16:00:00'
                    ]
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: '🚫 لم يتم العثور على المناوبة',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Record not found.'
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ]
        )
    )]
    public function show($id)
    {
        return $this->successResponse(new StaffShiftResource($this->service->getById($id)), 'Retrieved successfully');
    }

    #[OA\Put(
        path: '/v1/staff-shifts/{staff_shift}',
        summary: '📝 تعديل مناوبة الموظف',
        description: 'تحديث وتعديل مناوبة العمل المخصصة لموظف.',
        tags: ['Staff Shifts'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'staff_shift',
        in: 'path',
        required: true,
        description: 'المعرف الرقمي لمناوبة الموظف المراد تعديلها',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        description: 'بيانات التحديث لمناوبة الموظف',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'staff_id', type: 'integer', example: 5, description: 'معرف الموظف (اختياري)'),
                new OA\Property(property: 'branch_shift_id', type: 'integer', example: 3, description: 'معرف مناوبة الفرع الجديدة (اختياري)')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تعديل المناوبة بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Updated successfully',
                'data' => [
                    'id' => 1,
                    'staff_id' => 5,
                    'branch_shift_id' => 3,
                    'branch_shift' => [
                        'id' => 3,
                        'name' => 'الوردية المسائية - Evening Shift',
                        'start_time' => '16:00:00',
                        'end_time' => '00:00:00'
                    ]
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: '🚫 لم يتم العثور على المناوبة',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Record not found.'
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: '⚠️ خطأ في التحقق من البيانات',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'The given data was invalid.',
                'errors' => [
                    'branch_shift_id' => ['The selected branch shift id is invalid.']
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ]
        )
    )]
    public function update(UpdateStaffShiftRequest $request, $id)
    {
        $record = $this->service->update($id, $request->validated());
        return $this->successResponse(new StaffShiftResource($record), 'Updated successfully');
    }

    #[OA\Delete(
        path: '/v1/staff-shifts/{staff_shift}',
        summary: '🗑️ حذف مناوبة موظف',
        description: 'إلغاء وحذف مناوبة عمل مسجلة لموظف.',
        tags: ['Staff Shifts'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'staff_shift',
        in: 'path',
        required: true,
        description: 'المعرف الرقمي لمناوبة الموظف المراد حذفها',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم حذف المناوبة بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Deleted successfully',
                'data' => null
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: '🚫 لم يتم العثور على المناوبة',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Record not found.'
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ]
        )
    )]
    public function destroy($id)
    {
        $this->service->delete($id);
        return $this->successResponse(null, 'Deleted successfully');
    }
}
