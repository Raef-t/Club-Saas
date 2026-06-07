<?php

namespace Modules\Sports\Services;

use Modules\Sports\Repositories\SessionRepositoryInterface;
use Modules\Core\Contracts\BranchSharedServiceInterface;
use Modules\Core\Contracts\StaffSharedServiceInterface;
use Modules\Sports\Models\SportSession;
use Modules\Sports\Models\SportSessionBooking;
use Carbon\Carbon;
use Exception;

class SessionService
{
    protected $sessionRepository;
    protected $branchService;
    protected $staffService;

    public function __construct(
        SessionRepositoryInterface $sessionRepository,
        BranchSharedServiceInterface $branchService,
        StaffSharedServiceInterface $staffService
    ) {
        $this->sessionRepository = $sessionRepository;
        $this->branchService = $branchService;
        $this->staffService = $staffService;
    }

    /**
     * Get all sessions with resolved cross-module DTOs.
     */
    public function getAllSessions()
    {
        $sessions = $this->sessionRepository->all();
        foreach ($sessions as $session) {
            $this->attachSharedDTOs($session);
        }
        return $sessions;
    }

    /**
     * Get a single session by ID with resolved DTOs.
     */
    public function getSessionById(int $id)
    {
        $session = $this->sessionRepository->find($id);
        return $this->attachSharedDTOs($session);
    }

    /**
     * Create a new session with cross-module validation.
     */
    public function createSession(array $data)
    {
        // Validate branch exists via Core contract
        $branch = $this->branchService->getBranchById($data['branch_id']);
        if (!$branch) {
            throw new Exception(__('Branch not found.'));
        }

        // Validate coach (staff) exists via Core contract if provided
        if (!empty($data['staff_id'])) {
            $staff = $this->staffService->getStaffById($data['staff_id']);
            if (!$staff || !$staff->isActive) {
                throw new Exception(__('Coach not found or inactive.'));
            }
        }

        // Validate facility exists via Core contract if provided
        if (!empty($data['facility_id'])) {
            if (!$this->branchService->facilityExists($data['facility_id'])) {
                throw new Exception(__('Facility not found.'));
            }
        }

        // Validate coach schedule conflicts (overlap)
        if (!empty($data['staff_id']) && !empty($data['start_time']) && !empty($data['end_time'])) {
            $startTime = Carbon::parse($data['start_time']);
            $endTime = Carbon::parse($data['end_time']);
            
            $query = SportSession::where('staff_id', $data['staff_id'])
                ->where('status', 'scheduled')
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->whereBetween('start_time', [$startTime, $endTime])
                      ->orWhereBetween('end_time', [$startTime, $endTime])
                      ->orWhere(function ($sub) use ($startTime, $endTime) {
                          $sub->where('start_time', '<=', $startTime)
                              ->where('end_time', '>=', $endTime);
                      });
                });

