<?php

namespace Modules\NotificationManager\Http\Controllers\Api\V1;

use Modules\NotificationManager\Repositories\NotificationTemplateRepositoryInterface;
use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Modules\NotificationManager\Models\NotificationTemplate;

class NotificationTemplateController extends BaseController
{
    protected $templateRepository;

    public function __construct(NotificationTemplateRepositoryInterface $templateRepository)
    {
        $this->templateRepository = $templateRepository;
    }

    #[OA\Get(
        path: '/v1/notification-templates',
        summary: '📋 List notification templates',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function index()
    {
        return $this->successResponse($this->templateRepository->all(), __('Templates retrieved'));
    }

    #[OA\Post(
        path: '/v1/notification-templates',
        summary: '➕ Create template',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Template created')
        ]
    )]
    public function store(Request $request)
    {
        $data = $request->validate([
            'slug' => 'required|string|unique:notification_templates,slug',
            'subject' => 'required|array',
            'content' => 'required|array',
            'channel' => 'required|in:sms,email,whatsapp,push',
        ]);

        $template = $this->templateRepository->create($data);
        return $this->successResponse($template, __('Template created'), 201);
    }

    #[OA\Get(
        path: '/v1/notification-templates/{id}',
        summary: '🔍 View template details',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function show($id)
    {
        $template = NotificationTemplate::findOrFail($id);
        return $this->successResponse($template, __('Template retrieved'));
    }

    #[OA\Put(
        path: '/v1/notification-templates/{id}',
        summary: 'Edit a template',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Template updated')
        ]
    )]
    public function update(Request $request, $id)
    {
        $template = NotificationTemplate::findOrFail($id);
        $data = $request->validate([
            'subject' => 'nullable|array',
            'content' => 'nullable|array',
            'channel' => 'nullable|in:sms,email,whatsapp,push',
            'is_active' => 'nullable|boolean',
        ]);

        $template->update($data);
        return $this->successResponse($template, __('Template updated'));
    }

    #[OA\Delete(
        path: '/v1/notification-templates/{id}',
        summary: '🗑 Delete a template',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Template deleted')
        ]
    )]
    public function destroy($id)
    {
        $template = NotificationTemplate::findOrFail($id);
        $template->delete();
        return $this->successResponse(null, __('Template deleted'));
    }

    #[OA\Post(
        path: '/v1/notification-templates/{id}/toggle',
        summary: '🔘 Enable or disable a template',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Status toggled')
        ]
    )]
    public function toggleStatus($id)
    {
        $template = NotificationTemplate::findOrFail($id);
        $template->update(['is_active' => !$template->is_active]);

        return $this->successResponse($template, __('Status updated'));
    }

    #[OA\Post(
        path: '/v1/notification-templates/{slug}/test',
        summary: '🧪 Send a test notification',
        tags: ['Notification Management'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Test sent')
        ]
    )]
    public function testSend(Request $request, $slug, \Modules\NotificationManager\Services\NotificationService $service)
    {
        $request->validate([
            'person_id' => 'required|exists:people,id',
            'data' => 'nullable|array',
        ]);

        $personService = app(\Modules\Core\Contracts\PersonSharedServiceInterface::class);
        $person = $personService->getPersonById($request->person_id);
        if (!$person) {
            return $this->errorResponse(__('Person not found'), 404);
        }

        $log = $service->sendFromTemplate($person, $slug, $request->data ?? [
            'name' => 'Test User',
            'expiry_date' => now()->addDays(7)->toDateString(),
            'plan_name' => 'Premium Plan'
        ]);

        return $this->successResponse($log, __('Test notification dispatched'));
    }
}
