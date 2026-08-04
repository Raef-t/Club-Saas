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

    /**
     * Delete a branch with full cascading soft delete for all related records.
     */
    public function deleteBranch($id): bool
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
            $branch = Branch::findOrFail($id);
            return $branch->delete();
        });
    }

    /**
     * Restore a deleted branch and all cascaded children.
     */
    public function restoreBranch($id): bool
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
            $branch = Branch::onlyTrashed()->findOrFail($id);
            return $branch->restore();
        });
    }

    /**
     * Force delete a branch permanently.
     */
    public function forceDeleteBranch($id): bool
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
            $branch = Branch::withTrashed()->findOrFail($id);
            return $branch->forceDelete();
        });
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
