<?php

namespace Modules\AttendanceManager\Services;

use Modules\AttendanceManager\Repositories\StaffAttendanceRepositoryInterface;
use Modules\AttendanceManager\Services\AttendanceRecorder;
use Carbon\Carbon;
use Exception;

class StaffAttendanceService
{
    protected $repository;
    protected $recorder;

    public function __construct(StaffAttendanceRepositoryInterface $repository, AttendanceRecorder $recorder)
    {
        $this->repository = $repository;
        $this->recorder = $recorder;
    }

    public function getAll() { return $this->repository->all(); }
    public function getById($id) { return $this->repository->find($id); }
    public function create(array $data) { return $this->repository->create($data); }
    public function update($id, array $data) { return $this->repository->update($id, $data); }
    public function delete($id) { return $this->repository->delete($id); }

    public function checkIn(int $staffId, int $clubId, int $branchId, ?int $facilityId = null)
    {
        $openAttendance = $this->repository->findOpenAttendance($staffId);

        if ($openAttendance) {
            throw new Exception("Staff member is already checked in.");
        }

        $attempt = new \Modules\AttendanceManager\DTOs\CheckInAttempt(
            attendableType: 'staff',
            attendableId: $staffId,
            clubId: $clubId,
            branchId: $branchId,
            timestamp: new \DateTimeImmutable(),
            metadata: array_filter(['facility_id' => $facilityId])
        );

        $decision = $this->recorder->record($attempt);

        if (!$decision->isAllowed) {
            throw new Exception($decision->rejectionReason);
        }

        return $this->repository->findOpenAttendance($staffId);
    }

    public function checkOut(int $attendanceId)
    {
        $attendance = $this->repository->find($attendanceId);

        if ($attendance->check_out_at) {
            throw new Exception("Already checked out.");
        }

        return $this->repository->update($attendanceId, [
            'check_out_at' => Carbon::now(),
            'status' => 'checked_out'
        ]);
    }

    public function getHistory(int $staffId, $from = null, $to = null)
    {
        return $this->repository->getHistory($staffId, $from, $to);
    }
}
