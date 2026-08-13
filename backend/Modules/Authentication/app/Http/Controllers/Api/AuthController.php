<?php

namespace Modules\Authentication\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Models\User;
use Modules\Authentication\Http\Requests\ChangePasswordRequest;
use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Modules\Authentication\Http\Requests\LoginRequest;
use Modules\Authentication\Services\PersonQrCodeService;
use Modules\Authentication\Services\UsernameSuggestionService;

class AuthController extends BaseController
{
    public function __construct(
        protected PersonQrCodeService $qrCodeService
    ) {}
    #[OA\Post(
        path: '/v1/auth/login',
        summary: '🔐 تسجيل الدخول',
        description: 'تسجيل دخول المستخدم عبر ٣ طرق (رقم الهاتف / معرف النظام tec-* / اسم المستخدم المخصص).',
        tags: ['Authentication']
    )]
    #[OA\RequestBody(
        required: true,
        description: 'بيانات تسجيل الدخول',
        content: new OA\JsonContent(
            required: ['username', 'password'],
            properties: [
                new OA\Property(property: 'username', type: 'string', description: 'اسم المستخدم الفريد أو رقم الموبايل أو المعرف المولد', example: 'tec-adm-75054'),
                new OA\Property(property: 'password', type: 'string', description: 'كلمة المرور', example: '12345678'),
                new OA\Property(property: 'fcm_token', type: 'string', description: 'رمز الجهاز لإشعارات Firebase', example: 'fcm_token_string_here', nullable: true),
                new OA\Property(
                    property: 'device_info', 
                    type: 'object', 
                    description: 'معلومات الجهاز', 
                    nullable: true,
                    example: ["device_id" => "unique_id_123", "sdk" => 33, "brand" => "Redmi", "model" => "M2101K6G", "version" => "13", "manufacturer" => "Xiaomi", "isPhysicalDevice" => true]
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تسجيل الدخول بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'تم تسجيل الدخول بنجاح'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'access_token', type: 'string', example: '1|abc123token...'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                        new OA\Property(
                            property: 'user',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'user_id', type: 'integer', example: 1),
                                new OA\Property(property: 'person_id', type: 'integer', nullable: true, example: 5),
                                new OA\Property(property: 'member_id', type: 'integer', nullable: true, example: 10),
                                new OA\Property(property: 'staff_id', type: 'integer', nullable: true, example: 3),
                                new OA\Property(property: 'username', type: 'string', example: 'tec-ply-75054'),
                                new OA\Property(property: 'custom_username', type: 'string', nullable: true, example: 'ahmed_player'),
                                new OA\Property(property: 'must_change_password', type: 'boolean', example: true),
                                new OA\Property(property: 'full_name', type: 'string', example: 'أحمد محمد'),
                                new OA\Property(property: 'photo_url', type: 'string', nullable: true, example: 'https://example.com/photo.jpg'),
                                new OA\Property(property: 'gender', type: 'string', nullable: true, example: 'male'),
                                new OA\Property(property: 'type', type: 'string', nullable: true, example: 'player'),
                            ]
                        )
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: '❌ بيانات الدخول غير صحيحة',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'error'),
                new OA\Property(property: 'message', type: 'string', example: 'بيانات الدخول غير صحيحة'),
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: '🚫 الحساب غير مفعل',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'error'),
                new OA\Property(property: 'message', type: 'string', example: 'حساب المستخدم غير مفعل'),
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
                        new OA\Property(property: 'username', type: 'array', items: new OA\Items(type: 'string', example: 'حقل اسم المستخدم مطلوب.')),
                        new OA\Property(property: 'password', type: 'array', items: new OA\Items(type: 'string', example: 'حقل كلمة المرور مطلوب.'))
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 500, description: '🔥 خطأ في الخادم', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'حدث خطأ داخلي في الخادم.')]))]
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();
        $input = trim($validated['username']);

        // البحث بـ ٣ طرق: معرف النظام (username), الاسم المخصص (custom_username), أو رقم الجوال في person.contacts
        $user = User::where('username', $input)
            ->orWhere('custom_username', $input)
            ->orWhereHas('person.contacts', function ($q) use ($input) {
                $q->where('phone_number', $input);
            })
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->errorResponse(__('Invalid credentials'), 401);
        }

        if (!$user->is_active) {
            return $this->errorResponse(__('User account is inactive'), 403);
        }

        if (!empty($validated['fcm_token'])) {
            $deviceInfo = $validated['device_info'] ?? null;
            $deviceId = $deviceInfo['device_id'] ?? null;

            if ($deviceId) {
                $existingDevice = \Modules\Authentication\Models\UserDevice::where('user_id', $user->id)
                    ->where('device_info->device_id', $deviceId)
                    ->first();

                if ($existingDevice) {
                    \Modules\Authentication\Models\UserDevice::where('fcm_token', $validated['fcm_token'])
                        ->where('id', '!=', $existingDevice->id)
                        ->delete();

                    $existingDevice->update([
                        'fcm_token' => $validated['fcm_token'],
                        'device_info' => $deviceInfo,
                    ]);
                } else {
                    \Modules\Authentication\Models\UserDevice::updateOrCreate(
                        ['fcm_token' => $validated['fcm_token']],
                        [
                            'user_id' => $user->id,
                            'device_info' => $deviceInfo,
                        ]
                    );
                }
            } else {
                \Modules\Authentication\Models\UserDevice::updateOrCreate(
                    ['fcm_token' => $validated['fcm_token']],
                    [
                        'user_id' => $user->id,
                        'device_info' => $deviceInfo,
                    ]
                );
            }
        }

        // Generate Token
        $token = $user->createToken('auth_token')->plainTextToken;

        $person = $user->person;
        $personId = $person ? $person->id : null;
        
        $userData = [
            'id' => $user->id,
            'user_id' => $user->id,
            'person_id' => $personId,
            'username' => $user->username,
            'custom_username' => $user->custom_username,
            'must_change_password' => (bool) ($user->must_change_password ?? true),
            'full_name' => $person->full_name ?? null,
            'photo_url' => $person->photo_url ?? null,
            'gender' => $person->gender ?? null,
            'type' => $person->type ?? null,
            'branch_id' => null,
        ];

        if ($personId) {
            $member = DB::table('members')->where('person_id', $personId)->first();
            if ($member) {
                $userData['member_id'] = $member->id;
                $userData['branch_id'] = $member->branch_id;
            }

            $staff = DB::table('staff')->where('person_id', $personId)->first();
            if ($staff) {
                $userData['staff_id'] = $staff->id;
                $staffBranch = DB::table('staff_branches')->where('staff_id', $staff->id)->first();
                if ($staffBranch) {
                    $userData['branch_id'] = $staffBranch->branch_id;
                }
                $userData['qr_code'] = $this->qrCodeService->getSingleCodeForPerson($personId);
            } elseif (($person->type ?? null) !== 'admin') {
                // Attach the 7 QR codes for members (Frontend caches them)
                $rawQrCodes = $this->qrCodeService->getCodesForPerson($personId);
                $formattedQrCodes = [];
                foreach ($rawQrCodes as $day => $code) {
                    $formattedQrCodes[] = [
                        'day' => $day,
                        'code' => $code
                    ];
                }
                $userData['qr_codes'] = $formattedQrCodes;
            } else {
                $userData['qr_codes'] = [];
            }
        }

        return $this->successResponse([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $userData
        ], __('Logged in successfully'));
    }

    #[OA\Post(
        path: '/v1/auth/logout',
        summary: '🚪 تسجيل الخروج',
        description: 'إبطال رمز الوصول الحالي للمستخدم وإنهاء الجلسة. يتم حذف fcm_token الخاص بالجهاز في حال إرساله.',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: false,
        description: 'رمز الـ FCM',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'fcm_token', type: 'string', description: 'رمز الجهاز لإشعارات Firebase', example: 'fcm_token_string_here'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تسجيل الخروج بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'تم تسجيل الخروج بنجاح'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
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
    #[OA\Response(response: 500, description: '🔥 خطأ في الخادم', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'حدث خطأ داخلي في الخادم.')]))]
    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $fcmToken = $request->input('fcm_token');
            if ($fcmToken) {
                $user->devices()->where('fcm_token', $fcmToken)->delete();
            }
            $user->currentAccessToken()->delete();
        }
        return $this->successResponse(null, __('Logged out successfully'));
    }

    #[OA\Get(
        path: '/v1/auth/me',
        summary: '👤 الحصول على الملف الشخصي للمستخدم',
        description: 'إرجاع بيانات المستخدم المصادق عليه مع ملفاته الشخصية المرتبطة (لاعب / موظف).',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع الملف الشخصي بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'تم استرجاع الملف الشخصي بنجاح'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'username', type: 'string', example: 'admin'),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'person',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 5),
                                new OA\Property(property: 'full_name', type: 'string', example: 'أحمد محمد'),
                                new OA\Property(property: 'type', type: 'string', example: 'player'),
                                new OA\Property(property: 'dob', type: 'string', format: 'date', example: '1995-08-20')
                            ]
                        ),
                        new OA\Property(
                            property: 'member',
                            type: 'object',
                            nullable: true,
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 10),
                                new OA\Property(property: 'member_number', type: 'string', example: 'MEM-10023'),
                                new OA\Property(property: 'membership_status', type: 'string', example: 'active'),
                                new OA\Property(property: 'is_vip', type: 'boolean', example: true)
                            ]
                        ),
                        new OA\Property(
                            property: 'measurements',
                            type: 'object',
                            nullable: true,
                            properties: [
                                new OA\Property(property: 'weight', type: 'number', format: 'float', example: 75.5),
                                new OA\Property(property: 'height', type: 'number', format: 'float', example: 180.0),
                                new OA\Property(property: 'bmi', type: 'number', format: 'float', example: 23.3),
                                new OA\Property(property: 'measured_at', type: 'string', format: 'date-time', example: '2023-10-01 10:00:00')
                            ]
                        ),
                        new OA\Property(property: 'age', type: 'integer', nullable: true, example: 28),
                        new OA\Property(property: 'health_status', type: 'string', nullable: true, example: 'لا توجد أمراض مزمنة')
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
    #[OA\Response(response: 500, description: '🔥 خطأ في الخادم', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'حدث خطأ داخلي في الخادم.')]))]
    public function me(Request $request)
    {
        $user = clone $request->user();
        $user->load(['person', 'person.contacts']);

        $personData = $user->person;
        $profileData = [
            'id' => $user->id,
            'username' => $user->username,
            'is_active' => $user->is_active,
            'person' => $personData,
        ];

        // Enrich with member-specific data if the user is a player
        if ($personData) {
            $member = DB::table('members')->where('person_id', $personData->id)->first();

            if ($member) {
                $latestMeasurement = DB::table('member_measurements')
                    ->where('member_id', $member->id)
                    ->orderByDesc('measurement_date')
                    ->first();

                $healthProfile = DB::table('member_health_profiles')->where('member_id', $member->id)->first();

                $profileData['member'] = [
                    'id' => $member->id,
                    'member_number' => $member->member_number,
                    'membership_status' => $member->membership_status,
                    'is_vip' => $this->checkIsVip($member->id),
                ];

                $profileData['measurements'] = $latestMeasurement ? [
                    'weight' => (float) $latestMeasurement->weight,
                    'height' => $latestMeasurement->height ? (float) $latestMeasurement->height : null,
                    'bmi' => $latestMeasurement->bmi ? (float) $latestMeasurement->bmi : null,
                    'measured_at' => $latestMeasurement->measurement_date,
                ] : null;

                $profileData['health_profile'] = $healthProfile;

                // Age from person dob
                $profileData['age'] = $personData->dob
                    ? \Carbon\Carbon::parse($personData->dob)->age
                    : null;

                // Health status from chronic_diseases field
                $profileData['health_status'] = $personData->chronic_diseases ?: null;
            }
        }

        return $this->successResponse($profileData, __('Profile retrieved successfully'));
    }

    #[OA\Post(
        path: '/v1/auth/reset-password',
        summary: '🔄 تصفير كلمة المرور لمستخدم إلى 12345678',
        description: 'يقوم بتصفير كلمة السر لأي مستخدم (عضو / موظف / مدرب) بتمرير user_id لتصبح تلقائياً 12345678.',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        description: 'معرف المستخدم (user_id)',
        content: new OA\JsonContent(
            required: ['user_id'],
            properties: [
                new OA\Property(property: 'user_id', type: 'integer', description: 'معرف المستخدم من جدول authentication_users', example: 15),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تصفير كلمة المرور بنجاح إلى 12345678',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'تم إعادة تعيين كلمة المرور بنجاح إلى 12345678'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'user_id', type: 'integer', example: 15),
                        new OA\Property(property: 'username', type: 'string', example: 'coach_15'),
                    ]
                )
            ]
        )
    )]
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:authentication_users,id',
        ]);

        $user = User::findOrFail($validated['user_id']);

        $user->update([
            'password' => Hash::make('12345678'),
            'must_change_password' => true,
        ]);

        $user->tokens()->delete();

        return $this->successResponse([
            'user_id'  => $user->id,
            'username' => $user->username,
        ], __('تم إعادة تعيين كلمة المرور بنجاح إلى 12345678'));
    }

    #[OA\Post(
        path: '/v1/auth/change-password',
        summary: '🔑 تغيير كلمة المرور وتعيين اسم المستخدِم المخصص (اختياري)',
        description: 'تغيير كلمة المرور الخاصة بالمستخدم الحالي أو لمستخدم آخر بتمرير user_id، مع إمكانية تمرير custom_username (اختيارياً) لتعيين اسم مستخدم فريد في نفس الطلب.',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        description: 'بيانات تغيير كلمة المرور وتحديث اسم المستخدم المخصص',
        content: new OA\JsonContent(
            required: ['new_password', 'new_password_confirmation'],
            properties: [
                new OA\Property(property: 'user_id', type: 'integer', description: 'معرف المستخدم (في حال تعديل كلمة سر مستخدم آخر)', example: 15, nullable: true),
                new OA\Property(property: 'current_password', type: 'string', description: 'كلمة المرور الحالية (مطلوبة فقط إذا لم يتم تمرير user_id)', example: 'oldPassword123', nullable: true),
                new OA\Property(property: 'new_password', type: 'string', description: 'كلمة المرور الجديدة', example: '12345678'),
                new OA\Property(property: 'new_password_confirmation', type: 'string', description: 'تأكيد كلمة المرور الجديدة', example: '12345678'),
                new OA\Property(property: 'custom_username', type: 'string', description: 'اسم المستخدم المخصص الفريد (اختياري)', example: 'ahmed_player99', nullable: true),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تغيير كلمة المرور بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'تم تغيير كلمة المرور والتأكيدات بنجاح'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'username', type: 'string', example: 'tec-ply-75054'),
                        new OA\Property(property: 'custom_username', type: 'string', nullable: true, example: 'ahmed_player99')
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
        response: 422,
        description: '⚠️ خطأ في التحقق من صحة البيانات أو اسم المستخدم المخصص مُستخدَم مسبقاً',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'كلمة المرور الحالية غير صحيحة.'),
                new OA\Property(
                    property: 'errors',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'current_password', type: 'array', items: new OA\Items(type: 'string', example: 'كلمة المرور الحالية غير مطابقة.')),
                        new OA\Property(property: 'new_password', type: 'array', items: new OA\Items(type: 'string', example: 'كلمة المرور الجديدة يجب ألا تقل عن 6 أحرف.')),
                        new OA\Property(property: 'custom_username', type: 'array', items: new OA\Items(type: 'string', example: 'اسم المستخدم المخصص مُستخدَم بالفعل.'))
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 500, description: '🔥 خطأ في الخادم', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'حدث خطأ داخلي في الخادم.')]))]
    public function changePassword(ChangePasswordRequest $request)
    {
        $validated = $request->validated();

        if (!empty($validated['user_id'])) {
            $user = User::findOrFail($validated['user_id']);
        } else {
            $user = $request->user();
            if (!Hash::check($validated['current_password'], $user->password)) {
                return $this->errorResponse(__('Current password is incorrect'), 422);
            }
        }

        // فحص وتحديث custom_username في حال إرساله في نفس الطلب
        if (!empty($validated['custom_username'])) {
            $customUsername = trim($validated['custom_username']);

            if (!UsernameSuggestionService::isValidFormat($customUsername) || !UsernameSuggestionService::isAvailable($customUsername, $user->id)) {
                $suggestions = UsernameSuggestionService::generateSuggestions(
                    $customUsername,
                    $user->person?->full_name,
                    $user->id
                );

                return response()->json([
                    'status' => 'error',
                    'message' => __('اسم المستخدم المخصص مُستخدَم بالفعل أو غير صالح، اختر اسماً آخر.'),
                    'data' => [
                        'is_available' => false,
                        'suggestions'  => $suggestions,
                    ],
                    'errors' => [
                        'custom_username' => [__('اسم المستخدم المخصص مُستخدَم بالفعل أو غير صالح، إليك بعض الاقتراحات المتاحة.')]
                    ]
                ], 422);
            }

            $user->custom_username = $customUsername;
        }

        $user->password = Hash::make($validated['new_password']);
        $user->must_change_password = false;
        $user->save();

        // مسح جميع الجلسات (Tokens) الحالية للمستخدم لإجباره على تسجيل الدخول كلمة المرور الجديدة
        $user->tokens()->delete();

        return $this->successResponse([
            'username'        => $user->username,
            'custom_username' => $user->custom_username,
        ], __('Password changed successfully'));
    }

    /**
     * Check if member has an active VIP subscription.
     */
    protected function checkIsVip(int $memberId): bool
    {
        return DB::table('player_subscriptions')
            ->join('subscription_plans', 'player_subscriptions.plan_id', '=', 'subscription_plans.id')
            ->where('player_subscriptions.member_id', $memberId)
            ->where('player_subscriptions.status', 'active')
            ->where('subscription_plans.name', 'like', '%vip%')
            ->exists();
    }

    #[OA\Post(
        path: '/v1/auth/change-photo',
        summary: '🖼️ تحديث الصورة الشخصية',
        description: 'يقوم المستخدم المسجل برفع وتحديث صورته الشخصية مباشرة.',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        description: 'ملف الصورة (jpeg, png, jpg, gif) بحد أقصى 2MB',
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['photo'],
                properties: [
                    new OA\Property(property: 'photo', type: 'string', format: 'binary', description: 'ملف الصورة المراد رفعها'),
                    new OA\Property(property: 'user_id', type: 'integer', description: 'معرف المستخدم (في حال تعديل صورة مستخدم آخر)', nullable: true)
                ]
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تغيير الصورة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'تم تحديث الصورة الشخصية بنجاح'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'photo_url', type: 'string', example: 'photos/xyz123.jpg')
                    ]
                )
            ]
        )
    )]
    public function updatePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'user_id' => 'nullable|integer|exists:authentication_users,id'
        ]);

        if ($request->has('user_id')) {
            $user = User::findOrFail($request->user_id);
        } else {
            $user = $request->user();
        }

        $person = $user->person;

        if (!$person) {
            return $this->errorResponse(__('User does not have a profile'), 404);
        }

        if ($request->hasFile('photo')) {
            // حذف الصورة القديمة إذا كانت موجودة
            $oldPhoto = $person->getRawOriginal('photo_url');
            if ($oldPhoto && \Illuminate\Support\Facades\Storage::disk('public')->exists($oldPhoto)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPhoto);
            }

            // حفظ الصورة الجديدة
            $path = $request->file('photo')->store('photos', 'public');

            // ربطها بالمستخدم
            $person->update(['photo_url' => $path]);

            return $this->successResponse([
                'photo_url' => 'storage/' . $path
            ], __('تم تحديث الصورة الشخصية بنجاح'));
        }

        return $this->errorResponse(__('لم يتم إرفاق صورة'), 400);
    }

    #[OA\Delete(
        path: '/v1/auth/delete-photo',
        summary: '🗑️ حذف الصورة الشخصية',
        description: 'حذف الصورة الشخصية للمستخدم الحالي أو لأي مستخدم آخر (في حال تمرير user_id).',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: false,
        description: 'معرف المستخدم',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'user_id', type: 'integer', description: 'معرف المستخدم (في حال حذف صورة مستخدم آخر)', nullable: true)
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم حذف الصورة بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'تم حذف الصورة الشخصية بنجاح')
            ]
        )
    )]
    public function deletePhoto(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|integer|exists:authentication_users,id'
        ]);

        if ($request->has('user_id')) {
            $user = User::findOrFail($request->user_id);
        } else {
            $user = $request->user();
        }

        $person = $user->person;

        if (!$person) {
            return $this->errorResponse(__('User does not have a profile'), 404);
        }

        $oldPhoto = $person->getRawOriginal('photo_url');
        if ($oldPhoto && \Illuminate\Support\Facades\Storage::disk('public')->exists($oldPhoto)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPhoto);
        }

        $person->update(['photo_url' => null]);

        return $this->successResponse(null, __('تم حذف الصورة الشخصية بنجاح'));
    }

    #[OA\Post(
        path: '/v1/auth/set-custom-username',
        summary: '✏️ تعيين أو تحديث اسم المستخدم المخصص',
        description: 'يسمح للمستخدم المصادق عليه (مثلاً اللاعب) بتحديد اسم مستخدم فريد خاص به ليتسنى له الدخول به لاحقاً.',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        description: 'اسم المستخدم المخصص الجديد',
        content: new OA\JsonContent(
            required: ['custom_username'],
            properties: [
                new OA\Property(property: 'custom_username', type: 'string', description: 'اسم المستخدم المخصص الفريد', example: 'ahmed_player99'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تحديث اسم المستخدم المخصص بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'تم تحديث اسم المستخدم المخصص بنجاح'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'username', type: 'string', example: 'tec-ply-75054'),
                        new OA\Property(property: 'custom_username', type: 'string', example: 'ahmed_player99')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: '⚠️ اسم المستخدم غير صالح أو مُستخْدَم من قبل مع اقتراحات بديلة',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'error'),
                new OA\Property(property: 'message', type: 'string', example: 'اسم المستخدم المخصص مُستخدَم بالفعل، اختر اسماً آخر.'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'is_available', type: 'boolean', example: false),
                        new OA\Property(
                            property: 'suggestions',
                            type: 'array',
                            items: new OA\Items(type: 'string', example: 'ahmed_player_2026')
                        )
                    ]
                )
            ]
        )
    )]
    public function setCustomUsername(Request $request)
    {
        $user = $request->user();

        $rawUsername = trim((string) $request->input('custom_username', ''));

        if (empty($rawUsername)) {
            return response()->json([
                'status' => 'error',
                'message' => __('حقل اسم المستخدم المخصص مطلوب.'),
                'errors' => [
                    'custom_username' => [__('حقل اسم المستخدم المخصص مطلوب.')]
                ]
            ], 422);
        }

        if (!UsernameSuggestionService::isValidFormat($rawUsername)) {
            $suggestions = UsernameSuggestionService::generateSuggestions(
                $rawUsername,
                $user->person?->full_name,
                $user->id
            );

            return response()->json([
                'status' => 'error',
                'message' => __('صيغة اسم المستخدم غير صالحة. يجب أن يتكون من 3-30 حرفاً (حروف إنجليزية، أرقام، _ . -).'),
                'data' => [
                    'is_available' => false,
                    'suggestions'  => $suggestions,
                ],
                'errors' => [
                    'custom_username' => [__('صيغة اسم المستخدم غير صالحة. إليك بعض الاقتراحات المتاحة.')]
                ]
            ], 422);
        }

        if (!UsernameSuggestionService::isAvailable($rawUsername, $user->id)) {
            $suggestions = UsernameSuggestionService::generateSuggestions(
                $rawUsername,
                $user->person?->full_name,
                $user->id
            );

            return response()->json([
                'status' => 'error',
                'message' => __('اسم المستخدم المخصص مُستخدَم بالفعل، اختر اسماً آخر.'),
                'data' => [
                    'is_available' => false,
                    'suggestions'  => $suggestions,
                ],
                'errors' => [
                    'custom_username' => [__('اسم المستخدم المخصص مُستخدَم بالفعل، إليك بعض الاقتراحات المتاحة.')]
                ]
            ], 422);
        }

        $user->update([
            'custom_username' => $rawUsername,
        ]);

        return $this->successResponse([
            'username'        => $user->username,
            'custom_username' => $user->custom_username,
        ], __('Custom username set successfully'));
    }

    #[OA\Get(
        path: '/v1/auth/check-username',
        summary: '🔍 فحص توفر اسم المستخدم وتوليد اقتراحات عند التعارض',
        description: 'يتحقق مما إذا كان اسم المستخدم متاحاً أو محجوزاً مسبقاً، وفي حال عدم التوفر يعيد قائمة باقتراحات صالحة ومتاحة.',
        tags: ['Authentication']
    )]
    #[OA\Parameter(
        name: 'username',
        in: 'query',
        required: true,
        description: 'اسم المستخدم المراد فحصه',
        schema: new OA\Schema(type: 'string', example: 'ahmed_player')
    )]
    #[OA\Parameter(
        name: 'user_id',
        in: 'query',
        required: false,
        description: 'معرف المستخدم لتجاهله أثناء الفحص (اختياري)',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: '✅ نتيجة فحص اسم المستخدم مع الاقتراحات إن وجدت',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'اسم المستخدم متاح للاستخدام.'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'username', type: 'string', example: 'ahmed_player'),
                        new OA\Property(property: 'is_available', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'suggestions',
                            type: 'array',
                            items: new OA\Items(type: 'string', example: 'ahmed_player99')
                        )
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: '⚠️ لم يتم إرسال اسم المستخدم',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'error'),
                new OA\Property(property: 'message', type: 'string', example: 'يرجى إدخال اسم المستخدم المراد فحصه.')
            ]
        )
    )]
    public function checkUsername(Request $request)
    {
        $input = trim((string) $request->query('username', $request->input('username', '')));
        $ignoreUserId = $request->query('user_id', $request->input('user_id', $request->user('sanctum')?->id));

        if (empty($input)) {
            return $this->errorResponse(__('يرجى إدخال اسم المستخدم المراد فحصه.'), 422);
        }

        $isValidFormat = UsernameSuggestionService::isValidFormat($input);
        $isAvailable = $isValidFormat && UsernameSuggestionService::isAvailable($input, $ignoreUserId ? (int)$ignoreUserId : null);

        if ($isAvailable) {
            return $this->successResponse([
                'username'     => $input,
                'is_available' => true,
                'suggestions'  => [],
            ], __('اسم المستخدم متاح للاستخدام.'));
        }

        $fullName = null;
        if ($ignoreUserId) {
            $user = User::find($ignoreUserId);
            $fullName = $user?->person?->full_name;
        }

        $suggestions = UsernameSuggestionService::generateSuggestions(
            $input,
            $fullName,
            $ignoreUserId ? (int)$ignoreUserId : null,
            5
        );

        return $this->successResponse([
            'username'     => $input,
            'is_available' => false,
            'suggestions'  => $suggestions,
        ], __('اسم المستخدم مُستخدَم بالفعل أو غير صالح، إليك بعض الاقتراحات المتاحة.'));
    }
}
