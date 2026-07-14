<?php

namespace Modules\AttendanceManager\Http\Controllers\Api\V1;

use Exception;
use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\AttendanceManager\Models\GateDevice;
use Modules\AttendanceManager\Services\QRSecurityService;
use Modules\AttendanceManager\Services\UnifiedAttendanceService;
use Modules\AttendanceManager\Http\Requests\GateScanRequest;
use OpenApi\Attributes as OA;

class GateController extends BaseController
{
    public function __construct(
        protected QRSecurityService $qrService,
        protected UnifiedAttendanceService $attendanceService
    ) {}

    /*
    #[OA\Post(
        path: '/v1/gates/scan',
        summary: '📲 تسجيل الدخول عبر البوابة',
        description: 'معالجة مسح رمز QR من جهاز البوابة (Hardware Gate).',
        tags: ['Gate Integration'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['qr_code'],
            properties: [
                new OA\Property(property: 'qr_code', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...')
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
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'member_id', type: 'integer', example: 123),
                    new OA\Property(property: 'action', type: 'string', example: 'unlock_door'),
                    new OA\Property(property: 'display_message', type: 'string', example: 'Welcome!')
                ])
            ]
        )
    )]
    #[OA\Response(response: 400, description: '⚠️ خطأ في المعالجة', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Invalid token.')]))]
    #[OA\Response(response: 401, description: '❌ غير مصرح (جهاز البوابة غير صالح)', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Unauthorized gate device.')]))]
    #[OA\Response(response: 403, description: '🚫 محظور (الدخول مرفوض)', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Membership expired.')]))]
    #[OA\Response(response: 422, description: '⚠️ خطأ في التحقق من صحة البيانات', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'البيانات المدخلة غير صالحة.'), new OA\Property(property: 'errors', type: 'object')]))]
    #[OA\Response(response: 429, description: '⏳ طلبات متكررة جداً', content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'error'), new OA\Property(property: 'message', type: 'string', example: 'Scan already processing.')]))]
    */
    public function scan(GateScanRequest $request)
    {
        $validated = $request->validated();

        // 1. Authenticate the Gate Device via Bearer Token
        $token = $request->bearerToken();
        if (!$token) {
            return $this->errorResponse('Unauthorized gate device.', 401);
        }

        $hashedToken = hash('sha256', $token);
        $gate = GateDevice::where('api_token', $hashedToken)
            ->where('is_active', true)
            ->first();

        if (!$gate) {
            return $this->errorResponse('Invalid or inactive gate device.', 401);
        }

        try {
            // 2. Validate QR Token (Fast, Cache-First Validation inside QRSecurityService)
            $memberId = $this->qrService->validateToken($validated['qr_code']);
            
            // 3. Concurrency Protection (Redis Atomic Lock)
            // Lock for 2 seconds to prevent double scans from bouncing signals
            $lockKey = "member_gate_scan_{$memberId}";
            $lock = Cache::lock($lockKey, 2);

            if (!$lock->get()) {
                Log::warning("Double scan detected at Gate {$gate->id} for Member {$memberId}");
                return $this->errorResponse('Scan already processing.', 429);
            }

            try {
                // 4. Record Attendance via UnifiedAttendanceService → MemberAttendanceHandler
                $attendance = $this->attendanceService->checkIn(
                    type: 'member',
                    entityId: (int) $memberId,
                    clubId: (int) $gate->club_id,
                    branchId: (int) $gate->branch_id,
                    metadata: ['source' => 'gate_hardware', 'gate_id' => $gate->id]
                );

                try {
                    // 5. Attempt auto-deduction and validation (this throws if frozen/expired/debt)
                    $deductionService = app(\Modules\AttendanceManager\Services\SessionDeductionService::class);
                    $deductionService->autoDeductSessionForGate($attendance->id, $memberId);
                } catch (\Exception $e) {
                    // 6. If deduction fails, we rollback the attendance and deny entry
                    $attendance->delete();
                    return $this->errorResponse($e->getMessage(), 403);
                }

                // 7. Success - Instruct Gate to Unlock
                return $this->successResponse([
                    'member_id' => $memberId,
                    'action'    => 'unlock_door',
                    'display_message' => 'Welcome!'
                ], 'Check-in successful.');
                
            } finally {
                // We do NOT release the lock immediately. We let it expire after 2 seconds 
                // to effectively debounce any physical double scans during that window.
            }

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
