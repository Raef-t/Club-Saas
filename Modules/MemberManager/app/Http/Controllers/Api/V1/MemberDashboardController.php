<?php

namespace Modules\MemberManager\Http\Controllers\Api\V1;

use Modules\Core\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Modules\MemberManager\Models\Member;
use Modules\SubscriptionManager\Models\PlayerSubscription;
use Modules\AttendanceManager\Models\Attendance;
use Carbon\Carbon;

class MemberDashboardController extends BaseController
{
    public function index(Request $request)
    {
        // For security, only the authenticated member can access their own dashboard
        $member = $request->user();
        
        if (!$member || get_class($member) !== Member::class) {
            return $this->errorResponse('Unauthorized or invalid user type.', 403);
        }

        $activeSubscriptions = PlayerSubscription::with('plan')
            ->where('player_id', $member->id)
            ->where('status', 'active')
            ->get();

        $todayAttendance = Attendance::where('attendable_type', 'member')
            ->where('attendable_id', $member->id)
            ->whereDate('check_in_at', Carbon::today())
            ->latest('check_in_at')
            ->first();

        return $this->successResponse([
            'profile' => [
                'id' => $member->id,
                'name' => $member->name,
                'phone' => $member->phone,
                'email' => $member->email,
            ],
            'active_subscriptions' => $activeSubscriptions->map(function ($sub) {
                return [
                    'id' => $sub->id,
                    'plan_name' => $sub->plan->name ?? 'Unknown Plan',
                    'status' => $sub->status,
                    'remaining_sessions' => $sub->remaining_sessions,
                    'start_date' => $sub->start_date,
                    'end_date' => $sub->end_date,
                    'last_used_at' => $sub->last_used_at,
                ];
            }),
            'today_attendance' => $todayAttendance ? [
                'id' => $todayAttendance->id,
                'check_in_at' => $todayAttendance->check_in_at,
                'check_out_at' => $todayAttendance->check_out_at,
                'duration_minutes' => $todayAttendance->duration_minutes,
                'status' => $todayAttendance->status,
            ] : null,
        ], 'Dashboard data retrieved successfully.');
    }
}
