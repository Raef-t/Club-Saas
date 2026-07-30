<?php

namespace Modules\AttendanceManager\Services;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Modules\AttendanceManager\Contracts\AttendanceHandlerInterface;
use Modules\AttendanceManager\Models\Attendance;

class UnifiedAttendanceService
{
    /**
     * Map of attendable_type → handler instance.
     *
     * @var array<string, AttendanceHandlerInterface>
     */
    private array $handlers;

    public function __construct(array $handlers)
    {
        $this->handlers = $handlers;
    }

    /**
     * Register an additional handler at runtime.
     * Useful for modules that want to add new attendable types (e.g. Coach, Guest).
     */
    public function registerHandler(string $type, AttendanceHandlerInterface $handler): void
    {
        $this->handlers[$type] = $handler;
    }

    /**
     * Check in an entity (member, staff, …).
     *
     * @param  string  $type      attendable_type ('member' | 'staff' | …)
     * @param  int     $entityId  The primary entity id
     * @param  int     $branchId
     * @return Attendance
     * @throws Exception
     */
    public function checkIn(string $type, int $entityId, int $branchId): Attendance
    {
        return $this->resolveHandler($type)->checkIn($entityId, $branchId);
    }

    /**
     * Check out by attendance ID.
     * Type is resolved from the attendance record itself.
     *
     * @param  int  $attendanceId
     * @return Attendance
     * @throws Exception
     */
    public function checkOut(int $attendanceId): Attendance
    {
        $attendance = Attendance::findOrFail($attendanceId);
        return $this->resolveHandler($attendance->attendable_type)->checkOut($attendanceId);
    }

    /**
     * Bulk check out open attendances for a specific branch and subscription plan.
     *
     * @param  int  $branchId
     * @param  int  $subscriptionPlanId
     * @return array
     */
    public function bulkCheckOut(int $branchId, int $subscriptionPlanId): array
    {
        $targetIds = Attendance::where('branch_id', $branchId)
            ->whereNull('check_out_at')
            ->whereHas('consumptions', function ($q) use ($subscriptionPlanId) {
                $q->where('subscription_plan_id', $subscriptionPlanId);
            })
            ->pluck('id')
            ->toArray();

        $successful = [];
        $failed = [];

        foreach ($targetIds as $id) {
            try {
                $attendance = $this->checkOut((int) $id);
                $successful[] = $attendance;
            } catch (Exception $e) {
                $failed[] = [
                    'attendance_id' => $id,
                    'error'         => $e->getMessage(),
                ];
            }
        }

        return [
            'total_processed' => count($targetIds),
            'success_count'   => count($successful),
            'failed_count'    => count($failed),
            'successful'      => $successful,
            'failed'          => $failed,
        ];
    }

    /**
     * Find the open (checked-in) attendance record for an entity.
     *
     * @param  string  $type
     * @param  int     $entityId
     * @return Attendance|null
     */
    public function findOpen(string $type, int $entityId): ?Attendance
    {
        return $this->resolveHandler($type)->findOpenAttendance($entityId);
    }

    /**
     * Get history query builder for an entity or all entities.
     *
     * @param  string|null  $type
     * @param  int|null     $entityId
     * @param  string|null  $from
     * @param  string|null  $to
     * @return Builder
     */
    public function getHistory(?string $type = null, ?int $entityId = null, ?string $from = null, ?string $to = null): Builder
    {
        if (!$type || $type === 'all') {
            $query = Attendance::query()->orderByDesc('check_in_at');

            if ($entityId) {
                $query->where('attendable_id', $entityId);
            }

            if ($from) {
                $query->whereDate('check_in_at', '>=', $from);
            }

            if ($to) {
                $query->whereDate('check_in_at', '<=', $to);
            }

            return $query;
        }

        return $this->resolveHandler($type)->getHistory($entityId, $from, $to);
    }

    /**
     * Resolve the handler for the given type.
     *
     * @param  string  $type
     * @return AttendanceHandlerInterface
     * @throws Exception
     */
    private function resolveHandler(string $type): AttendanceHandlerInterface
    {
        if (!isset($this->handlers[$type])) {
            throw new Exception("No attendance handler registered for type: '{$type}'.");
        }

        return $this->handlers[$type];
    }
}
