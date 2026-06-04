<?php

namespace Modules\AttendanceManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\AttendanceManager\Models\GateDevice;
use Modules\AttendanceManager\Services\QRSecurityService;
use Modules\AttendanceManager\Services\AttendanceRecorder;
use Modules\AttendanceManager\DTOs\CheckInAttempt;
use Modules\AttendanceManager\Http\Requests\GateScanRequest;

class GateController extends BaseController
{
    public function __construct(
        protected QRSecurityService $qrService,
        protected AttendanceRecorder $attendanceRecorder
    ) {}

    /**
     * Process a QR scan from a hardware gate device.
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
            $memberId = $this->qrService->validateToken($validated['qr_token']);
            
            // 3. Concurrency Protection (Redis Atomic Lock)
            // Lock for 2 seconds to prevent double scans from bouncing signals
            $lockKey = "member_gate_scan_{$memberId}";
            $lock = Cache::lock($lockKey, 2);

            if (!$lock->get()) {
                Log::warning("Double scan detected at Gate {$gate->id} for Member {$memberId}");
                return $this->errorResponse('Scan already processing.', 429);
            }

            try {
                // 4. Record Attendance
                $attempt = new CheckInAttempt(
                    clubId: $gate->club_id,
                    attendableType: 'member',
                    attendableId: $memberId,
                    branchId: $gate->branch_id,
                    timestamp: now()->toDateTimeImmutable(),
                    metadata: ['source' => 'gate_hardware', 'gate_id' => $gate->id]
                );

                $decision = $this->attendanceRecorder->record($attempt);

                if (!$decision->isAllowed) {
                    return $this->errorResponse($decision->rejectionReason, 403);
                }

                // 5. Success - Instruct Gate to Unlock
                return $this->successResponse([
                    'member_id' => $memberId,
                    'action' => 'unlock_door',
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
