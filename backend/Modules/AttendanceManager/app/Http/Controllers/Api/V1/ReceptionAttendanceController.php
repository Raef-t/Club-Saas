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
                    'ps.id',
                    'ps.member_id',
                    'ps.plan_id',
                    'sp.name as plan_name',
                    'sp.type as plan_type',
                    'ps.start_date',
                    'ps.end_date',
                    'ps.status',
                    'ps.remaining_sessions',
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
                    ->where('psi.player_subscription_id', $sub->id)
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
        required: ['subscription_id'],
        properties: [
            new OA\Property(property: 'subscription_id', type: 'integer', example: 5, description: 'معرف الاشتراك المراد الخصم منه'),
        ]
    ))]
    #[OA\Response(response: 200, description: '✅ تم خصم الجلسة بنجاح', content: new OA\JsonContent())]
    #[OA\Response(response: 400, description: '❌ لا يمكن خصم الجلسة (ديون، لا يوجد جلسات متبقية، تم الخصم مسبقاً)')]
    public function deductSession(int $attendanceId, Request $request, \Modules\AttendanceManager\Services\SessionDeductionService $sessionDeductionService)
    {
        $request->validate([
            'subscription_id' => 'required|integer'
        ]);

        try {
            $subscriptionId = $request->input('subscription_id');
            $attendance = $sessionDeductionService->deductSession($attendanceId, $subscriptionId);
            
            return $this->successResponse(new AttendanceResource($attendance), __('Session deducted successfully.'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }


    // ──────────────────────────────────────────────────────────────────────────
    //  2. Lockers in Branch (with full holder state)
    // ──────────────────────────────────────────────────────────────────────────

    #[OA\Get(
        path: '/v1/reception/lockers',
        summary: '🔑 خزائن الفرع مع حالة كل مفتاح',
        description: "يعرض جميع خزائن الفرع مع حالتها الكاملة:\n- **available**: المفتاح في الاستقبال\n- **with_member**: عضو مسجّل يحمله\n- **with_staff**: موظف/كوتش يحمله\n- **with_guest**: ضيف غير مسجّل يحمله\n\nيُعرض أيضاً اسم الحامل ووقت التخصيص.",
        tags: ['Reception'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'available_only', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['true', 'false', 'all'], default: 'false'))]
    #[OA\Response(response: 200, description: '✅ تم استرجاع الخزائن', content: new OA\JsonContent())]
    public function availableLockers(Request $request)
    {
        $request->validate([
            'branch_id'      => ['required', 'integer'],
            'available_only' => ['nullable', 'string', 'in:true,false,all,1,0'],
        ]);

        try {
            $query = DB::table('lockers')
                ->where('branch_id', $request->input('branch_id'))
                ->whereNull('deleted_at')
                ->select(
                    'id',
                    'locker_number',
                    'status',
                    'holder_id',
                    'holder_type',
                    'holder_name',
                    'assigned_at'
                )
                ->orderBy('locker_number');

            $availableOnly = $request->input('available_only');
            if ($availableOnly === 'true' || $availableOnly === '1' || $availableOnly === true || $availableOnly === 1) {
                $query->where('status', 'available');
            }

            $lockers = $query->get();

            return $this->successResponse($lockers, __('Lockers retrieved successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }



    // ──────────────────────────────────────────────────────────────────────────
    //  5. Update Locker Holder (change who holds the key at any time)
    // ──────────────────────────────────────────────────────────────────────────

    #[OA\Patch(
        path: '/v1/lockers/{lockerId}/holder',
        summary: '🔄 تغيير حامل المفتاح',
        description: "يتيح تغيير مَن يحمل المفتاح في أي وقت بدون الحاجة لعمل check-out.\n\nالاستخدامات:\n- تحويل المفتاح من عضو لآخر\n- منح كوتش خزانة ثابتة\n- تسجيل ضيف حمل المفتاح يدوياً",
        tags: ['Reception'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'lockerId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ['holder_type', 'holder_name'],
        properties: [
            new OA\Property(property: 'holder_type', type: 'string', enum: ['member', 'staff', 'guest'], example: 'staff'),
            new OA\Property(property: 'holder_id', type: 'integer', nullable: true, example: 7, description: 'ID العضو أو الموظف – مطلوب إذا holder_type ≠ guest'),
            new OA\Property(property: 'holder_name', type: 'string', example: 'المدرب خالد'),
        ]
    ))]
    #[OA\Response(response: 200, description: '✅ تم تحديث حامل المفتاح', content: new OA\JsonContent())]
    #[OA\Response(response: 404, description: '❌ الخزانة غير موجودة')]
    #[OA\Response(response: 422, description: '⚠️ بيانات غير صالحة')]
    public function updateLockerHolder(int $lockerId, UpdateLockerHolderRequest $request)
    {
        try {
            return DB::transaction(function () use ($lockerId, $request) {

                $locker = DB::table('lockers')
                    ->where('id', $lockerId)
                    ->whereNull('deleted_at')
                    ->first();

                if (!$locker) {
                    return $this->errorResponse(__('Locker not found.'), 404);
                }

                $holderType = $request->input('holder_type');
                $holderId   = $request->input('holder_id');
                $holderName = $request->input('holder_name');

                // Map holder_type to status
                $statusMap = [
                    'member' => 'with_member',
                    'staff'  => 'with_staff',
                    'guest'  => 'with_guest',
                ];

                $newStatus = $statusMap[$holderType];

                DB::table('lockers')->where('id', $lockerId)->update([
                    'status'      => $newStatus,
                    'holder_id'   => $holderType !== 'guest' ? $holderId : null,
                    'holder_type' => $holderType,
                    'holder_name' => $holderName,
                    'assigned_at' => $locker->assigned_at ?? now(), // keep original if already assigned
                    'updated_at'  => now(),
                ]);

                $updatedLocker = DB::table('lockers')->where('id', $lockerId)->first();

                return $this->successResponse(
                    $updatedLocker,
                    __('Locker holder updated successfully.')
                );
            });
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  6. Free Locker (direct release – no attendance record needed)
    // ──────────────────────────────────────────────────────────────────────────

    #[OA\Delete(
        path: '/v1/lockers/{lockerId}/holder',
        summary: '🔓 إلغاء تخصيص الخزانة مباشرة',
        description: "يُحرِّر الخزانة ويجعلها متاحة فوراً بغض النظر عن سجل الحضور.\n\nمفيد في حالات:\n- إلغاء خزانة مخصصة لكوتش/موظف بشكل دائم\n- تحرير خزانة يدوياً من لوحة الاستقبال\n- تصحيح تخصيص خاطئ",
        tags: ['Reception'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'lockerId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: '✅ تم تحرير الخزانة', content: new OA\JsonContent())]
    #[OA\Response(response: 404, description: '❌ الخزانة غير موجودة')]
    #[OA\Response(response: 409, description: '⚠️ الخزانة متاحة بالفعل')]
    public function freeLocker(int $lockerId)
    {
        try {
            $locker = DB::table('lockers')
                ->where('id', $lockerId)
                ->whereNull('deleted_at')
                ->first();

            if (!$locker) {
                return $this->errorResponse(__('Locker not found.'), 404);
            }

            if ($locker->status === 'available') {
                return $this->errorResponse(__('Locker is already available.'), 409);
            }

            DB::table('lockers')->where('id', $lockerId)->update([
                'status'      => 'available',
                'holder_id'   => null,
                'holder_type' => null,
                'holder_name' => null,
                'assigned_at' => null,
                'updated_at'  => now(),
            ]);

            $freed = DB::table('lockers')->where('id', $lockerId)->first();

            return $this->successResponse(
                $freed,
                __('Locker :number is now available.', ['number' => $locker->locker_number])
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
