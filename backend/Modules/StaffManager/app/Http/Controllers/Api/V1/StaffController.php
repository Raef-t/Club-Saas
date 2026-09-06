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
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, description: 'تصفية حسب معرف الفرع', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'role', in: 'query', required: false, description: 'تصفية حسب الدور (اسم أي دور مسجل في النظام مثل reception, accountant, member_manager, admin...)', schema: new OA\Schema(type: 'string', example: 'reception'))]
    #[OA\Parameter(name: 'gender', in: 'query', required: false, description: 'تصفية حسب الجنس', schema: new OA\Schema(type: 'string', enum: ['male', 'female', 'mixed']))]
    #[OA\Parameter(name: 'work_status', in: 'query', required: false, description: 'تصفية حسب حالة العمل (active: نشط، suspended: موقوف، on_leave: إجازة)', schema: new OA\Schema(type: 'string', enum: ['active', 'suspended', 'on_leave']))]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (أو "all" لجلب الكل بدون ترقيم)', schema: new OA\Schema(type: 'string', example: '15'))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
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
                            new OA\Property(property: 'person_id', type: 'integer', example: 10),
                            new OA\Property(property: 'qr_code', type: 'string', nullable: true, example: 'QR-STF-001'),
                            new OA\Property(property: 'role', type: 'string', example: 'reception', description: 'اسم الدور المعين للموظف في النظام'),
                            new OA\Property(property: 'employment_type', type: 'string', enum: ['fixed_salary', 'commission_based', 'hybrid'], example: 'fixed_salary', description: 'نوع التوظيف'),
                            new OA\Property(property: 'base_salary', type: 'number', format: 'float', example: 5000.00, description: 'الراتب الأساسي'),
                            new OA\Property(property: 'work_status', type: 'string', enum: ['active', 'suspended', 'on_leave'], example: 'active', description: 'حالة العمل'),
                            new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'male', nullable: true, description: 'الجنس'),
                            new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-07-16', description: 'تاريخ بداية العمل'),
                            new OA\Property(property: 'end_date', type: 'string', format: 'date', nullable: true, example: null, description: 'تاريخ نهاية العمل'),
                            new OA\Property(property: 'start_time', type: 'string', example: '08:00', nullable: true, description: 'وقت بداية الدوام'),
                            new OA\Property(property: 'end_time', type: 'string', example: '16:00', nullable: true, description: 'وقت نهاية الدوام'),
                            new OA\Property(property: 'reason', type: 'string', nullable: true, example: null),
                            new OA\Property(property: 'username', type: 'string', nullable: true, example: 'staff_john'),
                            new OA\Property(property: 'branch_name', type: 'string', nullable: true, example: 'الفرع الرئيسي'),
                            new OA\Property(
                                property: 'person',
                                type: 'object',
                                nullable: true,
                                properties: [
                                    new OA\Property(property: 'full_name', type: 'string', example: 'John Doe'),
                                    new OA\Property(property: 'country_code', type: 'string', example: '+966'),
                                    new OA\Property(property: 'phone_number', type: 'string', example: '599123456'),
                                    new OA\Property(property: 'email', type: 'string', example: 'john@example.com'),
                                    new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'male'),
                                    new OA\Property(property: 'age', type: 'integer', example: 28),
                                    new OA\Property(property: 'dob', type: 'string', format: 'date', example: '1998-05-15'),
                                    new OA\Property(property: 'national_id', type: 'string', nullable: true, example: '1098765432'),
                                    new OA\Property(property: 'address', type: 'string', nullable: true, example: 'الرياض - حي الرياض'),
                                    new OA\Property(property: 'photo_url', type: 'string', nullable: true, example: 'storage/photos/john.jpg')
                                ]
                            )
                        ]
                    )
                )
            ],
            example: [
                'status' => 'success',
                'message' => 'Staff retrieved successfully',
                'data' => [
                    [
                        'id' => 1,
                        'person_id' => 10,
                        'qr_code' => 'QR-STF-001',
                        'role' => 'reception',
                        'employment_type' => 'fixed_salary',
                        'base_salary' => 5000.00,
                        'work_status' => 'active',
                        'gender' => 'male',
                        'start_date' => '2026-07-16',
                        'end_date' => null,
                        'start_time' => '08:00',
                        'end_time' => '16:00',
                        'username' => 'staff_john',
                        'branch_name' => 'الفرع الرئيسي',
                        'person' => [
                            'full_name' => 'John Doe',
                            'country_code' => '+966',
                            'phone_number' => '599123456',
                            'email' => 'john@example.com',
                            'gender' => 'male',
                            'age' => 28,
                            'dob' => '1998-05-15',
                            'national_id' => '1098765432',
                            'address' => 'الرياض',
                            'photo_url' => 'storage/photos/john.jpg'
                        ]
                    ]
                ]
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
                    new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'male', description: 'الجنس (male: ذكر، female: أنثى)', nullable: true),
                    new OA\Property(property: 'role', type: 'string', example: 'reception', description: 'اسم الدور (Role) المسجل في النظام، يتم جلبه من GET /v1/roles'),
                    new OA\Property(property: 'employment_type', type: 'string', enum: ['fixed_salary', 'commission_based', 'hybrid'], example: 'fixed_salary'),
                    new OA\Property(property: 'base_salary', type: 'number', example: 5000),
                    new OA\Property(property: 'work_status', type: 'string', enum: ['active', 'suspended', 'on_leave'], example: 'active', description: 'حالة العمل (active: نشط، suspended: موقوف، on_leave: إجازة)'),
                    new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-07-16'),
                    new OA\Property(property: 'start_time', type: 'string', example: '08:00', description: 'وقت بداية الدوام (HH:MM)'),
                    new OA\Property(property: 'end_time', type: 'string', example: '16:00', description: 'وقت نهاية الدوام (HH:MM)'),
                    new OA\Property(property: 'address', type: 'string', description: 'العنوان', example: 'شارع الملك فهد، الرياض', nullable: true),
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
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'role', type: 'string', example: 'reception'),
                        new OA\Property(property: 'employment_type', type: 'string', example: 'fixed_salary'),
                        new OA\Property(property: 'base_salary', type: 'number', example: 5000.00),
                        new OA\Property(property: 'work_status', type: 'string', example: 'active'),
                        new OA\Property(property: 'generated_username', type: 'string', example: 'john_rec_2026'),
                        new OA\Property(property: 'generated_password', type: 'string', example: 'P@ssw0rd123'),
                        new OA\Property(
                            property: 'person',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'full_name', type: 'string', example: 'John Doe'),
                                new OA\Property(property: 'phone_number', type: 'string', example: '599123456')
                            ]
                        )
                    ]
                )
            ],
            example: [
                'status' => 'success',
                'message' => 'Staff onboarded successfully',
                'data' => [
                    'id' => 1,
                    'role' => 'reception',
                    'employment_type' => 'fixed_salary',
                    'base_salary' => 5000.00,
                    'work_status' => 'active',
                    'generated_username' => 'john_rec_2026',
                    'generated_password' => 'P@ssw0rd123',
                    'person' => [
                        'full_name' => 'John Doe',
                        'gender' => 'male',
                        'phone_number' => '599123456'
                    ]
                ]
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
            ],
            example: [
                'status' => 'success',
                'message' => 'Schedule updated successfully',
                'data' => [
                    'id' => 1,
                    'shifts' => [
                        ['id' => 1, 'shift_name' => 'الوردية الصباحية', 'start_time' => '08:00', 'end_time' => '16:00']
                    ]
                ]
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
                        new OA\Property(property: 'person_id', type: 'integer', example: 10),
                        new OA\Property(property: 'qr_code', type: 'string', example: 'QR-STF-001'),
                        new OA\Property(property: 'role', type: 'string', example: 'reception'),
                        new OA\Property(property: 'employment_type', type: 'string', example: 'fixed_salary'),
                        new OA\Property(property: 'base_salary', type: 'number', example: 5000.00),
                        new OA\Property(property: 'work_status', type: 'string', example: 'active'),
                        new OA\Property(property: 'start_date', type: 'string', example: '2026-07-16'),
                        new OA\Property(property: 'start_time', type: 'string', example: '08:00'),
                        new OA\Property(property: 'end_time', type: 'string', example: '16:00'),
                        new OA\Property(
                            property: 'person',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'full_name', type: 'string', example: 'John Doe'),
                                new OA\Property(property: 'country_code', type: 'string', example: '+966'),
                                new OA\Property(property: 'phone_number', type: 'string', example: '599123456'),
                                new OA\Property(property: 'email', type: 'string', example: 'john@example.com'),
                                new OA\Property(property: 'gender', type: 'string', example: 'male'),
                                new OA\Property(property: 'age', type: 'integer', example: 28),
                                new OA\Property(property: 'dob', type: 'string', example: '1998-05-15'),
                                new OA\Property(property: 'photo_url', type: 'string', example: 'storage/photos/john.jpg')
                            ]
                        )
                    ]
                )
            ],
            example: [
                'status' => 'success',
                'message' => 'Staff retrieved successfully',
                'data' => [
                    'id' => 1,
                    'person_id' => 10,
                    'qr_code' => 'QR-STF-001',
                    'role' => 'reception',
                    'employment_type' => 'fixed_salary',
                    'base_salary' => 5000.00,
                    'work_status' => 'active',
                    'start_date' => '2026-07-16',
                    'start_time' => '08:00',
                    'end_time' => '16:00',
                    'person' => [
                        'full_name' => 'John Doe',
                        'country_code' => '+966',
                        'phone_number' => '599123456',
                        'email' => 'john@example.com',
                        'gender' => 'male',
                        'age' => 28,
                        'dob' => '1998-05-15',
                        'photo_url' => 'storage/photos/john.jpg'
                    ]
                ]
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
        description: 'تعديل المعلومات الخاصة بموظف مسجل. لتحديث الصورة استخدم endpoint منفصل: POST /v1/staff/{id}/photo',
        tags: ['Staff Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'staff', in: 'path', required: true, description: 'معرف الموظف', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['reason', 'first_name', 'last_name', 'phone_number', 'role', 'employment_type', 'branch_ids'],
            properties: [
                new OA\Property(property: 'reason', type: 'string', description: 'سبب التعديل (حقل إجباري قبل الحفظ)', example: 'تعديل المسمى الوظيفي والراتب'),
                new OA\Property(property: 'first_name', type: 'string', example: 'John'),
                new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
                new OA\Property(property: 'country_code', type: 'string', example: '+1'),
                new OA\Property(property: 'phone_number', type: 'string', example: '234567890'),
                new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'male', description: 'الجنس (male: ذكر، female: أنثى)', nullable: true),
                new OA\Property(property: 'role', type: 'string', example: 'reception', description: 'اسم الدور (Role) المسجل في النظام، يتم جلبه من GET /v1/roles'),
                new OA\Property(property: 'employment_type', type: 'string', enum: ['fixed_salary', 'commission_based', 'hybrid'], example: 'fixed_salary'),
                new OA\Property(property: 'base_salary', type: 'number', example: 5000),
                new OA\Property(property: 'work_status', type: 'string', enum: ['active', 'suspended', 'on_leave'], example: 'active', description: 'حالة العمل (active: نشط، suspended: موقوف، on_leave: إجازة)'),
                new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-07-16'),
                new OA\Property(property: 'start_time', type: 'string', example: '08:00', description: 'وقت بداية الدوام (HH:MM)'),
                new OA\Property(property: 'end_time', type: 'string', example: '16:00', description: 'وقت نهاية الدوام (HH:MM)'),
                new OA\Property(property: 'address', type: 'string', description: 'العنوان', example: 'شارع الملك فهد، الرياض', nullable: true),
                new OA\Property(property: 'branch_ids', type: 'array', items: new OA\Items(type: 'integer', example: 1))
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تحديث بيانات الموظف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Staff updated successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'role', type: 'string', example: 'reception'),
                        new OA\Property(property: 'base_salary', type: 'number', example: 5500.00),
                        new OA\Property(property: 'work_status', type: 'string', example: 'active'),
                        new OA\Property(property: 'reason', type: 'string', example: 'تعديل المسمى الوظيفي والراتب')
                    ]
                )
            ],
            example: [
                'status' => 'success',
                'message' => 'Staff updated successfully',
                'data' => [
                    'id' => 1,
                    'role' => 'reception',
                    'base_salary' => 5500.00,
                    'work_status' => 'active',
                    'reason' => 'تعديل المسمى الوظيفي والراتب'
                ]
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

    #[OA\Post(
        path: '/v1/staff/{id}/photo',
        summary: '🖼️ تحديث صورة الموظف',
        description: 'رفع أو تحديث صورة الموظف. يجب استخدام هذا الـ endpoint المخصص بدلاً من إرسال الصورة ضمن طلب التحديث العام.',
        tags: ['Staff Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الموظف', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['photo'],
                properties: [
                    new OA\Property(property: 'photo', type: 'string', format: 'binary', description: 'صورة الموظف')
                ]
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تحديث الصورة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Staff photo updated successfully'),
                new OA\Property(property: 'data', type: 'object')
            ],
            example: [
                'status' => 'success',
                'message' => 'Staff photo updated successfully',
                'data' => [
                    'id' => 1,
                    'photo_url' => 'storage/photos/staff_1.jpg'
                ]
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 الموظف غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function updatePhoto(\Modules\StaffManager\Http\Requests\UpdateStaffPhotoRequest $request, $id)
    {
        $staff = $this->staffService->updateStaffPhoto($id, $request->file('photo'));
        return $this->successResponse(new StaffResource($staff), __('Staff photo updated successfully'));
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
            ],
            example: [
                'status' => 'success',
                'message' => 'Status toggled successfully',
                'data' => [
                    'id' => 1,
                    'work_status' => 'suspended'
                ]
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
            ],
            example: [
                'status' => 'success',
                'message' => 'Branches synced successfully',
                'data' => null
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

        $inserts = array_map(function ($branchId) use ($staff) {
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
        summary: '🗑️ حذف موظف (Soft Delete)',
        description: 'حذف موظف/مدرب من النظام. يتطلب إرسال كلمة التأكيد "delete".',
        tags: ['Staff Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'staff', in: 'path', required: true, description: 'معرف الموظف', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'confirmation', in: 'query', required: false, description: 'كلمة تأكيد الحذف (delete)', schema: new OA\Schema(type: 'string', example: ''))]
    #[OA\Response(
        response: 200,
        description: '✅ تم حذف الموظف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Staff deleted successfully'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ],
            example: [
                'status' => 'success',
                'message' => 'Staff deleted successfully',
                'data' => null
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ عدم إرسال كلمة التأكيد "delete"', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Deletion confirmation failed.')]))]
    #[OA\Response(response: 404, description: '🚫 الموظف غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy(Request $request, $id)
    {
        $confirmation = $request->input('confirmation', '');
        $this->staffService->deleteStaff((int) $id, (string) $confirmation);
        return $this->successResponse(null, __('Staff deleted successfully'));
    }

    #[OA\Get(
        path: '/v1/staff/trashed',
        summary: '🗑️ عرض الموظفين المحذوفين (سلة المهملات)',
        description: 'جلب قائمة بالموظفين والمدربين المحذوفين.',
        tags: ['Staff Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (أو "all" لجلب الكل بدون ترقيم)', schema: new OA\Schema(type: 'string', example: '15'))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم جلب الموظفين المحذوفين بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Trashed staff retrieved successfully'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
            ],
            example: [
                'status' => 'success',
                'message' => 'Trashed staff retrieved successfully',
                'data' => [
                    [
                        'id' => 5,
                        'username' => 'deleted_staff',
                        'role' => 'reception',
                        'deleted_at' => '2026-08-01 12:00:00'
                    ]
                ]
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function trashed(Request $request)
    {
        $staff = $this->staffService->getTrashedStaff($request->all());
        return $this->successResponse(
            StaffResource::collection($staff)->response()->getData(true),
            __('Trashed staff retrieved successfully')
        );
    }

    #[OA\Post(
        path: '/v1/staff/{id}/restore',
        summary: '♻️ استرجاع موظف محذوف',
        description: 'استرجاع موظف/مدرب من سلة المهملات وإعادة تفعيل حسابه.',
        tags: ['Staff Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الموظف', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الموظف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Staff restored successfully'),
                new OA\Property(property: 'data', type: 'object')
            ],
            example: [
                'status' => 'success',
                'message' => 'Staff restored successfully',
                'data' => [
                    'id' => 5,
                    'username' => 'restored_staff',
                    'work_status' => 'active'
                ]
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 الموظف غير موجود في المهملات', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function restore($id)
    {
        $staff = $this->staffService->restoreStaff((int) $id);
        return $this->successResponse(new StaffResource($staff), __('Staff restored successfully'));
    }
}
