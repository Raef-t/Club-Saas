<?php

namespace Modules\AttendanceManager\Http\Controllers\Api\V1;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\AttendanceManager\Services\QRSecurityService;
use Modules\AttendanceManager\Services\UnifiedAttendanceService;
use Modules\AttendanceManager\Http\Requests\QRCheckInRequest;
use Modules\AttendanceManager\Http\Requests\QRCheckOutRequest;
use Modules\Core\Http\Controllers\Api\BaseController;
use OpenApi\Attributes as OA;

class QRController extends BaseController
{
    public function __construct(
        protected QRSecurityService $qrService,
        protected UnifiedAttendanceService $attendanceService
    ) {}

    #[OA\Post(
        path: '/v1/qr/generate',
        summary: 'توليد رمز QR الخاص بالعضو',
        description: 'توليد رمز QR فريد وصالح لمدة قصيرة (30 ثانية) ليستخدمه العضو في تسجيل الدخول عبر البوابة.',
        tags: ['Attendance'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم توليد الرمز بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'QR code generated successfully.'),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'qr_token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'),
                    new OA\Property(property: 'expires_in_seconds', type: 'integer', example: 30)
                ])
            ]
        )
    )]
    #[OA\Response(response: 403, description: '🚫 غير مصرح أو نوع المستخدم غير صالح', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Unauthorized or invalid user type.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function generate(Request $request)
    {
        $user = $request->user();
        $member = $this->resolveMember($user);
        
        if (!$member) {
            return $this->errorResponse('Unauthorized or invalid user type.', 403);
        }

        $personId = $member->person_id;
        $qrCodes = app(\Modules\Authentication\Services\PersonQrCodeService::class)->getCodesForPerson($personId);
        $today = (int) \Carbon\Carbon::now()->format('w');
        $token = $qrCodes[$today] ?? null;

        if (!$token) {
            return $this->errorResponse('No QR code generated for today.', 404);
        }

        return $this->successResponse([
            'qr_token' => $token,
            'expires_in_seconds' => 86400
        ], 'QR code retrieved successfully.');
    }

    #[OA\Get(
        path: '/v1/qr/screen',
        summary: '📱 عرض شاشة الـ QR',
        description: 'استرجاع البيانات اللازمة لعرض شاشة مسح QR في تطبيق العضو (الاسم، الباقة، الصورة، رمز الـ QR، الرصيد، حالة الدخول).',
        tags: ['Attendance'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع البيانات بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'QR data retrieved successfully.'),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'member_name', type: 'string', example: 'أحمد محمود'),
                    new OA\Property(property: 'plan_name', type: 'string', example: 'باقة شهرية'),
                    new OA\Property(property: 'avatar_url', type: 'string', nullable: true, example: 'https://example.com/avatar.jpg'),
                    new OA\Property(property: 'qr_code_value', type: 'string', example: 'eyJ0eXAi...'),
                    new OA\Property(property: 'expires_in_seconds', type: 'integer', example: 30),
                    new OA\Property(property: 'remaining_sessions', type: 'integer', example: 12),
                    new OA\Property(property: 'is_inside_facility', type: 'boolean', example: false)
                ])
            ]
        )
    )]
    #[OA\Response(response: 403, description: '🚫 الملف الشخصي للعضو غير موجود', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Member profile not found.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function show(Request $request)
    {
        $user = $request->user();
        $member = $this->resolveMember($user);

        if (!$member) {
            return $this->errorResponse(__('Member profile not found.'), 403);
        }

        // Retrieve today's static QR code
        $personId = $member->person_id;
        $qrCodes = app(\Modules\Authentication\Services\PersonQrCodeService::class)->getCodesForPerson($personId);
        $today = (int) \Carbon\Carbon::now()->format('w');
        $qrToken = $qrCodes[$today] ?? null;

        // Get person data
        $person = DB::table('people')->where('id', $member->person_id)->first();

        // Get active subscription
        $subscription = DB::table('player_subscriptions')
            ->join('subscription_plans', 'player_subscriptions.plan_id', '=', 'subscription_plans.id')
            ->where('player_subscriptions.member_id', $member->id)
            ->where('player_subscriptions.status', 'active')
            ->select(
                'player_subscriptions.remaining_sessions',
                'subscription_plans.name as plan_name'
            )
            ->first();

        // Check if member is currently inside facility (has check-in without check-out today)
        $activeCheckIn = DB::table('attendances')
            ->where('attendable_type', 'player_subscription')
            ->whereIn('attendable_id', function ($query) use ($member) {
                $query->select('id')
                    ->from('player_subscriptions')
                    ->where('member_id', $member->id);
            })
            ->where('status', 'checked_in')
            ->whereNull('check_out_at')
            ->exists();

        return $this->successResponse([
            'member_name' => $person->full_name ?? null,
            'plan_name' => $subscription->plan_name ?? null,
            'avatar_url' => $person->photo_url ?? null,
            'qr_code_value' => $qrToken,
            'expires_in_seconds' => 30,
            'remaining_sessions' => $subscription ? (int) $subscription->remaining_sessions : 0,
            'is_inside_facility' => $activeCheckIn,
        ], __('QR data retrieved successfully.'));
    }

    #[OA\Post(
        path: '/v1/qr/check-in',
        summary: '✅ تسجيل الدخول عبر مسح QR',
        description: 'معالجة تسجيل الدخول (Check-In) باستخدام رمز QR من تطبيق الهاتف أو الموظف.',
        tags: ['Attendance'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['qr_token', 'branch_id'],
            properties: [
                new OA\Property(property: 'qr_token', type: 'string', example: 'eyJ0eXAi...'),
                new OA\Property(property: 'branch_id', type: 'integer', example: 1)
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم الدخول بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Check-in successful.'),
                new OA\Property(property: 'data', type: 'object', nullable: true, example: null)
            ]
        )
    )]
    #[OA\Response(response: 400, description: '⚠️ خطأ في الرمز أو المعالجة', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Invalid token.')]))]
    #[OA\Response(response: 403, description: '🚫 محظور (الدخول مرفوض)', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Membership expired.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function checkIn(QRCheckInRequest $request)
    {
        $validated = $request->validated();

        try {
            $personId = $this->qrService->validateCode($validated['qr_token']);

            $member = DB::table('members')->where('person_id', $personId)->whereNull('deleted_at')->first();
            $staff = DB::table('staff')->where('person_id', $personId)->whereNull('deleted_at')->first();

            $type = null;
            $entityId = null;

            if ($member) {
                $type = 'member';
                $entityId = $member->id;
            } elseif ($staff) {
                $type = 'staff';
                $entityId = $staff->id;
            } else {
                throw new Exception('No active profile found for this QR code.');
            }

            $branch = \Illuminate\Support\Facades\DB::table('branches')->where('id', $validated['branch_id'])->first();
            if (!$branch) {
                return $this->errorResponse('Branch not found.', 404);
            }

            $attendance = $this->attendanceService->checkIn(
                type: $type,
                entityId: (int) $entityId,
                clubId: (int) $branch->club_id,
                branchId: (int) $branch->id,
                metadata: ['source' => 'qr_scan']
            );

            return $this->successResponse(
                ['attendance_id' => $attendance->id, 'type' => $type],
                'Check-in successful.'
            );

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    #[OA\Post(
        path: '/v1/qr/check-out',
        summary: '🚪 تسجيل الانصراف عبر مسح QR',
        description: 'معالجة تسجيل الانصراف (Check-Out) باستخدام رمز QR.',
        tags: ['Attendance'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['qr_token', 'branch_id'],
            properties: [
                new OA\Property(property: 'qr_token', type: 'string', example: 'eyJ0eXAi...'),
                new OA\Property(property: 'branch_id', type: 'integer', example: 1)
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم الانصراف بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Check-out successful.'),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'duration_minutes', type: 'integer', example: 120)
                ])
            ]
        )
    )]
    #[OA\Response(response: 400, description: '⚠️ خطأ في الرمز أو المعالجة', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Invalid token.')]))]
    #[OA\Response(response: 404, description: '🚫 لا يوجد تسجيل دخول نشط', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'No active check-in found.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]))]
    public function checkOut(QRCheckOutRequest $request)
    {
        $validated = $request->validated();

        try {
            $personId = $this->qrService->validateCode($validated['qr_token']);

            $member = DB::table('members')->where('person_id', $personId)->whereNull('deleted_at')->first();
            $staff = DB::table('staff')->where('person_id', $personId)->whereNull('deleted_at')->first();

            $type = null;
            $entityId = null;

            if ($member) {
                $type = 'member';
                $entityId = $member->id;
            } elseif ($staff) {
                $type = 'staff';
                $entityId = $staff->id;
            } else {
                throw new Exception('No active profile found for this QR code.');
            }

            // Find the open attendance
            $open = $this->attendanceService->findOpen($type, (int) $entityId);

            if (!$open) {
                return $this->errorResponse('No active check-in found.', 404);
            }

            $attendance = $this->attendanceService->checkOut($open->id);

            return $this->successResponse([
                'duration_minutes' => $attendance->duration_minutes,
            ], 'Check-out successful.');

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Resolve the Member record from the authenticated user.
     */
    protected function resolveMember($user): ?object
    {
        if ($user instanceof \Modules\MemberManager\Models\Member) {
            return $user;
        }

        if (isset($user->person_id)) {
            return DB::table('members')
                ->where('person_id', $user->person_id)
                ->whereNull('deleted_at')
                ->first();
        }

        return null;
    }
}
