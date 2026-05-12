<?php

namespace Modules\StaffManager\Services;

use Modules\StaffManager\Repositories\StaffRepositoryInterface;
use Modules\Authentication\Services\PersonServiceInterface;
use Illuminate\Support\Facades\DB;
use Exception;

class StaffService
{
    protected $staffRepository;
    protected $personService;

    public function __construct(
        StaffRepositoryInterface $staffRepository,
        PersonServiceInterface $personService
    ) {
        $this->staffRepository = $staffRepository;
        $this->personService = $personService;
    }

    /**
     * Register a new staff member
     */
    public function onboardStaff(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1. Create the person profile in Authentication module
            $personData = array_merge($data, ['type' => 'staff']);
            $person = $this->personService->createPerson($personData);

            // 2. Create the staff record
            return $this->staffRepository->create(array_merge($data, [
                'person_id' => $person->id,
            ]));
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
                $staff->shifts()->create(array_merge($shift, [
                    'tenant_id' => $staff->tenant_id
                ]));
            }

            return $staff->load('shifts');
        });
    }

    /**
     * Staff Check-in
     */
    public function checkIn($staffId)
    {
        $staff = $this->staffRepository->find($staffId);
        
        return $staff->attendances()->create([
            'tenant_id' => $staff->tenant_id,
            'check_in' => now(),
        ]);
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
}
