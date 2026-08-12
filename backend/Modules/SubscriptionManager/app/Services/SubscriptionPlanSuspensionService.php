<?php

namespace Modules\SubscriptionManager\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\NotificationManager\Services\NotificationService;
use Modules\Sports\Models\SessionException;
use Modules\Sports\Models\SportSessionTemplate;
use Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus;
use Modules\SubscriptionManager\Models\PlayerSubscription;
use Modules\SubscriptionManager\Models\SubscriptionFreeze;
use Modules\SubscriptionManager\Models\SubscriptionPlan;
use Modules\SubscriptionManager\Models\SubscriptionPlanSuspension;

class SubscriptionPlanSuspensionService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Preview affected subscribers and sessions before confirming suspension.
     */
    public function preview(int $planId, string $startDate, string $endDate): array
    {
        $plan = SubscriptionPlan::with(['branch', 'planActivities.staffActivity.staff.person', 'sessionTemplates'])->findOrFail($planId);
        
        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->startOfDay();
        $days  = (int) $start->diffInDays($end) + 1;
        $today = Carbon::today();

        // 1. Resolve Coach
        $coach = $this->resolveCoachFromPlan($plan);

        // 2. Fetch active subscriptions for this plan
        $subscriptions = PlayerSubscription::where('plan_id', $planId)
            ->where('status', PlayerSubscriptionStatus::ACTIVE->value)
            ->where('start_date', '<=', $end->toDateString())
            ->where('end_date', '>=', $start->toDateString())
            ->with(['member.person.user'])
            ->get();

        $subscribers = $subscriptions->map(function ($sub) use ($days, $today) {
            $currentEndDate = Carbon::parse($sub->end_date);
            $newEndDate = $currentEndDate->copy()->addDays($days);
            $person = $sub->member?->person;

            return [
                'subscription_id'        => $sub->id,
                'member_id'              => $sub->member_id,
                'member_number'          => $sub->member?->member_number,
                'full_name'              => $person?->full_name,
                'current_end_date'       => $currentEndDate->toDateString(),
                'new_end_date'           => $newEndDate->toDateString(),
                'days_extended'          => $days,
                'current_remaining_days' => max(0, (int) $today->diffInDays($currentEndDate, false)),
                'new_remaining_days'     => max(0, (int) $today->diffInDays($newEndDate, false)),
            ];
        });

        // 3. Calculate affected sessions
        $affectedSessions = $this->calculateAffectedSessions($plan, $start, $end);

        return [
            'plan_id'                    => $plan->id,
            'plan_name'                  => $plan->name,
            'coach'                      => $coach ? [
                'id'        => $coach->id,
                'name'      => $coach->person?->full_name ?? 'N/A',
                'role'      => $coach->role,
            ] : null,
            'suspend_start_date'         => $start->toDateString(),
            'suspend_end_date'           => $end->toDateString(),
            'suspension_days'            => $days,
            'affected_subscribers_count' => $subscribers->count(),
            'subscribers'                => $subscribers,
            'affected_sessions_count'    => count($affectedSessions),
            'affected_sessions'          => $affectedSessions,
        ];
    }

    /**
     * Execute suspension of the SubscriptionPlan.
     */
    public function suspend(int $planId, array $data, ?int $userId = null): SubscriptionPlanSuspension
    {
        return DB::transaction(function () use ($planId, $data, $userId) {
            $plan = SubscriptionPlan::with(['branch', 'planActivities.staffActivity.staff.person', 'sessionTemplates'])->findOrFail($planId);
            
            $start = Carbon::parse($data['suspend_start_date'])->startOfDay();
            $end   = Carbon::parse($data['suspend_end_date'])->startOfDay();
            $days  = (int) $start->diffInDays($end) + 1;
            $today = Carbon::today();

            // Check for existing overlapping active/scheduled suspensions
            $hasOverlap = SubscriptionPlanSuspension::where('plan_id', $planId)
                ->whereIn('status', ['scheduled', 'active'])
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('suspend_start_date', [$start->toDateString(), $end->toDateString()])
                      ->orWhereBetween('suspend_end_date', [$start->toDateString(), $end->toDateString()])
                      ->orWhere(function ($subQ) use ($start, $end) {
                          $subQ->where('suspend_start_date', '<=', $start->toDateString())
                               ->where('suspend_end_date', '>=', $end->toDateString());
                      });
                })
                ->exists();

            if ($hasOverlap) {
                throw ValidationException::withMessages([
                    'suspend_start_date' => [__('توجد فترة إيقاف نشطة أو مجدولة تتداخل مع التواريخ المحددة لهذه الفعالية.')],
                ]);
            }

            $coach = $this->resolveCoachFromPlan($plan);
            $status = ($start->isToday() || $start->isPast()) ? 'active' : 'scheduled';

            // 1. Create SubscriptionPlanSuspension
            $suspension = SubscriptionPlanSuspension::create([
                'plan_id'                    => $plan->id,
                'coach_id'                   => $coach?->id,
                'suspend_start_date'         => $start->toDateString(),
                'suspend_end_date'           => $end->toDateString(),
                'suspension_days'            => $days,
                'reason'                     => $data['reason'] ?? null,
                'status'                     => $status,
                'affected_subscribers_count' => 0,
                'created_by'                 => $userId,
            ]);

            // 2. Freeze and extend active player subscriptions
            $subscriptions = PlayerSubscription::where('plan_id', $planId)
                ->where('status', PlayerSubscriptionStatus::ACTIVE->value)
                ->where('start_date', '<=', $end->toDateString())
                ->where('end_date', '>=', $start->toDateString())
                ->with(['member.person.user'])
                ->get();

            $notificationsToSend = [];

            foreach ($subscriptions as $sub) {
                // Record freeze entry
                SubscriptionFreeze::create([
                    'player_subscription_id'          => $sub->id,
                    'subscription_plan_suspension_id' => $suspension->id,
                    'freeze_start_date'               => $start->toDateString(),
                    'freeze_end_date'                 => $end->toDateString(),
                    'reason'                          => 'إيقاف الفعالية: ' . ($data['reason'] ?? 'اعتذار الكوتش'),
                ]);

                // Extend end_date
                $currentEndDate = Carbon::parse($sub->end_date);
                $newEndDate = $currentEndDate->copy()->addDays($days);

                $updatePayload = ['end_date' => $newEndDate->toDateString()];
                if ($status === 'active') {
                    $updatePayload['status'] = PlayerSubscriptionStatus::FROZEN->value;
                }
                $sub->update($updatePayload);

                // Prepare notification data
                $person = $sub->member?->person;
                $userId = $person?->user?->id;
                if ($person && $userId) {
                    $newRemainingDays = max(0, (int) $today->diffInDays($newEndDate, false));
                    $coachName = $coach?->person?->full_name ?? __('الكوتش');
                    $reasonText = !empty($data['reason']) ? " بسبب: {$data['reason']}" : "";

                    $notificationsToSend[] = [
                        'title'           => "⚠️ تعليق فعالية {$plan->name}",
                        'body'            => "عزيزي {$person->full_name}، نعتذر عن تعليق فعالية \"{$plan->name}\" مع الكوتش {$coachName} من {$start->toDateString()} حتى {$end->toDateString()}{$reasonText}. تم تمديد اشتراكك تلقائياً ليصبح تاريخ النهاية: {$newEndDate->toDateString()} (أيامك المتبقية: {$newRemainingDays} يوم).",
                        'user_ids'        => [$userId],
                        'sender_type'     => 'system',
                        'target_snapshot' => [
                            'plan_id'       => $plan->id,
                            'plan_name'     => $plan->name,
                            'suspension_id' => $suspension->id,
                            'type'          => 'subscription_plan_suspension',
                        ],
                    ];
                }
            }

            // 3. Cancel scheduled sessions in the date range
            $this->cancelSessionsInRange($plan, $coach?->id, $start, $end, $data['reason'] ?? 'اعتذار الكوتش عن الفعالية');

            // 4. Send Notifications
            foreach ($notificationsToSend as $notif) {
                try {
                    $this->notificationService->createNotification($notif);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("خطأ أثناء إرسال إشعار تعليق الفعالية: " . $e->getMessage());
                }
            }

            // Update affected count & notified_at
            $suspension->update([
                'affected_subscribers_count' => $subscriptions->count(),
                'notified_at'                => now(),
            ]);

            return $suspension->fresh(['plan', 'coach.person', 'freezes.subscription.member.person']);
        });
    }

    /**
     * Lift suspension early and recalculate subscriber extension dates.
     */
    public function liftSuspension(int $planId, int $suspensionId): SubscriptionPlanSuspension
    {
        return DB::transaction(function () use ($planId, $suspensionId) {
            $suspension = SubscriptionPlanSuspension::where('plan_id', $planId)
                ->with(['plan.branch', 'freezes.subscription.member.person'])
                ->findOrFail($suspensionId);

            if (!in_array($suspension->status, ['scheduled', 'active'])) {
                throw ValidationException::withMessages([
                    'suspension' => [__('هذا الإيقاف غير نشط أو مكتمل مسبقاً.')],
                ]);
            }

            $today = Carbon::today();
            $plan = $suspension->plan;

            // Scenario A: Suspension was scheduled but has not started yet
            if ($suspension->status === 'scheduled') {
                // Revert end_date for all subscribers
                foreach ($suspension->freezes as $freeze) {
                    $sub = $freeze->subscription;
                    if ($sub) {
                        $revertedEndDate = Carbon::parse($sub->end_date)->subDays($suspension->suspension_days);
                        $sub->update(['end_date' => $revertedEndDate->toDateString()]);
                    }
                    $freeze->forceDelete();
                }

                // Delete SessionExceptions
                $this->removeSessionExceptionsInRange($plan, Carbon::parse($suspension->suspend_start_date), Carbon::parse($suspension->suspend_end_date));

                $suspension->update([
                    'status'          => 'cancelled',
                    'actual_end_date' => $today->toDateString(),
                ]);

                // Send notification of cancellation
                $this->sendResumptionNotifications($suspension, "تم إلغاء الإيقاف المجدول لفعالية \"{$plan->name}\" وتستمر الحصص التدريبية كالمعتاد.");

                return $suspension->fresh();
            }

            // Scenario B: Suspension is currently ACTIVE and being lifted early today
            $startDate = Carbon::parse($suspension->suspend_start_date);
            $actualDaysPassed = max(1, (int) $startDate->diffInDays($today));
            $unusedDays = max(0, $suspension->suspension_days - $actualDaysPassed);

            foreach ($suspension->freezes as $freeze) {
                $freeze->update(['actual_end_date' => $today->toDateString()]);

                $sub = $freeze->subscription;
                if ($sub) {
                    $adjustedEndDate = Carbon::parse($sub->end_date)->subDays($unusedDays);
                    $sub->update([
                        'end_date' => $adjustedEndDate->toDateString(),
                        'status'   => PlayerSubscriptionStatus::ACTIVE->value,
                    ]);
                }
            }

            // Restore remaining session instances from today onwards
            $this->removeSessionExceptionsInRange($plan, $today, Carbon::parse($suspension->suspend_end_date));

            $suspension->update([
                'status'          => 'completed',
                'actual_end_date' => $today->toDateString(),
            ]);

            // Send early resumption notification
            $this->sendResumptionNotifications($suspension, "🟢 تم استئناف فعالية \"{$plan->name}\" بنجاح ابتداءً من اليوم. نتمنى لك تدريباً ممتعاً!");

            return $suspension->fresh(['plan', 'coach.person']);
        });
    }

    /**
     * Get list of suspensions for a given SubscriptionPlan.
     */
    public function getSuspensions(int $planId)
    {
        return SubscriptionPlanSuspension::where('plan_id', $planId)
            ->with(['coach.person', 'creator'])
            ->orderByDesc('id')
            ->paginate(15);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Helper Methods
    // ─────────────────────────────────────────────────────────────────────────────

    protected function resolveCoachFromPlan(SubscriptionPlan $plan)
    {
        foreach ($plan->planActivities as $pa) {
            if ($pa->staffActivity && $pa->staffActivity->staff) {
                return $pa->staffActivity->staff;
            }
            if ($pa->coach_id && $pa->coach) {
                return $pa->coach;
            }
        }
        return null;
    }

    protected function calculateAffectedSessions(SubscriptionPlan $plan, Carbon $start, Carbon $end): array
    {
        $sessions = [];
        $templates = $plan->sessionTemplates()->active()->get();
        if ($templates->isEmpty()) {
            return $sessions;
        }

        $period = CarbonPeriod::create($start, $end);

        foreach ($period as $date) {
            $dayOfWeek = $date->dayOfWeek; // 0 (Sun) - 6 (Sat)
            $matchingTemplates = $templates->where('day_of_week', $dayOfWeek);

            foreach ($matchingTemplates as $tmpl) {
                $sessions[] = [
                    'session_template_id' => $tmpl->id,
                    'date'                => $date->format('Y-m-d'),
                    'day_name'            => $date->translatedFormat('l'),
                    'start_time'          => is_string($tmpl->start_time) ? $tmpl->start_time : $tmpl->start_time?->format('H:i'),
                    'end_time'            => is_string($tmpl->end_time) ? $tmpl->end_time : $tmpl->end_time?->format('H:i'),
                    'facility_id'         => $tmpl->facility_id,
                ];
            }
        }

        return $sessions;
    }

    protected function cancelSessionsInRange(SubscriptionPlan $plan, ?int $coachId, Carbon $start, Carbon $end, string $reason): void
    {
        $templates = $plan->sessionTemplates()->active()->get();
        if ($templates->isEmpty()) {
            return;
        }

        $period = CarbonPeriod::create($start, $end);

        foreach ($period as $date) {
            $dayOfWeek = $date->dayOfWeek;
            $matchingTemplates = $templates->where('day_of_week', $dayOfWeek);

            foreach ($matchingTemplates as $tmpl) {
                SessionException::updateOrCreate(
                    [
                        'sport_session_template_id' => $tmpl->id,
                        'date'                      => $date->toDateString(),
                    ],
                    [
                        'coach_id' => $coachId,
                        'status'   => 'cancelled',
                        'reason'   => $reason,
                    ]
                );
            }
        }
    }

    protected function removeSessionExceptionsInRange(SubscriptionPlan $plan, Carbon $start, Carbon $end): void
    {
        $templateIds = $plan->sessionTemplates()->pluck('id');
        if ($templateIds->isNotEmpty()) {
            SessionException::whereIn('sport_session_template_id', $templateIds)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->delete();
        }
    }

    protected function sendResumptionNotifications(SubscriptionPlanSuspension $suspension, string $message): void
    {
        $plan = $suspension->plan;
        foreach ($suspension->freezes as $freeze) {
            $sub = $freeze->subscription;
            $person = $sub?->member?->person;
            $userId = $person?->user?->id;
            if ($person && $userId) {
                try {
                    $this->notificationService->createNotification([
                        'title'           => "🟢 استئناف فعالية {$plan?->name}",
                        'body'            => $message,
                        'user_ids'        => [$userId],
                        'sender_type'     => 'system',
                        'target_snapshot' => [
                            'plan_id'       => $plan?->id,
                            'plan_name'     => $plan?->name,
                            'suspension_id' => $suspension->id,
                            'type'          => 'subscription_plan_resumption',
                        ],
                    ]);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("خطأ أثناء إرسال إشعار استئناف الفعالية: " . $e->getMessage());
                }
            }
        }
    }
}
