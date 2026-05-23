<?php

namespace Modules\AttendanceManager\Services;

use Modules\AttendanceManager\Repositories\StaffAttendanceRepositoryInterface;
use Carbon\Carbon;
use Exception;

class StaffAttendanceService
{
    protected $repository;

    public function __construct(StaffAttendanceRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getAll() { return $this->repository->all(); }
    public function getById($id) { return $this->repository->find($id); }
    public function create(array $data) { return $this->repository->create($data); }
    public function update($id, array $data) { return $this->repository->update($id, $data); }
    public function delete($id) { return $this->repository->delete($id); }

    public function checkIn(int $staffId, ?int $facilityId = null)
    {
        $openAttendance = $this->repository->findOpenAttendance($staffId);

        if ($openAttendance) {
            throw new Exception("Staff member is already checked in.");
        }

        return $this->repository->create([
            'staff_id' => $staffId,
            'facility_id' => $facilityId,
            'check_in' => Carbon::now(),
            'status' => 'present'
        ]);
    }

    public function checkOut(int $attendanceId)
    {
        $attendance = $this->repository->find($attendanceId);

        if ($attendance->check_out) {
            throw new Exception("Already checked out.");
        }

        return $this->repository->update($attendanceId, [
            'check_out' => Carbon::now()
        ]);
    }

    public function getHistory(int $staffId, $from = null, $to = null)
    {
        return $this->repository->getHistory($staffId, $from, $to);
    }
}
