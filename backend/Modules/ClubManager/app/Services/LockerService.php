<?php

namespace Modules\ClubManager\Services;

use Modules\ClubManager\Repositories\LockerRepositoryInterface;
use Modules\ClubManager\Domain\Rules\LockerUniquenessRule;

class LockerService
{
    protected $repository;
    protected $uniquenessRule;

    public function __construct(
        LockerRepositoryInterface $repository,
        LockerUniquenessRule $uniquenessRule
    ) {
        $this->repository = $repository;
        $this->uniquenessRule = $uniquenessRule;
    }

    public function getAllLockers(array $filters = [])
    {
        return $this->repository->all($filters);
    }

    public function createLocker(array $data)
    {
        // Execute Domain Business Rule
        $this->uniquenessRule->validate($data['branch_id'], $data['locker_number']);

        return $this->repository->create($data);
    }

    public function getLockerById($id)
    {
        return $this->repository->find($id);
    }

    public function updateLocker($id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    public function deleteLocker($id)
    {
        return $this->repository->delete($id);
    }


}
