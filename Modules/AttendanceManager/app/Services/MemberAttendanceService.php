<?php

namespace Modules\AttendanceManager\Services;

use Modules\AttendanceManager\Repositories\MemberAttendanceRepositoryInterface;
use Carbon\Carbon;
use Exception;

class MemberAttendanceService
{
    protected $repository;

    public function __construct(MemberAttendanceRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getAll() { return $this->repository->all(); }
    public function getById($id) { return $this->repository->find($id); }
    public function create(array $data) { return $this->repository->create($data); }
    public function update($id, array $data) { return $this->repository->update($id, $data); }
    public function delete($id) { return $this->repository->delete($id); }

    public function checkIn(int $memberId, ?int $facilityId = null)
    {
        $openAttendance = $this->repository->findOpenAttendance($memberId);

        if ($openAttendance) {
            throw new Exception("Member is already checked in.");
        }

        // 1. Verify Member Gender against Facility gender restriction
        if ($facilityId) {
            $member = \Illuminate\Support\Facades\DB::table('members')
                ->join('people', 'members.person_id', '=', 'people.id')
                ->where('members.id', $memberId)
                ->select('people.gender')
                ->first();

            $facility = \Illuminate\Support\Facades\DB::table('facilities')
                ->where('id', $facilityId)
                ->first();

            if ($member && $facility && $facility->gender_restriction !== 'mixed') {
                if ($member->gender !== $facility->gender_restriction) {
                    throw new Exception(__("Member gender does not match the facility gender restriction."));
                }
            }
        }

        // 2. Verify Member has an Active Subscription
        $activeSubscription = \Illuminate\Support\Facades\DB::table('player_subscriptions')
            ->where('member_id', $memberId)
            ->where('status', 'active')
            ->first();

        if (!$activeSubscription) {
            throw new Exception(__("Member does not have an active subscription."));
        }

        return $this->repository->create([
            'member_id' => $memberId,
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

    public function getHistory(int $memberId, $from = null, $to = null)
    {
        return $this->repository->getHistory($memberId, $from, $to);
    }
}
