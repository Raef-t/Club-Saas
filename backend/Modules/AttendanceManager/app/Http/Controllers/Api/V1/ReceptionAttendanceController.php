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
        summary: '📋 اشتراكات اللاعب النشطة المتاحة اليوم (للاستقبال)',
        description: 'يعرض اشتراكات اللاعب النشطة التي تحتوي على جلسات مجدولة لهذا اليوم (أو اشتراكات الدخول العام/المفتوح) مع تفاصيل الجلسات المتبقية وجلسات اليوم، مع استبعاد الاشتراكات غير المبدوءة، المنتهية، المجمدة، الموقوفة، المحذوفة، أو منتهية الرصيد. يستخدمه موظف الاستقبال لاختيار الاشتراك المناسب عند تسجيل الحضور.',
        tags: ['Reception'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'memberId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'date', in: 'query', required: false, description: 'تاريخ التحقق من الجلسات (الافتراضي: اليوم بصيغة Y-m-d)', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-08-19'))]
    #[OA\Response(response: 200, description: '✅ تم استرجاع الاشتراكات', content: new OA\JsonContent())]
    #[OA\Response(response: 404, description: '❌ لا توجد اشتراكات نشطة لها جلسات اليوم')]
    public function memberSubscriptions(Request $request, int $memberId)
    {
        try {
            $checkDate = $request->query('date', now()->toDateString());
            $carbonDate = \Carbon\Carbon::parse($checkDate)->startOfDay();
            $todayString = $carbonDate->toDateString();
            $dayOfWeek = (int) $carbonDate->dayOfWeek;

            // 1. Fetch member's active lockers
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
                ->where(function ($sessionQ) use ($dayOfWeek, $todayString) {
                    // Case 1: Plan has NO session templates defined (open gym / equipment / daily entry)
                    $sessionQ->whereNotExists(function ($noTmplQ) {
                        $noTmplQ->select(DB::raw(1))
                            ->from('sport_session_templates as sst_all')
                            ->whereColumn('sst_all.plan_id', 'ps.plan_id')
                            ->where('sst_all.is_active', true)
                            ->whereNull('sst_all.deleted_at');
                    })
                    // Case 2: Plan HAS session templates, and has at least one active template for today's day_of_week and not cancelled
                    ->orWhereExists(function ($hasTmplQ) use ($dayOfWeek, $todayString) {
                        $hasTmplQ->select(DB::raw(1))
                            ->from('sport_session_templates as sst_today')
                            ->whereColumn('sst_today.plan_id', 'ps.plan_id')
                            ->where('sst_today.is_active', true)
                            ->where('sst_today.day_of_week', $dayOfWeek)
                            ->whereNull('sst_today.deleted_at')
                            ->whereNotExists(function ($excQ) use ($todayString) {
                                $excQ->select(DB::raw(1))
                                    ->from('session_exceptions as se')
                                    ->whereColumn('se.sport_session_template_id', 'sst_today.id')
                                    ->whereDate('se.date', $todayString)
                                    ->whereIn('se.status', ['cancelled', 'canceled'])
                                    ->whereNull('se.deleted_at');
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

                if ($hasAnyActiveSub) {
                    return $this->errorResponse(__('لا توجد جلسات مجدولة لهذا المشترك اليوم.'), 404);
                }

                return $this->errorResponse(__('لا توجد اشتراكات نشطة لهذا المشترك.'), 404);
            }

            // Attach items (session breakdown per activity) and today's sessions for each subscription
            $transformedSubscriptions = $subscriptions->map(function ($sub) use ($activeLockers, $dayOfWeek, $todayString) {
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

                // Attach today's session schedule details
                $todaySessions = DB::table('sport_session_templates as sst')
                    ->leftJoin('facilities as f', 'f.id', '=', 'sst.facility_id')
                    ->where('sst.plan_id', $sub->plan_id)
                    ->where('sst.is_active', true)
                    ->where('sst.day_of_week', $dayOfWeek)
                    ->whereNull('sst.deleted_at')
                    ->whereNotExists(function ($excQ) use ($todayString) {
                        $excQ->select(DB::raw(1))
                            ->from('session_exceptions as se')
                            ->whereColumn('se.sport_session_template_id', 'sst.id')
                            ->whereDate('se.date', $todayString)
                            ->whereIn('se.status', ['cancelled', 'canceled'])
                            ->whereNull('se.deleted_at');
                    })
                    ->select(
                        'sst.id as session_template_id',
                        'sst.day_of_week',
                        'sst.start_time',
                        'sst.end_time',
                        'sst.facility_id',
                        'f.name as facility_name'
                    )
                    ->orderBy('sst.start_time')
                    ->get();

                $sub->today_sessions = $todaySessions;
                $sub->has_scheduled_sessions = $todaySessions->isNotEmpty();

                // Determine if the subscription has session templates at all across all days
                $hasAnySessionTemplates = DB::table('sport_session_templates')
                    ->where('plan_id', $sub->plan_id)
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->exists();

                // If plan has no session templates at all (open gym / general entrance), it's always on schedule
                if (!$hasAnySessionTemplates) {
                    $sub->is_on_schedule = true;
                    $sub->requires_override_reason = false;
                } else {
                    // Check if current time falls within any of today's active templates
                    $currentTimeStr = now()->format('H:i:s');
                    $isOnSchedule = false;
                    foreach ($todaySessions as $sessionTmpl) {
                        $startTimeStr = \Carbon\Carbon::parse($sessionTmpl->start_time)->format('H:i:s');
                        $endTimeStr = \Carbon\Carbon::parse($sessionTmpl->end_time)->format('H:i:s');
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
                }

                // Attach general active lockers
                $sub->active_lockers = $activeLockers;

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
                return $this->errorResponse(__('لا توجد جلسات مجدولة أو متبقية لهذا المشترك اليوم.'), 404);
            }

            return $this->successResponse($filteredSubscriptions, __('Subscriptions retrieved successfully'));
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
    #[OA\Parameter(name: 'attendanceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(
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
    ))]
    #[OA\Response(response: 200, description: '✅ تم خصم الجلسات بنجاح', content: new OA\JsonContent())]
    #[OA\Response(response: 400, description: '❌ لا يمكن خصم الجلسة (ديون، لا يوجد جلسات متبقية، تم الخصم مسبقاً)')]
    public function deductSession(int $attendanceId, Request $request, \Modules\AttendanceManager\Services\SessionDeductionService $sessionDeductionService)
    {
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
    #[OA\Parameter(name: 'attendanceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: false,
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
        description: '✅ تم إرجاع الخصم بنجاح',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Attendance rolled back and session returned successfully.'),
            ]
        )
    )]
    #[OA\Response(response: 400, description: '❌ خطأ: الاشتراك غير موجود في سجل الخصومات أو لا يمكن إلغاء الحضور')]
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
