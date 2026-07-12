<?php

namespace Modules\ClubManager\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Modules\ClubManager\Http\Requests\StoreLockerRequest;
use Modules\ClubManager\Http\Requests\UpdateLockerRequest;
use Modules\ClubManager\Http\Resources\LockerResource;
use Modules\ClubManager\Services\LockerService;
use Modules\Core\Http\Controllers\Api\BaseController;
use OpenApi\Attributes as OA;

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


}
