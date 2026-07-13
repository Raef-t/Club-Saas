<?php

namespace Modules\SubscriptionManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\SubscriptionManager\Models\ExtraService;
use Modules\SubscriptionManager\Http\Requests\StoreExtraServiceRequest;
use Modules\SubscriptionManager\Http\Requests\UpdateExtraServiceRequest;
use Modules\SubscriptionManager\Http\Resources\ExtraServiceResource;
use OpenApi\Attributes as OA;

class ExtraServiceController extends BaseController
{
    #[OA\Get(
        path: '/v1/extra-services',
        summary: '🛠️ عرض الخدمات الإضافية',
        description: 'استرجاع جميع الخدمات الإضافية المتاحة في النظام.',
        tags: ['Extra Services'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, description: 'تصفية حسب معرف الفرع', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الخدمات بنجاح',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(type: 'object')
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index(\Illuminate\Http\Request $request)
    {
        $query = ExtraService::query();
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        $services = $query->get();
        return ExtraServiceResource::collection($services);
    }

    #[OA\Post(
        path: '/v1/extra-services',
        summary: '➕ إضافة خدمة إضافية',
        description: 'إنشاء خدمة إضافية جديدة يمكن للأعضاء الاشتراك بها.',
        tags: ['Extra Services'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'price'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'مساج'),
                new OA\Property(property: 'price', type: 'number', format: 'float', example: 150.00)
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء الخدمة بنجاح',
        content: new OA\JsonContent(type: 'object')
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function store(StoreExtraServiceRequest $request)
    {
        $service = ExtraService::create($request->validated());
        return new ExtraServiceResource($service);
    }

    #[OA\Get(
        path: '/v1/extra-services/{extra_service}',
        summary: '🔍 تفاصيل الخدمة الإضافية',
        description: 'استرجاع تفاصيل خدمة إضافية معينة.',
        tags: ['Extra Services'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'extra_service', in: 'path', required: true, description: 'معرف الخدمة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تفاصيل الخدمة',
        content: new OA\JsonContent(type: 'object')
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الخدمة', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show(int $id)
    {
        $service = ExtraService::findOrFail($id);
        return new ExtraServiceResource($service);
    }

    #[OA\Put(
        path: '/v1/extra-services/{extra_service}',
        summary: '📝 تعديل الخدمة الإضافية',
        description: 'تحديث بيانات خدمة إضافية موجودة.',
        tags: ['Extra Services'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'extra_service', in: 'path', required: true, description: 'معرف الخدمة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'price', type: 'number', format: 'float', example: 200.00)
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تحديث الخدمة بنجاح',
        content: new OA\JsonContent(type: 'object')
    )]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الخدمة', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(UpdateExtraServiceRequest $request, int $id)
    {
        $service = ExtraService::findOrFail($id);
        $service->update($request->validated());
        return new ExtraServiceResource($service);
    }

    #[OA\Delete(
        path: '/v1/extra-services/{extra_service}',
        summary: '🗑️ حذف الخدمة الإضافية',
        description: 'حذف خدمة إضافية من النظام.',
        tags: ['Extra Services'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'extra_service', in: 'path', required: true, description: 'معرف الخدمة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 204,
        description: '✅ تم حذف الخدمة بنجاح'
    )]
    #[OA\Response(response: 409, description: '🚫 لا يمكن الحذف — الخدمة مرتبطة باشتراكات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'لا يمكن حذف الخدمة.')]))]
    #[OA\Response(response: 404, description: '🚫 لم يتم العثور على الخدمة', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy(int $id)
    {
        $service = ExtraService::findOrFail($id);

        // Check if this extra service is linked to any active player subscriptions
        $subscriptionsCount = \Modules\SubscriptionManager\Models\PlayerSubscriptionService::where('extra_service_id', $id)->count();

        if ($subscriptionsCount > 0) {
            return $this->errorResponse(
                "لا يمكن حذف الخدمة الإضافية لارتباطها بـ {$subscriptionsCount} " . ($subscriptionsCount === 1 ? 'اشتراك' : 'اشتراكات') . " حالية. يمكنك تعطيل الخدمة بدلاً من الحذف.",
                409
            );
        }

        $service->delete();
        return response()->json(null, 204);
    }
}
