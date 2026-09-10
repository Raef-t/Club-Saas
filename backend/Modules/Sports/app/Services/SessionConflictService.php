<?php

namespace Modules\Sports\Services;

use Modules\Sports\Models\SportSessionTemplate;
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Carbon\Carbon;

class SessionConflictService
{
    /**
     * Validate an array of session templates for:
     * 1. Internal overlap on the same day.
     * 2. Overlap with existing sessions in the facility.
     * 3. Overlap with other group sessions in the branch.
     * 4. Overlap for the assigned coach(es).
     *
     * @param array $templates
     * @param int|null $branchId
     * @param int|null $ignorePlanId
     * @param int|null $ignoreTemplateId
     * @param array $coachIds
     * @param bool $isGroupSession
     * @return string|null Error message if conflict found, or null if valid.
     */
    public function validateTemplates(
        array $templates,
        ?int $branchId = null,
        ?int $ignorePlanId = null,
        ?int $ignoreTemplateId = null,
        array $coachIds = [],
        bool $isGroupSession = false
    ): ?string {
        // 1. Internal overlap check within the submitted templates
        $internalError = $this->validateInternalOverlap($templates);
        if ($internalError) {
            return $internalError;
        }

        // 2. Check each template against database records
        foreach ($templates as $template) {
            $conflictError = $this->checkSingleTemplateConflict(
                $template,
                $branchId,
                $ignorePlanId,
                $ignoreTemplateId,
                $coachIds,
                $isGroupSession
            );
            if ($conflictError) {
                return $conflictError;
            }
        }

        return null;
    }

    /**
     * Detect internal time overlap on the same day_of_week within a single array of templates.
     */
    public function validateInternalOverlap(array $templates): ?string
    {
        $byDay = [];
        foreach ($templates as $tmpl) {
            if (!isset($tmpl['day_of_week']) || !isset($tmpl['start_time']) || !isset($tmpl['end_time'])) {
                continue;
            }
            $day = (int) $tmpl['day_of_week'];
            $start = $this->normalizeTime($tmpl['start_time']);
            $end = $this->normalizeTime($tmpl['end_time']);
            $facility = isset($tmpl['facility_id']) && is_numeric($tmpl['facility_id']) ? (int) $tmpl['facility_id'] : null;

            $byDay[$day][] = [
                'start' => $start,
                'end' => $end,
                'facility_id' => $facility,
            ];
        }

        foreach ($byDay as $day => $dayTemplates) {
            $count = count($dayTemplates);
            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $a = $dayTemplates[$i];
                    $b = $dayTemplates[$j];

                    if ($this->timesOverlap($a['start'], $a['end'], $b['start'], $b['end'])) {
                        return __('يوجد تعارض في الوقت بين المواعيد المحددة لنفس اليوم في خطة الاشتراك.');
                    }
                }
            }
        }

        return null;
    }

    /**
     * Check single template against existing database records.
     */
    public function checkSingleTemplateConflict(
        array $template,
        ?int $branchId = null,
        ?int $ignorePlanId = null,
        ?int $ignoreTemplateId = null,
        array $coachIds = [],
        bool $isGroupSession = false
    ): ?string {
        if (!isset($template['day_of_week']) || !isset($template['start_time']) || !isset($template['end_time'])) {
            return null;
        }

        $dayOfWeek = (int) $template['day_of_week'];
        $startTime = $this->normalizeTime($template['start_time']);
        $endTime = $this->normalizeTime($template['end_time']);
        $facilityId = isset($template['facility_id']) && is_numeric($template['facility_id']) ? (int) $template['facility_id'] : null;

        // Rule 1: Same Facility Overlap Check
        if ($facilityId) {
            $facilityConflict = SportSessionTemplate::where('facility_id', $facilityId)
                ->where('day_of_week', $dayOfWeek)
                ->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->when($ignoreTemplateId, fn($q) => $q->where('id', '!=', $ignoreTemplateId))
                ->when($ignorePlanId, fn($q) => $q->where('plan_id', '!=', $ignorePlanId))
                ->exists();

            if ($facilityConflict) {
                return __('يوجد تعارض في الوقت مع جلسة أخرى في نفس المرفق (القاعة).');
            }
        }

        // Rule 2: Branch Group / Session-Based Overlap Check
        // If this activity/plan is session-based (is_session_based == true or حصة جماعية),
        // no two session-based subscription plans can run at the same time and day in the same branch, regardless of facility!
        if ($isGroupSession && $branchId) {
            $hasGroupConflict = SportSessionTemplate::where('day_of_week', $dayOfWeek)
                ->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->when($ignoreTemplateId, fn($q) => $q->where('id', '!=', $ignoreTemplateId))
                ->when($ignorePlanId, fn($q) => $q->where('plan_id', '!=', $ignorePlanId))
                ->whereHas('subscriptionPlan', function ($pq) use ($branchId) {
                    $pq->where('branch_id', $branchId)
                       ->where('status', '!=', 'inactive')
                       ->whereNull('deleted_at')
                       ->where(function ($subQ) {
                           $subQ->whereHas('planActivities.staffActivity.activity.activityType', function ($tq) {
                               $tq->where('is_session_based', true);
                           })
                           ->orWhereHas('planActivities.staffActivity.activity', function ($aq) {
                               $aq->where('name', 'like', '%حصة%')
                                  ->orWhere('name', 'like', '%جماع%')
                                  ->orWhere('name', 'like', '%group%');
                           })
                           ->orWhere('name', 'like', '%حصة%')
                           ->orWhere('name', 'like', '%جماع%')
                           ->orWhere('name', 'like', '%group%');
                       });
                })
                ->exists();

            if ($hasGroupConflict) {
                return __('لا يمكن إنشاء خطتي اشتراك من نوع حصة تدريبية/جماعية في نفس اليوم ونفس الوقت في نفس الفرع.');
            }
        }

        // Rule 3: Assigned Coach Conflict Check
        $cleanCoachIds = array_values(array_filter($coachIds, 'is_numeric'));
        if (!empty($cleanCoachIds)) {
            $coachConflict = SportSessionTemplate::where('day_of_week', $dayOfWeek)
                ->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->when($ignoreTemplateId, fn($q) => $q->where('id', '!=', $ignoreTemplateId))
                ->when($ignorePlanId, fn($q) => $q->where('plan_id', '!=', $ignorePlanId))
                ->whereHas('subscriptionPlan.planActivities.staffActivity', function ($sq) use ($cleanCoachIds) {
                    $sq->whereIn('staff_id', $cleanCoachIds)
                       ->whereNull('deleted_at');
                })
                ->exists();

            if ($coachConflict) {
                return __('المدرب المحدد لديه حصة أخرى في نفس هذا الوقت.');
            }
        }

        return null;
    }

    /**
     * Check if two time intervals overlap: [startA, endA] and [startB, endB].
     */
    public function timesOverlap(string $startA, string $endA, string $startB, string $endB): bool
    {
        return ($startA < $endB) && ($endA > $startB);
    }

    /**
     * Normalize time to 'H:i' format.
     */
    protected function normalizeTime(mixed $time): string
    {
        if ($time instanceof Carbon) {
            return $time->format('H:i');
        }

        $str = trim((string) $time);
        if (preg_match('/^(\d{1,2}):(\d{2})/', $str, $matches)) {
            return sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
        }

        return $str;
    }
}
