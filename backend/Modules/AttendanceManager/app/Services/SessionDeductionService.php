<?php

namespace Modules\AttendanceManager\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Modules\AttendanceManager\Models\Attendance;
use Modules\NotificationManager\Services\NotificationService;
use Modules\MemberManager\Models\Member;

class SessionDeductionService
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Automatically find an active subscription for a member and deduct a session.
     * Used mainly for Gate entries where there's no receptionist to pick a subscription.
     *
     * @param int $attendanceId
     * @param int $memberId
     * @return Attendance
     * @throws Exception
     */
    /**
     * Automatically find an active subscription for a member and deduct a session.
     * Used mainly for Gate entries where there's no receptionist to pick a subscription.
     *
     * @param int $attendanceId
     * @param int $memberId
     * @return Attendance
     * @throws Exception
     */
    public function autoDeductSessionForGate(int $attendanceId, int $memberId): Attendance
    {
        $todayString = now()->toDateString();
        $subscriptionIds = $this->getAvailableSubscriptionsForMemberOnDate($memberId, $todayString);

        // Deduct a session from each active subscription in a single transaction
        return $this->deductMultipleSessions($attendanceId, $subscriptionIds);
    }

    /**
     * Get all active and valid subscription IDs for a member on a specific date that have scheduled sessions
     * (or are open plans without templates) and have remaining sessions.
     * Throws an informative exception if no valid subscriptions are found.
     *
     * @param int $memberId
     * @param string $dateString
     * @return int[]
     * @throws Exception
     */
    public function getAvailableSubscriptionsForMemberOnDate(int $memberId, string $dateString): array
    {
        $checkDate = \Carbon\Carbon::parse($dateString)->startOfDay();
        $targetDate = $checkDate->toDateString();
        $dayOfWeek = (int) $checkDate->dayOfWeek;

        // 1. Check if the member has ANY active subscription in the system
        $hasAnyActiveSub = DB::table('player_subscriptions as ps')
            ->join('subscription_plans as sp', 'sp.id', '=', 'ps.plan_id')
            ->where('ps.member_id', $memberId)
            ->where('ps.status', 'active')
            ->whereNull('ps.deleted_at')
            ->whereNull('sp.deleted_at')
            ->where(function ($planStatusQ) {
                $planStatusQ->whereNull('sp.status')
                            ->orWhere('sp.status', '!=', 'inactive');
            })
            ->exists();

        if (!$hasAnyActiveSub) {
            throw new Exception(__('لا توجد اشتراكات نشطة لهذا المشترك.'));
        }

        // 2. Check if the member has active subscriptions valid on the check-in date
        // (started, not expired, not frozen, plan not suspended)
        $validDateQuery = DB::table('player_subscriptions as ps')
            ->join('subscription_plans as sp', 'sp.id', '=', 'ps.plan_id')
            ->where('ps.member_id', $memberId)
            ->where('ps.status', 'active')
            ->whereNull('ps.deleted_at')
            ->whereNull('sp.deleted_at')
            ->where(function ($planStatusQ) {
                $planStatusQ->whereNull('sp.status')
                            ->orWhere('sp.status', '!=', 'inactive');
            })
            ->where(function ($startQ) use ($targetDate) {
                $startQ->whereNull('ps.start_date')
                       ->orWhereDate('ps.start_date', '<=', $targetDate);
            })
            ->where(function ($endQ) use ($targetDate) {
                $endQ->whereNull('ps.end_date')
                     ->orWhereDate('ps.end_date', '>=', $targetDate);
            })
            ->whereNotExists(function ($freezeQ) use ($targetDate) {
                $freezeQ->select(DB::raw(1))
                    ->from('subscription_freezes as sf')
                    ->whereColumn('sf.player_subscription_id', 'ps.id')
                    ->whereNull('sf.deleted_at')
                    ->whereDate('sf.freeze_start_date', '<=', $targetDate)
                    ->whereDate('sf.freeze_end_date', '>=', $targetDate);
            })
            ->whereNotExists(function ($suspQ) use ($targetDate) {
                $suspQ->select(DB::raw(1))
                    ->from('subscription_plan_suspensions as sps')
                    ->whereColumn('sps.plan_id', 'ps.plan_id')
                    ->whereNull('sps.deleted_at')
                    ->where('sps.status', '!=', 'cancelled')
                    ->whereDate('sps.suspend_start_date', '<=', $targetDate)
                    ->where(function ($dateQ) use ($targetDate) {
                        $dateQ->where(function ($actualQ) use ($targetDate) {
                            $actualQ->whereNotNull('sps.actual_end_date')
                                    ->whereDate('sps.actual_end_date', '>=', $targetDate);
                        })->orWhere(function ($endQ) use ($targetDate) {
                            $endQ->whereNull('sps.actual_end_date')
                                 ->whereDate('sps.suspend_end_date', '>=', $targetDate);
                        });
                    });
            });

        if (!(clone $validDateQuery)->exists()) {
            throw new Exception(__('لا توجد اشتراكات نشطة وصالحة لهذا المشترك اليوم (قد يكون الاشتراك منتهياً أو مجمداً أو لم يبدأ بعد).'));
        }

        // 3. Check if any valid subscription has a scheduled session today (or is an open plan without templates)
        $validSessionQuery = (clone $validDateQuery)->where(function ($sessionQ) use ($dayOfWeek, $targetDate) {
            // Case 1: Plan has NO session templates defined (open gym / equipment / daily entry)
            $sessionQ->whereNotExists(function ($noTmplQ) {
                $noTmplQ->select(DB::raw(1))
                    ->from('sport_session_templates as sst_all')
                    ->whereColumn('sst_all.plan_id', 'ps.plan_id')
                    ->where('sst_all.is_active', true)
                    ->whereNull('sst_all.deleted_at');
            })
            // Case 2: Plan HAS session templates, and has at least one active template for today's day_of_week and not cancelled
            ->orWhereExists(function ($hasTmplQ) use ($dayOfWeek, $targetDate) {
                $hasTmplQ->select(DB::raw(1))
                    ->from('sport_session_templates as sst_today')
                    ->whereColumn('sst_today.plan_id', 'ps.plan_id')
                    ->where('sst_today.is_active', true)
                    ->where('sst_today.day_of_week', $dayOfWeek)
                    ->whereNull('sst_today.deleted_at')
                    ->whereNotExists(function ($excQ) use ($targetDate) {
                        $excQ->select(DB::raw(1))
                            ->from('session_exceptions as se')
                            ->whereColumn('se.sport_session_template_id', 'sst_today.id')
                            ->whereDate('se.date', $targetDate)
                            ->whereIn('se.status', ['cancelled', 'canceled'])
                            ->whereNull('se.deleted_at');
                    });
            });
        });

        $candidateSubIds = (clone $validSessionQuery)->orderBy('ps.id', 'asc')->pluck('ps.id')->toArray();

        if (empty($candidateSubIds)) {
            throw new Exception(__('لا توجد جلسات مجدولة لهذا المشترك اليوم.'));
        }

        // 4. Filter by remaining sessions
        $availableSubIds = [];
        foreach ($candidateSubIds as $subId) {
            $items = DB::table('player_subscription_items')
                ->where('player_subscription_id', $subId)
                ->whereNull('deleted_at')
                ->get();

            if ($items->isEmpty()) {
                $availableSubIds[] = $subId;
                continue;
            }

            $hasRemaining = $items->contains(function ($item) {
                return !empty($item->is_unlimited) || ($item->sessions_allocated > $item->sessions_consumed);
            });

            if ($hasRemaining) {
                $availableSubIds[] = $subId;
            }
        }

        if (empty($availableSubIds)) {
            throw new Exception(__('لا توجد جلسات مجدولة أو متبقية لهذا المشترك اليوم.'));
        }

        return $availableSubIds;
    }

    /**
     * Deducts a session from the specified subscription for the given attendance record.
     * 
     * @param int         $attendanceId
     * @param int         $subscriptionId
     * @param string|null $reason
     * @return Attendance
     * @throws Exception
     */
    public function deductSession(int $attendanceId, int $subscriptionId, ?string $reason = null): Attendance
    {
        return DB::transaction(function () use ($attendanceId, $subscriptionId, $reason) {
            $attendance = Attendance::where('attendable_type', 'member')->findOrFail($attendanceId);

            $alreadyConsumed = \Modules\AttendanceManager\Models\AttendanceConsumption::where('attendance_id', $attendanceId)
                ->where('player_subscription_id', $subscriptionId)
                ->exists();

            if ($alreadyConsumed) {
                throw new Exception(__('Session already deducted for this attendance.'));
            }

            $subscription = DB::table('player_subscriptions')
                ->where('id', $subscriptionId)
                ->where('member_id', $attendance->attendable_id)
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->first();

            if (!$subscription) {
                throw new Exception(__('The selected subscription is not active or does not belong to this member.'));
            }

            $attendanceTimestamp = $attendance->check_in_at ?: now();
            $effectiveReason = $reason ?? $attendance->notes;
            $this->validateSubscriptionAvailability($subscription, $attendanceTimestamp, $effectiveReason);

            $branch = DB::table('branches')->where('id', $attendance->branch_id)->first();
            $this->validateDebt($subscription, $branch ? $branch->club_id : 1);

            $items = DB::table('player_subscription_items')
                ->where('player_subscription_id', $subscription->id)
                ->where('is_unlimited', false)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->get();

            $this->validateRemainingSessions($items);

            $deductedItem = $this->decrementSessionsConsumed($items);

            $this->checkAndFinishSubscriptionIfExhausted($subscription->id, $subscription->plan_id);

            $planName = DB::table('subscription_plans')->where('id', $subscription->plan_id)->value('name') ?? 'اشتراك';

            \Modules\AttendanceManager\Models\AttendanceConsumption::create([
                'attendance_id' => $attendance->id,
                'player_subscription_id' => $subscription->id,
                'subscription_plan_id' => $subscription->plan_id,
            ]);

            if (!empty($reason) && $attendance->notes !== $reason) {
                $attendance->update(['notes' => $reason]);
            }

            $attendance = $attendance->fresh();

            $this->sendNotification($attendance, $planName, $deductedItem);

            return $attendance;
        });
    }

    /**
     * Deducts a session from each of the specified subscriptions for the given attendance record.
     * All deductions are performed inside a single transaction; any failure rolls back everything.
     *
     * @param int         $attendanceId
     * @param int[]       $subscriptionIds
     * @param string|null $reason
     * @return Attendance
     * @throws Exception
     */
    public function deductMultipleSessions(int $attendanceId, array $subscriptionIds, ?string $reason = null): Attendance
    {
        return DB::transaction(function () use ($attendanceId, $subscriptionIds, $reason) {
            $attendance = Attendance::where('attendable_type', 'member')->findOrFail($attendanceId);
            $branch = DB::table('branches')->where('id', $attendance->branch_id)->first();
            $clubId = $branch ? $branch->club_id : 1;

            // Auto-add the general (عام) subscription when deducting from a private (خاص) subscription
            $subscriptionIds = $this->enrichWithGeneralSubscription($attendance->attendable_id, $subscriptionIds);

            $attendanceTimestamp = $attendance->check_in_at ?: now();
            $effectiveReason = $reason ?? $attendance->notes;

            foreach ($subscriptionIds as $subscriptionId) {
                $alreadyConsumed = \Modules\AttendanceManager\Models\AttendanceConsumption::where('attendance_id', $attendanceId)
                    ->where('player_subscription_id', $subscriptionId)
                    ->exists();

                if ($alreadyConsumed) {
                    throw new Exception(__('Session already deducted for this attendance (Subscription ID: :id).', ['id' => $subscriptionId]));
                }

                $subscription = DB::table('player_subscriptions')
                    ->where('id', $subscriptionId)
                    ->where('member_id', $attendance->attendable_id)
                    ->where('status', 'active')
                    ->whereNull('deleted_at')
                    ->first();

                if (!$subscription) {
                    throw new Exception(__('الاشتراك المحدد (رقم :id) غير نشط أو لا ينتمي لهذا المشترك.', ['id' => $subscriptionId]));
                }

                $this->validateSubscriptionAvailability($subscription, $attendanceTimestamp, $effectiveReason);

                $this->validateDebt($subscription, $clubId);

                $items = DB::table('player_subscription_items')
                    ->where('player_subscription_id', $subscription->id)
                    ->where('is_unlimited', false)
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->get();

                $this->validateRemainingSessions($items);

                $deductedItem = $this->decrementSessionsConsumed($items);

                $this->checkAndFinishSubscriptionIfExhausted($subscription->id, $subscription->plan_id);

                \Modules\AttendanceManager\Models\AttendanceConsumption::create([
                    'attendance_id' => $attendance->id,
                    'player_subscription_id' => $subscription->id,
                    'subscription_plan_id' => $subscription->plan_id,
                ]);

                $planName = DB::table('subscription_plans')->where('id', $subscription->plan_id)->value('name') ?? 'اشتراك';
                $this->sendNotification($attendance, $planName, $deductedItem);
            }

            if (!empty($reason) && $attendance->notes !== $reason) {
                $attendance->update(['notes' => $reason]);
            }

            return $attendance->fresh();
        });
    }

    /**
     * Validate that the subscription is valid, has started, is not expired, not frozen,
     * not suspended, and has a scheduled session today (or is an open plan).
     * If off schedule, ensures an override reason is provided.
     */
    private function validateSubscriptionAvailability($subscription, $attendanceTimestamp, ?string $reason = null): void
    {
        $checkCarbon = $attendanceTimestamp instanceof \Carbon\Carbon
            ? $attendanceTimestamp
            : \Carbon\Carbon::parse($attendanceTimestamp);

        $dateString = $checkCarbon->toDateString();
        $dayOfWeek = (int) $checkCarbon->dayOfWeek;

        // 1. Check if subscription has not started yet
        if ($subscription->start_date && $dateString < \Carbon\Carbon::parse($subscription->start_date)->toDateString()) {
            throw new Exception(__('لا يمكن تسجيل الحضور: الاشتراك أو الفعالية لم تبدأ بعد. (تاريخ البدء: :date)', ['date' => $subscription->start_date]));
        }

        // 2. Check if subscription has expired
        if ($subscription->end_date && $dateString > \Carbon\Carbon::parse($subscription->end_date)->toDateString()) {
            throw new Exception(__('لا يمكن تسجيل الحضور: الاشتراك منتهي الصلاحية. (تاريخ الانتهاء: :date)', ['date' => $subscription->end_date]));
        }

        // 3. Check if subscription is currently frozen
        $isFrozen = DB::table('subscription_freezes')
            ->where('player_subscription_id', $subscription->id)
            ->whereNull('deleted_at')
            ->whereDate('freeze_start_date', '<=', $dateString)
            ->whereDate('freeze_end_date', '>=', $dateString)
            ->exists();

        if ($isFrozen) {
            throw new Exception(__('لا يمكن تسجيل الحضور: الاشتراك مجمّد حالياً.'));
        }

        // 4. Check if plan is suspended
        $isSuspended = DB::table('subscription_plan_suspensions')
            ->where('plan_id', $subscription->plan_id)
            ->whereNull('deleted_at')
            ->where('status', '!=', 'cancelled')
            ->whereDate('suspend_start_date', '<=', $dateString)
            ->where(function ($dateQ) use ($dateString) {
                $dateQ->where(function ($actualQ) use ($dateString) {
                    $actualQ->whereNotNull('actual_end_date')
                            ->whereDate('actual_end_date', '>=', $dateString);
                })->orWhere(function ($endQ) use ($dateString) {
                    $endQ->whereNull('actual_end_date')
                         ->whereDate('suspend_end_date', '>=', $dateString);
                });
            })
            ->exists();

        if ($isSuspended) {
            throw new Exception(__('لا يمكن تسجيل الحضور: الفعالية موقوفة حالياً.'));
        }

        // 5. Check if plan has session templates and whether today has a valid session
        $hasTemplates = DB::table('sport_session_templates')
            ->where('plan_id', $subscription->plan_id)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasTemplates) {
            $todayTemplates = DB::table('sport_session_templates as sst')
                ->where('sst.plan_id', $subscription->plan_id)
                ->where('sst.is_active', true)
                ->where('sst.day_of_week', $dayOfWeek)
                ->whereNull('sst.deleted_at')
                ->whereNotExists(function ($excQ) use ($dateString) {
                    $excQ->select(DB::raw(1))
                        ->from('session_exceptions as se')
                        ->whereColumn('se.sport_session_template_id', 'sst.id')
                        ->whereDate('se.date', $dateString)
                        ->whereIn('se.status', ['cancelled', 'canceled'])
                        ->whereNull('se.deleted_at');
                })
                ->select('sst.id', 'sst.start_time', 'sst.end_time')
                ->get();

            if ($todayTemplates->isEmpty()) {
                throw new Exception(__('لا يمكن تسجيل الحضور: لا توجد جلسة مجدولة لهذا الاشتراك اليوم.'));
            }

            // Check if attendance check-in time falls within any active template today
            $checkInTimeStr = $checkCarbon->format('H:i:s');
            $isOnSchedule = false;
            $formattedScheduleTimes = [];

            foreach ($todayTemplates as $tmpl) {
                $startTimeStr = \Carbon\Carbon::parse($tmpl->start_time)->format('H:i:s');
                $endTimeStr = \Carbon\Carbon::parse($tmpl->end_time)->format('H:i:s');

                $formattedStart = \Carbon\Carbon::parse($tmpl->start_time)->format('h:i A');
                $formattedEnd = \Carbon\Carbon::parse($tmpl->end_time)->format('h:i A');
                $formattedScheduleTimes[] = "{$formattedStart} - {$formattedEnd}";

                if ($endTimeStr >= $startTimeStr) {
                    if ($checkInTimeStr >= $startTimeStr && $checkInTimeStr <= $endTimeStr) {
                        $isOnSchedule = true;
                        break;
                    }
                } else {
                    // Cross-midnight session
                    if ($checkInTimeStr >= $startTimeStr || $checkInTimeStr <= $endTimeStr) {
                        $isOnSchedule = true;
                        break;
                    }
                }
            }

            if (!$isOnSchedule && empty(trim($reason ?? ''))) {
                $timesList = implode(', ', $formattedScheduleTimes);
                throw new Exception(__('لا يمكن تسجيل الحضور: هذا ليس موعد فعاليتك المجدول (الموعد المجدول اليوم: :times). يرجى إدخال سبب تسجيل الحضور في هذا الوقت.', ['times' => $timesList]));
            }
        }
    }

    private function validateDebt($subscription, $clubId): void
    {
        if ($subscription->remaining_amount > 0) {
            $settings = DB::table('club_settings')->where('club_id', $clubId)->first();
            $allowedDebt = $settings->allowed_debt_limit ?? 0;
            $gracePeriod = $settings->grace_period_days ?? 0;

            if ($subscription->remaining_amount > $allowedDebt) {
                throw new Exception(__('Access denied: Outstanding debt exceeds the allowed limit.'));
            }

            $startDate = \Carbon\Carbon::parse($subscription->start_date);
            if (now()->diffInDays($startDate) > $gracePeriod) {
                throw new Exception(__('Access denied: Grace period for payment has expired.'));
            }
        }
    }

    private function validateRemainingSessions($items): void
    {
        if ($items->isNotEmpty()) {
            $hasAvailable = $items->contains(
                fn($item) => $item->sessions_consumed < $item->sessions_allocated
            );

            if (!$hasAvailable) {
                throw new Exception(__('No remaining sessions in the selected subscription.'));
            }
        }
    }

    private function decrementSessionsConsumed($items)
    {
        $deductedItem = null;
        foreach ($items as $item) {
            if ($item->sessions_consumed < $item->sessions_allocated) {
                DB::table('player_subscription_items')
                    ->where('id', $item->id)
                    ->increment('sessions_consumed');
                $deductedItem = $item;
                break;
            }
        }
        return $deductedItem;
    }


    /**
     * When the supplied subscription list contains a "private training" (تدريب خاص, activity_type_id=5)
     * subscription, this helper finds the member's active "general training" (تدريب عام, activity_type_id=4)
     * subscription and appends it to the list so both are deducted together.
     *
     * @param int   $memberId
     * @param int[] $subscriptionIds
     * @return int[]
     */
    private function enrichWithGeneralSubscription(int $memberId, array $subscriptionIds): array
    {
        // PRIVATE activity type id
        $privateTypeId = 5;
        // GENERAL activity type id
        $generalTypeId = 4;

        // Determine which activity types are represented in the requested subscriptions
        $typesInRequest = DB::table('player_subscriptions as ps')
            ->join('plan_activities as pa', 'pa.plan_id', '=', 'ps.plan_id')
            ->join('staff_activities as sa', 'sa.id', '=', 'pa.staff_activity_id')
            ->join('activities as act', 'act.id', '=', 'sa.activity_id')
            ->whereIn('ps.id', $subscriptionIds)
            ->whereNull('pa.deleted_at')
            ->pluck('act.activity_type_id')
            ->unique()
            ->values()
            ->toArray();

        // Only enrich if private type is being deducted and general type is NOT already in the list
        if (in_array($privateTypeId, $typesInRequest) && !in_array($generalTypeId, $typesInRequest)) {
            $today = now()->toDateString();
            // Find the member's active general subscription that is NOT already included
            $generalSubId = DB::table('player_subscriptions as ps')
                ->join('plan_activities as pa', 'pa.plan_id', '=', 'ps.plan_id')
                ->join('staff_activities as sa', 'sa.id', '=', 'pa.staff_activity_id')
                ->join('activities as act', 'act.id', '=', 'sa.activity_id')
                ->where('ps.member_id', $memberId)
                ->where('ps.status', 'active')
                ->whereNull('ps.deleted_at')
                ->whereNull('pa.deleted_at')
                ->where(function ($dateQ) use ($today) {
                    $dateQ->whereNull('ps.start_date')
                          ->orWhereDate('ps.start_date', '<=', $today);
                })
                ->where(function ($dateQ) use ($today) {
                    $dateQ->whereNull('ps.end_date')
                          ->orWhereDate('ps.end_date', '>=', $today);
                })
                ->where('act.activity_type_id', $generalTypeId)
                ->whereNotIn('ps.id', $subscriptionIds)
                ->value('ps.id');

            if ($generalSubId) {
                $subscriptionIds[] = $generalSubId;
            }
        }

        return array_unique($subscriptionIds);
    }

    /**
     * Rollbacks session deductions for a given attendance.
     *
     * Behaviour depends on whether specific subscription IDs are provided:
     *  - No IDs supplied  → rollback ALL deductions and DELETE the attendance record (original behaviour).
     *  - IDs supplied     → rollback only the matched deductions.
     *                       If all deductions are now rolled back the attendance is also deleted;
     *                       otherwise the attendance record is kept with the remaining deductions.
     *
     * @param int   $attendanceId
     * @param int[] $subscriptionIds  Optional list of player_subscription IDs to rollback selectively.
     * @return void
     * @throws Exception
     */
    public function rollbackDeduction(int $attendanceId, array $subscriptionIds = []): void
    {
        DB::transaction(function () use ($attendanceId, $subscriptionIds) {
            $attendance = Attendance::findOrFail($attendanceId);

            if ($attendance->attendable_type === 'staff') {
                if (!empty($subscriptionIds)) {
                    throw new Exception(__('Staff attendances do not have subscription sessions to rollback.'));
                }
                $attendance->delete();
                return;
            }

            $member     = Member::with('person.user')->find($attendance->attendable_id);
            $userId     = $member?->person?->user?->id;
            $playerName = $member?->person?->full_name ?? 'لاعبنا العزيز';

            $remainingSessionsInfo = [];
            $isSessionBased        = false;

            $consumptionsQuery = \Modules\AttendanceManager\Models\AttendanceConsumption::where('attendance_id', $attendanceId);
            
            if (!empty($subscriptionIds)) {
                $consumptionsQuery->whereIn('player_subscription_id', $subscriptionIds);
            }

            $consumptions = $consumptionsQuery->get();

            if ($consumptions->isEmpty()) {
                // If there are no consumptions, maybe they were already rolled back, but we still want to clean up if a full rollback was requested.
                if (empty($subscriptionIds)) {
                    $attendance->delete();
                }
                return;
            }

            foreach ($consumptions as $consumption) {
                // Try to find a consumed item to rollback for this subscription
                $item = DB::table('player_subscription_items')
                    ->where('player_subscription_id', $consumption->player_subscription_id)
                    ->where('sessions_consumed', '>', 0)
                    ->lockForUpdate()
                    ->first();

                if ($item) {
                    $isSessionBased = true;
                    DB::table('player_subscription_items')
                        ->where('id', $item->id)
                        ->decrement('sessions_consumed');

                    $remainingSessionsInfo[] = $item->sessions_allocated - ($item->sessions_consumed - 1);
                }

                $consumption->delete();

                $this->restoreSubscriptionIfActiveCandidate($consumption->player_subscription_id);
            }

            // Check if any consumptions remain for this attendance
            $remainingConsumptions = \Modules\AttendanceManager\Models\AttendanceConsumption::where('attendance_id', $attendanceId)->exists();
            if (!$remainingConsumptions) {
                $attendance->delete();
            }

            // ── Send notification ──
            if ($userId) {
                if ($isSessionBased && !empty($remainingSessionsInfo)) {
                    $template     = \Modules\NotificationManager\Models\NotificationTemplate::where('system_key', 'attendance_rollback_session')->first();
                    $remainingStr = implode(', ', $remainingSessionsInfo);
                    if ($template) {
                        $body  = $template->parseBody([
                            'اسم اللاعب'       => $playerName,
                            'الجلسات المتبقية' => $remainingStr,
                        ]);
                        $title = $template->subject ?? 'إلغاء دخول واسترجاع جلسة 🔄';
                    } else {
                        $title = 'إلغاء دخول واسترجاع جلسة 🔄';
                        $body  = "أهلاً بك {$playerName}، تم إلغاء تسجيل دخولك الأخير واسترجاع الجلسة إلى رصيدك. الجلسات المتبقية لك الآن: {$remainingStr} جلسة.";
                    }
                } else {
                    $template = \Modules\NotificationManager\Models\NotificationTemplate::where('system_key', 'attendance_rollback_time')->first();
                    if ($template) {
                        $body  = $template->parseBody(['اسم اللاعب' => $playerName]);
                        $title = $template->subject ?? 'إلغاء تسجيل الدخول 🔄';
                    } else {
                        $title = 'إلغاء تسجيل الدخول 🔄';
                        $body  = "أهلاً بك {$playerName}، تم إلغاء تسجيل دخولك الأخير بنجاح. نتمنى رؤيتك قريباً!";
                    }
                }

                $this->notificationService->createNotification([
                    'title'       => $title,
                    'body'        => $body,
                    'user_ids'    => [$userId],
                    'sender_type' => 'system',
                ]);
            }
        });
    }

    private function sendNotification(Attendance $attendance, string $planName, $deductedItem): void
    {
        $member = Member::with('person.user')->find($attendance->attendable_id);
        $userId = $member?->person?->user?->id;
        $playerName = $member?->person?->full_name ?? 'لاعبنا العزيز';

        if ($userId) {
            $now = \Carbon\Carbon::now();
            $date = $now->format('Y-m-d');
            $dayName = $now->locale('ar')->translatedFormat('l');
            $time = $now->locale('ar')->translatedFormat('h:i A');

            if ($deductedItem) {
                $remaining = $deductedItem->sessions_allocated - ($deductedItem->sessions_consumed + 1);
                $template = \Modules\NotificationManager\Models\NotificationTemplate::where('system_key', 'attendance_session_based')->first();

                if ($template) {
                    $body = $template->parseBody([
                        'اسم اللاعب' => $playerName,
                        'التاريخ' => $date,
                        'اليوم' => $dayName,
                        'الوقت' => $time,
                        'اسم الاشتراك' => $planName,
                        'الجلسات المتبقية' => $remaining,
                    ]);
                    $title = $template->subject ?? 'خصم جلسة تسجيل حضور';
                } else {
                    $title = 'خصم جلسة تسجيل حضور';
                    $body = "أهلاً بك {$playerName}، تم تسجيل حضورك وخصم جلسة من اشتراكك بنجاح. الجلسات المتبقية: {$remaining}";
                }
            } else {
                $template = \Modules\NotificationManager\Models\NotificationTemplate::where('system_key', 'attendance_time_based')->first();

                if ($template) {
                    $body = $template->parseBody([
                        'اسم اللاعب' => $playerName,
                        'التاريخ' => $date,
                        'اليوم' => $dayName,
                        'الوقت' => $time,
                        'اسم الاشتراك' => $planName,
                    ]);
                    $title = $template->subject ?? 'تسجيل حضور ناجح';
                } else {
                    $title = 'تسجيل حضور ناجح';
                    $body = "أهلاً بك {$playerName}، تم تسجيل حضورك بنجاح. نتمنى لك تمريناً ممتعاً!";
                }
            }

            $this->notificationService->createNotification([
                'title' => $title,
                'body' => $body,
                'user_ids' => [$userId],
                'sender_type' => 'system'
            ]);
        }
    }

    private function checkAndFinishSubscriptionIfExhausted(int $subscriptionId, ?int $planId = null): void
    {
        $items = DB::table('player_subscription_items')
            ->where('player_subscription_id', $subscriptionId)
            ->whereNull('deleted_at')
            ->get();

        if ($items->isEmpty()) {
            return;
        }

        $hasUnlimited = $items->contains(fn($i) => (bool) $i->is_unlimited);
        $hasRemaining = $items->contains(fn($i) => (int) $i->sessions_consumed < (int) $i->sessions_allocated);

        if (!$hasUnlimited && !$hasRemaining) {
            DB::table('player_subscriptions')
                ->where('id', $subscriptionId)
                ->where('status', \Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus::ACTIVE->value)
                ->update(['status' => \Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus::FINISHED->value]);

            if ($planId) {
                $plan = \Modules\SubscriptionManager\Models\SubscriptionPlan::find($planId);
                if ($plan) {
                    app(\Modules\SubscriptionManager\Services\SubscriptionService::class)->decrementPlanSubscribers($plan);
                }
            }
        }
    }

    private function restoreSubscriptionIfActiveCandidate(int $subscriptionId): void
    {
        $sub = DB::table('player_subscriptions')->where('id', $subscriptionId)->first();
        if (!$sub || $sub->status !== \Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus::FINISHED->value) {
            return;
        }

        $today = now()->toDateString();
        $dateValid = empty($sub->end_date) || \Carbon\Carbon::parse($sub->end_date)->toDateString() >= $today;

        if ($dateValid) {
            DB::table('player_subscriptions')
                ->where('id', $subscriptionId)
                ->update(['status' => \Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus::ACTIVE->value]);

            if ($sub->plan_id) {
                $plan = \Modules\SubscriptionManager\Models\SubscriptionPlan::find($sub->plan_id);
                if ($plan) {
                    app(\Modules\SubscriptionManager\Services\SubscriptionService::class)->incrementPlanSubscribers($plan);
                }
            }
        }
    }
}
