<?php

namespace Modules\Sports\Services;

use Modules\Sports\Repositories\SessionRepositoryInterface;
use Modules\Core\Contracts\BranchSharedServiceInterface;
use Modules\Core\Contracts\StaffSharedServiceInterface;
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

        $session = $this->sessionRepository->create($data);
        return $this->attachSharedDTOs($session->load('activity'));
    }

    /**
     * Update an existing session.
     */
    public function updateSession(int $id, array $data)
    {
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
}
