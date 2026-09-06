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
        summary: '🏥 جلب جميع السجلات الصحية للأعضاء',
        description: 'استرجاع قائمة السجلات الصحية لجميع الأعضاء مع إمكانية التصفية حسب معرف الفرع (branch_id) والترقيم الصفحات.',
        tags: ['Member Health Profiles'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'branch_id',
        in: 'query',
        required: false,
        description: 'تصفية السجلات الصحية حسب معرف الفرع الذي ينتمي إليه العضو',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        required: false,
        description: 'عدد العناصر في كل صفحة (أو اكتب "all" لجلب كل السجلات بدون ترقيم صفحات)',
        schema: new OA\Schema(type: 'string', example: '15')
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        required: false,
        description: 'رقم الصفحة المستهدفة عند الترقيم',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع قائمة السجلات الصحية بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Health profiles retrieved successfully',
                'data' => [
                    'current_page' => 1,
                    'data' => [
                        [
                            'id' => 1,
                            'member_id' => 10,
                            'allergies' => 'حساسية من البنسلين والغبار',
                            'organic_diseases' => 'ارتفاع ضغط الدم',
                            'physical_injuries' => 'اصابة سابقة في الركبة اليمنى',
                            'medications' => 'أملوديبين 5 ملغ',
                            'blood_type' => 'O+',
                            'emergency_contact_name' => 'محمد أحمد',
                            'emergency_contact_country_code' => '+966',
                            'emergency_contact_phone' => '501234567',
                            'sport_goal' => 'خسارة الوزن وتحسين اللياقة البدنية',
                            'fitness_level' => 'intermediate',
                            'created_at' => '2026-01-15T10:30:00.000000Z',
                            'updated_at' => '2026-01-15T10:30:00.000000Z',
                            'deleted_at' => null,
                            'member' => [
                                'id' => 10,
                                'first_name' => 'أحمد',
                                'last_name' => 'محمود',
                                'email' => 'ahmed@example.com',
                                'phone_number' => '501234567',
                                'branch_id' => 1
                            ]
                        ]
                    ],
                    'first_page_url' => 'http://localhost:8000/api/v1/member/health-profiles?page=1',
                    'from' => 1,
                    'last_page' => 1,
                    'last_page_url' => 'http://localhost:8000/api/v1/member/health-profiles?page=1',
                    'next_page_url' => null,
                    'path' => 'http://localhost:8000/api/v1/member/health-profiles',
                    'per_page' => 15,
                    'prev_page_url' => null,
                    'to' => 1,
                    'total' => 1
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح - رمز المرور مفقود أو غير صالحة',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Unauthenticated.'
            ]
        )
    )]
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
        summary: '➕ إنشاء سجل صحي جديد لعضو',
        description: 'إنشاء سجل صحي جديد ومعلومات طبية ولياقية لعضو مسمى بـ member_id.',
        tags: ['Member Health Profiles'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        description: 'بيانات السجل الصحي الجديد للعضو',
        content: new OA\JsonContent(
            required: ['member_id'],
            properties: [
                new OA\Property(property: 'member_id', type: 'integer', example: 10, description: 'معرف العضو (مطلوب وموجود في جدول الأعضاء)'),
                new OA\Property(property: 'allergies', type: 'string', nullable: true, example: 'حساسية من البنسلين', description: 'أي حساسيات صحية أدوية أو أطعمة'),
                new OA\Property(property: 'organic_diseases', type: 'string', nullable: true, example: 'ارتفاع ضغط الدم', description: 'أمراض مزمنة أو عضوية'),
                new OA\Property(property: 'physical_injuries', type: 'string', nullable: true, example: 'اصابة سابقة في الركبة', description: 'إصابات جسدية سابقة أو حالية'),
                new OA\Property(property: 'medications', type: 'string', nullable: true, example: 'أملوديبين 5 ملغ', description: 'الأدوية الحالية المستعملة'),
                new OA\Property(property: 'blood_type', type: 'string', nullable: true, example: 'O+', description: 'فصيلة الدم (أقصى 10 أحرف)'),
                new OA\Property(property: 'emergency_contact_name', type: 'string', nullable: true, example: 'محمد أحمد', description: 'اسم جهة اتصال الطوارئ'),
                new OA\Property(property: 'emergency_contact_country_code', type: 'string', nullable: true, example: '+966', description: 'رمز دولة هاتف الطوارئ'),
                new OA\Property(property: 'emergency_contact_phone', type: 'string', nullable: true, example: '501234567', description: 'رقم هاتف الطوارئ'),
                new OA\Property(property: 'sport_goal', type: 'string', nullable: true, example: 'خسارة الوزن وزيادة اللياقة', description: 'الهدف الرياضي للعضو'),
                new OA\Property(property: 'fitness_level', type: 'string', nullable: true, example: 'intermediate', description: 'المستوى اللياقي (beginner, intermediate, advanced)')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء السجل الصحي بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Health profile created successfully',
                'data' => [
                    'id' => 1,
                    'member_id' => 10,
                    'allergies' => 'حساسية من البنسلين',
                    'organic_diseases' => 'ارتفاع ضغط الدم',
                    'physical_injuries' => 'اصابة سابقة في الركبة',
                    'medications' => 'أملوديبين 5 ملغ',
                    'blood_type' => 'O+',
                    'emergency_contact_name' => 'محمد أحمد',
                    'emergency_contact_country_code' => '+966',
                    'emergency_contact_phone' => '501234567',
                    'sport_goal' => 'خسارة الوزن وزيادة اللياقة',
                    'fitness_level' => 'intermediate',
                    'created_at' => '2026-01-15T10:30:00.000000Z',
                    'updated_at' => '2026-01-15T10:30:00.000000Z'
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: '❌ خطأ في بيانات الإدخال (فشل التحقق)',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'The given data was invalid.',
                'errors' => [
                    'member_id' => ['The selected member id is invalid.']
                ]
            ]
        )
    )]
    public function store(StoreMemberHealthProfileRequest $request)
    {
        $data = $request->validated();
        $profile = MemberHealthProfile::create($data);
        return $this->successResponse($profile, __('Health profile created successfully'), 201);
    }

    #[OA\Get(
        path: '/v1/member/health-profiles/{id}',
        summary: '🔍 جلب سجل صحي محدد',
        description: 'استرجاع تفاصيل كاملة لسجل صحي معين عن طريق المعرف الرقمي الخاص بالسجل.',
        tags: ['Member Health Profiles'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'المعرف الرقمي للسجل الصحي',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع تفاصيل السجل الصحي بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Health profile retrieved successfully',
                'data' => [
                    'id' => 1,
                    'member_id' => 10,
                    'allergies' => 'حساسية من البنسلين',
                    'organic_diseases' => 'ارتفاع ضغط الدم',
                    'physical_injuries' => 'اصابة سابقة في الركبة',
                    'medications' => 'أملوديبين 5 ملغ',
                    'blood_type' => 'O+',
                    'emergency_contact_name' => 'محمد أحمد',
                    'emergency_contact_country_code' => '+966',
                    'emergency_contact_phone' => '501234567',
                    'sport_goal' => 'خسارة الوزن وزيادة اللياقة',
                    'fitness_level' => 'intermediate',
                    'created_at' => '2026-01-15T10:30:00.000000Z',
                    'updated_at' => '2026-01-15T10:30:00.000000Z',
                    'member' => [
                        'id' => 10,
                        'first_name' => 'أحمد',
                        'last_name' => 'محمود',
                        'email' => 'ahmed@example.com',
                        'phone_number' => '501234567',
                        'branch_id' => 1
                    ]
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: '❌ السجل الصحي غير موجود',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Resource not found'
            ]
        )
    )]
    public function show($id)
    {
        $profile = MemberHealthProfile::with('member')->findOrFail($id);
        return $this->successResponse($profile, __('Health profile retrieved successfully'));
    }

    #[OA\Put(
        path: '/v1/member/health-profiles/{id}',
        summary: '📝 تعديل سجل صحي',
        description: 'تحديث بيانات وعناوين السجل الصحي لعضو محدد بواسطة معرف السجل.',
        tags: ['Member Health Profiles'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'المعرف الرقمي للسجل الصحي المراد تعديله',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        description: 'البيانات الصحية المراد تحديثها',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'allergies', type: 'string', nullable: true, example: 'حساسية من البنسلين والغبار', description: 'الحساسيات الصحية'),
                new OA\Property(property: 'organic_diseases', type: 'string', nullable: true, example: 'لا يوجد', description: 'الأمراض المزمنة والعضوية'),
                new OA\Property(property: 'physical_injuries', type: 'string', nullable: true, example: 'تم التنسيق وعلاج إصابة الركبة', description: 'الإصابات الجسدية'),
                new OA\Property(property: 'medications', type: 'string', nullable: true, example: 'لا يوجد أدوية منتظمة', description: 'الأدوية المستعملة'),
                new OA\Property(property: 'blood_type', type: 'string', nullable: true, example: 'A+', description: 'فصيلة الدم'),
                new OA\Property(property: 'emergency_contact_name', type: 'string', nullable: true, example: 'علي محمود', description: 'اسم جهة اتصال الطوارئ'),
                new OA\Property(property: 'emergency_contact_country_code', type: 'string', nullable: true, example: '+966', description: 'رمز دولة هاتف الطوارئ'),
                new OA\Property(property: 'emergency_contact_phone', type: 'string', nullable: true, example: '509876543', description: 'رقم هاتف الطوارئ'),
                new OA\Property(property: 'sport_goal', type: 'string', nullable: true, example: 'بناء العضلات واللياقة العالية', description: 'الهدف الرياضي'),
                new OA\Property(property: 'fitness_level', type: 'string', nullable: true, example: 'advanced', description: 'المستوى اللياقي (beginner, intermediate, advanced)')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تعديل السجل الصحي بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Health profile updated successfully',
                'data' => [
                    'id' => 1,
                    'member_id' => 10,
                    'allergies' => 'حساسية من البنسلين والغبار',
                    'organic_diseases' => 'لا يوجد',
                    'physical_injuries' => 'تم التنسيق وعلاج إصابة الركبة',
                    'medications' => 'لا يوجد أدوية منتظمة',
                    'blood_type' => 'A+',
                    'emergency_contact_name' => 'علي محمود',
                    'emergency_contact_country_code' => '+966',
                    'emergency_contact_phone' => '509876543',
                    'sport_goal' => 'بناء العضلات واللياقة العالية',
                    'fitness_level' => 'advanced',
                    'created_at' => '2026-01-15T10:30:00.000000Z',
                    'updated_at' => '2026-01-16T12:00:00.000000Z'
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: '❌ السجل الصحي غير موجود',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Resource not found'
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: '❌ خطأ في بيانات الإدخال (فشل التحقق)',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'The given data was invalid.',
                'errors' => [
                    'blood_type' => ['The blood type must not be greater than 10 characters.']
                ]
            ]
        )
    )]
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
        description: 'حذف سجل صحي محدد بواسطة معرف السجل.',
        tags: ['Member Health Profiles'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'المعرف الرقمي للسجل الصحي المراد حذفه',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم حذف السجل الصحي بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Health profile deleted successfully',
                'data' => null
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: '❌ السجل الصحي غير موجود',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Resource not found'
            ]
        )
    )]
    public function destroy($id)
    {
        $profile = MemberHealthProfile::findOrFail($id);
        $profile->delete();
        return $this->successResponse(null, __('Health profile deleted successfully'));
    }
}
