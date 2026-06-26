<?php

namespace Modules\Authentication\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Authentication\Models\Person;
use Modules\Authentication\Models\User;
use Modules\Core\Http\Controllers\Api\BaseController;
use OpenApi\Attributes as OA;
use Modules\Authentication\Http\Requests\RegisterPersonRequest;

class PeopleController extends BaseController
{
    #[OA\Post(
        path: '/v1/people',
        summary: '👥 التسجيل الذكي للأعضاء والموظفين',
        description: 'إنشاء ملف شخصي (Person) وملف مخصص (لاعب، مدرب، أو موظف) وحساب مستخدم (غير مفعل) في عملية واحدة متكاملة.',
        tags: ['People Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        description: 'التفاصيل الخاصة بالشخص وملفه الشخصي',
        content: new OA\JsonContent(
            required: ['full_name', 'type', 'mobile_1'],
            properties: [
                new OA\Property(property: 'full_name', type: 'string', description: 'الاسم الكامل للشخص', example: 'محمد أحمد'),
                new OA\Property(property: 'type', type: 'string', enum: ['player', 'coach', 'staff'], description: 'نوع الدور', example: 'player'),
                new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], description: 'الجنس', example: 'male'),
                new OA\Property(property: 'dob', type: 'string', format: 'date', description: 'تاريخ الميلاد (YYYY-MM-DD)', example: '2005-06-15'),
                new OA\Property(property: 'mobile_1', type: 'string', description: 'رقم الهاتف الأساسي', example: '0512345678'),
                new OA\Property(property: 'email', type: 'string', format: 'email', description: 'البريد الإلكتروني', example: 'player@example.com'),
                new OA\Property(property: 'password', type: 'string', description: 'كلمة المرور للحساب الجديد (الافتراضي: 123456)', example: 'pass123'),
                new OA\Property(
                    property: 'profile_data',
                    type: 'object',
                    description: 'بيانات محددة بناءً على حقل "النوع (type)"',
                    properties: [
                        new OA\Property(property: 'blood_type', type: 'string', description: '[للاعبين فقط] فصيلة الدم', example: 'A+'),
                        new OA\Property(property: 'specialization', type: 'string', description: '[للمدربين فقط] التخصص الرياضي', example: 'سباحة'),
                        new OA\Property(property: 'job_title', type: 'string', description: '[للموظفين فقط] المسمى الوظيفي', example: 'موظف استقبال'),
                        new OA\Property(property: 'medical_conditions', type: 'array', items: new OA\Items(type: 'string'), description: '[للاعبين فقط] قائمة بالمشاكل الصحية', example: ["ربو", "حساسية"]),
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ تم إنشاء الشخص والملف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'تم تسجيل الشخص بنجاح'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'person_id', type: 'integer', example: 50),
                        new OA\Property(property: 'username', type: 'string', example: 'mohamed50'),
                        new OA\Property(property: 'type', type: 'string', example: 'player'),
                        new OA\Property(property: 'qr_code', type: 'string', description: 'رمز الاستجابة السريعة (QR) للاعبين', example: 'QR-X1Y2Z3'),
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ غير مصرح (Unauthenticated)',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: '🚫 لا تملك صلاحية للوصول (Forbidden)',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'This action is unauthorized.'),
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: '⚠️ خطأ في التحقق من صحة البيانات',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'),
                new OA\Property(
                    property: 'errors',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'full_name', type: 'array', items: new OA\Items(type: 'string', example: 'الاسم الكامل مطلوب.')),
                        new OA\Property(property: 'type', type: 'array', items: new OA\Items(type: 'string', example: 'يجب اختيار نوع صحيح.')),
                        new OA\Property(property: 'mobile_1', type: 'array', items: new OA\Items(type: 'string', example: 'رقم الهاتف مطلوب.'))
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 500, description: '🔥 خطأ في الخادم', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'حدث خطأ داخلي في الخادم.')]))]
    public function store(RegisterPersonRequest $request)
    {
        $validated = $request->validated();

        try {
            return DB::transaction(function () use ($request) {
                // 1. Create Person
                $person = Person::create($request->only([
                    'full_name', 'gender', 'type', 'dob', 'national_id', 
                    'address', 'mobile_1', 'mobile_2', 'email'
                ]));

                // 2. Create Profile based on type
                $profile = $this->createProfile($person, $request->input('profile_data', []));

                // 3. Create User Account (Inactive by default)
                $username = $request->username ?? $this->generateUsername($person);
                $user = User::create([
                    'person_id' => $person->id,
                    'username' => $username,
                    'password' => Hash::make($request->input('password', 'password123')),
                    'is_active' => false, // Management activation required
                ]);

                return $this->successResponse([
                    'person_id' => $person->id,
                    'username' => $username,
                    'type' => $person->type,
                    'qr_code' => $profile->qr_code ?? null
                ], __('Person registered successfully'), 201);
            });
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    private function createProfile($person, $data)
    {
        switch ($person->type) {
            case 'player':
                return $person->playerProfile()->create([
                    'qr_code' => 'QR-' . strtoupper(Str::random(10)),
                    'blood_type' => $data['blood_type'] ?? null,
                    'medical_conditions' => $data['medical_conditions'] ?? [],
                    'emergency_contact' => $data['emergency_contact'] ?? [],
                ]);
            case 'coach':
                return $person->coachProfile()->create([
                    'specialization' => $data['specialization'] ?? 'General',
                    'experience_years' => $data['experience_years'] ?? 0,
                ]);
            case 'staff':
                return $person->staffProfile()->create([
                    'job_title' => $data['job_title'] ?? 'Employee',
                ]);
        }
    }

    private function generateUsername($person)
    {
        $base = strtolower(explode(' ', $person->full_name)[0]) . $person->id;
        return $base;
    }
}
