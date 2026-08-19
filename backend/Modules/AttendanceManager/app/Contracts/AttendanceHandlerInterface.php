<?php

namespace Modules\AttendanceManager\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Modules\AttendanceManager\Models\Attendance;

interface AttendanceHandlerInterface
{
    /**
     * Record a check-in for the given entity.
     *
     * @param  int         $entityId  The primary entity (member_id or staff_id)
     * @param  int         $branchId
     * @param  string|null $checkInAt
     * @param  array|null  $subscriptionIds
     * @param  int|null    $lockerId
     * @param  string|null $notes
     * @return Attendance
     */
    public function checkIn(int $entityId, int $branchId, ?string $checkInAt = null, ?array $subscriptionIds = null, ?int $lockerId = null, ?string $notes = null): Attendance;

    /**
     * Record a check-out for the given attendance record.
     *
     * @param  int         $attendanceId
     * @param  string|null $checkOutAt
     * @return Attendance
     */
    public function checkOut(int $attendanceId, ?string $checkOutAt = null): Attendance;

    /**
     * Get the open (checked-in) attendance record for this entity, if any.
     *
     * @param  int  $entityId
     * @return Attendance|null
     */
    public function findOpenAttendance(int $entityId): ?Attendance;

    /**
     * Return a query builder for the attendance history of this entity.
     * Callers can paginate/filter the result.
     *
     * @param  int|null    $entityId
     * @param  string|null $from  YYYY-MM-DD
     * @param  string|null $to    YYYY-MM-DD
     * @return Builder
     */
    public function getHistory(?int $entityId = null, ?string $from = null, ?string $to = null): Builder;
}
