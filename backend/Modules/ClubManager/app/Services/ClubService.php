<?php
namespace Modules\ClubManager\Services;

use Modules\ClubManager\Models\Club;
use Modules\ClubManager\Repositories\ClubRepositoryInterface;
use Modules\Core\Exceptions\CannotDeleteException;

class ClubService
{
    protected $repository;

    public function __construct(ClubRepositoryInterface $repository) {
        $this->repository = $repository;
    }

    public function getAll() { return $this->repository->all(); }
    public function getById($id) { return $this->repository->find($id); }
    public function create(array $data) { return $this->repository->create($data); }
    public function update($id, array $data) { return $this->repository->update($id, $data); }

    /**
     * Delete a club only if it has no branches.
     *
     * @throws CannotDeleteException
     */
    public function delete($id): void
    {
        $club = Club::findOrFail($id);

        $branchesCount = $club->branches()->count();

        if ($branchesCount > 0) {
            throw new CannotDeleteException(
                "لا يمكن حذف النادي لأنه يحتوي على {$branchesCount} " . ($branchesCount === 1 ? 'فرع' : 'فروع') . ". يرجى حذف جميع الفروع أولاً أو تعطيل النادي بدلاً من حذفه.",
                ['branches_count' => $branchesCount]
            );
        }

        $this->repository->delete($id);
    }
}
