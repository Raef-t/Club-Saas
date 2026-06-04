<?php

namespace Modules\AttendanceManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Modules\AttendanceManager\Services\QRSecurityService;
use Modules\AttendanceManager\Services\AttendanceRecorder;
use Modules\AttendanceManager\DTOs\CheckInAttempt;
use Modules\AttendanceManager\DTOs\CheckOutAttempt;
use Modules\AttendanceManager\Http\Requests\QRCheckInRequest;
use Modules\AttendanceManager\Http\Requests\QRCheckOutRequest;

class QRController extends BaseController
{
    public function __construct(
        protected QRSecurityService $qrService,
        protected AttendanceRecorder $attendanceRecorder
    ) {}

    public function generate(Request $request)
    {
        // For security, only the authenticated member can generate their own QR
        $member = $request->user();
        
        if (!$member || get_class($member) !== \Modules\MemberManager\Models\Member::class) {
            return $this->errorResponse('Unauthorized or invalid user type.', 403);
        }

        $token = $this->qrService->generateToken($member);

        return $this->successResponse([
            'qr_token' => $token,
            'expires_in_seconds' => 30
        ], 'QR code generated successfully.');
    }

    public function checkIn(QRCheckInRequest $request)
    {
        $validated = $request->validated();

        try {
            $memberId = $this->qrService->validateToken($validated['qr_token']);
            
            $attempt = new CheckInAttempt(
                clubId: $validated['club_id'],
                attendableType: 'member',
                attendableId: $memberId,
                branchId: $validated['branch_id'],
                timestamp: now()->toDateTimeImmutable(),
                metadata: ['source' => 'qr_scan']
            );

            $decision = $this->attendanceRecorder->record($attempt);

            if (!$decision->isAllowed) {
                return $this->errorResponse($decision->rejectionReason, 403);
            }

            return $this->successResponse(null, 'Check-in successful.');
            
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function checkOut(QRCheckOutRequest $request)
    {
        $validated = $request->validated();

        try {
            $memberId = $this->qrService->validateToken($validated['qr_token']);
            
            $attempt = new CheckOutAttempt(
                clubId: $validated['club_id'],
                attendableType: 'member',
                attendableId: $memberId,
                branchId: $validated['branch_id'],
                timestamp: now(),
                metadata: ['source' => 'qr_scan']
            );

            $attendance = $this->attendanceRecorder->recordCheckOut($attempt);

            if (!$attendance) {
                return $this->errorResponse('No active check-in found.', 404);
            }

            return $this->successResponse([
                'duration_minutes' => $attendance->duration_minutes
            ], 'Check-out successful.');
            
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
