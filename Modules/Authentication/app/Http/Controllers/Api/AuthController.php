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

class AuthController extends BaseController
{
    #[OA\Post(
        path: '/v1/auth/login',
        summary: '🔐 تسجيل الدخول',
        description: 'تسجيل دخول المستخدم وإنشاء رمز مرور (Bearer Token).',
        tags: ['Authentication']
    )]
    #[OA\RequestBody(
        required: true,
        description: 'بيانات تسجيل الدخول',
        content: new OA\JsonContent(
            required: ['username', 'password'],
            properties: [
                new OA\Property(property: 'username', type: 'string', description: 'اسم المستخدم الفريد (للموظف أو المدير)', example: 'super_admin'),
                new OA\Property(property: 'password', type: 'string', description: 'كلمة المرور', example: 'password123'),
                new OA\Property(property: 'fcm_token', type: 'string', description: 'رمز الجهاز لإشعارات Firebase', example: 'fcm_token_string_here', nullable: true),
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
                                new OA\Property(property: 'username', type: 'string', example: 'admin'),
                                new OA\Property(property: 'full_name', type: 'string', example: 'أحمد محمد'),
                                new OA\Property(property: 'photo_url', type: 'string', nullable: true, example: 'https://example.com/photo.jpg'),
                                new OA\Property(property: 'gender', type: 'string', nullable: true, example: 'male'),
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

        $user = User::where('username', $validated['username'])->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->errorResponse(__('Invalid credentials'), 401);
        }

        if (!$user->is_active) {
            return $this->errorResponse(__('User account is inactive'), 403);
        }

        if (!empty($validated['fcm_token'])) {
            $user->update(['fcm_token' => $validated['fcm_token']]);
        }

        // Generate Token
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'full_name' => $user->person->full_name ?? null,
                'photo_url' => $user->person->photo_url ?? null,
                'gender' => $user->person->gender ?? null,
            ]
        ], __('Logged in successfully'));
    }

    #[OA\Post(
        path: '/v1/auth/logout',
        summary: '🚪 تسجيل الخروج',
        description: 'إبطال رمز الوصول الحالي للمستخدم وإنهاء الجلسة.',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]]
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
            $user->update(['fcm_token' => null]);
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
        $user->load('person');

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
        path: '/v1/auth/change-password',
        summary: '🔑 تغيير كلمة المرور',
        description: 'تغيير كلمة المرور الخاصة بالمستخدم المصادق عليه حالياً.',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        description: 'بيانات تغيير كلمة المرور',
        content: new OA\JsonContent(
            required: ['current_password', 'new_password', 'new_password_confirmation'],
            properties: [
                new OA\Property(property: 'current_password', type: 'string', description: 'كلمة المرور الحالية', example: 'oldPassword123'),
                new OA\Property(property: 'new_password', type: 'string', description: 'كلمة المرور الجديدة', example: 'newPassword123'),
                new OA\Property(property: 'new_password_confirmation', type: 'string', description: 'تأكيد كلمة المرور الجديدة', example: 'newPassword123'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تغيير كلمة المرور بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'تم تغيير كلمة المرور بنجاح'),
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
    #[OA\Response(
        response: 422,
        description: '⚠️ خطأ في التحقق من صحة البيانات أو كلمة المرور الحالية غير صحيحة',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'كلمة المرور الحالية غير صحيحة.'),
                new OA\Property(
                    property: 'errors',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'current_password', type: 'array', items: new OA\Items(type: 'string', example: 'كلمة المرور الحالية غير مطابقة.')),
                        new OA\Property(property: 'new_password', type: 'array', items: new OA\Items(type: 'string', example: 'كلمة المرور الجديدة يجب ألا تقل عن 8 أحرف.'))
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 500, description: '🔥 خطأ في الخادم', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'حدث خطأ داخلي في الخادم.')]))]
    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return $this->errorResponse(__('Current password is incorrect'), 422);
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        // مسح جميع الجلسات (Tokens) الحالية للمستخدم لإجباره على تسجيل الدخول مرة أخرى
        $user->tokens()->delete();

        return $this->successResponse(null, __('Password changed successfully'));
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
            ->where('subscription_plans.type', 'like', '%vip%')
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
                    new OA\Property(property: 'photo', type: 'string', format: 'binary', description: 'ملف الصورة المراد رفعها')
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
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $user = $request->user();
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
}
