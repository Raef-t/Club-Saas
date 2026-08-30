<?php

namespace Modules\ClubManager\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Modules\ClubManager\Http\Requests\StoreFacilityRequest;
use Modules\ClubManager\Http\Requests\UpdateFacilityRequest;
use Modules\ClubManager\Http\Resources\FacilityResource;
use Modules\ClubManager\Services\FacilityService;
use Modules\Core\Http\Controllers\Api\BaseController;
use OpenApi\Attributes as OA;

class FacilityController extends BaseController
{
    protected $facilityService;

    public function __construct(FacilityService $facilityService)
    {
        $this->facilityService = $facilityService;
    }

    #[OA\Get(
        path: '/v1/facilities',
        summary: '🏊 عرض جميع المرافق',
        description: 'استرجاع قائمة بجميع المرافق (كالمسابح، الملاعب، الصالات الرياضية) المتاحة في النظام.',
        tags: ['Facility Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        required: false,
        description: 'عدد العناصر في الصفحة (الافتراضي: 15)',
        schema: new OA\Schema(type: 'integer', example: 15)
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        required: false,
        description: 'رقم الصفحة',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع المرافق بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Facilities retrieved successfully'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index(Request $request)
    {
        $perPage = $this->getPerPage($request);
        $facilities = $this->facilityService->getAllFacilities($perPage);
        return $this->successResponse(FacilityResource::collection($facilities), __('Facilities retrieved successfully'));
    }

    #[OA\Post(
        path: '/v1/facilities',
        summary: '➕ إضافة مرفق جديد',
        description: 'إنشاء مرفق جديد داخل أحد فروع النادي.',
        tags: ['Facility Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'branch_id'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'المسبح الأولمبي'),
                new OA\Property(property: 'branch_id', type: 'integer', example: 1)
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء المرفق بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Facility created successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function store(StoreFacilityRequest $request)
    {
        $facility = $this->facilityService->createFacility($request->validated());
        return $this->successResponse(new FacilityResource($facility), __('Facility created successfully'), 201);
    }

    #[OA\Get(
        path: '/v1/facilities/{id}',
        summary: '🔍 تفاصيل المرفق',
        description: 'استرجاع جميع تفاصيل مرفق معين.',
        tags: ['Facility Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف المرفق', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تفاصيل المرفق',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Facility details retrieved'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على المرفق', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Facility not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show($id)
    {
        $facility = $this->facilityService->getFacilityById($id);
        return $this->successResponse(new FacilityResource($facility), __('Facility details retrieved'));
    }

    #[OA\Put(
        path: '/v1/facilities/{id}',
        summary: '📝 تعديل المرفق',
        description: 'تحديث بيانات مرفق موجود مسبقًا.',
        tags: ['Facility Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف المرفق', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'المسبح الرئيسي')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تحديث المرفق بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Facility updated successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على المرفق', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Facility not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(UpdateFacilityRequest $request, $id)
    {
        $facility = $this->facilityService->updateFacility($id, $request->validated());
        return $this->successResponse(new FacilityResource($facility), __('Facility updated successfully'));
    }

    #[OA\Delete(
        path: '/v1/facilities/{id}',
        summary: '🗑️ حذف المرفق (Soft Delete)',
        description: 'حذف المرفق بالكامل من النظام مع كافة الخزائن والورديات وقوالب الجلسات التدريبية المعتمدة عليه. يتطلب إرسال كلمة التأكيد "delete" اختيارياً.',
        tags: ['Facility Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف المرفق', schema: new OA\Schema(type: 'integer', example: 1))]
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
        $this->facilityService->deleteFacility((int) $id, (string) $confirmation);
        return $this->successResponse(null, __('Facility deleted successfully'));
    }

    #[OA\Get(
        path: '/v1/facilities/trashed',
        summary: '🗑️ عرض المرافق المحذوفة (سلة المهملات)',
        description: 'جلب قائمة بالمرافق التي تم حذفها.',
        tags: ['Facility Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        required: false,
        description: 'عدد العناصر في الصفحة (الافتراضي: 15)',
        schema: new OA\Schema(type: 'integer', example: 15)
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        required: false,
        description: 'رقم الصفحة',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(response: 200, description: '✅ تم جلب المرافق المحذوفة بنجاح')]
    public function trashed(Request $request)
    {
        $perPage = $this->getPerPage($request);
        $facilities = $this->facilityService->getTrashed($perPage);
        return $this->successResponse(FacilityResource::collection($facilities), __('Trashed facilities retrieved successfully'));
    }

    #[OA\Post(
        path: '/v1/facilities/{id}/restore',
        summary: '♻️ استرجاع مرفق محذوف',
        description: 'استرجاع المرفق وكافة العلاقات التابعة له من سلة المهملات.',
        tags: ['Facility Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف المرفق', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم استرجاع المرفق بنجاح')]
    public function restore($id)
    {
        $facility = $this->facilityService->restoreFacility($id);
        return $this->successResponse(new FacilityResource($facility), __('Facility restored successfully'));
    }

    #[OA\Patch(
        path: '/v1/facilities/{id}/toggle-status',
        summary: '🔄 تفعيل / تعطيل المرفق',
        description: 'تغيير حالة المرفق ليصبح متاحاً أو غير متاح.',
        tags: ['Facility Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف المرفق', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم تحديث حالة المرفق',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Facility status updated'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على المرفق', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Facility not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function toggleStatus($id)
    {
        $facility = $this->facilityService->toggleStatus($id);
        return $this->successResponse(new FacilityResource($facility), __('Facility status updated'));
    }
}
