<?php

namespace Modules\ClubManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\ClubManager\Models\Branch;
use Modules\ClubManager\Models\BranchSetting;
use Illuminate\Http\Request;
use Modules\ClubManager\Http\Requests\UpdateBranchSettingRequest;
use Modules\ClubManager\Http\Resources\BranchSettingResource;
use OpenApi\Attributes as OA;

class BranchSettingController extends BaseController
{
    #[OA\Get(
        path: '/v1/branches/{branch}/settings',
        summary: '⚙️ عرض إعدادات الفرع',
        description: 'استرجاع إعدادات فرع محدد (نسبة النادي، نسبة المدرب، الراتب الافتراضي، الإجازات، الفعاليات المختلطة، وإعدادات الرواتب).',
        tags: ['Branch Settings'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch', in: 'path', required: true, description: 'معرف الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الإعدادات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Settings retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                        new OA\Property(property: 'working_hours_start', type: 'string', nullable: true, example: '08:00'),
                        new OA\Property(property: 'working_hours_end', type: 'string', nullable: true, example: '22:00'),
                        new OA\Property(property: 'default_club_commission_percentage', type: 'number', format: 'float', example: 40.00),
                        new OA\Property(property: 'default_coach_commission_percentage', type: 'number', format: 'float', example: 60.00),
                        new OA\Property(property: 'private_subscription_commission', type: 'number', format: 'float', example: 0.00, description: 'نسبة عمولة النادي من الاشتراك الخاص (%)'),
                        new OA\Property(property: 'default_employee_salary', type: 'number', format: 'float', example: 3500.00),
                        new OA\Property(property: 'daily_entry_price', type: 'number', format: 'float', example: 50.00),
                        new OA\Property(property: 'locker_price', type: 'number', format: 'float', example: 100.00),
                        new OA\Property(property: 'allow_freeze', type: 'boolean', example: true),
                        new OA\Property(property: 'display_mixed_activities', type: 'boolean', example: false),
                        new OA\Property(property: 'payroll_end_day', type: 'integer', example: 30),
                        new OA\Property(property: 'include_terminated_subscriptions', type: 'boolean', example: false),
                        new OA\Property(property: 'allow_installments', type: 'boolean', example: true),
                        new OA\Property(property: 'updated_at', type: 'string', example: '2026-09-06T12:00:00.000000Z')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 الفرع غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Branch not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show($branchId)
    {
        $branch = Branch::findOrFail($branchId);
        $setting = $branch->settings()->firstOrCreate(['branch_id' => $branch->id]);

        return $this->successResponse(
            new BranchSettingResource($setting),
            __('Settings retrieved successfully')
        );
    }

    #[OA\Put(
        path: '/v1/branches/{branch}/settings',
        summary: '📝 تحديث إعدادات الفرع',
        description: 'تحديث الحقول والخيارات المالية والتشغيلية الخاصة بفرع محدد.',
        tags: ['Branch Settings'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch', in: 'path', required: true, description: 'معرف الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'default_club_commission_percentage', description: 'نسبة عمولة النادي (%)', type: 'number', format: 'float', example: 40.00),
                new OA\Property(property: 'default_coach_commission_percentage', description: 'نسبة عمولة المدرب (%)', type: 'number', format: 'float', example: 60.00),
                new OA\Property(property: 'private_subscription_commission', description: 'نسبة عمولة النادي من الاشتراك الخاص (%)', type: 'number', format: 'float', example: 0.00),
                new OA\Property(property: 'default_employee_salary', description: 'الراتب الافتراضي للموظف', type: 'number', format: 'float', example: 3500.00),
                new OA\Property(property: 'daily_entry_price', description: 'سعر الدخول اليومي', type: 'number', format: 'float', example: 50.00),
                new OA\Property(property: 'locker_price', description: 'رسوم الخزانة', type: 'number', format: 'float', example: 100.00),
                new OA\Property(property: 'working_hours_start', description: 'ساعة الافتتاح (HH:mm)', type: 'string', format: 'time', nullable: true, example: '08:00'),
                new OA\Property(property: 'working_hours_end', description: 'ساعة الإغلاق (HH:mm)', type: 'string', format: 'time', nullable: true, example: '22:00'),
                new OA\Property(property: 'allow_freeze', description: 'السماح بتجميد الاشتراكات في هذا الفرع', type: 'boolean', example: true),
                new OA\Property(property: 'display_mixed_activities', description: 'السماح بعرض الفعاليات المختلطة', type: 'boolean', example: false),
                new OA\Property(property: 'payroll_end_day', description: 'يوم نهاية الرواتب (1-31)', type: 'integer', example: 30),
                new OA\Property(property: 'include_terminated_subscriptions', description: 'تضمين الاشتراكات المنتهية', type: 'boolean', example: false),
                new OA\Property(property: 'allow_installments', description: 'السماح بالتقسيط في الفرع', type: 'boolean', example: true)
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم التحديث بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Settings updated successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'branch_id', type: 'integer', example: 1),
                        new OA\Property(property: 'default_club_commission_percentage', type: 'number', example: 40.00),
                        new OA\Property(property: 'default_coach_commission_percentage', type: 'number', example: 60.00),
                        new OA\Property(property: 'allow_freeze', type: 'boolean', example: true),
                        new OA\Property(property: 'allow_installments', type: 'boolean', example: true),
                        new OA\Property(property: 'updated_at', type: 'string', example: '2026-09-06T13:00:00.000000Z')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 404, description: '🚫 الفرع غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Branch not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function update(UpdateBranchSettingRequest $request, $branchId)
    {
        $branch = Branch::findOrFail($branchId);
        $setting = $branch->settings()->firstOrCreate(['branch_id' => $branch->id]);

        $setting->update($request->validated());

        return $this->successResponse(
            new BranchSettingResource($setting),
            __('Settings updated successfully')
        );
    }
}