            if ($query->exists()) {
                throw new Exception(__('Coach has a conflicting session scheduled at this time.'));
            }
        }

        $session = $this->sessionRepository->create($data);
        return $this->attachSharedDTOs($session->load('activity'));
    }

    /**
     * Update an existing session.
     */
    public function updateSession(int $id, array $data)
    {
        $session = $this->sessionRepository->find($id);
        if (!$session) {
            throw new Exception(__('Session not found.'));
        }

        $staffId = $data['staff_id'] ?? $session->staff_id;
        $startTimeStr = $data['start_time'] ?? $session->start_time;
        $endTimeStr = $data['end_time'] ?? $session->end_time;

        if ($staffId && $startTimeStr && $endTimeStr) {
            $startTime = Carbon::parse($startTimeStr);
            $endTime = Carbon::parse($endTimeStr);
            
            $query = SportSession::where('staff_id', $staffId)
                ->where('id', '!=', $id)
                ->where('status', 'scheduled')
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->whereBetween('start_time', [$startTime, $endTime])
                      ->orWhereBetween('end_time', [$startTime, $endTime])
                      ->orWhere(function ($sub) use ($startTime, $endTime) {
                          $sub->where('start_time', '<=', $startTime)
                              ->where('end_time', '>=', $endTime);
                      });
                });

            if ($query->exists()) {
                throw new Exception(__('Coach has a conflicting session scheduled at this time.'));
            }
        }

        $session = $this->sessionRepository->update($id, $data);
        return $this->attachSharedDTOs($session);
    }

    /**
     * Cancel (soft delete) a session.
     */
    public function cancelSession(int $id)
    {
        $session = $this->sessionRepository->find($id);
        $session->update(['status' => 'cancelled']);
        return $session;
    }

    /**
     * Delete a session.
     */
    public function deleteSession(int $id)
    {
        return $this->sessionRepository->delete($id);
    }

    /**
     * Get weekly schedule for a branch.
     */
    public function getWeeklySchedule(int $branchId, ?string $startDate = null)
    {
        $start = $startDate ? Carbon::parse($startDate)->startOfWeek() : now()->startOfWeek();
        $end = $start->copy()->endOfWeek();

        $sessions = $this->sessionRepository->getWeeklySchedule(
            $branchId,
            $start->toDateTimeString(),
            $end->toDateTimeString()
        );

        foreach ($sessions as $session) {
            $this->attachSharedDTOs($session);
        }

        return $sessions;
    }

    /**
     * Resolve cross-module DTOs without direct Eloquent relationships.
     */
    protected function attachSharedDTOs($session)
    {
        if ($session) {
            $session->branch = $session->branch_id
                ? $this->branchService->getBranchById($session->branch_id)
                : null;
            $session->staff = $session->staff_id
                ? $this->staffService->getStaffById($session->staff_id)
                : null;
        }
        return $session;
    }

    /**
     * Book a session for a member.
     */
    public function bookSession(int $sessionId, int $memberId)
    {
        $session = $this->sessionRepository->find($sessionId);
        if (!$session) {
            throw new Exception(__('Session not found.'));
        }

        if ($session->status !== 'scheduled') {
            throw new Exception(__('Cannot book a session that is not scheduled.'));
        }

        // Check capacity
        if ($session->max_players !== null && $session->booked_count >= $session->max_players) {
            throw new Exception(__('Session is already full.'));
        }

        // Check if member exists
        $member = \Illuminate\Support\Facades\DB::table('members')->where('id', $memberId)->first();
        if (!$member) {
            throw new Exception(__('Member not found.'));
        }

        // Check duplicate booking
        $existing = SportSessionBooking::where('sports_session_id', $sessionId)
            ->where('member_id', $memberId)
            ->where('status', 'booked')
            ->first();
        if ($existing) {
            throw new Exception(__('Member has already booked this session.'));
        }

        // Check player overlap (cannot book another session overlapping this time)
        $startTime = Carbon::parse($session->start_time);
        $endTime = Carbon::parse($session->end_time);

        $overlap = SportSessionBooking::join('sports_sessions', 'sports_session_bookings.sports_session_id', '=', 'sports_sessions.id')
            ->where('sports_session_bookings.member_id', $memberId)
            ->where('sports_session_bookings.status', 'booked')
            ->where('sports_sessions.status', 'scheduled')
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('sports_sessions.start_time', [$startTime, $endTime])
                  ->orWhereBetween('sports_sessions.end_time', [$startTime, $endTime])
                  ->orWhere(function ($sub) use ($startTime, $endTime) {
                      $sub->where('sports_sessions.start_time', '<=', $startTime)
                          ->where('sports_sessions.end_time', '>=', $endTime);
                  });
            })
            ->exists();

        if ($overlap) {
            throw new Exception(__('Player has a conflicting session booking at this time.'));
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($session, $memberId) {
            $booking = SportSessionBooking::create([
                'sports_session_id' => $session->id,
                'member_id' => $memberId,
                'status' => 'booked',
            ]);

            $session->increment('booked_count');

            return $booking;
        });
    }

    /**
     * Cancel a booking.
     */
    public function cancelBooking(int $bookingId)
    {
        $booking = SportSessionBooking::find($bookingId);
        if (!$booking) {
            throw new Exception(__('Booking not found.'));
        }

        if ($booking->status === 'cancelled') {
            throw new Exception(__('Booking is already cancelled.'));
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'cancelled']);

            $session = $booking->session;
            if ($session && $session->booked_count > 0) {
                $session->decrement('booked_count');
            }

            return $booking;
        });
    }
}
