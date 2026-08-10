<?php

namespace Modules\ClubManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\ClubManager\Services\ClubService;
use Modules\ClubManager\Http\Requests\StoreClubRequest;
use Modules\ClubManager\Http\Requests\UpdateClubRequest;
use Modules\ClubManager\Http\Resources\ClubResource;
use OpenApi\Attributes as OA;

class ClubController extends BaseController
{
    protected $service;

    public function __construct(ClubService $service)
    {
        $this->service = $service;
    }

    #[OA\Get(
        path: '/v1/clubs',
        summary: '🏢 عرض جميع الأندية',
        description: 'استرجاع قائمة بجميع الأندية المسجلة في النظام.',
        tags: ['Club Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ قائمة الأندية',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Retrieved successfully'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object', properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'name', type: 'string', example: 'نادي الأبطال')
                ]))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index()
    {
        return $this->successResponse(ClubResource::collection($this->service->getAll()), 'Retrieved successfully');
    }

    #[OA\Post(
        path: '/v1/clubs',
        summary: '➕ إنشاء نادي جديد',
        description: 'إضافة نادي جديد إلى النظام مع تحديد المعلومات الأساسية مثل الاسم.',
        tags: ['Club Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'نادي الأبطال الذهبي')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء النادي بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Created successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function store(StoreClubRequest $request)
    {
        $record = $this->service->create($request->validated());
        return $this->successResponse(new ClubResource($record), 'Created successfully', 201);
    }

    #[OA\Get(
        path: '/v1/clubs/{id}',
        summary: '🔍 تفاصيل النادي',
        description: 'استرجاع جميع تفاصيل نادي محدد عن طريق المعرف.',
        tags: ['Club Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف النادي', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تفاصيل النادي',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Retrieved successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على النادي', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show($id)
    {
        return $this->successResponse(new ClubResource($this->service->getById($id)), 'Retrieved successfully');
    }

    #[OA\Put(
        path: '/v1/clubs/{id}',
        summary: '📝 تعديل النادي',
        description: 'تعديل تفاصيل نادي موجود في النظام.',
        tags: ['Club Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف النادي', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'نادي الأبطال الماسي')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم التعديل بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Updated successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على النادي', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(UpdateClubRequest $request, $id)
    {
        $record = $this->service->update($id, $request->validated());
        return $this->successResponse(new ClubResource($record), 'Updated successfully');
    }

    #[OA\Delete(
        path: '/v1/clubs/{id}',
        summary: '🗑️ حذف النادي (Soft Delete)',
        description: 'حذف النادي بالكامل من النظام مع كافة الفروع والمشتركين والمدربين التابعين له. يتطلب إرسال كلمة التأكيد "delete".',
        tags: ['Club Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف النادي', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'confirmation', type: 'string', description: 'تأكيد الحذف (delete)', example: '')
            ]
        )
    )]
    #[OA\Response(response: 200, description: '✅ تم الحذف بنجاح')]
    #[OA\Response(response: 422, description: '⚠️ خطأ عدم إرسال كلمة التأكيد "delete"')]
    public function destroy(Request $request, $id)
    {
        $confirmation = $request->input('confirmation', '');
        $this->service->delete((int) $id, (string) $confirmation);
        return $this->successResponse(null, __('Deleted successfully'));
    }

    #[OA\Get(
        path: '/v1/clubs/trashed',
        summary: '🗑️ عرض الأندية المحذوفة (سلة المهملات)',
        description: 'جلب قائمة بالأندية التي تم حذفها.',
        tags: ['Club Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: '✅ تم جلب الأندية المحذوفة بنجاح')]
    public function trashed(Request $request)
    {
        $clubs = $this->service->getTrashed();
        return $this->successResponse(ClubResource::collection($clubs), __('Trashed clubs retrieved successfully'));
    }

    #[OA\Post(
        path: '/v1/clubs/{id}/restore',
        summary: '♻️ استرجاع نادي محذوف',
        description: 'استرجاع النادي وكافة الفروع والمشتركين التابعين له من سلة المهملات.',
        tags: ['Club Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف النادي', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم استرجاع النادي بنجاح')]
    public function restore($id)
    {
        $club = $this->service->restoreClub($id);
        return $this->successResponse(new ClubResource($club), __('Club restored successfully'));
    }
}
