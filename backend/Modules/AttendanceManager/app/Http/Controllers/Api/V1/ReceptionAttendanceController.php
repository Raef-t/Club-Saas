<?php

namespace Modules\AttendanceManager\Http\Controllers\Api\V1;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Core\Http\Controllers\Api\BaseController;
use Modules\AttendanceManager\Models\Attendance;
use Modules\AttendanceManager\Http\Resources\AttendanceResource;
use Modules\AttendanceManager\Http\Requests\LockerAssignmentRequest;
use Modules\AttendanceManager\Http\Requests\ReleaseLockerRequest;
use Modules\AttendanceManager\Http\Requests\UpdateLockerHolderRequest;
use OpenApi\Attributes as OA;

/**
 * Handles the full reception desk check-in workflow:
 *   1. Browse a player's active subscriptions so the receptionist can pick one.
 *   2. View lockers in the branch with full holder state.
 *   3. Assign a locker key to an open attendance (member or staff holder).
 *   4. Release a locker key (return it or transfer it to a guest).
 *   5. Update the current locker holder at any time (change who holds the key).
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
                    ->get();

                return $sub;
            });

            return $this->successResponse($subscriptions, __('Subscriptions retrieved successfully'));
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
    #[OA\Parameter(name: 'available_only', in: 'query', required: false, schema: new OA\Schema(type: 'boolean', default: false))]
    #[OA\Response(response: 200, description: '✅ تم استرجاع الخزائن', content: new OA\JsonContent())]
    public function availableLockers(Request $request)
    {
        $request->validate([
            'branch_id'      => ['required', 'integer'],
            'available_only' => ['nullable', 'boolean'],
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

            if (filter_var($request->input('available_only', false), FILTER_VALIDATE_BOOLEAN)) {
                $query->where('status', 'available');
            }

            $lockers = $query->get();

            return $this->successResponse($lockers, __('Lockers retrieved successfully'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  3. Assign Locker to an Open Attendance
    // ──────────────────────────────────────────────────────────────────────────

    #[OA\Post(
        path: '/v1/attendances/{attendanceId}/assign-locker',
        summary: '🔐 تخصيص مفتاح للاعب أو موظف',
        description: "يخصص مفتاح خزانة للشخص المرتبط بسجل الحضور.\n\n- **holder_type = member** (default): عضو مسجّل\n- **holder_type = staff**: موظف أو كوتش",
        tags: ['Reception'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'attendanceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ['locker_id'],
        properties: [
            new OA\Property(property: 'locker_id', type: 'integer', example: 4),
            new OA\Property(property: 'holder_type', type: 'string', enum: ['member', 'staff'], example: 'member', description: 'اختياري – member افتراضياً'),
        ]
    ))]
    #[OA\Response(response: 200, description: '✅ تم تخصيص المفتاح', content: new OA\JsonContent())]
    public function assignLocker(int $attendanceId, LockerAssignmentRequest $request)
    {
        try {
            return DB::transaction(function () use ($attendanceId, $request) {

                // Load the open attendance to identify the person
                $attendance = Attendance::where('status', 'checked_in')
                    ->whereNull('check_out_at')
                    ->findOrFail($attendanceId);

                // Validate the target locker
                $locker = DB::table('lockers')
                    ->where('id', $request->input('locker_id'))
                    ->whereNull('deleted_at')
                    ->first();

                if (!$locker) {
                    return $this->errorResponse(__('Selected locker not found.'), 404);
                }

                if ($locker->status !== 'available') {
                    return $this->errorResponse(
                        __('Locker :number is not available.', ['number' => $locker->locker_number]),
                        409
                    );
                }

                $holderType = $request->input('holder_type', 'member');

                // If this person already holds another locker, free it first
                $existing = DB::table('lockers')
                    ->where('holder_id', $attendance->attendable_id)
                    ->where('holder_type', $holderType)
                    ->whereNull('deleted_at')
                    ->first();

                if ($existing) {
                    DB::table('lockers')->where('id', $existing->id)->update([
                        'status'      => 'available',
                        'holder_id'   => null,
                        'holder_type' => null,
                        'holder_name' => null,
                        'assigned_at' => null,
                        'updated_at'  => now(),
                    ]);
                }

                // Resolve holder display name
                $holderName = $this->resolveHolderName($attendance, $holderType);
                $newStatus  = $holderType === 'staff' ? 'with_staff' : 'with_member';

                // Update the locker row – the ONLY place locker state lives now
                DB::table('lockers')->where('id', $locker->id)->update([
                    'status'      => $newStatus,
                    'holder_id'   => $attendance->attendable_id,
                    'holder_type' => $holderType,
                    'holder_name' => $holderName,
                    'assigned_at' => now(),
                    'updated_at'  => now(),
                ]);

                $updatedLocker = DB::table('lockers')->where('id', $locker->id)->first();

                return $this->successResponse(
                    $updatedLocker,
                    __('Locker :number assigned successfully.', ['number' => $locker->locker_number])
                );
            });
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  4. Release Locker (Return or Transfer to Guest)
    // ──────────────────────────────────────────────────────────────────────────

    #[OA\Post(
        path: '/v1/attendances/{attendanceId}/release-locker',
        summary: '🔓 تسليم المفتاح (إرجاع أو نقل لضيف)',
        description: "يُحرِّر المفتاح المرتبط بالشخص الذي سجّل دخوله في هذا السجل.\n\n**release_type = return**: إرجاع المفتاح → يصبح متاحاً.\n\n**release_type = transfer**: نقل المفتاح لضيف غير مسجّل → يُسجَّل اسمه ويظل مشغولاً (`with_guest`).",
        tags: ['Reception'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'attendanceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ['release_type'],
        properties: [
            new OA\Property(property: 'release_type', type: 'string', enum: ['return', 'transfer'], example: 'return'),
            new OA\Property(property: 'transfer_to_name', type: 'string', example: 'مريم أحمد', description: 'مطلوب فقط عند النقل لضيف'),
        ]
    ))]
    #[OA\Response(response: 200, description: '✅ تم تسليم المفتاح', content: new OA\JsonContent())]
    #[OA\Response(response: 422, description: '⚠️ هذا الشخص لا يحمل أي مفتاح حالياً')]
    public function releaseLocker(int $attendanceId, ReleaseLockerRequest $request)
    {
        try {
            return DB::transaction(function () use ($attendanceId, $request) {

                $attendance = Attendance::findOrFail($attendanceId);

                // Find the locker held by this person via the lockers table
                $locker = DB::table('lockers')
                    ->where('holder_id', $attendance->attendable_id)
                    ->where('holder_type', $attendance->attendable_type)
                    ->whereNull('deleted_at')
                    ->first();

                if (!$locker) {
                    return $this->errorResponse(
                        __('This person is not currently holding any locker key.'),
                        422
                    );
                }

                $releaseType    = $request->input('release_type');
                $transferToName = $request->input('transfer_to_name');

                if ($releaseType === 'return') {
                    // ── Return: locker becomes fully available ─────────────────────
                    DB::table('lockers')->where('id', $locker->id)->update([
                        'status'      => 'available',
                        'holder_id'   => null,
                        'holder_type' => null,
                        'holder_name' => null,
                        'assigned_at' => null,
                        'updated_at'  => now(),
                    ]);

                    $message = __('Locker :number returned. It is now available.', [
                        'number' => $locker->locker_number,
                    ]);

                } else {
                    // ── Transfer: key passed to an unregistered guest ──────────────
                    DB::table('lockers')->where('id', $locker->id)->update([
                        'status'      => 'with_guest',
                        'holder_id'   => null,
                        'holder_type' => 'guest',
                        'holder_name' => $transferToName,
                        // keep assigned_at to preserve original hand-out time
                        'updated_at'  => now(),
                    ]);

                    $message = __('Locker :number transferred to :name.', [
                        'number' => $locker->locker_number,
                        'name'   => $transferToName,
                    ]);
                }

                $updatedLocker = DB::table('lockers')->where('id', $locker->id)->first();

                return $this->successResponse($updatedLocker, $message);
            });
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

    // ──────────────────────────────────────────────────────────────────────────
    //  Private Helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Resolve the display name for the holder based on the attendance record.
     * Falls back to the attendable_id if no name can be found.
     */
    private function resolveHolderName(Attendance $attendance, string $holderType): ?string
    {
        if ($holderType === 'member') {
            $member = DB::table('members')->where('id', $attendance->attendable_id)->first();
            return $member ? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) : null;
        }

        if ($holderType === 'staff') {
            $staff = DB::table('staff')->where('id', $attendance->attendable_id)->first();
            return $staff ? trim(($staff->first_name ?? '') . ' ' . ($staff->last_name ?? '')) : null;
        }

        return null;
    }
}
