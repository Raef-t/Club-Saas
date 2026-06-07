<?php

namespace Modules\AttendanceManager\Services;

use Modules\AttendanceManager\Repositories\MemberAttendanceRepositoryInterface;
use Modules\AttendanceManager\Services\AttendanceRecorder;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class MemberAttendanceService
{
    protected $repository;
    protected $recorder;

    public function __construct(MemberAttendanceRepositoryInterface $repository, AttendanceRecorder $recorder)
    {
        $this->repository = $repository;
        $this->recorder = $recorder;
    }

    public function getAll() { return $this->repository->all(); }
    public function getById($id) { return $this->repository->find($id); }
    public function create(array $data) { return $this->repository->create($data); }
    public function update($id, array $data) { return $this->repository->update($id, $data); }
    public function delete($id) { return $this->repository->delete($id); }

    public function checkIn(int $memberId, int $clubId, int $branchId, ?int $facilityId = null)
    {
        $openAttendance = $this->repository->findOpenAttendance($memberId);

        if ($openAttendance) {
            throw new Exception("Member is already checked in.");
        }

        // 1. Verify Member Gender against Facility gender restriction
        if ($facilityId) {
            $member = DB::table('members')
                ->join('people', 'members.person_id', '=', 'people.id')
                ->where('members.id', $memberId)
                ->select('people.gender')
                ->first();

            $facility = DB::table('facilities')
                ->where('id', $facilityId)
                ->first();

            if ($member && $facility && $facility->gender_restriction !== 'mixed') {
                if ($member->gender !== $facility->gender_restriction) {
                    throw new Exception(__("Member gender does not match the facility gender restriction."));
                }
            }
        }

        // 2. Verify Member has an Active Subscription
        $activeSubscription = DB::table('player_subscriptions')
            ->where('member_id', $memberId)
            ->where('status', 'active')
            ->first();

        if (!$activeSubscription) {
            throw new Exception(__("Member does not have an active subscription."));
        }

        // Verify remaining amount (debt) and grace period
        if ($activeSubscription->remaining_amount > 0) {
            $settings = DB::table('club_settings')->where('club_id', $clubId)->first();
            $allowedDebtLimit = $settings ? ($settings->allowed_debt_limit ?? 0) : 0;
            $gracePeriodDays = $settings ? ($settings->grace_period_days ?? 0) : 0;

            if ($activeSubscription->remaining_amount > $allowedDebtLimit) {
                throw new Exception(__("Access denied: Outstanding debt (:amount) exceeds the allowed limit (:limit).", [
                    'amount' => $activeSubscription->remaining_amount,
                    'limit' => $allowedDebtLimit
                ]));
            }

            $startDate = Carbon::parse($activeSubscription->start_date);
            if (now()->diffInDays($startDate) > $gracePeriodDays) {
                throw new Exception(__("Access denied: Grace period for payment has expired. Outstanding balance: :amount.", [
                    'amount' => $activeSubscription->remaining_amount
                ]));
            }
        }

        // 3. Dispatch check-in to policy-driven engine
        $attempt = new \Modules\AttendanceManager\DTOs\CheckInAttempt(
            attendableType: 'player_subscription',
            attendableId: $activeSubscription->id,
            clubId: $clubId,
            branchId: $branchId,
            timestamp: new \DateTimeImmutable(),
            metadata: array_filter([
                'member_id' => $memberId,
                'facility_id' => $facilityId,
            ])
        );

        $decision = $this->recorder->record($attempt);

        if (!$decision->isAllowed) {
            throw new Exception($decision->rejectionReason);
        }

        // Decrement remaining sessions if subscription is session-based
        if ($activeSubscription->remaining_sessions !== null) {
            DB::table('player_subscriptions')
                ->where('id', $activeSubscription->id)
                ->decrement('remaining_sessions');
        }

        return $this->repository->findOpenAttendance($memberId);
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

    public function getHistory(int $memberId, $from = null, $to = null)
    {
        return $this->repository->getHistory($memberId, $from, $to);
    }
}
