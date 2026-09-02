<?php

namespace Modules\MemberManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\MemberManager\Models\MemberHealthProfile;
use Modules\MemberManager\Http\Requests\StoreMemberHealthProfileRequest;
use Modules\MemberManager\Http\Requests\UpdateMemberHealthProfileRequest;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class MemberHealthProfileController extends BaseController
{
    #[OA\Get(
        path: '/v1/member/health-profiles',
        summary: '🏥 جلب جميع السجلات الصحية',
        description: 'استرجاع جميع السجلات الصحية للأعضاء. يمكن التصفية حسب الفرع.',
        tags: ['Member Health Profiles'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, description: 'تصفية حسب معرف الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (أو "all" لجلب الكل بدون ترقيم)', schema: new OA\Schema(type: 'string', example: '15'))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم الاسترجاع بنجاح')]
    public function index(Request $request)
    {
        $query = MemberHealthProfile::with('member');

        if ($request->has('branch_id')) {
            $query->whereHas('member', function($q) use ($request) {
                $q->where('branch_id', $request->input('branch_id'));
            });
        }

        if ($request->has('per_page') && $request->input('per_page') !== 'all') {
            $perPage = min(max((int) $request->input('per_page'), 1), 100);
            $profiles = $query->latest()->paginate($perPage);
        } else {
            $profiles = $query->latest()->get();
        }

        return $this->successResponse($profiles, __('Health profiles retrieved successfully'));
    }

    #[OA\Post(
        path: '/v1/member/health-profiles',
        summary: '➕ إنشاء سجل صحي جديد',
        description: 'إنشاء سجل صحي جديد لعضو باستخدام معرف العضو (member_id).',
        tags: ['Member Health Profiles'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['member_id'],
            properties: [
                new OA\Property(property: 'member_id', type: 'integer', example: 1),
                new OA\Property(property: 'allergies', type: 'string', example: 'حساسية من البنسلين'),
                new OA\Property(property: 'blood_type', type: 'string', example: 'O+'),
                new OA\Property(property: 'sport_goal', type: 'string', example: 'خسارة الوزن')
            ]
        )
    )]
    #[OA\Response(response: 201, description: '✅ تم إنشاء السجل الصحي بنجاح')]
    public function store(StoreMemberHealthProfileRequest $request)
    {
        $data = $request->validated();
        $profile = MemberHealthProfile::create($data);
        return $this->successResponse($profile, __('Health profile created successfully'), 201);
    }

    #[OA\Get(
        path: '/v1/member/health-profiles/{id}',
        summary: '🔍 جلب سجل صحي محدد',
        description: 'استرجاع تفاصيل سجل صحي معين بواسطة معرف السجل.',
        tags: ['Member Health Profiles'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف السجل الصحي', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم استرجاع السجل الصحي بنجاح')]
    #[OA\Response(response: 404, description: '❌ السجل غير موجود')]
    public function show($id)
    {
        $profile = MemberHealthProfile::with('member')->findOrFail($id);
        return $this->successResponse($profile, __('Health profile retrieved successfully'));
    }

    #[OA\Put(
        path: '/v1/member/health-profiles/{id}',
        summary: '📝 تعديل سجل صحي',
        description: 'تعديل بيانات سجل صحي محدد.',
        tags: ['Member Health Profiles'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف السجل الصحي', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'allergies', type: 'string', example: 'حساسية من البنسلين'),
                new OA\Property(property: 'blood_type', type: 'string', example: 'A+'),
                new OA\Property(property: 'sport_goal', type: 'string', example: 'بناء العضلات')
            ]
        )
    )]
    #[OA\Response(response: 200, description: '✅ تم التعديل بنجاح')]
    #[OA\Response(response: 404, description: '❌ السجل غير موجود')]
    public function update(UpdateMemberHealthProfileRequest $request, $id)
    {
        $profile = MemberHealthProfile::findOrFail($id);
        
        // Remove member_id from validated data to prevent updating it if we don't want to
        $data = $request->validated();
        if (isset($data['member_id'])) {
            unset($data['member_id']);
        }

        $profile->update($data);
        return $this->successResponse($profile, __('Health profile updated successfully'));
    }

    #[OA\Delete(
        path: '/v1/member/health-profiles/{id}',
        summary: '🗑️ حذف سجل صحي',
        description: 'حذف سجل صحي محدد.',
        tags: ['Member Health Profiles'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'معرف السجل الصحي', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم الحذف بنجاح')]
    #[OA\Response(response: 404, description: '❌ السجل غير موجود')]
    public function destroy($id)
    {
        $profile = MemberHealthProfile::findOrFail($id);
        $profile->delete();
        return $this->successResponse(null, __('Health profile deleted successfully'));
    }
}
