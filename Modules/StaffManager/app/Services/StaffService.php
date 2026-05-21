<?php

namespace Modules\StaffManager\Services;

use Modules\StaffManager\Repositories\StaffRepositoryInterface;
use Modules\Core\Contracts\PersonSharedServiceInterface;
use Modules\Core\Contracts\BranchSharedServiceInterface;
use Illuminate\Support\Facades\DB;
use Exception;

class StaffService
{
    protected $staffRepository;
    protected $personService;
    protected $branchService;

    public function __construct(
        StaffRepositoryInterface $staffRepository,
        PersonSharedServiceInterface $personService,
        BranchSharedServiceInterface $branchService
    ) {
        $this->staffRepository = $staffRepository;
        $this->personService = $personService;
        $this->branchService = $branchService;
    }

    /**
     * Get all staff and coaches with resolved person/branch DTOs.
     */
    public function getAllStaff()
    {
        $staffMembers = $this->staffRepository->all();
        foreach ($staffMembers as $staff) {
            $this->attachSharedDTOs($staff);
        }
        return $staffMembers;
    }

    /**
     * Get staff member by ID with resolved person/branch DTOs.
     */
    public function getStaffById($id)
    {
        $staff = $this->staffRepository->find($id);
        return $this->attachSharedDTOs($staff);
    }

    /**
     * Register a new staff member
     */
    public function onboardStaff(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1. Create the person profile in Authentication module
            $personDto = new \Modules\Core\DTOs\CreatePersonDTO(
                fullName: $data['full_name'],
                mobile1: $data['mobile_1'],
                type: 'staff',
                gender: null,
                dob: null,
                email: $data['email'] ?? null,
            );
            $person = $this->personService->createPerson($personDto);

            // 2. Create the staff record
            $staff = $this->staffRepository->create(array_merge($data, [
                'person_id' => $person->id,
            ]));

            return $this->attachSharedDTOs($staff);
        });
    }

    /**
     * Update staff schedule
     */
    public function setStaffSchedule($staffId, array $shifts)
    {
        $staff = $this->staffRepository->find($staffId);
        
        return DB::transaction(function () use ($staff, $shifts) {
            // Remove old shifts
            $staff->shifts()->delete();

            // Add new shifts
            foreach ($shifts as $shift) {
                $staff->shifts()->create($shift);
            }

            $staff->load('shifts');
            return $this->attachSharedDTOs($staff);
        });
    }

    /**
     * Staff Check-in
     */
    public function checkIn($staffId)
    {
        $staff = $this->staffRepository->find($staffId);
        
        $attendance = $staff->attendances()->create([
            'check_in' => now(),
        ]);

        return $attendance;
    }

    /**
     * Staff Check-out
     */
    public function checkOut($attendanceId)
    {
        $attendance = \Modules\StaffManager\Models\StaffAttendance::findOrFail($attendanceId);
        $checkOutTime = now();
        
        $duration = $attendance->check_in->diffInHours($checkOutTime);

        $attendance->update([
            'check_out' => $checkOutTime,
            'total_hours' => $duration
        ]);

        return $attendance;
    }

    /**
     * Helper to resolve and attach Person and Branch DTOs
     */
    protected function attachSharedDTOs($staff)
    {
        if ($staff) {
            $staff->person = $staff->person_id ? $this->personService->getPersonById($staff->person_id) : null;
            $staff->branch = $staff->branch_id ? $this->branchService->getBranchById($staff->branch_id) : null;
        }
        return $staff;
    }

    /**
     * Update staff member data.
     */
    public function updateStaff($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $staff = $this->staffRepository->find($id);

            // Update Person data via Core contract if provided
            if ($staff->person_id) {
                $personData = array_filter([
                    'fullName' => $data['full_name'] ?? null,
                    'mobile1' => $data['mobile_1'] ?? null,
                    'email' => $data['email'] ?? null,
                ], fn($value) => !is_null($value));

                if (!empty($personData)) {
                    $updateDto = new \Modules\Core\DTOs\UpdatePersonDTO(...$personData);
                    $this->personService->updatePerson($staff->person_id, $updateDto);
                }
            }

            // Update Staff record
            $staff->update($data);

            return $this->attachSharedDTOs($staff->fresh());
        });
    }

    /**
     * Toggle staff active status.
     */
    public function toggleStatus($id)
    {
        $staff = $this->staffRepository->find($id);
        $staff->update(['is_active' => !$staff->is_active]);
        return $this->attachSharedDTOs($staff->fresh());
    }

    /**
     * Get attendance history for a staff member.
     */
    public function getAttendanceHistory($staffId, $from = null, $to = null)
    {
        $staff = $this->staffRepository->find($staffId);

        $query = $staff->attendances()->orderBy('check_in', 'desc');

        if ($from) {
            $query->where('check_in', '>=', $from);
        }
        if ($to) {
            $query->where('check_in', '<=', $to);
        }

        return $query->get();
    }

    /**
     * Calculate total working hours for a staff member in a period.
     */
    public function calculateWorkingHours($staffId, $from, $to)
    {
        $staff = $this->staffRepository->find($staffId);

        return $staff->attendances()
            ->whereNotNull('total_hours')
            ->where('check_in', '>=', $from)
            ->where('check_in', '<=', $to)
            ->sum('total_hours');
    }
}
