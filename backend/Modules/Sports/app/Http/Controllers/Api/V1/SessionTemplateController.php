<?php

namespace Modules\Sports\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\Sports\Models\SportSessionTemplate;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SessionTemplateController extends BaseController
{
    #[OA\Get(
        path: '/v1/session-templates',
        summary: '📅 عرض قوالب الجلسات',
        description: 'استرجاع جميع قوالب الجلسات الأسبوعية.',
        tags: ['Session Templates'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع القوالب بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Templates retrieved successfully'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function index(Request $request)
    {
        $templates = SportSessionTemplate::with(['activity'])->get();
        return $this->successResponse($templates, __('Templates retrieved successfully'));
    }

    #[OA\Post(
        path: '/v1/session-templates',
        summary: '➕ إنشاء قالب جلسة',
        description: 'إضافة قالب جلسة رياضية أسبوعية.',
        tags: ['Session Templates'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['branch_id', 'activity_id', 'day_of_week', 'start_time', 'end_time'],
            properties: [
                new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                new OA\Property(property: 'activity_id', type: 'integer', example: 1),
                new OA\Property(property: 'staff_id', type: 'integer', nullable: true, example: 2),
                new OA\Property(property: 'facility_id', type: 'integer', nullable: true, example: 1),
                new OA\Property(property: 'day_of_week', type: 'integer', description: '0=Sunday, 1=Monday, ..., 6=Saturday', example: 0),
                new OA\Property(property: 'start_time', type: 'string', format: 'time', example: '08:00'),
                new OA\Property(property: 'end_time', type: 'string', format: 'time', example: '09:00'),
                new OA\Property(property: 'max_players', type: 'integer', nullable: true, example: 20),
                new OA\Property(property: 'gender_allowed', type: 'string', enum: ['male', 'female', 'both'], example: 'both'),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء القالب بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Template created successfully'),
                new OA\Property(property: 'data', type: 'object'),
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id' => 'required|integer',
            'activity_id' => 'required|integer',
            'staff_id' => 'nullable|integer',
            'facility_id' => 'nullable|integer',
            'day_of_week' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'max_players' => 'nullable|integer',
            'gender_allowed' => 'nullable|string',
        ]);

        $template = SportSessionTemplate::create($data);
        return $this->successResponse($template, __('Template created successfully'), 201);
    }

    #[OA\Put(
        path: '/v1/session-templates/{id}',
        summary: '✏️ تعديل قالب جلسة',
        description: 'تحديث قالب جلسة رياضية أسبوعية.',
        tags: ['Session Templates'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف القالب', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'staff_id', type: 'integer', nullable: true, example: 2),
                new OA\Property(property: 'facility_id', type: 'integer', nullable: true, example: 1),
                new OA\Property(property: 'day_of_week', type: 'integer', example: 1),
                new OA\Property(property: 'start_time', type: 'string', example: '10:00'),
                new OA\Property(property: 'end_time', type: 'string', example: '11:00'),
                new OA\Property(property: 'max_players', type: 'integer', nullable: true, example: 25),
                new OA\Property(property: 'is_active', type: 'boolean', example: true),
            ]
        )
    )]
    #[OA\Response(response: 200, description: '✅ تم التعديل بنجاح', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'success'), new OA\Property(property: 'data', type: 'object')]))]
    #[OA\Response(response: 404, description: '🚫 القالب غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(Request $request, int $id)
    {
        $template = SportSessionTemplate::findOrFail($id);

        $data = $request->validate([
            'staff_id' => 'nullable|integer',
            'facility_id' => 'nullable|integer',
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'max_players' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $template->update($data);
        return $this->successResponse($template, __('Template updated successfully'));
    }

    #[OA\Delete(
        path: '/v1/session-templates/{id}',
        summary: '🗑️ حذف قالب جلسة',
        description: 'حذف قالب جلسة رياضية أسبوعية.',
        tags: ['Session Templates'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف القالب', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم الحذف بنجاح', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'success'), new OA\Property(property: 'message', type: 'string', example: 'Template deleted successfully')]))]
    #[OA\Response(response: 404, description: '🚫 القالب غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function destroy(int $id)
    {
        $template = SportSessionTemplate::findOrFail($id);
        $template->delete();
        return $this->successResponse(null, __('Template deleted successfully'));
    }

    #[OA\Post(
        path: '/v1/session-templates/generate',
        summary: '⚙️ توليد الجلسات',
        description: 'توليد جلسات فعلية من القوالب النشطة للأسابيع القادمة.',
        tags: ['Session Templates'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'weeks', type: 'integer', description: 'عدد الأسابيع المراد توليدها (الافتراضي: 1)', example: 1),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم توليد الجلسات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: '5 sessions generated successfully from templates'),
                new OA\Property(property: 'data', type: 'object', properties: [new OA\Property(property: 'generated_count', type: 'integer', example: 5)]),
            ]
        )
    )]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function generate(Request $request, \Modules\Sports\Services\ScheduleGeneratorService $generatorService)
    {
        $request->validate([
            'weeks' => 'nullable|integer|min:1|max:10',
        ]);

        $weeks = $request->input('weeks', 1);
        $startDate = now()->startOfWeek();
        $endDate = now()->addWeeks($weeks - 1)->endOfWeek();

        $count = $generatorService->generateSessions($startDate->toDateString(), $endDate->toDateString());

        return $this->successResponse(['generated_count' => $count], __(':count sessions generated successfully from templates', ['count' => $count]));
    }
}
