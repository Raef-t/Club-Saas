<?php

namespace Modules\ClubManager\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Modules\ClubManager\Http\Requests\StoreLockerRequest;
use Modules\ClubManager\Http\Requests\UpdateLockerRequest;
use Modules\ClubManager\Http\Requests\ReserveLockerRequest;
use Modules\ClubManager\Http\Requests\TransferLockerReservationRequest;
use Modules\ClubManager\Http\Resources\LockerResource;
use Modules\ClubManager\Services\LockerService;
use Modules\Core\Http\Controllers\Api\BaseController;
use OpenApi\Attributes as OA;
use Exception;

class LockerController extends BaseController
{
    protected $lockerService;

    public function __construct(LockerService $lockerService)
    {
        $this->lockerService = $lockerService;
    }

    #[OA\Get(
        path: '/v1/lockers',
        summary: '🔐 عرض جميع الخزائن',
        description: 'استرجاع قائمة بجميع الخزائن مع إمكانية الفلترة حسب الفرع.',
        tags: ['Locker Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, description: 'تصفية الخزائن حسب الفرع', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(
        name: 'status',
        in: 'query',
        required: false,
        description: "تصفية الخزائن حسب الحالة. الخيارات المتاحة:\n" .
                     "• `available`: الخزائن المتاحة فقط\n" .
                     "• `with_member`: الخزائن المسندة للاعبين / المشتركين فقط\n" .
                     "• `with_staff_or_coach`: الخزائن المسندة للموظفين أو المدربين معاً (كوتش + موظف)\n" .
                     "• `with_coach`: الخزائن المسندة للمدربين فقط\n" .
                     "• `with_staff`: الخزائن المسندة للموظفين فقط\n" .
                     "• `maintenance`: الخزائن المعطلة أو التي في الصيانة\n" .
                     "• `occupied`: جميع الخزائن غير المتاحة (مشغولة عموماً)\n" .
                     "💡 ملاحظة: يمكن أيضاً تمرير أكثر من حالة مفصولة بفاصلة مثل: `with_member,with_coach`",
        schema: new OA\Schema(
            type: 'string',
            enum: [
                'available',
                'with_member',
                'with_staff_or_coach',
                'with_coach',
                'with_staff',
                'maintenance',
                'occupied'
            ],
            example: 'with_staff_or_coach'
        )
    )]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (أو "all" لجلب الكل بدون ترقيم)', schema: new OA\Schema(type: 'string', example: '15'))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الخزائن بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Lockers retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'summary',
                            type: 'object',
                            description: 'ملخص إحصائيات الخزائن',
                            properties: [
                                new OA\Property(property: 'available_lockers_count', type: 'integer', example: 12, description: 'عدد الخزائن المتاحة'),
                                new OA\Property(property: 'unavailable_lockers_count', type: 'integer', example: 8, description: 'إجمالي الخزائن غير المتاحة / المشغولة'),
                                new OA\Property(property: 'assigned_to_member_count', type: 'integer', example: 5, description: 'الخزائن المسندة للاعبين'),
                                new OA\Property(property: 'assigned_to_coach_count', type: 'integer', example: 2, description: 'الخزائن المسندة للمدربين'),
                                new OA\Property(property: 'assigned_to_staff_count', type: 'integer', example: 1, description: 'الخزائن المسندة للموظفين'),
                                new OA\Property(property: 'assigned_to_staff_or_coach_count', type: 'integer', example: 3, description: 'إجمالي الخزائن المسندة لموظفين أو مدربين'),
                                new OA\Property(property: 'maintenance_lockers_count', type: 'integer', example: 1, description: 'الخزائن المعطلة أو التي في الصيانة'),
                                new OA\Property(property: 'rented_lockers_count', type: 'integer', example: 4, description: 'الخزائن المستأجرة بمقابل مادي'),
                            ]
                        ),
                        new OA\Property(
                            property: 'lockers',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                                    new OA\Property(property: 'locker_number', type: 'string', example: 'L-101'),
                                    new OA\Property(property: 'key_number', type: 'string', nullable: true, example: 'K-101'),
                                    new OA\Property(property: 'status', type: 'string', enum: ['available', 'with_member', 'with_staff', 'with_coach', 'maintenance'], example: 'with_member'),
                                    new OA\Property(property: 'reason', type: 'string', nullable: true, example: null),
                                    new OA\Property(property: 'holder_id', type: 'integer', nullable: true, example: 120),
                                    new OA\Property(property: 'holder_type', type: 'string', nullable: true, enum: ['member', 'staff', 'coach'], example: 'member'),
                                    new OA\Property(property: 'holder_name', type: 'string', nullable: true, example: 'أحمد علي'),
                                    new OA\Property(property: 'assigned_at', type: 'string', format: 'date-time', nullable: true, example: '2026-07-13T08:00:00+03:00'),
                                    new OA\Property(property: 'contact_person', type: 'array', items: new OA\Items(type: 'object')),
                                    new OA\Property(property: 'created_at', type: 'string', example: '2026-07-01 10:00:00')
                                ]
                            )
                        )
                    ]
                )
            ],
            example: [
                'status' => 'success',
                'message' => 'Lockers retrieved successfully',
                'data' => [
                    'summary' => [
                        'available_lockers_count' => 12,
                        'unavailable_lockers_count' => 8,
                        'assigned_to_member_count' => 5,
                        'assigned_to_coach_count' => 2,
                        'assigned_to_staff_count' => 1,
                        'assigned_to_staff_or_coach_count' => 3,
                        'maintenance_lockers_count' => 1,
                        'rented_lockers_count' => 4
                    ],
                    'lockers' => [
                        [
                            'id' => 1,
                            'branch_id' => 1,
                            'locker_number' => 'L-101',
                            'key_number' => 'K-101',
                            'status' => 'with_member',
                            'reason' => null,
                            'holder_id' => 120,
                            'holder_type' => 'member',
                            'holder_name' => 'أحمد علي',
                            'assigned_at' => '2026-07-13T08:00:00+03:00',
                            'contact_person' => [],
                            'created_at' => '2026-07-01 10:00:00'
                        ]
                    ]
                ]
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index(Request $request)
    {
        $filters = $request->all();
        $branchId = !empty($filters['branch_id']) ? (int) $filters['branch_id'] : null;

        $lockers = $this->lockerService->getAllLockers($filters);
        $summary = $this->lockerService->getLockersSummary($branchId);

        // If paginated, return pagination meta at root level alongside data
        if ($lockers instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
            $collection = LockerResource::collection($lockers);
            return response()->json([
                'status'  => 'success',
                'message' => __('Lockers retrieved successfully'),
                'data'    => [
                    'summary' => $summary,
                    'lockers' => $collection->resolve(),
                ],
                'meta'    => [
                    'current_page' => $lockers->currentPage(),
                    'last_page'    => $lockers->lastPage(),
                    'per_page'     => $lockers->perPage(),
                    'total'        => $lockers->total(),
                    'from'         => $lockers->firstItem(),
                    'to'           => $lockers->lastItem(),
                ],
            ]);
        }

        // Non-paginated (per_page=all)
        return $this->successResponse([
            'summary' => $summary,
            'lockers' => LockerResource::collection($lockers),
        ], __('Lockers retrieved successfully'));
    }

    #[OA\Post(
        path: '/v1/lockers',
        summary: '➕ إضافة خزانة جديدة',
        description: "إنشاء خزانة جديدة في فرع معين وتعيين رقمها وحالتها الأولية.\n\n" .
                     "**الحالات المتاحة للخزانة (`status`):**\n" .
                     "- `available`: متاحة وفارغة (الافتراضية).\n" .
                     "- `maintenance`: معطلة أو قيد الصيانة (خارج الخدمة).\n" .
                     "- `with_member`: مسندة للاعب.\n" .
                     "- `with_coach`: مسندة لمدرب.\n" .
                     "- `with_staff`: مسندة لموظف.",
        tags: ['Locker Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/StoreLockerRequest')
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إضافة الخزانة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Locker created successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                        new OA\Property(property: 'locker_number', type: 'string', example: 'L-101'),
                        new OA\Property(property: 'key_number', type: 'string', example: 'K-101'),
                        new OA\Property(property: 'status', type: 'string', example: 'available')
                    ]
                )
            ],
            example: [
                'status' => 'success',
                'message' => 'Locker created successfully',
                'data' => [
                    'id' => 1,
                    'branch_id' => 1,
                    'locker_number' => 'L-101',
                    'key_number' => 'K-101',
                    'status' => 'available'
                ]
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات (مثل تكرار رقم الخزانة في الفرع)', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function store(StoreLockerRequest $request)
    {
        $locker = $this->lockerService->createLocker($request->validated());
        return $this->successResponse(new LockerResource($locker), __('Locker created successfully'), 201);
    }

    #[OA\Get(
        path: '/v1/lockers/{id}',
        summary: '🔍 عرض خزانة محددة',
        description: 'استرجاع تفاصيل خزانة محددة بواسطة معرفها.',
        tags: ['Locker Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الخزانة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الخزانة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Locker retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                        new OA\Property(property: 'locker_number', type: 'string', example: 'L-101'),
                        new OA\Property(property: 'key_number', type: 'string', example: 'K-101'),
                        new OA\Property(property: 'status', type: 'string', example: 'available')
                    ]
                )
            ],
            example: [
                'status' => 'success',
                'message' => 'Locker retrieved successfully',
                'data' => [
                    'id' => 1,
                    'branch_id' => 1,
                    'locker_number' => 'L-101',
                    'key_number' => 'K-101',
                    'status' => 'available'
                ]
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الخزانة', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show($id)
    {
        $locker = $this->lockerService->getLockerById($id);
        return $this->successResponse(new LockerResource($locker), __('Locker retrieved successfully'));
    }

    #[OA\Put(
        path: '/v1/lockers/{id}',
        summary: '✏️ تعديل بيانات خزانة',
        description: 'تحديث بيانات خزانة موجودة مثل رقمها أو حالتها أو حامل مفتاحها.',
        tags: ['Locker Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الخزانة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/UpdateLockerRequest')
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تحديث الخزانة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Locker updated successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'locker_number', type: 'string', example: 'L-101-A'),
                        new OA\Property(property: 'status', type: 'string', example: 'maintenance')
                    ]
                )
            ],
            example: [
                'status' => 'success',
                'message' => 'Locker updated successfully',
                'data' => [
                    'id' => 1,
                    'locker_number' => 'L-101-A',
                    'status' => 'maintenance'
                ]
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الخزانة', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(UpdateLockerRequest $request, $id)
    {
        $locker = $this->lockerService->updateLocker($id, $request->validated());
        return $this->successResponse(new LockerResource($locker), __('Locker updated successfully'));
    }

    #[OA\Delete(
        path: '/v1/lockers/{id}',
        summary: '🗑️ حذف خزانة (Soft Delete)',
        description: 'إزالة خزانة محددة ناعماً من النظام مع كافّة حجوزاتها التابعة حتى لو كانت محجوزة أو مسندة. يتطلب إرسال كلمة التأكيد "delete" للموافقة على الحذف.',
        tags: ['Locker Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الخزانة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'confirmation', in: 'query', required: false, description: 'كلمة تأكيد الحذف (delete)', schema: new OA\Schema(type: 'string', example: ''))]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'confirmation', type: 'string', description: 'تأكيد الحذف (delete)', example: 'delete')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم حذف الخزانة بنجاح ناعماً',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Locker deleted successfully'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ],
            example: [
                'status' => 'success',
                'message' => 'Locker deleted successfully',
                'data' => null
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الخزانة', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ عدم إرسال كلمة التأكيد "delete"', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Deletion confirmation failed.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy(Request $request, $id)
    {
        $confirmation = $request->input('confirm') ?? $request->input('confirmation') ?? $request->input('confirm_text') ?? '';
        $this->lockerService->deleteLocker((int) $id, (string) $confirmation);
        return $this->successResponse(null, __('Locker deleted successfully'));
    }

    #[OA\Get(
        path: '/v1/lockers/trashed',
        summary: '🗑️ عرض الخزائن المحذوفة (سلة المهملات)',
        description: 'استرجاع قائمة بجميع الخزائن المحذوفة ناعماً مع إمكانية الفلترة حسب الفرع أو الترقيم.',
        tags: ['Locker Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, description: 'تصفية حسب الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'status', in: 'query', required: false, description: 'تصفية حسب الحالة', schema: new OA\Schema(type: 'string', example: 'available'))]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (أو "all" لجلب الكل بدون ترقيم)', schema: new OA\Schema(type: 'string', example: '15'))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الخزائن المحذوفة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Trashed lockers retrieved successfully'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
            ],
            example: [
                'status' => 'success',
                'message' => 'Trashed lockers retrieved successfully',
                'data' => [
                    [
                        'id' => 3,
                        'locker_number' => 'L-103',
                        'deleted_at' => '2026-08-10 14:00:00'
                    ]
                ]
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function trashed(Request $request)
    {
        $lockers = $this->lockerService->getTrashed($request->all());

        if ($lockers instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
            $collection = LockerResource::collection($lockers);
            return response()->json([
                'status'  => 'success',
                'message' => __('Trashed lockers retrieved successfully'),
                'data'    => $collection->resolve(),
                'meta'    => [
                    'current_page' => $lockers->currentPage(),
                    'last_page'    => $lockers->lastPage(),
                    'per_page'     => $lockers->perPage(),
                    'total'        => $lockers->total(),
                    'from'         => $lockers->firstItem(),
                    'to'           => $lockers->lastItem(),
                ],
            ]);
        }

        return $this->successResponse(LockerResource::collection($lockers), __('Trashed lockers retrieved successfully'));
    }

    #[OA\Post(
        path: '/v1/lockers/{id}/restore',
        summary: '♻️ استرجاع خزانة محذوفة',
        description: 'استرجاع الخزانة المحذوفة ناعماً وكافّة حجوزاتها التابعة تلقائياً.',
        tags: ['Locker Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الخزانة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الخزانة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Locker restored successfully'),
                new OA\Property(property: 'data', type: 'object')
            ],
            example: [
                'status' => 'success',
                'message' => 'Locker restored successfully',
                'data' => [
                    'id' => 3,
                    'locker_number' => 'L-103',
                    'status' => 'available'
                ]
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 الخزانة غير موجودة في سلة المحذوفات', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ تكرار رقم الخزانة في الفرع', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Locker number already exists in this branch.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function restore($id)
    {
        try {
            $locker = $this->lockerService->restoreLocker((int) $id);
            return $this->successResponse(new LockerResource($locker), __('Locker restored successfully'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }


    #[OA\Post(
        path: '/v1/lockers/{locker}/reservations',
        summary: '📥 حجز خزانة / إسناد مجاني',
        description: 'استخدم `reservation_type: rental` للحجز الشهري المدفوع، أو `assign` للتخصيص اليومي أو للموظفين. سيتم تحديد حالة الخزانة بناءً على `holder_type` (والذي يمكن أن يكون `member`، `staff`، أو `coach`).',
        tags: ['Locker Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'locker', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            examples: [
                'rental_example' => new OA\Examples(
                    example: 'rental',
                    summary: 'حالة التأجير (Rental)',
                    description: 'تتطلب تحديد السعر وتاريخ البداية والنهاية، ويجب أن يكون المستلم عضواً (member).',
                    value: [
                        'reservation_type' => 'rental',
                        'holder_type' => 'member',
                        'holder_id' => 120,
                        'price' => 30000,
                        'start_date' => '2026-07-13',
                        'end_date' => '2026-08-13'
                    ]
                ),
                'assign_example' => new OA\Examples(
                    example: 'assign',
                    summary: 'حالة التخصيص (Assign)',
                    description: 'تخصيص الخزانة بدون مقابل مادي. لا تتطلب سعر أو تواريخ، ويمكن إسنادها لأي نوع.',
                    value: [
                        'reservation_type' => 'assign',
                        'holder_type' => 'staff',
                        'holder_id' => 5,
                        'holder_name' => 'أحمد الموظف'
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تخصيص الخزانة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Locker reserved successfully.'),
                new OA\Property(property: 'data', type: 'object')
            ],
            example: [
                'status' => 'success',
                'message' => 'Locker reserved successfully.',
                'data' => [
                    'id' => 10,
                    'locker_id' => 1,
                    'reservation_type' => 'rental',
                    'holder_type' => 'member',
                    'holder_id' => 120,
                    'price' => 300.00,
                    'start_date' => '2026-07-13',
                    'end_date' => '2026-08-13',
                    'status' => 'active'
                ]
            ]
        )
    )]
    #[OA\Response(response: 400, description: '❌ الخزانة غير متاحة أو بيانات غير صحيحة', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Locker is already occupied.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function reserve(int $locker, ReserveLockerRequest $request)
    {
        try {
            $reservation = $this->lockerService->reserveLocker($locker, $request->validated());
            return $this->successResponse($reservation, __('Locker reserved successfully.'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Delete(
        path: '/v1/lockers/{locker}/reservations/current',
        summary: '🔓 فك الحجز وإخلاء الخزانة',
        description: 'ينهي الحجز النشط حالياً على الخزانة ويحول حالة الخزانة إلى متاحة (available). إذا كان للحجز تاريخ نهاية مستقبلي ولم ينتهِ بعد، يكون حقل reason إجبارياً لتوضيح سبب الفك المبكر.',
        tags: ['Locker Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'locker', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'reason', type: 'string', description: 'سبب فك الحجز (إجباري إذا لم ينتهِ تاريخ نهاية الحجز بعد)', example: 'طلب المشترك إنهاء الحجز واستعادة الأمانة'),
                new OA\Property(property: 'is_refund', type: 'boolean', description: 'هل تم طلب استرجاع المبلغ للمشترك؟', example: true),
                new OA\Property(property: 'refund_amount', type: 'number', format: 'float', nullable: true, description: 'مبلغ الاسترجاع (اختياري - إذا لم يُرسل يحتسب كامل المبلغ المدفوع من الفاتورة)', example: 35.00)
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تحرير الخزانة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Locker released successfully.'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ],
            example: [
                'status' => 'success',
                'message' => 'Locker released successfully.',
                'data' => null
            ]
        )
    )]
    #[OA\Response(response: 400, description: '❌ الخزانة متاحة بالفعل أو حدث خطأ', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Locker has no active reservation.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ عدم إرسال سبب فك الحجز المبكر', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Reason is required for early release.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function releaseCurrentReservation(int $locker, Request $request)
    {
        try {
            $isRefund = $request->boolean('is_refund');
            $refundAmount = $request->input('refund_amount');
            $reason = $request->input('reason');

            $this->lockerService->releaseLocker(
                $locker,
                $reason,
                $isRefund,
                $refundAmount !== null && $refundAmount !== '' ? floatval($refundAmount) : null
            );
            return $this->successResponse(null, __('Locker released successfully.'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Patch(
        path: '/v1/locker-reservations/{reservation}/holder',
        summary: '🔄 نقل مفتاح الخزانة لحامل آخر',
        description: 'يسمح بتغيير بيانات الشخص الذي يحمل المفتاح دون إنهاء الحجز الأصلي.',
        tags: ['Locker Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'reservation', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 5))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['holder_type'],
            properties: [
                new OA\Property(property: 'holder_type', type: 'string', enum: ['member', 'staff', 'coach'], example: 'coach'),
                new OA\Property(property: 'holder_id', type: 'integer', example: 120),
                new OA\Property(property: 'holder_name', type: 'string', example: 'صديق اللاعب'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم نقل عهدة المفتاح بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Locker holder transferred successfully.'),
                new OA\Property(property: 'data', type: 'object')
            ],
            example: [
                'status' => 'success',
                'message' => 'Locker holder transferred successfully.',
                'data' => [
                    'id' => 5,
                    'holder_type' => 'coach',
                    'holder_id' => 120,
                    'holder_name' => 'الكابتن أحمد'
                ]
            ]
        )
    )]
    #[OA\Response(response: 400, description: '❌ الحجز غير نشط أو حدث خطأ', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Reservation is not active.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function transferReservationHolder(int $reservation, TransferLockerReservationRequest $request)
    {
        try {
            $updatedReservation = $this->lockerService->transferReservationHolder($reservation, $request->validated());
            return $this->successResponse($updatedReservation, __('Locker holder transferred successfully.'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Get(
        path: '/v1/lockers/holder/active',
        summary: '🧑 جلب الخزائن المسجلة باسم شخص',
        description: 'استرجاع الخزائن المحجوزة حالياً باسم لاعب (member) أو موظف (staff) بناءً على معرفه. ترجع مصفوفة فارغة إذا لم يوجد أي خزانة.',
        tags: ['Locker Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'holder_type', in: 'query', required: true, description: 'نوع الشخص (member أو staff أو coach)', schema: new OA\Schema(type: 'string', enum: ['member', 'staff', 'coach']))]
    #[OA\Parameter(name: 'holder_id', in: 'query', required: true, description: 'معرف الشخص', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الخزائن بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Lockers retrieved successfully'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
            ],
            example: [
                'status' => 'success',
                'message' => 'Lockers retrieved successfully',
                'data' => [
                    [
                        'id' => 1,
                        'locker_number' => 'L-101',
                        'status' => 'with_member',
                        'holder_type' => 'member',
                        'holder_id' => 120
                    ]
                ]
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'The selected holder type is invalid.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function getByHolder(Request $request)
    {
        $request->validate([
            'holder_type' => 'required|in:member,staff,coach',
            'holder_id' => 'required|integer',
        ]);

        $lockers = $this->lockerService->getLockersByHolder($request->holder_type, $request->holder_id);

        return $this->successResponse($lockers, __('Lockers retrieved successfully'));
    }
}
