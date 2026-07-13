<?php

namespace Modules\StaffManager\Http\Controllers\Api\V1;

use Modules\StaffManager\Services\StaffService;
use Modules\StaffManager\Repositories\StaffRepositoryInterface;
use Modules\StaffManager\Http\Resources\StaffResource;
use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

use Modules\StaffManager\Http\Requests\StoreStaffRequest;
use Modules\StaffManager\Http\Requests\UpdateStaffRequest;
use Modules\StaffManager\Http\Requests\SetStaffScheduleRequest;
use Modules\StaffManager\Http\Requests\SyncStaffBranchesRequest;

class StaffController extends BaseController
{
    protected $staffService;
    protected $staffRepository;

    public function __construct(StaffService $staffService, StaffRepositoryInterface $staffRepository)
    {
        $this->staffService = $staffService;
        $this->staffRepository = $staffRepository;
    }

    #[OA\Get(
        path: '/v1/staff',
        summary: '👥 عرض جميع الموظفين والمدربين',
        description: 'استرجاع قائمة بجميع الموظفين والمدربين المسجلين في النظام.',
        tags: ['Staff Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الموظفين بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Staff retrieved successfully'),
                new OA\Property(
                    property: 'data', 
                    type: 'array', 
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'username', type: 'string', nullable: true, example: 'staff_1_abcd')
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index(Request $request)
    {
        $staff = $this->staffService->getAllStaff($request->all());
        return $this->successResponse(
            StaffResource::collection($staff)->response()->getData(true),
            __('Staff retrieved successfully')
        );
    }

    #[OA\Post(
        path: '/v1/staff',
        summary: '➕ تسجيل موظف جديد',
        description: 'إضافة موظف جديد إلى النظام.',
        tags: ['Staff Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['first_name', 'last_name', 'phone_number', 'role', 'employment_type', 'branch_ids'],
                properties: [
                    new OA\Property(property: 'first_name', type: 'string', example: 'John'),
                    new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
                    new OA\Property(property: 'country_code', type: 'string', example: '+1'),
                    new OA\Property(property: 'phone_number', type: 'string', example: '234567890'),
                    new OA\Property(property: 'role', type: 'string', enum: ['admin', 'receptionist', 'coach', 'cleaner', 'manager', 'staff'], example: 'receptionist'),
                    new OA\Property(property: 'employment_type', type: 'string', enum: ['fixed_salary', 'commission_based', 'hybrid'], example: 'fixed_salary'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'photo', type: 'string', format: 'binary', description: 'صورة الموظف', nullable: true),
                    new OA\Property(property: 'branch_ids', type: 'array', items: new OA\Items(type: 'integer', example: 1))
                ]
            )
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم التسجيل بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Staff onboarded successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function store(StoreStaffRequest $request)
    {
        $staff = $this->staffService->onboardStaff($request->validated());
        return $this->successResponse(new StaffResource($staff), __('Staff onboarded successfully'), 201);
    }

