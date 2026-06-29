<?php

namespace Modules\MemberManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\MemberManager\Models\MemberMeasurement;
use Modules\MemberManager\Http\Requests\AddPlayerMeasurementRequest;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class MemberMeasurementController extends BaseController
{
    #[OA\Get(
        path: '/v1/member/measurements',
        summary: '📏 جلب جميع القياسات',
        description: 'استرجاع جميع سجلات القياسات. يمكن التصفية حسب العضو لعرض قياسات شخص محدد.',
        tags: ['Member Measurements'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'member_id', in: 'query', required: false, description: 'معرف العضو (ID) لعرض قياساته فقط', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم الاسترجاع بنجاح')]
    public function index(Request $request)
    {
        $query = MemberMeasurement::with('member');
        
        if ($request->has('member_id')) {
            $query->where('member_id', $request->input('member_id'));
        }

        $measurements = $query->get();
        return $this->successResponse($measurements, __('Measurements retrieved successfully'));
    }

    #[OA\Post(
        path: '/v1/member/measurements',
        summary: '➕ إضافة قياس جديد',
        description: 'إنشاء سجل قياس جديد لعضو محدد باستخدام معرف العضو (member_id).',
        tags: ['Member Measurements'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['member_id', 'weight'],
            properties: [
                new OA\Property(property: 'member_id', type: 'integer', example: 1),
                new OA\Property(property: 'weight', type: 'number', example: 75.5),
                new OA\Property(property: 'height', type: 'number', example: 178),
                new OA\Property(property: 'measurement_date', type: 'string', format: 'date', example: '2023-10-01')
            ]
        )
    )]
    #[OA\Response(response: 201, description: '✅ تم إضافة القياس بنجاح')]
    public function store(AddPlayerMeasurementRequest $request)
    {
        // AddPlayerMeasurementRequest validates measurement fields. We need to ensure member_id is provided.
        $request->validate([
            'member_id' => 'required|integer|exists:members,id'
        ]);

        $data = $request->validated();
        $data['member_id'] = $request->member_id;

        if (!isset($data['measurement_date'])) {
            $data['measurement_date'] = now();
        }
        
        $measurement = MemberMeasurement::create($data);
        return $this->successResponse($measurement, __('Measurement added successfully'), 201);
    }

    #[OA\Get(
        path: '/v1/member/measurements/{id}',
        summary: '🔍 عرض قياس محدد',
        description: 'استرجاع تفاصيل سجل قياس معين بواسطة معرف القياس (ID).',
        tags: ['Member Measurements'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف سجل القياس', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم استرجاع القياس بنجاح')]
    #[OA\Response(response: 404, description: '❌ السجل غير موجود')]
    public function show($id)
    {
        $measurement = MemberMeasurement::with('member')->findOrFail($id);
        return $this->successResponse($measurement, __('Measurement retrieved successfully'));
    }

    #[OA\Put(
        path: '/v1/member/measurements/{id}',
        summary: '📝 تحديث سجل قياس',
        description: 'تعديل بيانات سجل قياس محدد (مثل التحديث حسب تاريخ معين عبر تمرير measurement_date).',
        tags: ['Member Measurements'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف سجل القياس', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'weight', type: 'number', example: 74.0),
                new OA\Property(property: 'measurement_date', type: 'string', format: 'date', example: '2023-10-05')
            ]
        )
    )]
    #[OA\Response(response: 200, description: '✅ تم التحديث بنجاح')]
    #[OA\Response(response: 404, description: '❌ السجل غير موجود')]
    public function update(AddPlayerMeasurementRequest $request, $id)
    {
        $measurement = MemberMeasurement::findOrFail($id);
        
        $data = $request->validated();
        if (isset($data['member_id'])) {
            unset($data['member_id']); // Prevent changing the owner
        }

        $measurement->update($data);
        return $this->successResponse($measurement, __('Measurement updated successfully'));
    }

    #[OA\Delete(
        path: '/v1/member/measurements/{id}',
        summary: '🗑️ حذف سجل قياس',
        description: 'حذف سجل قياس محدد بواسطة المعرف (ID).',
        tags: ['Member Measurements'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف سجل القياس', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم الحذف بنجاح')]
    #[OA\Response(response: 404, description: '❌ السجل غير موجود')]
    public function destroy($id)
    {
        $measurement = MemberMeasurement::findOrFail($id);
        $measurement->delete();
        return $this->successResponse(null, __('Measurement deleted successfully'));
    }
}
