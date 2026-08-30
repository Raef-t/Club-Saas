<?php

namespace Modules\ClubManager\Services;

use Modules\ClubManager\Repositories\FacilityRepositoryInterface;

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
    public function getAllFacilities(int $perPage = 15)
    {
        return $this->repository->all($perPage);
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
     *
     * @throws \Modules\Core\Exceptions\CannotDeleteException
     */
    public function deleteFacility(int $id, string $confirmation = ''): void
    {
        if (strtolower(trim($confirmation)) !== 'delete') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'confirmation' => __('سيتم حذف هذا المرفق بالكامل مع كافة الخزائن وقوالب الجلسات التدريبية المعتمدة عليه، هل أنت متأكد؟ أرسل "delete" للتأكيد.')
            ]);
        }

        $facility = \Modules\ClubManager\Models\Facility::findOrFail($id);
        $facility->delete();
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

    /**
     * Get all soft-deleted (trashed) facilities.
     */
    public function getTrashed(int $perPage = 15)
    {
        return $this->repository->getTrashed($perPage);
    }

    /**
     * Restore a soft-deleted facility by ID.
     */
    public function restoreFacility($id)
    {
        return $this->repository->restore($id);
    }
}
