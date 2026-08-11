<?php

namespace Modules\SubscriptionManager\Services;

use Modules\SubscriptionManager\Services\Reports\RenewalReportService;
use Modules\SubscriptionManager\Services\Reports\TimeSlotCapacityReportService;
use Modules\SubscriptionManager\Services\Reports\PeakHoursReportService;
use Modules\SubscriptionManager\Services\Reports\FrozenAndTerminatedReportService;
use Modules\SubscriptionManager\Services\Reports\AllSubscriptionsReportService;
use Modules\SubscriptionManager\Services\Reports\ShiftAttendanceReportService;
use Modules\SubscriptionManager\Services\Reports\CoachSubscriptionReportService;

class SubscriptionReportService
{
    protected RenewalReportService $renewalReportService;
    protected TimeSlotCapacityReportService $timeSlotCapacityReportService;
    protected PeakHoursReportService $peakHoursReportService;
    protected FrozenAndTerminatedReportService $frozenAndTerminatedReportService;
    protected AllSubscriptionsReportService $allSubscriptionsReportService;
    protected ShiftAttendanceReportService $shiftAttendanceReportService;
    protected CoachSubscriptionReportService $coachSubscriptionReportService;

    public function __construct(
        RenewalReportService $renewalReportService,
        TimeSlotCapacityReportService $timeSlotCapacityReportService,
        PeakHoursReportService $peakHoursReportService,
        FrozenAndTerminatedReportService $frozenAndTerminatedReportService,
        AllSubscriptionsReportService $allSubscriptionsReportService,
        ShiftAttendanceReportService $shiftAttendanceReportService,
        CoachSubscriptionReportService $coachSubscriptionReportService
    ) {
        $this->renewalReportService = $renewalReportService;
        $this->timeSlotCapacityReportService = $timeSlotCapacityReportService;
        $this->peakHoursReportService = $peakHoursReportService;
        $this->frozenAndTerminatedReportService = $frozenAndTerminatedReportService;
        $this->allSubscriptionsReportService = $allSubscriptionsReportService;
        $this->shiftAttendanceReportService = $shiftAttendanceReportService;
        $this->coachSubscriptionReportService = $coachSubscriptionReportService;
    }

    /**
     * Get comprehensive report for all subscriptions.
     */
    public function getAllSubscriptionsReport(array $filters = []): array
    {
        return $this->allSubscriptionsReportService->getReport($filters);
    }

    /**
     * Get report for expired & renewed subscriptions with detailed metrics.
     */
    public function getRenewalStatusReport(array $filters = []): array
    {
        return $this->renewalReportService->getReport($filters);
    }

    /**
     * Get report for session templates, plans, and subscriber counts filtered by time slot.
     */
    public function getTimeSlotCapacityReport(array $filters = []): array
    {
        return $this->timeSlotCapacityReportService->getReport($filters);
    }

    /**
     * Get report for peak and off-peak attendance hours excluding branch holidays.
     */
    public function getPeakHoursReport(array $filters = []): array
    {
        return $this->peakHoursReportService->getReport($filters);
    }

    /**
     * Get report for frozen and terminated subscriptions with detailed reasons & timelines.
     */
    public function getFrozenAndTerminatedReport(array $filters = []): array
    {
        return $this->frozenAndTerminatedReportService->getReport($filters);
    }

    /**
     * Get report for player attendance per shift and identify busiest shifts.
     */
    public function getShiftAttendanceReport(array $filters = []): array
    {
        return $this->shiftAttendanceReportService->getReport($filters);
    }

    /**
     * Get report for coaches, their activities, subscription plans, and active subscriber counts.
     */
    public function getCoachSubscriptionReport(array $filters = []): array
    {
        return $this->coachSubscriptionReportService->getReport($filters);
    }
}
