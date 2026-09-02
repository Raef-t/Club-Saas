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
                        new OA\Property(property: 'lockers', type: 'array', items: new OA\Items(type: 'object'))
                    ]
                )
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
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات')]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
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
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الخزانة')]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
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
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الخزانة')]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات')]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
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
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الخزانة')]
    #[OA\Response(response: 422, description: '⚠️ خطأ عدم إرسال كلمة التأكيد "delete"')]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
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
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
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
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 الخزانة غير موجودة في سلة المحذوفات')]
    #[OA\Response(response: 422, description: '⚠️ خطأ تكرار رقم الخزانة في الفرع')]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
    public function restore($id)
    {
        try {
            $locker = $this->lockerService->restoreLocker((int) $id);
            return $this->successResponse(new LockerResource($locker), __('Locker restored successfully'));
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
    #[OA\Response(response: 200, description: '✅ تم تخصيص الخزانة', content: new OA\JsonContent())]
    #[OA\Response(response: 400, description: '❌ الخزانة غير متاحة أو بيانات غير صحيحة')]
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
                new OA\Property(property: 'reason', type: 'string', description: 'سبب فك الحجز (إجباري إذا لم ينتهِ تاريخ نهاية الحجز بعد)', example: 'طلب المشترك إنهاء الحجز واستعادة الأمانة')
            ]
        )
    )]
    #[OA\Response(response: 200, description: '✅ تم تحرير الخزانة', content: new OA\JsonContent())]
    #[OA\Response(response: 400, description: '❌ الخزانة متاحة بالفعل أو حدث خطأ')]
    #[OA\Response(response: 422, description: '⚠️ خطأ عدم إرسال سبب فك الحجز المبكر')]
    public function releaseCurrentReservation(int $locker, Request $request)
    {
        try {
            $this->lockerService->releaseLocker($locker, $request->input('reason'));
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
    #[OA\Response(response: 200, description: '✅ تم نقل عهدة المفتاح', content: new OA\JsonContent())]
    #[OA\Response(response: 400, description: '❌ الحجز غير نشط أو حدث خطأ')]
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
            ]
        )
    )]
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
