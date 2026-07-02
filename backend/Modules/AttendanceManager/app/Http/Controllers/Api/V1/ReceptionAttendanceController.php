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
use OpenApi\Attributes as OA;

/**
 * Handles the full reception desk check-in workflow:
 *   1. Browse a player's active subscriptions so the receptionist can pick one.
 *   2. View available lockers in the branch.
 *   3. Assign a locker key to an open attendance.
 *   4. Release a locker key (return it or transfer it to a friend).
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
    //  2. Available Lockers in Branch
    // ──────────────────────────────────────────────────────────────────────────

    #[OA\Get(
        path: '/v1/reception/lockers',
        summary: '🔑 المفاتيح المتاحة في الفرع',
        description: 'يعرض جميع خزائن الفرع مع حالتها (متاح / مشغول). يستخدمه موظف الاستقبال لاختيار مفتاح للاعب.',
        tags: ['Reception'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'branch_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'available_only', in: 'query', required: false, schema: new OA\Schema(type: 'boolean', default: false))]
    #[OA\Response(response: 200, description: '✅ تم استرجاع المفاتيح', content: new OA\JsonContent())]
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
                    'current_attendance_id'
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
        summary: '🔐 تخصيص مفتاح للاعب بعد تسجيل الدخول',
        description: 'يربط مفتاح خزانة بسجل الحضور المفتوح. يمكن استخدامه إذا لم يتم تحديد المفتاح أثناء check-in أو لتغييره.',
        tags: ['Reception'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'attendanceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ['locker_id'],
        properties: [new OA\Property(property: 'locker_id', type: 'integer', example: 4)]
    ))]
    #[OA\Response(response: 200, description: '✅ تم تخصيص المفتاح', content: new OA\JsonContent())]
    public function assignLocker(int $attendanceId, LockerAssignmentRequest $request)
    {
        try {
            return DB::transaction(function () use ($attendanceId, $request) {

                // Load the open attendance
                $attendance = Attendance::where('status', 'checked_in')
                    ->whereNull('check_out_at')
                    ->findOrFail($attendanceId);

                // Validate the locker
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

                // Release any previously assigned locker first
                if ($attendance->locker_id) {
                    DB::table('lockers')->where('id', $attendance->locker_id)->update([
                        'status'                => 'available',
                        'current_attendance_id' => null,
                        'updated_at'            => now(),
                    ]);
                }

                // Assign new locker to the attendance
                $attendance->update([
                    'locker_id'          => $locker->id,
                    'locker_holder_name' => null,
                ]);

                // Mark the locker as rented and link it
                DB::table('lockers')->where('id', $locker->id)->update([
                    'status'                => 'rented',
                    'current_attendance_id' => $attendance->id,
                    'updated_at'            => now(),
                ]);

                return $this->successResponse(
                    new AttendanceResource($attendance->fresh()),
                    __('Locker :number assigned to player successfully.', ['number' => $locker->locker_number])
                );
            });
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  4. Release Locker (Return or Transfer)
    // ──────────────────────────────────────────────────────────────────────────

    #[OA\Post(
        path: '/v1/attendances/{attendanceId}/release-locker',
        summary: '🔓 تسليم المفتاح (إرجاع أو نقل لصديق)',
        description: "يُفك ارتباط المفتاح عن اللاعب عند خروجه.\n\n**release_type = return**: اللاعب يُعيد المفتاح للاستقبال → يصبح المفتاح متاحاً.\n\n**release_type = transfer**: اللاعب يعطي المفتاح لصديقه → يُسجَّل اسم الصديق ويُفك الارتباط عن هذا اللاعب.",
        tags: ['Reception'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'attendanceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ['release_type'],
        properties: [
            new OA\Property(property: 'release_type', type: 'string', enum: ['return', 'transfer'], example: 'return'),
            new OA\Property(property: 'transfer_to_name', type: 'string', example: 'أحمد محمد', description: 'مطلوب فقط عند النقل'),
        ]
    ))]
    #[OA\Response(response: 200, description: '✅ تم تسليم المفتاح', content: new OA\JsonContent())]
    public function releaseLocker(int $attendanceId, ReleaseLockerRequest $request)
    {
        try {
            return DB::transaction(function () use ($attendanceId, $request) {

                $attendance = Attendance::findOrFail($attendanceId);

                if (!$attendance->locker_id) {
                    return $this->errorResponse(__('This attendance record has no locker assigned.'), 422);
                }

                $releaseType    = $request->input('release_type');
                $transferToName = $request->input('transfer_to_name');

                if ($releaseType === 'return') {
                    // ── Return: locker becomes available again ─────────────────────
                    DB::table('lockers')->where('id', $attendance->locker_id)->update([
                        'status'                => 'available',
                        'current_attendance_id' => null,
                        'updated_at'            => now(),
                    ]);

                    $attendance->update([
                        'locker_id'          => null,
                        'locker_holder_name' => null,
                    ]);

                    $message = __('Locker returned successfully. It is now available.');

                } else {
                    // ── Transfer: log friend's name, unlink from this player ───────
                    // The locker remains 'rented' (a friend still holds it),
                    // but this attendance is no longer its owner.
                    DB::table('lockers')->where('id', $attendance->locker_id)->update([
                        'current_attendance_id' => null,
                        // Keep status as 'rented' since the friend still holds it
                        'updated_at' => now(),
                    ]);

                    $attendance->update([
                        'locker_holder_name' => $transferToName,
                        // Keep locker_id on the record for audit trail
                    ]);

                    $message = __('Locker transferred to :name. Association removed from this player.', [
                        'name' => $transferToName,
                    ]);
                }

                return $this->successResponse(
                    new AttendanceResource($attendance->fresh()),
                    $message
                );
            });
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
