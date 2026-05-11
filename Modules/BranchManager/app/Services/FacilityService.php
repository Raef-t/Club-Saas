<?php

namespace Modules\BranchManager\Services;

use Modules\BranchManager\Repositories\FacilityRepositoryInterface;

class FacilityService
{
    protected $repository;

    public function __construct(FacilityRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all facilities.
     */
    public function getAllFacilities()
    {
        return $this->repository->all();
    }

    /**
     * Create a new facility.
     */
    public function createFacility(array $data)
    {
        return $this->repository->create($data);
    }

    /**
     * Get a specific facility.
     */
    public function getFacilityById($id)
    {
        return $this->repository->find($id);
    }

    /**
     * Update a facility.
     */
    public function updateFacility($id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    /**
     * Delete a facility.
     */
    public function deleteFacility($id)
    {
        return $this->repository->delete($id);
    }

    /**
     * Toggle the active status of a facility.
     */
    public function toggleStatus($id)
    {
        $facility = $this->getFacilityById($id);
        $facility->update(['is_active' => !$facility->is_active]);
        return $facility;
    }
}
