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
        summary: '📏 جلب جميع القياسات',
        description: 'استرجاع جميع سجلات القياسات. يمكن التصفية حسب العضو أو الفرع.',
        tags: ['Member Measurements'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'member_id', in: 'query', required: false, description: 'معرف العضو (ID) لعرض قياساته فقط', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: false, description: 'تصفية حسب معرف الفرع', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'عدد العناصر في الصفحة (الافتراضي: 15)', schema: new OA\Schema(type: 'integer', example: 15))]
    #[OA\Parameter(name: 'page', in: 'query', required: false, description: 'رقم الصفحة', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Response(response: 200, description: '✅ تم الاسترجاع بنجاح')]
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

        $perPage = $this->getPerPage($request);
        $measurements = $query->paginate($perPage)->through(function ($m) {
            $m->setAttribute('last_updated_at', $m->updated_at);
            return $m;
        });
        
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
                new OA\Property(property: 'measurement_date', type: 'string', format: 'date', example: '2023-10-01'),
                new OA\Property(property: 'weight', type: 'number', example: 75.5),
                new OA\Property(property: 'height', type: 'number', example: 178),
                new OA\Property(property: 'neck_circumference', type: 'number', example: 40.0),
                new OA\Property(property: 'shoulder_circumference', type: 'number', example: 110.0),
                new OA\Property(property: 'chest_circumference', type: 'number', example: 100.0),
                new OA\Property(property: 'waist_circumference', type: 'number', example: 85.0),
                new OA\Property(property: 'hip_circumference', type: 'number', example: 95.0),
                new OA\Property(property: 'buttocks_circumference', type: 'number', example: 100.0),
                new OA\Property(property: 'right_thigh_mid', type: 'number', example: 55.0),
                new OA\Property(property: 'left_thigh', type: 'number', example: 55.0),
                new OA\Property(property: 'above_right_knee', type: 'number', example: 40.0),
                new OA\Property(property: 'above_left_knee', type: 'number', example: 40.0),
                new OA\Property(property: 'right_calf', type: 'number', example: 38.0),
                new OA\Property(property: 'left_calf', type: 'number', example: 38.0),
                new OA\Property(property: 'right_bicep', type: 'number', example: 35.0),
                new OA\Property(property: 'left_bicep', type: 'number', example: 35.0),
                new OA\Property(property: 'arm_circumference', type: 'number', example: 32.0),
                new OA\Property(property: 'physical_activity_level', type: 'string', example: 'medium')
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
        
        // Calculate dynamic fields
        $data = $this->calculateMeasurements($data, $data['member_id']);
        
        $measurement = MemberMeasurement::create($data);
        $measurement->setAttribute('last_updated_at', $measurement->updated_at);
        
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
        $measurement->setAttribute('last_updated_at', $measurement->updated_at);
        
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
                new OA\Property(property: 'measurement_date', type: 'string', format: 'date', example: '2023-10-05'),
                new OA\Property(property: 'weight', type: 'number', example: 74.0),
                new OA\Property(property: 'height', type: 'number', example: 178),
                new OA\Property(property: 'neck_circumference', type: 'number', example: 39.5),
                new OA\Property(property: 'shoulder_circumference', type: 'number', example: 110.0),
                new OA\Property(property: 'chest_circumference', type: 'number', example: 99.0),
                new OA\Property(property: 'waist_circumference', type: 'number', example: 84.0),
                new OA\Property(property: 'hip_circumference', type: 'number', example: 94.0),
                new OA\Property(property: 'buttocks_circumference', type: 'number', example: 99.0),
                new OA\Property(property: 'right_thigh_mid', type: 'number', example: 54.0),
                new OA\Property(property: 'left_thigh', type: 'number', example: 54.0),
                new OA\Property(property: 'above_right_knee', type: 'number', example: 39.0),
                new OA\Property(property: 'above_left_knee', type: 'number', example: 39.0),
                new OA\Property(property: 'right_calf', type: 'number', example: 37.0),
                new OA\Property(property: 'left_calf', type: 'number', example: 37.0),
                new OA\Property(property: 'right_bicep', type: 'number', example: 36.0),
                new OA\Property(property: 'left_bicep', type: 'number', example: 36.0),
                new OA\Property(property: 'arm_circumference', type: 'number', example: 33.0),
                new OA\Property(property: 'physical_activity_level', type: 'string', example: 'medium')
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

        // Calculate dynamic fields
        $data = $this->calculateMeasurements($data, $measurement->member_id, $measurement);

        $measurement->update($data);
        $measurement->setAttribute('last_updated_at', $measurement->updated_at);
        
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
    #[OA\Parameter(name: 'member_id', in: 'query', required: true, description: 'معرف العضو (ID)', schema: new OA\Schema(type: 'integer', example: 1))]
    #[OA\Parameter(name: 'from_date', in: 'query', required: false, description: 'تاريخ البداية (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', example: '2026-01-01'))]
    #[OA\Parameter(name: 'to_date', in: 'query', required: false, description: 'تاريخ النهاية (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', example: '2026-06-30'))]
    #[OA\Response(response: 200, description: '✅ تم استخراج التقرير بنجاح')]
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
