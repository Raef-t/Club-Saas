<?php

namespace Modules\AttendanceManager\Http\Controllers\Api\V1;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        summary: '📋 اشتراكات اللاعب النشطة (للاستقبال)',
        description: 'يعرض جميع اشتراكات اللاعب النشطة مع تفاصيل الجلسات المتبقية لكل بند. يستخدمه موظف الاستقبال لاختيار الاشتراك المناسب قبل تسجيل الحضور.',
        tags: ['Reception'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'memberId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: '✅ تم استرجاع الاشتراكات', content: new OA\JsonContent())]
    #[OA\Response(response: 404, description: '❌ لا توجد اشتراكات نشطة')]
    public function memberSubscriptions(int $memberId)
    {
        try {
            $subscriptions = DB::table('player_subscriptions as ps')
                ->join('subscription_plans as sp', 'sp.id', '=', 'ps.plan_id')
                ->where('ps.member_id', $memberId)
                ->where('ps.status', 'active')
                ->select(
                    'ps.id as player_subscription_id',
                    'ps.member_id',
                    'ps.plan_id',
                    'sp.name as plan_name',
                    'sp.type as plan_type',
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
                return $this->errorResponse(__('No active subscriptions found for this member.'), 404);
            }

            // Attach items (session breakdown per activity) for each subscription
            $subscriptions->transform(function ($sub) {
                $sub->plan_name = json_decode($sub->plan_name, true) ?? $sub->plan_name;

                $sub->items = DB::table('player_subscription_items as psi')
                    ->leftJoin('activities as a', 'a.id', '=', 'psi.activity_id')
                    ->where('psi.player_subscription_id', $sub->player_subscription_id)
                    ->select(
                        'psi.id',
                        'psi.activity_id',
                        'a.name as activity_name',
                        'psi.coach_id',
                        'psi.sessions_allocated',
                        'psi.sessions_consumed',
                        'psi.is_unlimited',
                        DB::raw('(psi.sessions_allocated - psi.sessions_consumed) as sessions_remaining')
                    )
                    ->get()
                    ->map(function ($item) {
                        $item->activity_name = json_decode($item->activity_name, true) ?? $item->activity_name;
                        return $item;
                    });

                return $sub;
            });

            return $this->successResponse($subscriptions, __('Subscriptions retrieved successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  1.5 Assign Subscription and Deduct Session
    // ──────────────────────────────────────────────────────────────────────────

    #[OA\Post(
        path: '/v1/reception/attendances/{attendanceId}/deduct',
        summary: '💰 خصم جلسة من اشتراك محدد',
        description: 'بعد تسجيل حضور اللاعب (الذي يكون معلق الخصم)، يقوم موظف الاستقبال باختيار الاشتراك وتأكيد الخصم عبر هذا المسار.',
        tags: ['Reception'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'attendanceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ['player_subscription_id'],
        properties: [
            new OA\Property(property: 'player_subscription_id', type: 'integer', example: 5, description: 'معرف اشتراك اللاعب المراد الخصم منه'),
        ]
    ))]
    #[OA\Response(response: 200, description: '✅ تم خصم الجلسة بنجاح', content: new OA\JsonContent())]
    #[OA\Response(response: 400, description: '❌ لا يمكن خصم الجلسة (ديون، لا يوجد جلسات متبقية، تم الخصم مسبقاً)')]
    public function deductSession(int $attendanceId, Request $request, \Modules\AttendanceManager\Services\SessionDeductionService $sessionDeductionService)
    {
        $request->validate([
            'player_subscription_id' => 'required|integer'
        ]);

        try {
            $subscriptionId = $request->input('player_subscription_id');
            $attendance = $sessionDeductionService->deductSession($attendanceId, $subscriptionId);

            return $this->successResponse(new AttendanceResource($attendance), __('Session deducted successfully.'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }



}
