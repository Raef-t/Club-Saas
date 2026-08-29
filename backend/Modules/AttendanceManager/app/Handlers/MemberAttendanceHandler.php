<?php

namespace Modules\AttendanceManager\Handlers;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\AttendanceManager\Contracts\AttendanceHandlerInterface;
use Modules\AttendanceManager\Events\MemberCheckedIn;
use Modules\AttendanceManager\Events\MemberCheckedOut;
use Modules\AttendanceManager\Models\Attendance;

class MemberAttendanceHandler implements AttendanceHandlerInterface
{
    /**
     * Check in a member via the reception desk.
     *
     * Flow:
     *  1. Resolve the subscription — from explicit subscription_id in metadata
     *     (chosen by the receptionist) or fallback to the first active subscription.
     *  2. Ensure no open check-in exists.
     *  3. Validate debt / remaining sessions on the selected subscription.
     *  4. Create Attendance record, capturing:
     *      - recorded_by_staff_id  → the currently authenticated user (receptionist)
     *  5. Decrement sessions_consumed ONLY in player_subscription_items (per user's spec).
     *  6. Fire MemberCheckedIn event.
     */
    public function checkIn(int $entityId, int $branchId, ?string $checkInAt = null, ?array $subscriptionIds = null, ?int $lockerId = null, ?string $notes = null): Attendance
    {
        return DB::transaction(function () use ($entityId, $branchId, $checkInAt, $subscriptionIds, $lockerId, $notes) {

            // ── 0. Lock member row to prevent concurrent check-in ───────────────────
            DB::table('members')->where('id', $entityId)->lockForUpdate()->first();

            // ── 1. No double check-in ───────────────────────────────────────────────
            $open = $this->findOpenAttendance($entityId);
            if ($open) {
                throw new Exception(__('Member is already checked in.'));
            }

            $checkInTimestamp = $checkInAt ? Carbon::parse($checkInAt) : now();
            $checkInDate = $checkInTimestamp->toDateString();

            /** @var \Modules\AttendanceManager\Services\SessionDeductionService $sessionDeductionService */
            $sessionDeductionService = app(\Modules\AttendanceManager\Services\SessionDeductionService::class);

            // ── 2. Validate member subscription & session availability ───────────────
            if (empty($subscriptionIds)) {
                // Verify the member has at least one valid subscription with sessions today
                $sessionDeductionService->getAvailableSubscriptionsForMemberOnDate($entityId, $checkInDate);
            }

            // ── 3. Create Attendance record ─────────────────────
            $attendance = Attendance::create([
                'attendable_type'      => 'member',
                'attendable_id'        => $entityId,
                'branch_id'            => $branchId,
                'recorded_by_staff_id' => Auth::id(),   // The logged-in receptionist (if any)
                'locker_id'            => $lockerId,
                'notes'                => $notes,
                'check_in_at'          => $checkInTimestamp,
                'status'               => 'checked_in',
            ]);

            // ── 4. Deduct sessions if subscriptions were selected ────────────────────
            if (!empty($subscriptionIds)) {
                $sessionDeductionService->deductMultipleSessions($attendance->id, $subscriptionIds, $notes);
                $attendance = $attendance->fresh();
            }

            // ── 5. Fire MemberCheckedIn event ────────────────────
            event(new MemberCheckedIn($attendance));

            return $attendance;
        });
    }

    /**
     * Check out a member.
     */
    public function checkOut(int $attendanceId, ?string $checkOutAt = null): Attendance
    {
        return DB::transaction(function () use ($attendanceId, $checkOutAt) {
            /** @var Attendance $attendance */
            $attendance = Attendance::where('attendable_type', 'member')
                ->findOrFail($attendanceId);

            if ($attendance->check_out_at !== null) {
                throw new Exception(__('Member is already checked out.'));
            }

            $checkOutTimestamp = $checkOutAt ? Carbon::parse($checkOutAt) : now();
            $durationMinutes   = Carbon::parse($attendance->check_in_at)->diffInMinutes($checkOutTimestamp);

            $attendance->update([
                'check_out_at'     => $checkOutTimestamp,
                'duration_minutes' => $durationMinutes,
                'status'           => 'completed',
            ]);

            event(new MemberCheckedOut($attendance));

            $this->sendCheckoutNotification($attendance);

            return $attendance->fresh();
        });
    }

    /**
     * Find the current open check-in for a member, if any.
     */
    public function findOpenAttendance(int $entityId): ?Attendance
    {
        return Attendance::where('attendable_type', 'member')
            ->where('attendable_id', $entityId)
            ->where('status', 'checked_in')
            ->whereNull('check_out_at')
            ->latest('check_in_at')
            ->first();
    }

    /**
     * Return a history query for a given member.
     */
    public function getHistory(?int $entityId = null, ?string $from = null, ?string $to = null, ?int $branchId = null): Builder
    {
        $query = Attendance::where('attendable_type', 'member')
            ->with(['consumptions.subscriptionPlan', 'locker'])
            ->orderByDesc('check_in_at');

        if ($entityId) {
            $query->where('attendable_id', $entityId);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($from) {
            $query->whereDate('check_in_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('check_in_at', '<=', $to);
        }

        return $query;
    }

    private function sendCheckoutNotification(Attendance $attendance): void
    {
        $member = \Modules\MemberManager\Models\Member::with('person.user')->find($attendance->attendable_id);
        $userId = $member?->person?->user?->id;
        $playerName = $member?->person?->full_name ?? 'لاعبنا العزيز';

        if ($userId) {
            $now = \Carbon\Carbon::now();
            $date = $now->format('Y-m-d');
            $dayName = $now->locale('ar')->translatedFormat('l');
            $time = $now->locale('ar')->translatedFormat('h:i A');
            $duration = $attendance->formatted_duration ?? '0 دقيقة';

            $planName = 'اشتراك غير محدد';
            
            $consumption = \Modules\AttendanceManager\Models\AttendanceConsumption::where('attendance_id', $attendance->id)->first();
            if ($consumption) {
                $subscription = \Illuminate\Support\Facades\DB::table('player_subscriptions')
                    ->where('id', $consumption->player_subscription_id)
                    ->first();
                if ($subscription) {
                    $planName = \Illuminate\Support\Facades\DB::table('subscription_plans')
                        ->where('id', $subscription->plan_id)
                        ->value('name') ?? 'اشتراك';
                }
            }

            $template = \Modules\NotificationManager\Models\NotificationTemplate::where('system_key', 'attendance_checkout')->first();

            if ($template) {
                $body = $template->parseBody([
                    'اسم اللاعب' => $playerName,
                    'التاريخ' => $date,
                    'اليوم' => $dayName,
                    'الوقت' => $time,
                    'مدة التدريب' => $duration,
                    'اسم الاشتراك' => $planName,
                ]);
                $title = $template->subject ?? 'تسجيل خروج ناجح';
            } else {
                $title = 'تسجيل خروج ناجح';
                $body = "أهلاً بك {$playerName}، نتمنى أن تكون قد قضيت وقتاً رياضياً ممتعاً! تم تسجيل خروجك بنجاح بتاريخ {$date} الموافق ليوم {$dayName} الساعة {$time}. لقد استمر تدريبك لمدة {$duration} ضمن اشتراكك الحالي: {$planName}. نشكر التزامك ونتطلع لرؤيتك قريباً.";
            }

            app(\Modules\NotificationManager\Services\NotificationService::class)->createNotification([
                'title' => $title,
                'body' => $body,
                'user_ids' => [$userId],
                'sender_type' => 'system'
            ]);
        }
    }
}
