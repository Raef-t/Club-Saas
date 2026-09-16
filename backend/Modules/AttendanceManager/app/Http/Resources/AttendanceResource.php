<?php

namespace Modules\AttendanceManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\AttendanceManager\Models\Attendance;
use Carbon\Carbon;

class AttendanceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'               => $this->id,
            'attendable_type'  => $this->attendable_type,
            'attendable_id'    => $this->attendable_id,
            // Convenience fields depending on type
            'member_id'        => $this->attendable_type === 'member' ? $this->attendable_id : null,
            'staff_id'         => $this->attendable_type === 'staff'  ? $this->attendable_id : null,
            // The staff member (receptionist) who recorded this check-in
            'recorded_by_staff_id' => $this->recorded_by_staff_id,
            'branch_id'        => $this->branch_id,
            'locker'           => $this->locker ? [
                'id'            => $this->locker->id,
                'locker_number' => $this->locker->locker_number,
                'key_number'    => $this->locker->key_number ?? null,
            ] : null,
            'check_in'         => $this->check_in_at?->toIso8601String(),
            'check_out'        => $this->check_out_at?->toIso8601String(),
            'duration_minutes'   => $this->duration_minutes ?? null,
            'duration_formatted' => $this->formatted_duration,
            'status'           => $this->status,
            'notes'            => $this->notes,
            // Monthly stats for member (player)
            'monthly_attendance_percentage' => $this->attendable_type === 'member' ? $this->computeMonthlyPercentage() : null,
            'monthly_training_hours' => $this->attendable_type === 'member' ? $this->computeMonthlyHours() : null,
            'consumptions'     => $this->consumptions ? $this->consumptions->map(function ($consumption) {
                return [
                    'id'                     => $consumption->id,
                    'player_subscription_id' => $consumption->player_subscription_id,
                    'subscription_plan_id'   => $consumption->subscription_plan_id,
                    'subscription_plan_name' => $consumption->subscriptionPlan?->name,
                ];
            })->toArray() : [],
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Compute monthly attendance percentage for the member.
     */
    protected function computeMonthlyPercentage(): ?float
    {
        $now = Carbon::now();
        $start = $now->copy()->firstOfMonth();
        $end = $now->copy()->lastOfMonth();
        $attendances = Attendance::where('attendable_type', 'member')
            ->where('attendable_id', $this->attendable_id)
            ->whereBetween('check_in_at', [$start, $end])
            ->get();
        $daysAttended = $attendances->pluck('check_in_at')->map(function ($date) {
            return Carbon::parse($date)->format('Y-m-d');
        })->unique()->count();
        $daysInMonth = $now->daysInMonth;
        return $daysInMonth > 0 ? round(($daysAttended / $daysInMonth) * 100, 2) : null;
    }

    /**
     * Compute monthly training hours for the member.
     */
    protected function computeMonthlyHours(): ?float
    {
        $now = Carbon::now();
        $start = $now->copy()->firstOfMonth();
        $end = $now->copy()->lastOfMonth();
        $totalMinutes = Attendance::where('attendable_type', 'member')
            ->where('attendable_id', $this->attendable_id)
            ->whereBetween('check_in_at', [$start, $end])
            ->sum('duration_minutes');
        return $totalMinutes ? round($totalMinutes / 60, 2) : null;
    }
}
