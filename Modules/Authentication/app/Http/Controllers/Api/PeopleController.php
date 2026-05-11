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
        description: "## 📘 نظرة عامة\nهذا المسار (Endpoint) ديناميكي وذكي جداً، مصمم لإدارة تسجيل الموارد البشرية بالكامل في النادي. يقوم بمعالجة إنشاء ثلاث كيانات مختلفة في عملية قاعدة بيانات واحدة (Atomic Transaction):\n\n1. **كيان الشخص (Person)**: البيانات الشخصية الأساسية (الاسم، الجوال، النوع، تاريخ الميلاد) وتخزن في جدول `people` المركزي.\n2. **الملف الشخصي المتخصص (Profile)**: بناءً على النوع (`type`) المختار، يتم إنشاء سجل متخصص في جداول (لاعبين، مدربين، أو موظفين) مع بيانات وصفية فريدة.\n3. **حساب المستخدم (User)**: يتم إنشاء حساب نظام تلقائياً مرتبط بالشخص، مع اسم مستخدم مولد آلياً وحالة 'غير نشط'، لضمان عدم الدخول إلا بعد تفعيل الإدارة.\n\n### 🛠 المنطق الديناميكي حسب النوع:\n- **لاعب (Player)**: يولّد نظامنا **QR Code** دائم وفريد لكل لاعب لاستخدامه في الحضور والانصراف، مع تسجيل البيانات الطبية وحالات الطوارئ.\n- **مدرب (Coach)**: يسجل التخصص الرياضي (مثلاً: كاراتيه، سباحة) وسنوات الخبرة.\n- **موظف (Staff)**: يسجل المسمى الوظيفي الإداري (مثلاً: محاسب، مدير فرع).\n\n### 🛡 الأمان وتعدد الأندية (Tenancy):\nكل عملية تسجيل محصورة بدقة داخل معرف النادي (`X-Tenant-ID`) المرسل في الهيدر. هذا يضمن عزل بيانات كل نادي تماماً عن الآخر حتى لو تكررت بيانات الأشخاص.",
        tags: ['People Management'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'X-Tenant-ID',
        in: 'header',
        description: 'هام جداً: المعرف الفريد للنادي. كل البيانات المنشأة سيتم ربطها بهذا النادي فقط.',
        required: true,
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        description: 'بيانات التسجيل الكاملة. ملاحظة: هيكلية "profile_data" يجب أن تتوافق مع نوع الشخص المختير.',
        content: new OA\JsonContent(
            required: ['full_name', 'type', 'mobile_1'],
            properties: [
                new OA\Property(property: 'full_name', type: 'string', description: 'الاسم الكامل الرسمي (مثال: أحمد محمد علي)', example: 'Mohamed Ahmed'),
                new OA\Property(property: 'type', type: 'string', enum: ['player', 'coach', 'staff'], description: 'يحدد الجدول الذي سيتم ملء البيانات الشخصية فيه.', example: 'player'),
                new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], example: 'male'),
                new OA\Property(property: 'dob', type: 'string', format: 'date', description: 'تاريخ الميلاد (YYYY-MM-DD). يستخدم لتوزيع الفئات العمرية.', example: '2005-06-15'),
                new OA\Property(property: 'mobile_1', type: 'string', description: 'رقم الجوال الأساسي (المعرف الرئيسي في أغلب العمليات).', example: '0512345678'),
                new OA\Property(property: 'email', type: 'string', format: 'email', description: 'البريد الإلكتروني (اختياري).', example: 'player@example.com'),
                new OA\Property(property: 'password', type: 'string', description: 'كلمة المرور الأولية. إذا تركت فارغة ستكون الافتراضية 123456.', example: 'password123'),
                new OA\Property(
                    property: 'profile_data',
                    type: 'object',
                    description: 'كائن يحتوي على البيانات المتخصصة بناءً على نوع الشخص.',
                    properties: [
                        new OA\Property(property: 'blood_type', type: 'string', description: '[للاعبين] فصيلة الدم لضمان السلامة الطبية.', example: 'A+'),
                        new OA\Property(property: 'specialization', type: 'string', description: '[للمدربين] الرياضة أو المهارة الأساسية التي يدربها.', example: 'Swimming'),
                        new OA\Property(property: 'job_title', type: 'string', description: '[للموظفين] المسمى الوظيفي الإداري الرسمي.', example: 'Accountant'),
                        new OA\Property(property: 'medical_conditions', type: 'array', items: new OA\Items(type: 'string'), description: '[للاعبين] قائمة بالأمراض المزمنة أو الحساسية.', example: ["Asthma"]),
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
