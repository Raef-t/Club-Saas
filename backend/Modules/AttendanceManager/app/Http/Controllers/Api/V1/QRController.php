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

            $member = DB::table('members')->where('person_id', $personId)->first();
            $staff = DB::table('staff')->where('person_id', $personId)->first();

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
                [
                    'attendance_id' => $attendance->id,
                    'type' => $type,
                    'member_id' => $type === 'member' ? (int) $entityId : null
                ],
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

            $member = DB::table('members')->where('person_id', $personId)->first();
            $staff = DB::table('staff')->where('person_id', $personId)->first();

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
                ->first();
        }

        return null;
    }
}
