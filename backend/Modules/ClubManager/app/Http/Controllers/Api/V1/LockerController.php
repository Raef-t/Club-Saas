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
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index(Request $request)
    {
        $filters = $request->only(['branch_id', 'status']);
        $lockers = $this->lockerService->getAllLockers($filters);
        return $this->successResponse(LockerResource::collection($lockers), __('Lockers retrieved successfully'));
    }

    #[OA\Post(
        path: '/v1/lockers',
        summary: '➕ إضافة خزانة جديدة',
        description: 'إنشاء خزانة جديدة في فرع معين وتعيين رقمها.',
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
        summary: '🗑️ حذف خزانة',
        description: 'إزالة خزانة محددة من النظام. لا يمكن حذف خزانة وهي مشغولة.',
        tags: ['Locker Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف الخزانة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم حذف الخزانة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Locker deleted successfully'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الخزانة')]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
    public function destroy($id)
    {
        $this->lockerService->deleteLocker($id);
        return $this->successResponse(null, __('Locker deleted successfully'));
    }


    #[OA\Post(
        path: '/v1/lockers/{locker}/reservations',
        summary: '📥 حجز خزانة / إسناد مجاني',
        description: 'استخدم `reservation_type: rental` للحجز الشهري المدفوع، أو `assign` للتخصيص اليومي أو للموظفين. سيتم تحديد حالة الخزانة بناءً على `holder_type`.',
        tags: ['Locker Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'locker', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['reservation_type', 'holder_type'],
            properties: [
                new OA\Property(property: 'reservation_type', type: 'string', enum: ['rental', 'assign'], example: 'rental'),
                new OA\Property(property: 'holder_type', type: 'string', enum: ['member', 'staff', 'guest'], example: 'member'),
                new OA\Property(property: 'holder_id', type: 'integer', example: 120),
                new OA\Property(property: 'holder_name', type: 'string', example: 'أحمد'),
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
        description: 'ينهي الحجز النشط حالياً على الخزانة ويحول حالة الخزانة إلى متاحة (available).',
        tags: ['Locker Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'locker', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم تحرير الخزانة', content: new OA\JsonContent())]
    #[OA\Response(response: 400, description: '❌ الخزانة متاحة بالفعل أو حدث خطأ')]
    public function releaseCurrentReservation(int $locker)
    {
        try {
            $this->lockerService->releaseLocker($locker);
            return $this->successResponse(null, __('Locker released successfully.'));
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
                new OA\Property(property: 'holder_type', type: 'string', enum: ['member', 'staff', 'guest'], example: 'guest'),
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
}
