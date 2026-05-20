<?php

namespace Modules\BranchManager\Services;

use Modules\BranchManager\Repositories\BranchRepositoryInterface;

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
     * Delete a branch.
     */
    public function deleteBranch($id)
    {
        return $this->repository->delete($id);
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
}
