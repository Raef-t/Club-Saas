<?php

namespace Modules\StaffManager\Services;

use Modules\Core\Contracts\StaffSharedServiceInterface;
use Modules\Core\DTOs\StaffDTO;
use Modules\Core\DTOs\PersonDTO;
use Modules\StaffManager\Repositories\StaffRepositoryInterface;
use Modules\Core\Contracts\PersonSharedServiceInterface;

class StaffSharedService implements StaffSharedServiceInterface
{
    protected $repository;
    protected $personSharedService;

    public function __construct(
        StaffRepositoryInterface $repository,
        PersonSharedServiceInterface $personSharedService
    ) {
        $this->repository = $repository;
        $this->personSharedService = $personSharedService;
    }

    public function getStaffById(int $id): ?StaffDTO
    {
        try {
            $staff = $this->repository->find($id);
            if (!$staff) {
                return null;
            }

            $personDTO = null;
            if ($staff->person_id) {
                $personDTO = $this->personSharedService->getPersonById($staff->person_id);
            }

            return new StaffDTO(
                id: $staff->id,
                personId: $staff->person_id,
                branchId: $staff->branch_id,
                employmentType: $staff->employment_type,
                isActive: (bool)$staff->is_active,
                person: $personDTO
            );
        } catch (\Exception $e) {
            return null;
        }
    }
}
