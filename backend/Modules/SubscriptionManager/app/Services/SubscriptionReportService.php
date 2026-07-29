<?php

namespace Modules\SubscriptionManager\Services;

use Modules\SubscriptionManager\Services\Reports\RenewalReportService;
use Modules\SubscriptionManager\Services\Reports\TimeSlotCapacityReportService;
use Modules\SubscriptionManager\Services\Reports\PeakHoursReportService;
use Modules\SubscriptionManager\Services\Reports\FrozenAndTerminatedReportService;

class SubscriptionReportService
{
    protected RenewalReportService $renewalReportService;
    protected TimeSlotCapacityReportService $timeSlotCapacityReportService;
    protected PeakHoursReportService $peakHoursReportService;
    protected FrozenAndTerminatedReportService $frozenAndTerminatedReportService;

    public function __construct(
        RenewalReportService $renewalReportService,
        TimeSlotCapacityReportService $timeSlotCapacityReportService,
        PeakHoursReportService $peakHoursReportService,
        FrozenAndTerminatedReportService $frozenAndTerminatedReportService
    ) {
        $this->renewalReportService = $renewalReportService;
        $this->timeSlotCapacityReportService = $timeSlotCapacityReportService;
        $this->peakHoursReportService = $peakHoursReportService;
        $this->frozenAndTerminatedReportService = $frozenAndTerminatedReportService;
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
}
