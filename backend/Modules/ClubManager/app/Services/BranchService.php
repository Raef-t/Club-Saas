<?php

namespace Modules\ClubManager\Services;

use Modules\ClubManager\Models\Branch;
use Modules\ClubManager\Repositories\BranchRepositoryInterface;
use Modules\Core\Exceptions\CannotDeleteException;

class BranchService
{
    protected $repository;

    public function __construct(BranchRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all branches.
     */
    public function getAllBranches()
    {
        return $this->repository->all();
    }

    /**
     * Create a new branch.
     */
    public function createBranch(array $data)
    {
        return $this->repository->create($data);
    }

    /**
     * Get a specific branch.
     */
    public function getBranchById($id)
    {
        return $this->repository->find($id);
    }

    /**
     * Update a branch.
     */
    public function updateBranch($id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    public function deleteBranch(int $id, string $confirmation = ''): void
    {
        if (strtolower(trim($confirmation)) !== 'delete') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'confirmation' => __('سيتم حذف هذا الفرع بالكامل مع كافة المشتركين والمدربين والأنشطة والاشتراكات المتعلقة به، هل أنت متأكد؟ أرسل "delete" للتأكيد.')
            ]);
        }

        $branch = Branch::findOrFail($id);
        $branch->delete();
    }

    /**
     * Toggle the active status of a branch.
     */
    public function toggleStatus($id)
    {
        $branch = $this->getBranchById($id);
        $branch->update(['is_active' => !$branch->is_active]);
        return $branch;
    }

    /**
     * Get all soft-deleted (trashed) branches.
     */
    public function getTrashed()
    {
        return $this->repository->getTrashed();
    }

    /**
     * Restore a soft-deleted branch by ID.
     */
    public function restoreBranch($id)
    {
        return $this->repository->restore($id);
    }

    /**
     * Get statistics for branches.
     */
    public function getStats()
    {
        $query = \Modules\ClubManager\Models\Branch::query();

        return [
            'total_branches'  => (clone $query)->count(),
            'active_branches' => (clone $query)->where('is_active', true)->count(),
            'male_branches'   => (clone $query)->where('gender_restriction', 'male')->count(),
            'female_branches' => (clone $query)->where('gender_restriction', 'female')->count(),
            'mixed_branches'  => (clone $query)->where('gender_restriction', 'mixed')->count(),
        ];
    }
}
