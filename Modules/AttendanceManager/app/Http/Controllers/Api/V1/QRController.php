<?php

namespace Modules\AttendanceManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
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

    /**
     * Show QR screen data for the authenticated member.
     * Returns member info, QR code value, remaining sessions, check-in status.
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $member = $this->resolveMember($user);

        if (!$member) {
            return $this->errorResponse(__('Member profile not found.'), 403);
        }

        // Generate a fresh QR token
        $memberModel = \Modules\MemberManager\Models\Member::find($member->id);
        $qrToken = $memberModel ? $this->qrService->generateToken($memberModel) : null;

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
