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
     *
     * @throws \Modules\Core\Exceptions\CannotDeleteException
     */
    public function deleteFacility($id)
    {
        $facility = \Modules\ClubManager\Models\Facility::findOrFail($id);

        $templatesCount = \Modules\Sports\Models\SportSessionTemplate::where('facility_id', $id)->count();
        $lockersCount = \Modules\ClubManager\Models\Locker::where('facility_id', $id)->count();

        $blocked = [];
        if ($templatesCount > 0) {
            $blocked[] = "يوجد {$templatesCount} " . ($templatesCount === 1 ? 'قالب جلسة تدريبية' : 'قوالب جلسات تدريبية') . " تعتمد على هذا المرفق";
        }
        if ($lockersCount > 0) {
            $blocked[] = "يحتوي على {$lockersCount} " . ($lockersCount === 1 ? 'خزانة' : 'خزائن');
        }

        if (!empty($blocked)) {
            $reasons = implode('، و ', $blocked);
            throw new \Modules\Core\Exceptions\CannotDeleteException(
                "لا يمكن حذف هذا المرفق لأنه: {$reasons}. يُنصح بتعطيل المرفق بدلاً من حذفه.",
                [
                    'templates_count' => $templatesCount,
                    'lockers_count' => $lockersCount
                ]
            );
        }

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
