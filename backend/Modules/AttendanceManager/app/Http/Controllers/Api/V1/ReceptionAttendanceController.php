<?php

namespace Modules\AttendanceManager\Http\Controllers\Api\V1;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\AttendanceManager\Models\Attendance;
use Modules\AttendanceManager\Http\Resources\AttendanceResource;
use Modules\AttendanceManager\Http\Requests\UpdateLockerHolderRequest;
use Modules\AttendanceManager\Http\Requests\ReceptionCheckInAndDeductRequest;
use Modules\AttendanceManager\Services\UnifiedAttendanceService;
use Modules\AttendanceManager\Services\SessionDeductionService;
use OpenApi\Attributes as OA;

/**
 * Handles the full reception desk check-in workflow:
 *   1. Browse a player's active subscriptions so the receptionist can pick one.
 *   2. View lockers in the branch with full holder state.
 *   3. Update the current locker holder at any time (change who holds the key).
 *   4. Free a locker directly (staff override).
 */
class ReceptionAttendanceController extends BaseController
{
    // ──────────────────────────────────────────────────────────────────────────
    //  1. Player's Active Subscriptions
    // ──────────────────────────────────────────────────────────────────────────

    #[OA\Get(
        path: '/v1/reception/members/{memberId}/subscriptions',
        summary: '📋 جميع فعاليات واشتراكات اللاعب المسجل بها (لتسجيل الحضور في الاستقبال)',
        description: 'يعرض كافة الفعاليات والاشتراكات النشطة التي سجل فيها اللاعب مع تفاصيل الجلسات المتبقية، الجدول الأسبوعي الكامل (all_sessions)، وجلسات اليوم (today_sessions). يوضح لكل اشتراك ما إذا كان الحضور في نفس اليوم والوقت المجدول (is_on_schedule) أو يتطلب إدخال سبب التجاوز (requires_override_reason: true عند الحضور بغير يوم different_day أو بغير وقت different_time).',
        tags: ['Reception'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'memberId',
        in: 'path',
        required: true,
        description: 'المعرف الرقمي للاعب / العضو',
        schema: new OA\Schema(type: 'integer', example: 10)
    )]
    #[OA\Parameter(
        name: 'date',
        in: 'query',
        required: false,
        description: 'تاريخ التحقق من الجلسات (الافتراضي: اليوم بصيغة Y-m-d)',
        schema: new OA\Schema(type: 'string', format: 'date', example: '2026-08-19')
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم استرجاع قائمة الاشتراكات المتاحة بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Subscriptions retrieved successfully',
                'data' => [
                    [
                        'player_subscription_id' => 5,
                        'member_id' => 10,
                        'plan_id' => 2,
                        'plan_name' => 'اشتراك سباحة وجيم ثلاثي الأشهر',
                        'start_date' => '2026-06-01',
                        'end_date' => '2026-09-01',
                        'status' => 'active',
                        'total_amount' => 1200.0,
                        'paid_amount' => 1200.0,
                        'remaining_amount' => 0.0,
                        'notes' => null,
                        'items' => [
                            [
                                'id' => 12,
                                'sessions_allocated' => 24,
                                'sessions_consumed' => 8,
                                'is_unlimited' => false,
                                'sessions_remaining' => 16,
                                'activity_id' => 1,
                                'activity_name' => 'سباحة',
                                'coach' => [
                                    'id' => 3,
                                    'name' => 'الكابتن علي حسين',
                                    'role' => 'coach'
                                ]
                            ]
                        ],
                        'total_sessions_allocated' => 24,
                        'total_sessions_consumed' => 8,
                        'total_sessions_remaining' => 16,
                        'all_sessions' => [
                            [
                                'session_template_id' => 101,
                                'day_of_week' => 1,
                                'day_name' => 'الإثنين',
                                'start_time' => '16:00:00',
                                'end_time' => '17:30:00',
                                'formatted_time' => '04:00 PM - 05:30 PM',
                                'facility_id' => 2,
                                'facility_name' => 'المسبح الأولمبي'
                            ]
                        ],
                        'today_sessions' => [
                            [
                                'session_template_id' => 101,
                                'day_of_week' => 1,
                                'day_name' => 'الإثنين',
                                'start_time' => '16:00:00',
                                'end_time' => '17:30:00',
                                'formatted_time' => '04:00 PM - 05:30 PM',
                                'facility_id' => 2,
                                'facility_name' => 'المسبح الأولمبي'
                            ]
                        ],
                        'has_scheduled_sessions' => true,
                        'is_today_scheduled' => true,
                        'is_on_schedule' => true,
                        'requires_override_reason' => false,
                        'off_schedule_reason_type' => null,
                        'schedule_notes' => null,
                        'active_lockers' => [
                            [
                                'reservation_id' => 1,
                                'locker_id' => 15,
                                'locker_number' => 'L-105',
                                'branch_id' => 1,
                                'start_date' => '2026-06-01',
                                'end_date' => '2026-09-01',
                                'price' => 50.0
                            ]
                        ]
                    ]
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: '❌ لا توجد اشتراكات نشطة أو لا توجد جلسات متبقية',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'لا توجد جلسات متبقية لهذا المشترك.'
            ]
        )
    )]
    public function memberSubscriptions(Request $request, int $memberId)
    {
        try {
            $checkDate = $request->query('date', now()->toDateString());
            $carbonDate = \Carbon\Carbon::parse($checkDate)->startOfDay();
            $todayString = $carbonDate->toDateString();
            $dayOfWeek = (int) $carbonDate->dayOfWeek;

            // 1. Check if member has an active open attendance session
            $openAttendance = DB::table('attendances')
                ->where('attendable_type', 'member')
                ->where('attendable_id', $memberId)
                ->where('status', 'checked_in')
                ->whereNull('check_out_at')
                ->whereNull('deleted_at')
                ->latest('check_in_at')
                ->first();

            $alreadyConsumedSubIds = [];
            $currentLocker = null;
            $hasLockerInCurrentAttendance = false;

            if ($openAttendance) {
                $alreadyConsumedSubIds = DB::table('attendance_consumptions')
                    ->where('attendance_id', $openAttendance->id)
                    ->whereNull('deleted_at')
                    ->pluck('player_subscription_id')
                    ->toArray();

                if ($openAttendance->locker_id) {
                    $hasLockerInCurrentAttendance = true;
                    $lockerRow = DB::table('lockers')->where('id', $openAttendance->locker_id)->first();
                    if ($lockerRow) {
                        $currentLocker = [
                            'id'            => $lockerRow->id,
                            'locker_id'     => $lockerRow->id,
                            'locker_number' => $lockerRow->locker_number,
                            'branch_id'     => $lockerRow->branch_id,
                            'key_number'    => $lockerRow->key_number ?? null,
                        ];
                    }
                }
            }

            // 1.5. Fetch member's active lockers
            $lockerSelectColumns = [
                'lr.id as reservation_id',
                'lr.locker_id',
                'l.locker_number',
                'l.branch_id',
                'lr.start_date',
                'lr.end_date',
                'lr.price',
            ];
            if (Schema::hasColumn('lockers', 'key_number')) {
                $lockerSelectColumns[] = 'l.key_number';
            }

            $activeLockers = DB::table('locker_reservations as lr')
                ->join('lockers as l', 'l.id', '=', 'lr.locker_id')
                ->where('lr.member_id', $memberId)
                ->where('lr.status', 'active')
                ->whereNull('lr.deleted_at')
                ->whereNull('l.deleted_at')
                ->select($lockerSelectColumns)
                ->get();

            // 2. Fetch member's valid subscriptions for today
            $subscriptions = DB::table('player_subscriptions as ps')
                ->join('subscription_plans as sp', 'sp.id', '=', 'ps.plan_id')
                ->where('ps.member_id', $memberId)
                ->where('ps.status', 'active')
                ->whereNull('ps.deleted_at')
                ->whereNull('sp.deleted_at')
                ->where(function ($planStatusQ) {
                    $planStatusQ->whereNull('sp.status')
                                ->orWhere('sp.status', '!=', 'inactive');
                })
                ->where(function ($startQ) use ($todayString) {
                    $startQ->whereNull('ps.start_date')
                           ->orWhereDate('ps.start_date', '<=', $todayString);
                })
                ->where(function ($endQ) use ($todayString) {
                    $endQ->whereNull('ps.end_date')
                         ->orWhereDate('ps.end_date', '>=', $todayString);
                })
                ->whereNotExists(function ($freezeQ) use ($todayString) {
                    $freezeQ->select(DB::raw(1))
                        ->from('subscription_freezes as sf')
                        ->whereColumn('sf.player_subscription_id', 'ps.id')
                        ->whereNull('sf.deleted_at')
                        ->whereDate('sf.freeze_start_date', '<=', $todayString)
                        ->whereDate('sf.freeze_end_date', '>=', $todayString);
                })
                ->whereNotExists(function ($suspQ) use ($todayString) {
                    $suspQ->select(DB::raw(1))
                        ->from('subscription_plan_suspensions as sps')
                        ->whereColumn('sps.plan_id', 'ps.plan_id')
                        ->whereNull('sps.deleted_at')
                        ->where('sps.status', '!=', 'cancelled')
                        ->whereDate('sps.suspend_start_date', '<=', $todayString)
                        ->where(function ($dateQ) use ($todayString) {
                            $dateQ->where(function ($actualQ) use ($todayString) {
                                $actualQ->whereNotNull('sps.actual_end_date')
                                        ->whereDate('sps.actual_end_date', '>=', $todayString);
                            })->orWhere(function ($endQ) use ($todayString) {
                                $endQ->whereNull('sps.actual_end_date')
                                     ->whereDate('sps.suspend_end_date', '>=', $todayString);
                            });
                        });
                })
                ->select(
                    'ps.id as player_subscription_id',
                    'ps.member_id',
                    'ps.plan_id',
                    'sp.name as plan_name',
                    'ps.start_date',
                    'ps.end_date',
                    'ps.status',
                    'ps.total_amount',
                    'ps.paid_amount',
                    'ps.remaining_amount',
                    'ps.notes'
                )
                ->latest('ps.created_at')
                ->get();

            if ($subscriptions->isEmpty()) {
                $hasAnySub = DB::table('player_subscriptions')
                    ->where('member_id', $memberId)
                    ->whereNull('deleted_at')
                    ->exists();

                if ($hasAnySub) {
                    return $this->errorResponse(__('لا توجد اشتراكات نشطة وصالحة لهذا المشترك (قد يكون الاشتراك منتهياً أو مجمداً أو لم يبدأ بعد).'), 404);
                }

                return $this->errorResponse(__('لا توجد اشتراكات نشطة لهذا المشترك.'), 404);
            }

            // Exclude subscriptions already consumed in current open attendance
            $remainingSubscriptions = $subscriptions;
            if ($openAttendance && !empty($alreadyConsumedSubIds)) {
                $remainingSubscriptions = $subscriptions->reject(function ($sub) use ($alreadyConsumedSubIds) {
                    return in_array($sub->player_subscription_id, $alreadyConsumedSubIds);
                })->values();

                if ($remainingSubscriptions->isEmpty()) {
                    return response()->json([
                        'status'  => 'success',
                        'message' => __('اللاعب مسجل في كافة فعالياته في حضوره الحالي ولا يملك فعاليات أخرى لتسجيله عليها.'),
                        'data'    => [],
                        'meta'    => [
                            'is_currently_checked_in'           => true,
                            'current_attendance_id'             => $openAttendance->id,
                            'all_activities_attended'           => true,
                            'all_today_activities_attended'     => true,
                            'has_current_locker'                => $hasLockerInCurrentAttendance,
                            'show_locker_selection'             => false,
                            'current_locker'                    => $currentLocker,
                            'already_consumed_subscription_ids' => $alreadyConsumedSubIds,
                        ]
                    ], 200);
                }
            }

            // Attach items (session breakdown per activity) and today's sessions for each subscription
            $transformedSubscriptions = $remainingSubscriptions->map(function ($sub) use ($activeLockers, $dayOfWeek, $todayString, $openAttendance, $hasLockerInCurrentAttendance, $currentLocker) {
                $sub->plan_name = json_decode($sub->plan_name, true) ?? $sub->plan_name;

                // Fetch raw subscription items
                $rawItems = DB::table('player_subscription_items as psi')
                    ->where('psi.player_subscription_id', $sub->player_subscription_id)
                    ->whereNull('psi.deleted_at')
                    ->select(
                        'psi.id',
                        'psi.sessions_allocated',
                        'psi.sessions_consumed',
                        'psi.is_unlimited',
                        DB::raw('(psi.sessions_allocated - psi.sessions_consumed) as sessions_remaining')
                    )
                    ->get();

                // Fetch activity & coach info from the subscription plan
                $planActivities = DB::table('plan_activities as pa')
                    ->join('staff_activities as sa', 'sa.id', '=', 'pa.staff_activity_id')
                    ->join('activities as act', 'act.id', '=', 'sa.activity_id')
                    ->leftJoin('staff as s', 's.id', '=', 'sa.staff_id')
                    ->leftJoin('people as p', 'p.id', '=', 's.person_id')
                    ->where('pa.plan_id', $sub->plan_id)
                    ->whereNull('pa.deleted_at')
                    ->whereNull('act.deleted_at')
                    ->select(
                        'act.id as activity_id',
                        'act.name as activity_name',
                        's.id as coach_id',
                        'p.full_name as coach_name',
                        's.role as coach_role'
                    )
                    ->get();

                // Merge items with activity/coach data from the plan
                if ($planActivities->isNotEmpty()) {
                    $sub->items = $rawItems->map(function ($item, $index) use ($planActivities) {
                        $planActivity = $planActivities->get($index);
                        $item->activity_id   = $planActivity->activity_id ?? null;
                        $item->activity_name = $planActivity->activity_name ?? 'عام';
                        if (!empty($planActivity->coach_id)) {
                            $item->coach = [
                                'id'   => $planActivity->coach_id,
                                'name' => $planActivity->coach_name,
                                'role' => $planActivity->coach_role,
                            ];
                        } else {
                            $item->coach = null;
                        }
                        return $item;
                    });
                } else {
                    $sub->items = $rawItems;
                }

                // Calculate total sessions for the subscription
                $sub->total_sessions_allocated = $sub->items->sum('sessions_allocated');
                $sub->total_sessions_consumed = $sub->items->sum('sessions_consumed');
                $sub->total_sessions_remaining = $sub->items->sum('sessions_remaining');

                // Fetch all weekly session templates for this plan
                $allSessions = DB::table('sport_session_templates as sst')
                    ->leftJoin('facilities as f', 'f.id', '=', 'sst.facility_id')
                    ->where('sst.plan_id', $sub->plan_id)
                    ->where('sst.is_active', true)
                    ->whereNull('sst.deleted_at')
                    ->select(
                        'sst.id as session_template_id',
                        'sst.day_of_week',
                        'sst.start_time',
                        'sst.end_time',
                        'sst.facility_id',
                        'f.name as facility_name'
                    )
                    ->orderBy('sst.day_of_week')
                    ->orderBy('sst.start_time')
                    ->get()
                    ->map(function ($tmpl) {
                        $dayNames = [
                            0 => 'الأحد',
                            1 => 'الإثنين',
                            2 => 'الثلاثاء',
                            3 => 'الأربعاء',
                            4 => 'الخميس',
                            5 => 'الجمعة',
                            6 => 'السبت',
                        ];
                        $tmpl->day_name = $dayNames[$tmpl->day_of_week] ?? null;
                        $tmpl->formatted_time = \Carbon\Carbon::parse($tmpl->start_time)->format('h:i A') . ' - ' . \Carbon\Carbon::parse($tmpl->end_time)->format('h:i A');
                        return $tmpl;
                    });

                // Attach today's session schedule details (excluding cancelled sessions today)
                $cancelledTodayTmplIds = DB::table('session_exceptions')
                    ->whereIn('sport_session_template_id', $allSessions->pluck('session_template_id'))
                    ->whereDate('date', $todayString)
                    ->whereIn('status', ['cancelled', 'canceled'])
                    ->whereNull('deleted_at')
                    ->pluck('sport_session_template_id')
                    ->toArray();

                $todaySessions = $allSessions->where('day_of_week', $dayOfWeek)
                    ->reject(fn($t) => in_array($t->session_template_id, $cancelledTodayTmplIds))
                    ->values();

                $sub->all_sessions = $allSessions;
                $sub->today_sessions = $todaySessions;
                $sub->has_scheduled_sessions = $todaySessions->isNotEmpty();
                $sub->is_today_scheduled = $todaySessions->isNotEmpty() || $allSessions->isEmpty();

                $hasAnySessionTemplates = $allSessions->isNotEmpty();

                // If plan has no session templates at all (open gym / general entrance), it's always on schedule
                if (!$hasAnySessionTemplates) {
                    $sub->is_on_schedule = true;
                    $sub->requires_override_reason = false;
                    $sub->off_schedule_reason_type = null;
                    $sub->schedule_notes = null;
                } elseif ($todaySessions->isEmpty()) {
                    // Plan has session templates, but none scheduled for today (different day!)
                    $sub->is_on_schedule = false;
                    $sub->requires_override_reason = true;
                    $sub->off_schedule_reason_type = 'different_day';
                    $scheduledDays = $allSessions->pluck('day_name')->unique()->values()->implode('، ');
                    $sub->schedule_notes = "الفعالية غير مجدولة اليوم. الأيام المجدولة: {$scheduledDays}";
                } else {
                    // Check if current time falls within any of today's active templates
                    $currentTimeStr = now()->format('H:i:s');
                    $isOnSchedule = false;
                    $formattedScheduleTimes = [];

                    foreach ($todaySessions as $sessionTmpl) {
                        $startTimeStr = \Carbon\Carbon::parse($sessionTmpl->start_time)->format('H:i:s');
                        $endTimeStr = \Carbon\Carbon::parse($sessionTmpl->end_time)->format('H:i:s');
                        $formattedScheduleTimes[] = $sessionTmpl->formatted_time;

                        if ($endTimeStr >= $startTimeStr) {
                            if ($currentTimeStr >= $startTimeStr && $currentTimeStr <= $endTimeStr) {
                                $isOnSchedule = true;
                                break;
                            }
                        } else {
                            if ($currentTimeStr >= $startTimeStr || $currentTimeStr <= $endTimeStr) {
                                $isOnSchedule = true;
                                break;
                            }
                        }
                    }

                    $sub->is_on_schedule = $isOnSchedule;
                    $sub->requires_override_reason = !$isOnSchedule;
                    if (!$isOnSchedule) {
                        $sub->off_schedule_reason_type = 'different_time';
                        $timesList = implode(', ', $formattedScheduleTimes);
                        $sub->schedule_notes = "الفعالية مجدولة اليوم في الأوقات: {$timesList}";
                    } else {
                        $sub->off_schedule_reason_type = null;
                        $sub->schedule_notes = null;
                    }
                }

                // Attach general active lockers
                $sub->active_lockers = $activeLockers;

                // Attach current attendance & locker state
                $sub->is_currently_checked_in = (bool) $openAttendance;
                $sub->current_attendance_id = $openAttendance?->id;
                $sub->has_locker_in_current_attendance = $hasLockerInCurrentAttendance;
                $sub->show_locker_selection = !$hasLockerInCurrentAttendance;
                $sub->current_locker = $currentLocker;

                return $sub;
            });

            // 3. Exclude subscriptions that have 0 remaining sessions (for session-limited subscriptions)
            $filteredSubscriptions = $transformedSubscriptions->filter(function ($sub) {
                if ($sub->items->isNotEmpty()) {
                    $hasAvailableSessions = $sub->items->contains(function ($item) {
                        return !empty($item->is_unlimited) || ($item->sessions_remaining > 0);
                    });
                    if (!$hasAvailableSessions) {
                        return false;
                    }
                }
                return true;
            })->values();

            if ($filteredSubscriptions->isEmpty()) {
                if ($openAttendance && !empty($alreadyConsumedSubIds)) {
                    return response()->json([
                        'status'  => 'success',
                        'message' => __('اللاعب مسجل في كافة فعالياته في حضوره الحالي ولا يملك فعاليات أخرى لتسجيله عليها.'),
                        'data'    => [],
                        'meta'    => [
                            'is_currently_checked_in'           => true,
                            'current_attendance_id'             => $openAttendance->id,
                            'all_activities_attended'           => true,
                            'all_today_activities_attended'     => true,
                            'has_current_locker'                => $hasLockerInCurrentAttendance,
                            'show_locker_selection'             => false,
                            'current_locker'                    => $currentLocker,
                            'already_consumed_subscription_ids' => $alreadyConsumedSubIds,
                        ]
                    ], 200);
                }

                return $this->errorResponse(__('لا توجد جلسات متبقية لهذا المشترك.'), 404);
            }

            return response()->json([
                'status'  => 'success',
                'message' => __('Subscriptions retrieved successfully'),
                'data'    => $filteredSubscriptions,
                'meta'    => [
                    'is_currently_checked_in'           => (bool) $openAttendance,
                    'current_attendance_id'             => $openAttendance?->id,
                    'all_activities_attended'           => false,
                    'all_today_activities_attended'     => false,
                    'has_current_locker'                => $hasLockerInCurrentAttendance,
                    'show_locker_selection'             => !$hasLockerInCurrentAttendance,
                    'current_locker'                    => $currentLocker,
                    'already_consumed_subscription_ids' => $alreadyConsumedSubIds,
                ]
            ], 200);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  1.5 Assign Subscriptions and Deduct Sessions
    // ──────────────────────────────────────────────────────────────────────────

    #[OA\Post(
        path: '/v1/reception/attendances/{attendanceId}/deduct',
        summary: '💰 خصم جلسات من اشتراكات محددة',
        description: 'بعد تسجيل حضور اللاعب (الذي يكون معلق الخصم)، يقوم موظف الاستقبال باختيار اشتراك واحد أو أكثر وتأكيد الخصم عبر هذا المسار. يتم خصم جلسة من كل اشتراك في المصفوفة بنفس المنطق.',
        tags: ['Reception'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'attendanceId',
        in: 'path',
        required: true,
        description: 'المعرف الرقمي لسجل الحضور (الذي يكون في حالة معلق الخصم pending)',
        schema: new OA\Schema(type: 'integer', example: 105)
    )]
    #[OA\RequestBody(
        required: true,
        description: 'اشتراكات اللاعب المراد خصم الجلسات منها وسبب تجاوز الموعد إن وجد',
        content: new OA\JsonContent(
            required: ['player_subscription_ids'],
            properties: [
                new OA\Property(
                    property: 'player_subscription_ids',
                    type: 'array',
                    items: new OA\Items(type: 'integer'),
                    example: [5, 7],
                    description: 'مصفوفة معرفات اشتراكات اللاعب المراد الخصم منها (يمكن إرسال اشتراك واحد أو أكثر)'
                ),
                new OA\Property(
                    property: 'notes',
                    type: 'string',
                    nullable: true,
                    example: 'اللاعبة غيرت موعدها لظرف خاص',
                    description: 'سبب تسجيل الحضور في غير الموعد المجدول (اختياري / إلزامي عند الحضور خارج وقت الجلسة)'
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم خصم الجلسات بنجاح وتأكيد الحضور',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Sessions deducted successfully.',
                'data' => [
                    'id' => 105,
                    'member_id' => 10,
                    'check_in' => '2026-08-19 16:10:00',
                    'status' => 'deducted',
                    'deducted_subscriptions' => [
                        [
                            'player_subscription_id' => 5,
                            'plan_name' => 'اشتراك سباحة وجيم ثلاثي الأشهر',
                            'sessions_deducted' => 1,
                            'sessions_remaining_after' => 15
                        ]
                    ]
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: '❌ لا يمكن خصم الجلسة (وجود ديون، عدم توفر جلسات متبقية، أو تم الخصم مسبقاً)',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'عذراً، لا توجد جلسات متبقية للاخصم في هذا الاشتراك.'
            ]
        )
    )]
    public function deductSession(int $attendanceId, Request $request, \Modules\AttendanceManager\Services\SessionDeductionService $sessionDeductionService)
    {
        $rawIds = $request->input('player_subscription_ids') 
            ?? $request->input('subscription_ids') 
            ?? $request->input('player_subscription_id') 
            ?? $request->input('subscription_id');

        if (!is_null($rawIds) && !is_array($rawIds)) {
            $rawIds = [$rawIds];
        }

        if (!empty($rawIds)) {
            $request->merge(['player_subscription_ids' => $rawIds]);
        }

        $request->validate([
            'player_subscription_ids'   => 'required|array|min:1',
            'player_subscription_ids.*' => 'required|integer',
            'notes'                     => 'nullable|string|max:1000',
            'reason'                    => 'nullable|string|max:1000',
            'override_reason'           => 'nullable|string|max:1000',
        ]);

        try {
            $subscriptionIds = $request->input('player_subscription_ids');
            $reason = $request->input('notes') ?? $request->input('reason') ?? $request->input('override_reason');
            $attendance = $sessionDeductionService->deductMultipleSessions($attendanceId, $subscriptionIds, $reason);

            return $this->successResponse(new AttendanceResource($attendance), __('Sessions deducted successfully.'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  1.6 Check In and Deduct Session (Single Unified Reception Step)
    // ──────────────────────────────────────────────────────────────────────────

    #[OA\Post(
        path: '/v1/reception/check-in-and-deduct',
        summary: '⚡ تسجيل دخول العضو وخصم الجلسة في خطوة واحدة موحدة (للاستقبال)',
        description: 'يقوم بتسجيل حضور المشترك (Member Check-in) وخصم جلسة من اشتراكه/اشتراكاته المحددة في طلب واحد وبشكل ذري (Atomic Transaction). إذا لم يتم إرسال معرف الاشتراك، يكتشف النظام تلقائياً الاشتراك المتاح والمجدول لليوم ويخصم منه. في حال وجود أي خطأ أو مانع مالي أو نفاد جلسات، يتم التراجع عن تسجيل الحضور بالكامل.',
        tags: ['Reception'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        description: 'بيانات المشترك، الفرع، والاشتراكات المراد خصم الجلسات منها',
        content: new OA\JsonContent(
            required: ['branch_id'],
            properties: [
                new OA\Property(property: 'member_id', type: 'integer', example: 10, description: 'المعرف الرقمي للاعب / المشترك (أو attendable_id)'),
                new OA\Property(property: 'attendable_id', type: 'integer', example: 10, description: 'المعرف الرقمي للاعب / المشترك (بديل لـ member_id)'),
                new OA\Property(property: 'branch_id', type: 'integer', example: 1, description: 'معرف الفرع (إلزامي)'),
                new OA\Property(
                    property: 'player_subscription_ids',
                    type: 'array',
                    items: new OA\Items(type: 'integer'),
                    example: [5],
                    description: 'مصفوفة معرفات الاشتراكات المراد الخصم منها (اختياري، في حال عدم الإرسال سيتم الخصم تلقائياً من اشتراك اليوم المتاح)'
                ),
                new OA\Property(property: 'locker_id', type: 'integer', nullable: true, example: 7, description: 'معرف الخزانة المخصصة (اختياري)'),
                new OA\Property(property: 'check_in_at', type: 'string', format: 'date-time', example: '2026-08-19 16:10:00', description: 'تاريخ ووقت تسجيل الحضور المخصص (اختياري)'),
                new OA\Property(property: 'notes', type: 'string', nullable: true, example: 'تسجيل دخول وخصم فوري من الاستقبال', description: 'ملاحظات أو سبب تسجيل الحضور في غير الموعد المجدول (اختياري / إلزامي خارج وقت الجلسة)')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم تسجيل الدخول وخصم الجلسات بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Checked in and sessions deducted successfully.',
                'data' => [
                    'id' => 105,
                    'attendable_type' => 'Modules\\MemberManager\\Models\\Member',
                    'attendable_id' => 10,
                    'member_id' => 10,
                    'branch_id' => 1,
                    'check_in' => '2026-08-19T16:10:00.000000Z',
                    'status' => 'checked_in',
                    'consumptions' => [
                        [
                            'id' => 1,
                            'player_subscription_id' => 5,
                            'subscription_plan_id' => 2,
                            'subscription_plan_name' => 'اشتراك سباحة وجيم ثلاثي الأشهر'
                        ]
                    ]
                ]
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: '❌ خطأ في تسجيل الحضور أو خصم الجلسة (وجود ديون، نفاد الرصيد، الحضور مسجل مسبقاً، أو خارج الموعد دون سبب)'
    )]
    public function checkInAndDeduct(
        ReceptionCheckInAndDeductRequest $request,
        UnifiedAttendanceService $attendanceService,
        SessionDeductionService $sessionDeductionService
    ) {
        try {
            $memberId = (int) ($request->input('member_id') ?? $request->input('attendable_id'));
            $branchId = (int) $request->input('branch_id');
            $checkInAt = $request->input('check_in_at');
            $lockerId = $request->filled('locker_id') ? (int) $request->input('locker_id') : null;
            $notes = $request->input('notes') ?? $request->input('reason') ?? $request->input('override_reason');

            // 1. Resolve subscription IDs: explicit or auto-detect for member today
            $subscriptionIds = $request->input('player_subscription_ids');
            if (empty($subscriptionIds)) {
                $openAttendance = $attendanceService->findOpen('member', $memberId);
                $targetDate = $checkInAt ? \Carbon\Carbon::parse($checkInAt)->toDateString() : now()->toDateString();
                $subscriptionIds = $sessionDeductionService->getAvailableSubscriptionsForMemberOnDate(
                    $memberId,
                    $targetDate,
                    $openAttendance?->id
                );
            }

            // 2. Perform check-in and atomic session deduction
            $attendance = $attendanceService->checkIn(
                type: 'member',
                entityId: $memberId,
                branchId: $branchId,
                checkInAt: $checkInAt,
                subscriptionIds: $subscriptionIds,
                lockerId: $lockerId,
                notes: $notes
            );

            $attendance->load(['consumptions.subscriptionPlan', 'locker']);

            return $this->successResponse(
                new AttendanceResource($attendance),
                __('Checked in and sessions deducted successfully.')
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  2. Rollback Attendance (Full or Partial)
    // ──────────────────────────────────────────────────────────────────────────

    #[OA\Delete(
        path: '/v1/reception/attendances/{attendanceId}/rollback',
        summary: '↩️ إلغاء الحضور وإرجاع الجلسة (كلي أو جزئي)',
        description: <<<'DESC'
يتيح هذا المسار لموظف الاستقبال التراجع عن تسجيل حضور عضو أو موظف.

**السلوك:**
- **حضور الموظف (Staff):**
  - يتم حذف سجل حضور الموظف بالكامل (عند عدم إرسال `player_subscription_ids`).
- **حضور العضو (Member):**
  - **بدون body (أو مصفوفة فارغة):** يتم إرجاع **جميع** الخصومات المسجّلة وحذف سجل الحضور بالكامل.
  - **مع `player_subscription_ids`:** يتم إرجاع الخصم **فقط** للاشتراكات المحددة.
    - إذا بقي خصم آخر في سجل الحضور → يُبقى سجل الحضور ويُحدَّث.
    - إذا لم يبقَ أي خصم → يُحذف سجل الحضور تلقائياً.
DESC,
        tags: ['Reception'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(
        name: 'attendanceId',
        in: 'path',
        required: true,
        description: 'المعرف الرقمي لسجل الحضور المراد إلغاؤه أو التراجع عن خصم جلساته',
        schema: new OA\Schema(type: 'integer', example: 105)
    )]
    #[OA\RequestBody(
        required: false,
        description: 'اختياري: معرفات الاشتراكات المراد إرجاع خصمها فقط (في حال التراجع الجزئي)',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'player_subscription_ids',
                    type: 'array',
                    items: new OA\Items(type: 'integer'),
                    nullable: true,
                    example: [7],
                    description: <<<'DESC'
(اختياري) مصفوفة معرّفات اشتراكات اللاعب المراد إرجاع خصمها فقط.
- إذا أُرسلت → Partial Rollback: يُرجع فقط الخصومات المحددة.
- إذا لم تُرسل → Full Rollback: يُرجع كل الخصومات ويحذف سجل الحضور.
DESC
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '✅ تم إرجاع الخصم وإلغاء الحضور بنجاح',
        content: new OA\JsonContent(
            example: [
                'status' => 'success',
                'message' => 'Attendance rolled back and session returned successfully.',
                'data' => null
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: '❌ خطأ: الاشتراك غير موجود في سجل الخصومات أو تعذر إلغاء الحضور',
        content: new OA\JsonContent(
            example: [
                'status' => 'error',
                'message' => 'Specified subscription deduction not found in this attendance record.'
            ]
        )
    )]
    public function rollbackAttendance(int $attendanceId, Request $request, \Modules\AttendanceManager\Services\SessionDeductionService $sessionDeductionService)
    {
        $request->validate([
            'player_subscription_ids'   => 'sometimes|nullable|array|min:1',
            'player_subscription_ids.*' => 'integer',
        ]);

        try {
            $subscriptionIds = $request->input('player_subscription_ids', []);
            $sessionDeductionService->rollbackDeduction($attendanceId, $subscriptionIds);
            return $this->successResponse(null, __('Attendance rolled back and session returned successfully.'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

}
