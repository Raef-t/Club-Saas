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
        description: 'استرجاع إعدادات فرع محدد (نسبة النادي، نسبة المدرب، والراتب الافتراضي).',
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
                new OA\Property(property: 'data', ref: '#/components/schemas/BranchSettingResource')
            ]
        )
    )]
    #[OA\Response(response: 404, description: '🚫 الفرع غير موجود')]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
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
        description: 'تحديث إعدادات فرع محدد.',
        tags: ['Branch Settings'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch', in: 'path', required: true, description: 'معرف الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'default_club_commission_percentage', type: 'number', format: 'float', example: 40.00),
                new OA\Property(property: 'default_coach_commission_percentage', type: 'number', format: 'float', example: 60.00),
                new OA\Property(property: 'default_employee_salary', type: 'number', format: 'float', example: 3500.00)
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
                new OA\Property(property: 'data', ref: '#/components/schemas/BranchSettingResource')
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ خطأ في البيانات')]
    #[OA\Response(response: 404, description: '🚫 الفرع غير موجود')]
    #[OA\Response(response: 401, description: '❌ غير مصرح')]
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
