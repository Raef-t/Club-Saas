<?php

namespace Modules\MemberManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\MemberManager\Models\MemberMeasurement;
use Modules\MemberManager\Http\Requests\AddPlayerMeasurementRequest;
use Modules\MemberManager\Services\MemberMeasurementReportService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class MemberMeasurementController extends BaseController
{
    protected MemberMeasurementReportService $reportService;

    public function __construct(MemberMeasurementReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    #[OA\Get(
        path: '/v1/member/measurements',
        summary: '📏 جلب جميع سجلات القياسات البدنية',
        description: 'استرجاع جميع سجلات القياسات البدنية للأعضاء مع إمكانية التصفية حسب العضو أو الفرع والترقيم الصفحات.',
        tags: ['Member Measurements'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'member_id',
        in: 'query',
        required: false,
        description: 'تصفية القياسات البدنية لعضو محدد عن طريق معرف العضو (member_id)',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Parameter(
        name: 'branch_id',
        in: 'query',
        required: false,
        description: 'تصفية القياسات البدنية حسب معرف الفرع',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        required: false,
        description: 'عدد العناصر في كل صفحة (أو "all" لجلب كافة السجلات بدون ترقيم صفحات)',
        schema: new OA\Schema(type: 'string', example: '15')
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        required: false,
        description: 'رقم الصفحة المستهدفة',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع قائمة القياسات البدنية بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Measurements retrieved successfully',
                'data' => [
                    'current_page' => 1,
                    'data' => [
                        [
                            'id' => 1,
                            'member_id' => 10,
                            'measurement_date' => '2026-01-15',
                            'weight' => 75.5,
                            'height' => 178,
                            'body_fat_percentage' => 18.5,
                            'muscle_mass' => 61.53,
                            'waist_circumference' => 85.0,
                            'neck_circumference' => 40.0,
                            'shoulder_circumference' => 110.0,
                            'right_bicep' => 35.0,
                            'left_bicep' => 35.0,
                            'hip_circumference' => 95.0,
                            'chest_circumference' => 100.0,
                            'right_thigh_mid' => 55.0,
                            'left_thigh' => 55.0,
                            'right_calf' => 38.0,
                            'left_calf' => 38.0,
                            'fat_free_mass_percentage' => 81.5,
                            'bmi' => 23.83,
                            'body_water_percentage' => 59.68,
                            'resting_metabolic_rate' => 1717.5,
                            'total_daily_energy_expenditure' => 2662.13,
                            'physical_activity_level' => 'medium',
                            'buttocks_circumference' => 100.0,
                            'above_right_knee' => 40.0,
                            'above_left_knee' => 40.0,
                            'created_at' => '2026-01-15T10:30:00.000000Z',
                            'updated_at' => '2026-01-15T10:30:00.000000Z',
                            'deleted_at' => null,
                            'last_updated_at' => '2026-01-15T10:30:00.000000Z',
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
                    'first_page_url' => 'http://localhost:8000/api/v1/member/measurements?page=1',
                    'from' => 1,
                    'last_page' => 1,
                    'last_page_url' => 'http://localhost:8000/api/v1/member/measurements?page=1',
                    'next_page_url' => null,
                    'path' => 'http://localhost:8000/api/v1/member/measurements',
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
        $query = MemberMeasurement::with('member');
        
        if ($request->has('member_id')) {
            $query->where('member_id', $request->input('member_id'));
        }

        if ($request->has('branch_id')) {
            $query->whereHas('member', function($q) use ($request) {
                $q->where('branch_id', $request->input('branch_id'));
            });
        }

        if ($request->has('per_page') && $request->input('per_page') !== 'all') {
            $perPage = min(max((int) $request->input('per_page'), 1), 100);
            $measurements = $query->latest()->paginate($perPage);
            foreach ($measurements->items() as $m) {
                $m->setAttribute('last_updated_at', $m->updated_at);
            }
        } else {
            $measurements = $query->latest()->get();
            foreach ($measurements as $m) {
                $m->setAttribute('last_updated_at', $m->updated_at);
            }
        }
        
        return $this->successResponse($measurements, __('Measurements retrieved successfully'));
    }

    #[OA\Post(
        path: '/v1/member/measurements',
        summary: '➕ إضافة سجل قياس بدني جديد',
        description: 'إضافة سجل قياس بدني جديد لعضو. يتم حساب المؤشرات الطبية والتغذوية الحيوية تلقائياً (BMI, BMR, TDEE, Body Fat %, Muscle Mass, Body Water %).',
        tags: ['Member Measurements'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        description: 'بيانات القياسات البدنية الجديدة للعضو',
        content: new OA\JsonContent(
            required: ['member_id', 'weight'],
            properties: [
                new OA\Property(property: 'member_id', type: 'integer', example: 10, description: 'معرف العضو (مطلوب وموجود بجدول الأعضاء)'),
                new OA\Property(property: 'measurement_date', type: 'string', format: 'date', example: '2026-01-15', description: 'تاريخ إخضاع العضو للقياس (افتراضياً اليوم)'),
                new OA\Property(property: 'weight', type: 'number', format: 'float', example: 75.5, description: 'الوزن بالكيلوغرام (مطلوب، بين 20 و 300)'),
                new OA\Property(property: 'height', type: 'number', format: 'float', example: 178, description: 'الطول بالسنتيمتر (بين 50 و 250)'),
                new OA\Property(property: 'neck_circumference', type: 'number', format: 'float', example: 40.0, description: 'محيط الرقبة (سم)'),
                new OA\Property(property: 'shoulder_circumference', type: 'number', format: 'float', example: 110.0, description: 'محيط الكتفين (سم)'),
                new OA\Property(property: 'chest_circumference', type: 'number', format: 'float', example: 100.0, description: 'محيط الصدر (سم)'),
                new OA\Property(property: 'waist_circumference', type: 'number', format: 'float', example: 85.0, description: 'محيط الخصر (سم)'),
                new OA\Property(property: 'hip_circumference', type: 'number', format: 'float', example: 95.0, description: 'محيط الورك (سم)'),
                new OA\Property(property: 'buttocks_circumference', type: 'number', format: 'float', example: 100.0, description: 'محيط المؤخرة (سم)'),
                new OA\Property(property: 'right_thigh_mid', type: 'number', format: 'float', example: 55.0, description: 'منتصف الفخذ الأيمن (سم)'),
                new OA\Property(property: 'left_thigh', type: 'number', format: 'float', example: 55.0, description: 'منتصف الفخذ الأيسر (سم)'),
                new OA\Property(property: 'above_right_knee', type: 'number', format: 'float', example: 40.0, description: 'فوق الركبة اليمنى (سم)'),
                new OA\Property(property: 'above_left_knee', type: 'number', format: 'float', example: 40.0, description: 'فوق الركبة اليسرى (سم)'),
                new OA\Property(property: 'right_calf', type: 'number', format: 'float', example: 38.0, description: 'محيط الساق اليمنى (سم)'),
                new OA\Property(property: 'left_calf', type: 'number', format: 'float', example: 38.0, description: 'محيط الساق اليسرى (سم)'),
                new OA\Property(property: 'right_bicep', type: 'number', format: 'float', example: 35.0, description: 'عضلة البايسبس اليمنى (سم)'),
                new OA\Property(property: 'left_bicep', type: 'number', format: 'float', example: 35.0, description: 'عضلة البايسبس اليسرى (سم)'),
                new OA\Property(property: 'arm_circumference', type: 'number', format: 'float', example: 32.0, description: 'محيط الذراع (سم)'),
                new OA\Property(
                    property: 'physical_activity_level',
                    type: 'string',
                    enum: ['sedentary', 'light', 'medium', 'high', 'extreme'],
                    example: 'medium',
                    description: 'مستوى النشاط البدني اليومي (sedentary, light, medium, high, extreme)'
                )
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إضافة سجل القياس البدني وحساب المؤشرات الحيوية بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Measurement added successfully',
                'data' => [
                    'id' => 1,
                    'member_id' => 10,
                    'measurement_date' => '2026-01-15',
                    'weight' => 75.5,
                    'height' => 178,
                    'neck_circumference' => 40.0,
                    'shoulder_circumference' => 110.0,
                    'chest_circumference' => 100.0,
                    'waist_circumference' => 85.0,
                    'hip_circumference' => 95.0,
                    'buttocks_circumference' => 100.0,
                    'right_thigh_mid' => 55.0,
                    'left_thigh' => 55.0,
                    'above_right_knee' => 40.0,
                    'above_left_knee' => 40.0,
                    'right_calf' => 38.0,
                    'left_calf' => 38.0,
                    'right_bicep' => 35.0,
                    'left_bicep' => 35.0,
                    'physical_activity_level' => 'medium',
                    'bmi' => 23.83,
                    'resting_metabolic_rate' => 1717.5,
                    'total_daily_energy_expenditure' => 2662.13,
                    'body_fat_percentage' => 18.5,
                    'fat_free_mass_percentage' => 81.5,
                    'muscle_mass' => 61.53,
                    'body_water_percentage' => 59.68,
                    'created_at' => '2026-01-15T10:30:00.000000Z',
                    'updated_at' => '2026-01-15T10:30:00.000000Z',
                    'last_updated_at' => '2026-01-15T10:30:00.000000Z'
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: '❌ خطأ في التحقق من صحة المدخلات',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'The given data was invalid.',
                'errors' => [
                    'weight' => ['The weight field is required.'],
                    'member_id' => ['The selected member id is invalid.']
                ]
            ]
        )
    )]
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
        
        // Calculate dynamic fields
        $data = $this->calculateMeasurements($data, $data['member_id']);
        
        $measurement = MemberMeasurement::create($data);
        $measurement->setAttribute('last_updated_at', $measurement->updated_at);
        
        return $this->successResponse($measurement, __('Measurement added successfully'), 201);
    }

    #[OA\Get(
        path: '/v1/member/measurements/{id}',
        summary: '🔍 عرض سجل قياس محدد',
        description: 'استرجاع التفاصيل الكاملة والمؤشرات الحيوية لسجل قياس محدد عن طريق معرف القياس (ID).',
        tags: ['Member Measurements'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'المعرف الرقمي لسجل القياس البدني',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع سجل القياس بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Measurement retrieved successfully',
                'data' => [
                    'id' => 1,
                    'member_id' => 10,
                    'measurement_date' => '2026-01-15',
                    'weight' => 75.5,
                    'height' => 178,
                    'body_fat_percentage' => 18.5,
                    'muscle_mass' => 61.53,
                    'waist_circumference' => 85.0,
                    'neck_circumference' => 40.0,
                    'shoulder_circumference' => 110.0,
                    'right_bicep' => 35.0,
                    'left_bicep' => 35.0,
                    'hip_circumference' => 95.0,
                    'chest_circumference' => 100.0,
                    'right_thigh_mid' => 55.0,
                    'left_thigh' => 55.0,
                    'right_calf' => 38.0,
                    'left_calf' => 38.0,
                    'fat_free_mass_percentage' => 81.5,
                    'bmi' => 23.83,
                    'body_water_percentage' => 59.68,
                    'resting_metabolic_rate' => 1717.5,
                    'total_daily_energy_expenditure' => 2662.13,
                    'physical_activity_level' => 'medium',
                    'buttocks_circumference' => 100.0,
                    'above_right_knee' => 40.0,
                    'above_left_knee' => 40.0,
                    'created_at' => '2026-01-15T10:30:00.000000Z',
                    'updated_at' => '2026-01-15T10:30:00.000000Z',
                    'last_updated_at' => '2026-01-15T10:30:00.000000Z',
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
        description: '❌ سجل القياس غير موجود',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Resource not found'
            ]
        )
    )]
    public function show($id)
    {
        $measurement = MemberMeasurement::with('member')->findOrFail($id);
        $measurement->setAttribute('last_updated_at', $measurement->updated_at);
        
        return $this->successResponse($measurement, __('Measurement retrieved successfully'));
    }

    #[OA\Put(
        path: '/v1/member/measurements/{id}',
        summary: '📝 تحديث سجل قياس بدني',
        description: 'تعديل بيانات قياس بدني محدد وإعادة حساب النسب الطبية تلقائياً.',
        tags: ['Member Measurements'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'المعرف الرقمي لسجل القياس البدني المراد تحديثه',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        description: 'القيم المراد تحديثها في القياس البدني',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'measurement_date', type: 'string', format: 'date', example: '2026-01-20', description: 'تاريخ القياس'),
                new OA\Property(property: 'weight', type: 'number', format: 'float', example: 74.0, description: 'الوزن المعدل بالكيلوغرام'),
                new OA\Property(property: 'height', type: 'number', format: 'float', example: 178, description: 'الطول بالسنتيمتر'),
                new OA\Property(property: 'neck_circumference', type: 'number', format: 'float', example: 39.5, description: 'محيط الرقبة'),
                new OA\Property(property: 'shoulder_circumference', type: 'number', format: 'float', example: 110.0, description: 'محيط الكتفين'),
                new OA\Property(property: 'chest_circumference', type: 'number', format: 'float', example: 99.0, description: 'محيط الصدر'),
                new OA\Property(property: 'waist_circumference', type: 'number', format: 'float', example: 84.0, description: 'محيط الخصر'),
                new OA\Property(property: 'hip_circumference', type: 'number', format: 'float', example: 94.0, description: 'محيط الورك'),
                new OA\Property(property: 'buttocks_circumference', type: 'number', format: 'float', example: 99.0, description: 'محيط المؤخرة'),
                new OA\Property(property: 'right_thigh_mid', type: 'number', format: 'float', example: 54.0, description: 'منتصف الفخذ الأيمن'),
                new OA\Property(property: 'left_thigh', type: 'number', format: 'float', example: 54.0, description: 'منتصف الفخذ الأيسر'),
                new OA\Property(property: 'above_right_knee', type: 'number', format: 'float', example: 39.0, description: 'فوق الركبة اليمنى'),
                new OA\Property(property: 'above_left_knee', type: 'number', format: 'float', example: 39.0, description: 'فوق الركبة اليسرى'),
                new OA\Property(property: 'right_calf', type: 'number', format: 'float', example: 37.0, description: 'محيط الساق اليمنى'),
                new OA\Property(property: 'left_calf', type: 'number', format: 'float', example: 37.0, description: 'محيط الساق اليسرى'),
                new OA\Property(property: 'right_bicep', type: 'number', format: 'float', example: 36.0, description: 'البايسبس الأيمن'),
                new OA\Property(property: 'left_bicep', type: 'number', format: 'float', example: 36.0, description: 'البايسبس الأيسر'),
                new OA\Property(property: 'arm_circumference', type: 'number', format: 'float', example: 33.0, description: 'محيط الذراع'),
                new OA\Property(property: 'physical_activity_level', type: 'string', enum: ['sedentary', 'light', 'medium', 'high', 'extreme'], example: 'medium', description: 'مستوى النشاط البدني')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تحديث سجل القياس وإعادة احتساب المؤشرات بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Measurement updated successfully',
                'data' => [
                    'id' => 1,
                    'member_id' => 10,
                    'measurement_date' => '2026-01-20',
                    'weight' => 74.0,
                    'height' => 178,
                    'body_fat_percentage' => 17.8,
                    'muscle_mass' => 60.83,
                    'waist_circumference' => 84.0,
                    'neck_circumference' => 39.5,
                    'shoulder_circumference' => 110.0,
                    'right_bicep' => 36.0,
                    'left_bicep' => 36.0,
                    'fat_free_mass_percentage' => 82.2,
                    'bmi' => 23.36,
                    'body_water_percentage' => 60.19,
                    'resting_metabolic_rate' => 1702.5,
                    'total_daily_energy_expenditure' => 2638.88,
                    'physical_activity_level' => 'medium',
                    'created_at' => '2026-01-15T10:30:00.000000Z',
                    'updated_at' => '2026-01-20T11:15:00.000000Z',
                    'last_updated_at' => '2026-01-20T11:15:00.000000Z'
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: '❌ سجل القياس غير موجود',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Resource not found'
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: '❌ خطأ في التحقق من المدخلات',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'The given data was invalid.',
                'errors' => [
                    'weight' => ['The weight must be between 20 and 300.']
                ]
            ]
        )
    )]
    public function update(AddPlayerMeasurementRequest $request, $id)
    {
        $measurement = MemberMeasurement::findOrFail($id);
        
        $data = $request->validated();
        if (isset($data['member_id'])) {
            unset($data['member_id']); // Prevent changing the owner
        }

        // Calculate dynamic fields
        $data = $this->calculateMeasurements($data, $measurement->member_id, $measurement);

        $measurement->update($data);
        $measurement->setAttribute('last_updated_at', $measurement->updated_at);
        
        return $this->successResponse($measurement, __('Measurement updated successfully'));
    }

    #[OA\Delete(
        path: '/v1/member/measurements/{id}',
        summary: '🗑️ حذف سجل قياس بدني',
        description: 'حذف سجل قياس بدني محدد عن طريق معرف القياس (ID).',
        tags: ['Member Measurements'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'المعرف الرقمي لسجل القياس المراد حذفه',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم حذف سجل القياس بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Measurement deleted successfully',
                'data' => null
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: '❌ سجل القياس غير موجود',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Resource not found'
            ]
        )
    )]
    public function destroy($id)
    {
        $measurement = MemberMeasurement::findOrFail($id);
        $measurement->delete();
        return $this->successResponse(null, __('Measurement deleted successfully'));
    }
    
    /**
     * حساب البيانات المرتبطة بالقياسات (BMI، نسبة الدهون، السعرات، إلخ).
     */
    private function calculateMeasurements(array $data, $member_id, $existingMeasurement = null)
    {
        $member = \Modules\MemberManager\Models\Member::with('person')->find($member_id);
        if (!$member) return $data;
        
        $gender = $member->person->gender ?? 'male';
        $age = $member->person->dob ? \Carbon\Carbon::parse($member->person->dob)->age : 30;

        // Merge with existing if updating
        $mergedData = $data;
        if ($existingMeasurement) {
            $mergedData = array_merge($existingMeasurement->toArray(), $data);
        }

        $weight = $mergedData['weight'] ?? null;
        $height = $mergedData['height'] ?? null;
        
        if ($weight && $height) {
            // BMI
            $heightInMeters = $height / 100;
            if ($heightInMeters > 0) {
                $data['bmi'] = round($weight / ($heightInMeters * $heightInMeters), 2);
            }

            // BMR (Mifflin-St Jeor)
            if ($gender === 'male') {
                $data['resting_metabolic_rate'] = round((10 * $weight) + (6.25 * $height) - (5 * $age) + 5, 2);
            } else {
                $data['resting_metabolic_rate'] = round((10 * $weight) + (6.25 * $height) - (5 * $age) - 161, 2);
            }

            // TDEE
            $activityLevel = $mergedData['physical_activity_level'] ?? ($mergedData['activity_level'] ?? 'sedentary');
            $activityMultipliers = [
                'sedentary' => 1.2,
                'light' => 1.375,
                'medium' => 1.55,
                'high' => 1.725,
                'extreme' => 1.9
            ];
            $activityLevel = strtolower($activityLevel);
            $multiplier = $activityMultipliers[$activityLevel] ?? 1.2;
            $data['total_daily_energy_expenditure'] = round($data['resting_metabolic_rate'] * $multiplier, 2);
        }

        // Body Fat Percentage (US Navy Method)
        $waist = $mergedData['waist_circumference'] ?? null;
        $neck = $mergedData['neck_circumference'] ?? null;
        $hip = $mergedData['hip_circumference'] ?? null;

        if ($weight && $height && $waist && $neck) {
            if ($gender === 'male') {
                $diff = $waist - $neck;
                if ($diff > 0) {
                    $bodyFat = 495 / (1.0324 - 0.19077 * log10($diff) + 0.15456 * log10($height)) - 450;
                    $data['body_fat_percentage'] = round(max(2, min($bodyFat, 60)), 2);
                }
            } else {
                if ($hip) {
                    $diff = $waist + $hip - $neck;
                    if ($diff > 0) {
                        $bodyFat = 495 / (1.29579 - 0.35004 * log10($diff) + 0.22100 * log10($height)) - 450;
                        $data['body_fat_percentage'] = round(max(5, min($bodyFat, 70)), 2);
                    }
                }
            }
        }

        // Fat Free Mass and Body Water
        if (isset($data['body_fat_percentage']) && $weight) {
            $fatFreeMass = $weight - ($weight * ($data['body_fat_percentage'] / 100));
            $data['fat_free_mass_percentage'] = round(100 - $data['body_fat_percentage'], 2);
            $data['muscle_mass'] = round($fatFreeMass, 2); 
            $waterWeight = $fatFreeMass * 0.732;
            $data['body_water_percentage'] = round(($waterWeight / $weight) * 100, 2);
        }

        return $data;
    }

    #[OA\Get(
        path: '/v1/member/measurements/report',
        summary: '📊 تقرير القياسات البدنية الشهري المقارن',
        description: 'استرجاع التقرير الشهري للقياسات البدنية للاعب بين تاريخين مع ترحيل القيم التلقائي للأشهر التي لم يسجل بها قياسات.',
        tags: ['Member Measurements'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'member_id',
        in: 'query',
        required: true,
        description: 'معرف العضو (ID) المطلوبة دراسة تقرير قياساته',
        schema: new OA\Schema(type: 'integer', example: 10)
    )]
    #[OA\Parameter(
        name: 'from_date',
        in: 'query',
        required: false,
        description: 'تاريخ البداية للنطاق المعتمد في التقرير (YYYY-MM-DD)',
        schema: new OA\Schema(type: 'string', format: 'date', example: '2026-01-01')
    )]
    #[OA\Parameter(
        name: 'to_date',
        in: 'query',
        required: false,
        description: 'تاريخ النهاية للنطاق المعتمد في التقرير (YYYY-MM-DD)',
        schema: new OA\Schema(type: 'string', format: 'date', example: '2026-06-30')
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استخراج التقرير المقارن بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Physical measurement report generated successfully',
                'data' => [
                    'member' => [
                        'id' => 10,
                        'first_name' => 'أحمد',
                        'last_name' => 'محمود',
                        'email' => 'ahmed@example.com'
                    ],
                    'period' => [
                        'from_date' => '2026-01-01',
                        'to_date' => '2026-06-30'
                    ],
                    'monthly_data' => [
                        [
                            'month' => '2026-01',
                            'weight' => 75.5,
                            'bmi' => 23.83,
                            'body_fat_percentage' => 18.5,
                            'muscle_mass' => 61.53,
                            'waist_circumference' => 85.0
                        ],
                        [
                            'month' => '2026-02',
                            'weight' => 74.0,
                            'bmi' => 23.36,
                            'body_fat_percentage' => 17.8,
                            'muscle_mass' => 60.83,
                            'waist_circumference' => 84.0
                        ]
                    ]
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: '❌ خطأ في التحقق من قيم المعلمات (تاريخ النهاية يسبق تاريخ البداية أو معرف العضو غير موجود)',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'The given data was invalid.',
                'errors' => [
                    'to_date' => ['The to date field must be a date after or equal to from date.']
                ]
            ]
        )
    )]
    public function report(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|integer|exists:members,id',
            'from_date' => 'nullable|date',
            'to_date'   => 'nullable|date|after_or_equal:from_date',
        ]);

        $memberId = (int) $validated['member_id'];
        $fromDate = $validated['from_date'] ?? null;
        $toDate   = $validated['to_date'] ?? null;

        $data = $this->reportService->generateMonthlyReport($memberId, $fromDate, $toDate);

        return $this->successResponse($data, __('Physical measurement report generated successfully'));
    }
}
