<?php

namespace Modules\NotificationManager\Http\Controllers\Api\V1;

use Modules\NotificationManager\Repositories\NotificationTemplateRepositoryInterface;
use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Modules\NotificationManager\Models\NotificationTemplate;
use Modules\NotificationManager\Http\Requests\StoreNotificationTemplateRequest;
use Modules\NotificationManager\Http\Requests\UpdateNotificationTemplateRequest;
use Modules\NotificationManager\Http\Requests\TestSendNotificationRequest;

class NotificationTemplateController extends BaseController
{
    protected $templateRepository;

    public function __construct(NotificationTemplateRepositoryInterface $templateRepository)
    {
        $this->templateRepository = $templateRepository;
    }

    #[OA\Get(
        path: '/v1/notification-templates',
        summary: '📋 عرض قوالب الإشعارات',
        description: 'استرجاع قائمة بجميع قوالب الإشعارات المتاحة في النظام.',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع القوالب بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Templates retrieved'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index()
    {
        return $this->successResponse($this->templateRepository->all(), __('Templates retrieved'));
    }

    #[OA\Post(
        path: '/v1/notification-templates',
        summary: '➕ إضافة قالب جديد',
        description: 'إنشاء قالب إشعار جديد.',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'slug', 'content'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Welcome Email'),
                new OA\Property(property: 'slug', type: 'string', example: 'welcome_email'),
                new OA\Property(property: 'content', type: 'string', example: 'Welcome to our club!'),
                new OA\Property(property: 'is_active', type: 'boolean', example: true)
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء القالب بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Template created'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function store(StoreNotificationTemplateRequest $request)
    {
        $data = $request->validated();

        $template = $this->templateRepository->create($data);
        return $this->successResponse($template, __('Template created'), 201);
    }

    #[OA\Get(
        path: '/v1/notification-templates/{notification_template}',
        summary: '🔍 تفاصيل القالب',
        description: 'استرجاع تفاصيل قالب إشعار محدد.',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'notification_template', in: 'path', required: true, description: 'معرف القالب', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تفاصيل القالب',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Template retrieved'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 القالب غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show($id)
    {
        $template = NotificationTemplate::findOrFail($id);
        return $this->successResponse($template, __('Template retrieved'));
    }

    #[OA\Put(
        path: '/v1/notification-templates/{notification_template}',
        summary: '✏️ تعديل القالب',
        description: 'تحديث بيانات ومحتوى قالب إشعار.',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'notification_template', in: 'path', required: true, description: 'معرف القالب', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'content', type: 'string', example: 'Welcome back to our club!')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تحديث القالب بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Template updated'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 القالب غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(UpdateNotificationTemplateRequest $request, $id)
    {
        $template = NotificationTemplate::findOrFail($id);
        $data = $request->validated();

        $template->update($data);
        return $this->successResponse($template, __('Template updated'));
    }

    #[OA\Delete(
        path: '/v1/notification-templates/{notification_template}',
        summary: '🗑️ حذف القالب',
        description: 'حذف قالب إشعار من النظام.',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'notification_template', in: 'path', required: true, description: 'معرف القالب', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم الحذف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Template deleted'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 القالب غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy($id)
    {
        $template = NotificationTemplate::findOrFail($id);
        $template->delete();
        return $this->successResponse(null, __('Template deleted'));
    }

    #[OA\Post(
        path: '/v1/notification-templates/{id}/toggle',
        summary: '🔘 تفعيل/تعطيل القالب',
        description: 'تبديل حالة القالب بين مفعل وغير مفعل.',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف القالب', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم تحديث الحالة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Status updated'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 القالب غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function toggleStatus($id)
    {
        $template = NotificationTemplate::findOrFail($id);
        $template->update(['is_active' => !$template->is_active]);

        return $this->successResponse($template, __('Status updated'));
    }

    #[OA\Post(
        path: '/v1/notification-templates/{slug}/test',
        summary: '🧪 إرسال إشعار تجريبي',
        description: 'إرسال إشعار اختباري باستخدام قالب محدد.',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'slug', in: 'path', required: true, description: 'اسم القالب الفريد (Slug)', schema: new OA\Schema(type: 'string', example: 'welcome_email'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['person_id'],
            properties: [
                new OA\Property(property: 'person_id', type: 'integer', example: 1),
                new OA\Property(property: 'data', type: 'object', example: ['name' => 'Test', 'plan_name' => 'Premium'])
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم الإرسال بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Test notification dispatched'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 الشخص أو القالب غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Person not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function testSend(TestSendNotificationRequest $request, $slug, \Modules\NotificationManager\Services\NotificationService $service)
    {
        $data = $request->validated();

        $personService = app(\Modules\Core\Contracts\PersonSharedServiceInterface::class);
        $person = $personService->getPersonById($data['person_id']);
        if (!$person) {
            return $this->errorResponse(__('Person not found'), 404);
        }

        $log = $service->sendFromTemplate($person, $slug, $data['data'] ?? [
            'name' => 'Test User',
            'expiry_date' => now()->addDays(7)->toDateString(),
            'plan_name' => 'Premium Plan'
        ]);

        return $this->successResponse($log, __('Test notification dispatched'));
    }
}
