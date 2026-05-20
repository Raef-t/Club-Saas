<?php

namespace Modules\SubscriptionManager\Services;

use Modules\Core\Contracts\MemberSharedServiceInterface;
use Modules\SubscriptionManager\Repositories\PlayerSubscriptionRepositoryInterface;
use Modules\SubscriptionManager\Models\PlayerAttendance;
use Exception;

class AttendanceService
{
    protected $memberSharedService;
    protected $subscriptionRepository;

    public function __construct(
        MemberSharedServiceInterface $memberSharedService,
        PlayerSubscriptionRepositoryInterface $subscriptionRepository
    ) {
        $this->memberSharedService = $memberSharedService;
        $this->subscriptionRepository = $subscriptionRepository;
    }

    /**
     * Process member check-in using barcode
     */
    public function processCheckIn(string $barcode, $activityId = null)
    {
        // 1. Find member by barcode
        $member = $this->memberSharedService->getMemberByBarcode($barcode);
        if (!$member) {
            throw new Exception(__('Member not found with this barcode.'));
        }

        // 2. Validate member eligibility (Administrative Status)
        if (!$member->isActive) {
            throw new Exception(__('Member is not administratively active.'));
        }

        // 3. Find active subscription
        $subscription = $this->subscriptionRepository->findActiveByMember($member->id);
        if (!$subscription) {
            throw new Exception(__('No active subscription found for this member.'));
        }

        // 3. Validation: Check if frozen
        if ($subscription->status === 'frozen') {
            throw new Exception(__('Subscription is currently frozen.'));
        }

        // 4. Validation: Check expiry date
        if ($subscription->end_date && $subscription->end_date->isPast()) {
            throw new Exception(__('Subscription has expired.'));
        }

        // 5. Validation: Check remaining sessions
        if ($subscription->remaining_sessions !== null && $subscription->remaining_sessions <= 0) {
            throw new Exception(__('No sessions remaining in this subscription.'));
        }

        // 6. Record Attendance
        return \Illuminate\Support\Facades\DB::transaction(function () use ($subscription, $activityId, $member) {
            $attendance = PlayerAttendance::create([
                'member_id' => $subscription->member_id,
                'player_subscription_id' => $subscription->id,
                'activity_id' => $activityId,
                'check_in' => now(),
            ]);

            // 7. Decrement session count
            if ($subscription->remaining_sessions !== null) {
                $subscription->decrement('remaining_sessions');
            }

            $attendance->member = $member;

            return $attendance;
        });
    }

    /**
     * Process member check-out
     */
    public function processCheckOut($attendanceId)
    {
        $attendance = PlayerAttendance::findOrFail($attendanceId);
        
        $checkOutTime = now();
        $duration = $attendance->check_in->diffInMinutes($checkOutTime);

        $attendance->update([
            'check_out' => $checkOutTime,
            'duration_minutes' => $duration
        ]);

        $attendance->member = $this->memberSharedService->getMemberById($attendance->member_id);

        return $attendance;
    }
}
