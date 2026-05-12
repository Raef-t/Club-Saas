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

class PeopleController extends BaseController
{
    #[OA\Post(
        path: '/v1/people',
        summary: '👥 التسجيل الشامل: لاعبين، مدربين، وموظفين',
        description: "## 📘 نظرة عامة\nهذا المسار (Endpoint) ديناميكي وذكي جداً، مصمم لإدارة تسجيل الموارد البشرية بالكامل في النادي. يقوم بمعالجة إنشاء ثلاث كيانات مختلفة في عملية قاعدة بيانات واحدة:\n\n1. **كيان الشخص (Person)**: البيانات الشخصية الأساسية وتخزن في جدول `people` المركزي.\n2. **الملف الشخصي المتخصص (Profile)**: يتم إنشاء سجل متخصص بناءً على القيمة المرسلة في حقل النوع (`type`).\n3. **حساب المستخدم (User)**: يتم إنشاء حساب نظام تلقائياً مرتبط بالشخص.\n\n### 🛠 تحديد الأنواع (Types) والمنطق البرمجي:\nيجب إرسال إحدى القيم التالية بالإنجليزية حصراً في حقل `type`:\n\n- **`player`**: عند تسجيل (لاعب) جديد؛ سيقوم النظام بتوليد **QR Code** فريد وتسجيل بيانات طبية.\n- **`coach`**: عند تسجيل (مدرب)؛ سيقوم النظام بتسجيل التخصص الرياضي وسنوات الخبرة.\n- **`staff`**: عند تسجيل (موظف إداري)؛ سيقوم النظام بتسجيل المسمى الوظيفي الإداري.\n\n### 🛡 الأمان وتعدد الأندية (Tenancy):\nكل عملية تسجيل محصورة بدقة داخل معرف النادي (`X-Tenant-ID`) المرسل في الهيدر لضمان عزل البيانات.",
        tags: ['People Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'X-Tenant-ID',
        in: 'header',
        description: 'المعرف الفريد للنادي. هام جداً لعزل البيانات.',
        required: true,
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        description: 'بيانات التسجيل الكاملة. القيم التقنية (مثل player, coach) يجب أن ترسل بالإنجليزية كما هي موضح أدناه.',
        content: new OA\JsonContent(
            required: ['full_name', 'type', 'mobile_1'],
            properties: [
                new OA\Property(property: 'full_name', type: 'string', description: 'الاسم الكامل الرسمي', example: 'Mohamed Ahmed'),
                new OA\Property(property: 'type', type: 'string', enum: ['player', 'coach', 'staff'], description: 'نوع الشخص: (player) للاعب، (coach) للمدرب، (staff) للموظف.', example: 'player'),
                new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'male'),
                new OA\Property(property: 'dob', type: 'string', format: 'date', description: 'تاريخ الميلاد (YYYY-MM-DD)', example: '2005-06-15'),
                new OA\Property(property: 'mobile_1', type: 'string', description: 'رقم الجوال الأساسي', example: '0512345678'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'player@example.com'),
                new OA\Property(property: 'password', type: 'string', description: 'كلمة المرور (الافتراضية: 123456)', example: 'password123'),
                new OA\Property(
                    property: 'profile_data',
                    type: 'object',
                    description: 'بيانات الملف الشخصي (تعتمد على القيمة المرسلة في type)',
                    properties: [
                        new OA\Property(property: 'blood_type', type: 'string', description: '[للنوع player] فصيلة الدم', example: 'A+'),
                        new OA\Property(property: 'specialization', type: 'string', description: '[للنوع coach] التخصص الرياضي', example: 'Swimming'),
                        new OA\Property(property: 'job_title', type: 'string', description: '[للنوع staff] المسمى الوظيفي', example: 'Accountant'),
                        new OA\Property(property: 'medical_conditions', type: 'array', items: new OA\Items(type: 'string'), description: '[للنوع player] الحالات الطبية', example: ["Asthma"]),
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: '✅ Person & Profile Created Successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'person_id', type: 'integer', example: 50),
                        new OA\Property(property: 'username', type: 'string', example: 'mohamed50'),
                        new OA\Property(property: 'type', type: 'string', example: 'player'),
                        new OA\Property(property: 'qr_code', type: 'string', description: 'Generated QR code for players', example: 'QR-X1Y2Z3'),
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 422, description: '⚠️ Validation Error', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse'))]
    public function store(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'type' => 'required|in:player,coach,staff',
            'mobile_1' => 'required|string|max:20',
            'email' => 'nullable|email',
            'username' => 'nullable|string|unique:authentication_users,username',
        ]);

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
                    'password' => Hash::make($request->input('password', '123456')),
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
                    'tenant_id' => $person->tenant_id,
                    'qr_code' => 'QR-' . strtoupper(Str::random(10)),
                    'blood_type' => $data['blood_type'] ?? null,
                    'medical_conditions' => $data['medical_conditions'] ?? [],
                    'emergency_contact' => $data['emergency_contact'] ?? [],
                ]);
            case 'coach':
                return $person->coachProfile()->create([
                    'tenant_id' => $person->tenant_id,
                    'specialization' => $data['specialization'] ?? 'General',
                    'experience_years' => $data['experience_years'] ?? 0,
                ]);
            case 'staff':
                return $person->staffProfile()->create([
                    'tenant_id' => $person->tenant_id,
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
