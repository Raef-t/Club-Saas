<?php

namespace Modules\Sports\Http\Controllers\Api\V1;

use Modules\Sports\Services\SessionService;
use Modules\Sports\Http\Resources\SessionResource;
use Modules\Sports\Http\Requests\StoreSessionRequest;
use Modules\Sports\Http\Requests\UpdateSessionRequest;
use Modules\Sports\Http\Requests\GetWeeklyScheduleRequest;
use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SessionController extends BaseController
{
    protected $sessionService;

    public function __construct(SessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    #[OA\Get(
        path: '/v1/sessions',
        summary: '📅 عرض الجلسات الرياضية',
        description: 'استرجاع جميع الجلسات الرياضية المتاحة (كالتدريب الشخصي والحصص الجماعية).',
        tags: ['Session Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الجلسات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Sessions retrieved successfully'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index()
    {
        $sessions = $this->sessionService->getAllSessions();
        return $this->successResponse(SessionResource::collection($sessions), __('Sessions retrieved successfully'));
    }

    #[OA\Post(
        path: '/v1/sessions',
        summary: '➕ إنشاء جلسة رياضية',
        description: 'إضافة جلسة رياضية جديدة للنظام.',
        tags: ['Session Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['activity_id', 'facility_id', 'start_time', 'end_time', 'capacity'],
            properties: [
                new OA\Property(property: 'activity_id', type: 'integer', example: 1),
                new OA\Property(property: 'facility_id', type: 'integer', example: 1),
                new OA\Property(property: 'start_time', type: 'string', format: 'date-time', example: '2023-11-01 10:00:00'),
                new OA\Property(property: 'end_time', type: 'string', format: 'date-time', example: '2023-11-01 11:00:00'),
                new OA\Property(property: 'capacity', type: 'integer', example: 20)
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء الجلسة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Session created successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function store(StoreSessionRequest $request)
    {
        $session = $this->sessionService->createSession($request->validated());
        return $this->successResponse(new SessionResource($session), __('Session created successfully'), 201);
    }

    #[OA\Get(
        path: '/v1/sessions/{session}',
        summary: '🔍 تفاصيل الجلسة',
        description: 'استرجاع كافة تفاصيل جلسة رياضية محددة.',
        tags: ['Session Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'session', in: 'path', required: true, description: 'معرف الجلسة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تفاصيل الجلسة',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Session retrieved successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 الجلسة غير موجودة', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show(int $id)
    {
        $session = $this->sessionService->getSessionById($id);
        return $this->successResponse(new SessionResource($session), __('Session retrieved successfully'));
    }

    #[OA\Put(
        path: '/v1/sessions/{session}',
        summary: '✏️ تعديل الجلسة',
        description: 'تحديث بيانات جلسة رياضية مسجلة.',
        tags: ['Session Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'session', in: 'path', required: true, description: 'معرف الجلسة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'capacity', type: 'integer', example: 25)
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم التعديل بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Session updated successfully'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 الجلسة غير موجودة', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(UpdateSessionRequest $request, int $id)
    {
        $data = $request->validated();

        $session = $this->sessionService->updateSession($id, $data);
        return $this->successResponse(new SessionResource($session), __('Session updated successfully'));
    }

    #[OA\Delete(
        path: '/v1/sessions/{session}',
        summary: '🗑️ حذف الجلسة',
        description: 'إزالة الجلسة الرياضية من النظام.',
        tags: ['Session Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'session', in: 'path', required: true, description: 'معرف الجلسة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم الحذف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Session deleted successfully'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 الجلسة غير موجودة', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Record not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy(int $id)
    {
        $this->sessionService->deleteSession($id);
        return $this->successResponse(null, __('Session deleted successfully'));
    }

    #[OA\Get(
        path: '/v1/sessions/weekly-schedule',
        summary: '📆 الجدول الأسبوعي',
        description: 'استرجاع الجدول الأسبوعي للجلسات في فرع معين.',
        tags: ['Session Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: true, description: 'معرف الفرع', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'start_date', in: 'query', required: false, description: 'تاريخ بداية الأسبوع', schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الجدول بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Weekly schedule retrieved'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function weeklySchedule(GetWeeklyScheduleRequest $request)
    {
        $data = $request->validated();

        $sessions = $this->sessionService->getWeeklySchedule(
            $data['branch_id'],
            $data['start_date'] ?? null
        );

        return $this->successResponse(SessionResource::collection($sessions), __('Weekly schedule retrieved'));
    }
}