    #[OA\Post(
        path: '/v1/staff/{id}/schedule',
        summary: '📅 تعيين جدول الموظف',
        description: 'تحديد جدول العمل الأسبوعي للموظف.',
        tags: ['Staff Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الموظف', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['shifts'],
            properties: [
                new OA\Property(property: 'shifts', type: 'array', items: new OA\Items(type: 'integer', example: 1), description: 'مصفوفة معرفات الورديات من جدول branch_shifts')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تحديث الجدول بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Schedule updated successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 الموظف غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function setSchedule(SetStaffScheduleRequest $request, $id)
    {
        $data = $request->validated();

        $staff = $this->staffService->setStaffSchedule($id, $data['shifts']);
        return $this->successResponse(new StaffResource($staff), __('Schedule updated successfully'));
    }


    #[OA\Get(
        path: '/v1/staff/{staff}',
        summary: '🔍 تفاصيل الموظف',
        description: 'استرجاع كافة التفاصيل الخاصة بموظف محدد.',
        tags: ['Staff Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'staff', in: 'path', required: true, description: 'معرف الموظف', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تفاصيل الموظف',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Staff retrieved successfully'),
                new OA\Property(
                    property: 'data', 
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'username', type: 'string', nullable: true, example: 'staff_1_abcd')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 الموظف غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show($id)
    {
        $staff = $this->staffService->getStaffById($id);
        return $this->successResponse(new StaffResource($staff), __('Staff retrieved successfully'));
    }

    #[OA\Put(
        path: '/v1/staff/{staff}',
        summary: '✏️ تحديث بيانات الموظف',
        description: 'تعديل المعلومات الخاصة بموظف مسجل.',
        tags: ['Staff Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'staff', in: 'path', required: true, description: 'معرف الموظف', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['first_name', 'last_name', 'phone_number', 'role', 'employment_type', 'branch_ids'],
                properties: [
                    new OA\Property(property: 'first_name', type: 'string', example: 'John'),
                    new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
                    new OA\Property(property: 'country_code', type: 'string', example: '+1'),
                    new OA\Property(property: 'phone_number', type: 'string', example: '234567890'),
                    new OA\Property(property: 'role', type: 'string', enum: ['admin', 'receptionist', 'coach', 'cleaner', 'manager', 'staff'], example: 'receptionist'),
                    new OA\Property(property: 'employment_type', type: 'string', enum: ['fixed_salary', 'commission_based', 'hybrid'], example: 'fixed_salary'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'photo', type: 'string', format: 'binary', description: 'صورة الموظف', nullable: true),
                    new OA\Property(property: 'branch_ids', type: 'array', items: new OA\Items(type: 'integer', example: 1))
                ]
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تحديث بيانات الموظف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Staff updated successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 الموظف غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(UpdateStaffRequest $request, $id)
    {
        $data = $request->validated();

        $staff = $this->staffService->updateStaff($id, $data);
        return $this->successResponse(new StaffResource($staff), __('Staff updated successfully'));
    }


    #[OA\Patch(
        path: '/v1/staff/{id}/toggle-status',
        summary: '🔄 تبديل حالة الموظف',
        description: 'تفعيل أو تعطيل حساب الموظف.',
        tags: ['Staff Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الموظف', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم تبديل الحالة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Status toggled successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 الموظف غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function toggleStatus($id)
    {
        $staff = $this->staffService->toggleStatus($id);
        return $this->successResponse(new StaffResource($staff), __('Status toggled successfully'));
    }

    #[OA\Post(
        path: '/v1/staff/{id}/sync-branches',
        summary: '🔄 مزامنة الفروع للموظف',
        description: 'ربط الموظف بمجموعة من الفروع.',
        tags: ['Staff Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الموظف', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['branch_ids'],
            properties: [
                new OA\Property(property: 'branch_ids', type: 'array', items: new OA\Items(type: 'integer', example: 1))
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تمت المزامنة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Branches synced successfully'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 الموظف غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function syncBranches(SyncStaffBranchesRequest $request, $id)
    {
        $validated = $request->validated();

        $staff = \Modules\StaffManager\Models\Staff::findOrFail($id);
        
        // Zero code-coupling: Delete existing and insert new instead of relying on Eloquent relationships with foreign modules
        \Modules\StaffManager\Models\StaffBranch::where('staff_id', $staff->id)->delete();
        
        $inserts = array_map(function($branchId) use ($staff) {
            return [
                'staff_id' => $staff->id,
                'branch_id' => $branchId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $validated['branch_ids']);
        
        \Modules\StaffManager\Models\StaffBranch::insert($inserts);

        return $this->successResponse(null, __('Branches synced successfully'));
    }


    #[OA\Delete(
        path: '/v1/staff/{staff}',
        summary: '🗑️ حذف موظف',
        description: 'حذف موظف من النظام. لا يمكن حذفه إذا كان مرتبطاً ببيانات مالية، حضور، أو أنشطة.',
        tags: ['Staff Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'staff', in: 'path', required: true, description: 'معرف الموظف', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم حذف الموظف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Staff deleted successfully'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ]
        )
    )]
    #[OA\Response(response: 409, description: '🚫 لا يمكن الحذف — الموظف مرتبط ببيانات أخرى', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'لا يمكن حذف الموظف.')]))]
    #[OA\Response(response: 404, description: '🚫 الموظف غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy($id)
    {
        $this->staffService->deleteStaff($id);
        return $this->successResponse(null, __('Staff deleted successfully'));
    }
}
