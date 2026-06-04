<?php

namespace Modules\SubscriptionManager\Policies;

use Modules\AttendanceManager\Contracts\AttendancePolicy;
use Modules\AttendanceManager\DTOs\CheckInAttempt;
use Modules\AttendanceManager\DTOs\AttendanceDecision;
use Modules\SubscriptionManager\Models\PlayerSubscription;
use Modules\ClubManager\Models\Branch;

class SubscriptionAttendancePolicy implements AttendancePolicy
{
    /**
     * Authorize player check-in based on subscription validity and club/branch ownership.
     *
     * @param CheckInAttempt $attempt
     * @return AttendanceDecision
     */
    public function authorize(CheckInAttempt $attempt): AttendanceDecision
    {
        /** @var PlayerSubscription|null $subscription */
        $subscription = PlayerSubscription::find($attempt->attendableId);

        if (!$subscription) {
            return AttendanceDecision::deny("Subscription record not found.");
        }

        if ($subscription->status !== 'active') {
            return AttendanceDecision::deny("Subscription is not active (Current status: {$subscription->status}).");
        }

        // Verify the check-in branch belongs to the specified club
        $checkInBranch = Branch::find($attempt->branchId);
        if (!$checkInBranch || $checkInBranch->club_id != $attempt->clubId) {
            return AttendanceDecision::deny("The check-in branch does not belong to the specified club.");
        }

        // Verify the subscription branch belongs to the specified club
        if ($subscription->branch_id) {
            $subscriptionBranch = Branch::find($subscription->branch_id);
            if (!$subscriptionBranch || $subscriptionBranch->club_id != $attempt->clubId) {
                return AttendanceDecision::deny("Subscription belongs to a different club.");
            }
        }

        $now = now();

        // Check if subscription has started
        if ($subscription->start_date && $subscription->start_date->isFuture()) {
            return AttendanceDecision::deny("Subscription has not started yet (Starts on: {$subscription->start_date->toDateString()}).");
        }

        // Check if subscription has expired
        if ($subscription->end_date && $subscription->end_date->isPast()) {
            return AttendanceDecision::deny("Subscription has expired (Expired on: {$subscription->end_date->toDateString()}).");
        }

        // Check if frozen
        $isFrozen = $subscription->freezes()->where('status', 'active')->exists();
        if ($isFrozen) {
            return AttendanceDecision::deny("Subscription is currently frozen.");
        }

        // Check remaining sessions if session-based
        if ($subscription->remaining_sessions !== null && $subscription->remaining_sessions <= 0) {
            return AttendanceDecision::deny("No remaining sessions left on this subscription.");
        }

        return AttendanceDecision::allow([
            'member_id' => $subscription->member_id,
            'plan_id' => $subscription->plan_id,
            'remaining_sessions_before' => $subscription->remaining_sessions,
        ]);
    }
}
